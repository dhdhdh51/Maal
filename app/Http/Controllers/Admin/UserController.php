<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\UserCategoryAccess;
use App\Services\AuditLogger;
use App\Services\Payments\AccessGrantService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(protected AccessGrantService $access) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%"))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'categoryAccess.category', 'devices']);

        return view('admin.users.show', [
            'user' => $user,
            'roles' => Role::pluck('name'),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended,banned'],
            'device_limit' => ['nullable', 'integer', 'min:0'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $user->update(['status' => $data['status'], 'device_limit' => $data['device_limit'] ?? null]);

        if (! $user->hasRole(Permissions::ROLE_ADMIN) || $request->user()->id !== $user->id) {
            $user->syncRoles($data['roles'] ?? []);
        }

        AuditLogger::log('user.updated', $user, "User {$user->email} updated");

        return back()->with('status', 'User updated.');
    }

    public function forceLogout(User $user): RedirectResponse
    {
        $user->sessions()->update(['revoked_at' => now(), 'is_playing' => false]);
        $user->tokens()->delete();
        AuditLogger::log('user.force_logout', $user, "Force logout {$user->email}");

        return back()->with('status', 'All sessions revoked.');
    }

    public function grantAccess(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'days' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->access->adminGrant($user, Category::findOrFail($data['category_id']), $data['days'] ?? null, $request->user(), $data['note'] ?? null);

        return back()->with('status', 'Access granted.');
    }

    public function revokeAccess(User $user, UserCategoryAccess $access): RedirectResponse
    {
        abort_unless($access->user_id === $user->id, 404);
        $this->access->revoke($access, request()->user());

        return back()->with('status', 'Access revoked.');
    }
}
