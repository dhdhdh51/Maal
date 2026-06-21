<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->get(),
            'groups' => Permissions::groups(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['string']]);
        $role->syncPermissions($data['permissions'] ?? []);

        AuditLogger::log('role.updated', null, "Role {$role->name} permissions updated");

        return back()->with('status', 'Role permissions updated.');
    }
}
