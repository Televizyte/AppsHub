<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $post->title }} — {{ $app->name }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ $kind === 'shorts' ? 'video.other' : 'article' }}">
    <meta property="og:site_name" content="{{ $app->name }}">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($imageUrl)
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:secure_url" content="{{ $imageUrl }}">
    <meta property="og:image:alt" content="{{ $post->title }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($imageUrl)
    <meta name="twitter:image" content="{{ $imageUrl }}">
    @endif
    <style>
        body{margin:0;background:#070b18;color:#fff;font-family:Arial,sans-serif}
        .wrap{max-width:760px;margin:0 auto;padding:24px}
        .card{background:#10162c;border:1px solid #28304f;border-radius:24px;overflow:hidden}
        img{width:100%;display:block;max-height:520px;object-fit:cover}
        .body{padding:24px}.muted{color:#b9bfd3;line-height:1.6}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
        a{display:inline-block;padding:14px 20px;border-radius:999px;text-decoration:none;font-weight:700}
        .primary{background:#ed008c;color:#fff}.secondary{border:1px solid #fff;color:#fff}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $post->title }}">@endif
        <div class="body">
            <div class="muted">{{ $app->name }}</div>
            <h1>{{ $post->title }}</h1>
            <p class="muted">{{ $description }}</p>
            <div class="actions">
                @php
                    $customSchemeUrl = 'dunamistv://open?route='.urlencode($appRoute);
                    $intentUrl = 'intent://open?route='.urlencode($appRoute)
                        .'#Intent;scheme=dunamistv;package='.$androidPackage
                        .';S.browser_fallback_url='.urlencode($playStoreUrl).';end';
                @endphp
                <a class="primary" href="{{ $intentUrl }}" data-custom-url="{{ $customSchemeUrl }}">Open in Dunamis TV</a>
                <a class="secondary" href="{{ $playStoreUrl }}">Download on Google Play</a>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var link = document.querySelector('a.primary[data-custom-url]');
    if (!link) return;
    link.addEventListener('click', function () {
        if (!/Android/i.test(navigator.userAgent)) {
            window.location.href = link.getAttribute('data-custom-url');
        }
    });
})();
</script>
</body>
</html>
