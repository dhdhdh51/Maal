<?php

namespace App\Services;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read model for the public catalog: home sections, browse, filters, search,
 * recommendations. Annotates videos with a per-user lock state.
 */
class CatalogService
{
    public function __construct(protected AccessService $access) {}

    /**
     * Base query for videos that may appear in listings (published, enabled,
     * ready, in an active category or none).
     */
    public function playableQuery(): Builder
    {
        return Video::query()
            ->where('is_published', true)
            ->where('is_disabled', false)
            ->where('processing_status', 'ready')
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereHas('category', fn ($c) => $c->where('is_active', true));
            })
            ->with('category');
    }

    /**
     * Build the ordered home sections payload.
     *
     * @return array<int, array<string, mixed>>
     */
    public function homeSections(?User $user): array
    {
        $sections = HomepageSection::where('is_active', true)->orderBy('sort_order')->get();
        $out = [];

        foreach ($sections as $section) {
            if ($section->type === 'featured_categories') {
                $out[] = [
                    'type' => 'categories',
                    'title' => $section->title,
                    'categories' => Category::where('is_active', true)->orderBy('sort_order')->limit($section->item_limit)->get(),
                ];

                continue;
            }

            $videos = $this->sectionVideos($section, $user);

            if ($videos->isNotEmpty()) {
                $out[] = [
                    'type' => 'videos',
                    'layout' => $section->layout,
                    'title' => $section->title,
                    'videos' => $this->decorate($videos, $user),
                ];
            }
        }

        return $out;
    }

    protected function sectionVideos(HomepageSection $section, ?User $user): Collection
    {
        $limit = $section->item_limit ?: 12;

        return match ($section->type) {
            'trending' => $this->playableQuery()->orderByDesc('views_count')->limit($limit)->get(),
            'newest' => $this->playableQuery()->orderByDesc('published_at')->limit($limit)->get(),
            'recently_added' => $this->playableQuery()->latest()->limit($limit)->get(),
            'recommended' => $this->recommended($user, $limit),
            'because_you_watched' => $this->becauseYouWatched($user, $limit),
            'continue_watching' => $this->continueWatching($user, $limit),
            'category_row' => $section->category_id
                ? $this->playableQuery()->where('category_id', $section->category_id)->latest()->limit($limit)->get()
                : collect(),
            'manual' => ! empty($section->video_ids)
                ? $this->playableQuery()->whereIn('id', $section->video_ids)->get()
                : collect(),
            default => collect(),
        };
    }

    public function continueWatching(?User $user, int $limit = 12): Collection
    {
        if (! $user || ! setting('watch_history_enabled', true)) {
            return collect();
        }

        $videoIds = WatchHistory::where('user_id', $user->id)
            ->where('completed', false)
            ->where('percent', '>', 2)
            ->orderByDesc('last_watched_at')
            ->limit($limit)
            ->pluck('video_id');

        return $this->playableQuery()->whereIn('id', $videoIds)->get()
            ->sortBy(fn ($v) => array_search($v->id, $videoIds->all()))->values();
    }

    public function recommended(?User $user, int $limit = 12): Collection
    {
        // Simple heuristic: most-watched, optionally biased to watched categories.
        $query = $this->playableQuery()->orderByDesc('views_count');

        if ($user) {
            $catIds = WatchHistory::where('user_id', $user->id)
                ->join('videos', 'videos.id', '=', 'watch_history.video_id')
                ->pluck('videos.category_id')->filter()->unique()->values();

            if ($catIds->isNotEmpty()) {
                $query->whereIn('category_id', $catIds);
            }
        }

        return $query->limit($limit)->get();
    }

    public function becauseYouWatched(?User $user, int $limit = 12): Collection
    {
        if (! $user) {
            return collect();
        }

        $lastWatched = WatchHistory::where('user_id', $user->id)
            ->orderByDesc('last_watched_at')->with('video')->first();

        if (! $lastWatched?->video?->category_id) {
            return collect();
        }

        return $this->playableQuery()
            ->where('category_id', $lastWatched->video->category_id)
            ->where('id', '!=', $lastWatched->video_id)
            ->latest()->limit($limit)->get();
    }

    /**
     * Filtered + searched browse query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function browse(array $filters, ?User $user)
    {
        $query = $this->playableQuery();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term));
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $filters['category']));
        }

        if (! empty($filters['free_preview'])) {
            $query->where('is_free_preview', true);
        }

        match ($filters['sort'] ?? 'newest') {
            'trending', 'most_watched' => $query->orderByDesc('views_count'),
            'longest' => $query->orderByDesc('duration'),
            default => $query->orderByDesc('published_at'),
        };

        $results = $query->paginate(24)->withQueryString();
        $this->decorate($results->getCollection(), $user);

        return $results;
    }

    /**
     * Annotate each video with a `user_locked` flag (and skip N+1 access checks).
     */
    public function decorate(Collection $videos, ?User $user): Collection
    {
        $accessibleCatIds = $user
            ? $user->categoryAccess()->active()->pluck('category_id')->all()
            : [];

        $isStaff = $user?->can('videos.view') ?? false;

        foreach ($videos as $video) {
            $cat = $video->category;
            $locked = ! (
                $isStaff
                || ! $cat
                || $cat->access_type === 'free'
                || ! $video->is_locked
                || in_array($video->category_id, $accessibleCatIds, true)
            );
            $video->setAttribute('user_locked', $locked);
        }

        return $videos;
    }
}
