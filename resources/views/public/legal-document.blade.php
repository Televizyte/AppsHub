<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $document['title'] }} · {{ $app->name }}</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#07101f;color:#e8eefc;font-family:Inter,system-ui,sans-serif}.wrap{max-width:940px;margin:auto;padding:28px 16px}.head,.doc{background:#101a2e;border:1px solid #263653;border-radius:22px;padding:24px}.head{margin-bottom:14px}.muted{color:#9fb0ca}.doc{line-height:1.75}.doc h1,.doc h2,.doc h3{line-height:1.25}.doc a,a{color:#5ee7f7}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}.btn{display:inline-block;padding:10px 14px;border-radius:11px;background:#0ea5e9;color:white;text-decoration:none;font-weight:800}.btn.alt{background:#263653}.footer{font-size:13px;color:#9fb0ca;margin-top:16px}
</style>
</head>
<body><main class="wrap">
<section class="head"><div class="muted">{{ $app->name }}</div><h1>{{ $document['title'] }}</h1><p class="muted">{{ $document['summary'] ?? '' }}</p>
<div class="actions"><a class="btn" href="{{ url('/'.$app->slug.'/support-request') }}">Contact app support</a>@if($documentKey !== 'privacy')<a class="btn alt" href="{{ url('/'.$app->slug.'/privacy-policy') }}">Privacy Policy</a>@endif</div></section>
<article class="doc">{!! $html !!}</article>
<p class="footer">Technical operator: {{ $profile['operator_name'] }} · Support: {{ $profile['technical_support_email'] }}</p>
</main></body></html>
