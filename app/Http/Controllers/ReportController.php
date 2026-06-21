<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(Request $request): View
    {
        $video = $request->query('video') ? Video::find($request->query('video')) : null;
        $category = $request->query('category') ? Category::find($request->query('category')) : null;

        return view('report.create', [
            'video' => $video,
            'category' => $category,
            'reasons' => ['copyright', 'consent', 'wrong_category', 'technical', 'illegal', 'other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:video,category'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'in:copyright,consent,wrong_category,technical,illegal,other'],
            'details' => ['nullable', 'string', 'max:2000'],
            'reporter_email' => ['nullable', 'email'],
        ]);

        $model = $data['type'] === 'video' ? Video::findOrFail($data['id']) : Category::findOrFail($data['id']);

        $priority = in_array($data['reason'], ['copyright', 'consent', 'illegal'], true) ? 'high' : 'medium';

        $model->morphMany(Report::class, 'reportable')->create([
            'user_id' => $request->user()?->id,
            'reporter_email' => $data['reporter_email'] ?? $request->user()?->email,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'priority' => $priority,
            'status' => 'open',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Thank you. Your report has been submitted for review.');
    }
}
