<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryRestriction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CountryRestrictionController extends Controller
{
    public function index(): View
    {
        $rules = CountryRestriction::orderBy('country_code')->get();

        return view('admin.countries.index', compact('rules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'country_name' => ['nullable', 'string', 'max:100'],
            'mode' => ['required', 'in:allow,block'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['is_active'] = true;
        CountryRestriction::updateOrCreate(
            ['country_code' => $data['country_code'], 'mode' => $data['mode']],
            $data,
        );

        return back()->with('status', 'Rule saved.');
    }

    public function destroy(CountryRestriction $country): RedirectResponse
    {
        $country->delete();

        return back()->with('status', 'Rule removed.');
    }
}
