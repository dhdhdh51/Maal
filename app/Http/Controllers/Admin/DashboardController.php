<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function index(): View
    {
        return view('admin.dashboard', [
            'overview' => $this->analytics->overview(),
            'preview' => $this->analytics->previewConversion(),
            'revenue' => $this->analytics->revenueSeries(30),
            'topVideos' => $this->analytics->topVideos(),
            'topCategories' => $this->analytics->topCategoriesBySales(),
        ]);
    }

    /**
     * CSV export of daily revenue.
     */
    public function exportRevenue(): Response
    {
        $rows = $this->analytics->revenueSeries(90);

        $csv = "date,revenue\n";
        foreach ($rows as $row) {
            $csv .= "{$row['date']},{$row['total']}\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="revenue.csv"',
        ]);
    }
}
