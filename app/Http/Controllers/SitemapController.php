<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * XML sitemap of public pages only (no private/restricted URLs).
     */
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('categories'), 'priority' => '0.8'],
        ];

        Category::where('is_active', true)->get()->each(function (Category $c) use (&$urls) {
            if ($c->noindex) {
                return;
            }
            $urls[] = ['loc' => route('category.show', $c), 'priority' => '0.7', 'lastmod' => $c->updated_at?->toAtomString()];
        });

        Video::where('is_published', true)->where('is_disabled', false)->where('noindex', false)
            ->latest('published_at')->limit(1000)->get()
            ->each(function (Video $v) use (&$urls) {
                $urls[] = ['loc' => route('video.show', $v), 'priority' => '0.6', 'lastmod' => $v->updated_at?->toAtomString()];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>';
            if (! empty($u['lastmod'])) {
                $xml .= '<lastmod>'.$u['lastmod'].'</lastmod>';
            }
            $xml .= '<priority>'.$u['priority'].'</priority></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /watch',
            'Disallow: /stream',
            'Disallow: /payment',
            'Disallow: /checkout',
            'Allow: /',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
