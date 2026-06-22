<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = Report::query()
            ->with(['reportable', 'reporter'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(30)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,dismissed'],
            'reviewer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update([
            'status' => $data['status'],
            'reviewer_notes' => $data['reviewer_notes'] ?? $report->reviewer_notes,
            'reviewed_by' => $request->user()->id,
            'resolved_at' => in_array($data['status'], ['resolved', 'dismissed'], true) ? now() : null,
        ]);

        AuditLogger::log('report.'.$data['status'], $report, "Report #{$report->id} {$data['status']}");

        return back()->with('status', 'Report updated.');
    }
}
