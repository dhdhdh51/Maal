<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(protected CatalogService $catalog) {}

    public function index(Request $request): View
    {
        return view('home', [
            'banner' => Banner::live()->where('placement', 'hero')->orderBy('sort_order')->first(),
            'sections' => $this->catalog->homeSections($request->user()),
        ]);
    }
}
