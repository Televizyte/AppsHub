<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Support\Facades\DB;

class ContentShareController extends Controller
{
    public function show(string $appSlug, string $kind, string $idOrSlug)
    {
        abort_unless(in_array($kind, ['articles', 'shorts'], true), 404);

        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $query = DB::table('content_posts')
            ->where('app_id', (int) $app->id);

        if ($kind === 'shorts') {
            $query->where('bucket', 'short_videos');
        } else {
            $query->where('bucket', '!=', 'short_videos');
        }

        $post = $query
            ->where(function ($q) use ($idOrSlug) {
                if (ctype_digit($idOrSlug)) {
                    $q->where('id', (int) $idOrSlug)
                      ->orWhere('slug', $idOrSlug);
                } else {
                    $q->where('slug', $idOrSlug);
                }
            })
            ->firstOrFail();

        $meta = [];
        if (! empty($post->meta_json)) {
            $decoded = json_decode((string) $post->meta_json, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        $description = trim(strip_tags((string) ($post->subtitle ?: $post->body_html)));
        $description = preg_replace('/\s+/', ' ', $description) ?: '';
        if (mb_strlen($description) > 220) {
            $description = rtrim(mb_substr($description, 0, 217));
            $lastSpace = mb_strrpos($description, ' ');
            if ($lastSpace !== false && $lastSpace > 160) {
                $description = mb_substr($description, 0, $lastSpace);
            }
            $description .= '...';
        }

        if ($description === '' && $kind === 'shorts') {
            $description = 'Watch this short video in '.$app->name.'.';
        }

        $canonical = route('public.content-share.show', [
            'appSlug' => $appSlug,
            'kind' => $kind,
            'idOrSlug' => $post->slug ?: $post->id,
        ]);

        $appRoute = $kind === 'shorts'
            ? '/short-videos?id=short_video_'.$post->id
            : '/content/'.($post->slug ?: $post->id);

        return view('public.content-share', [
            'app' => $app,
            'post' => $post,
            'kind' => $kind,
            'description' => $description,
            'imageUrl' => $post->cover_image_url ?: ($meta['thumbnail_url'] ?? null),
            'canonical' => $canonical,
            'appRoute' => $appRoute,
            'playStoreUrl' => 'https://play.google.com/store/apps/details?id=com.digitxtramedia.dunamistv',
            'androidPackage' => 'com.digitxtramedia.dunamistv',
        ]);
    }
}
