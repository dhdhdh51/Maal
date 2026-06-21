<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email-templates.index', ['templates' => EmailTemplate::orderBy('name')->get()]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.form', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'channel' => ['required', 'in:email,whatsapp,both'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $emailTemplate->update($data);

        return back()->with('status', 'Template saved.');
    }
}
