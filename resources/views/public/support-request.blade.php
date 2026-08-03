<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $app->name }} Support</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#08101f;color:#eef2ff;font-family:Inter,system-ui,sans-serif}.wrap{max-width:820px;margin:auto;padding:30px 16px}.card{background:#10182b;border:1px solid #26324c;border-radius:22px;padding:24px}.muted{color:#aeb9d0;line-height:1.65}.field{display:grid;gap:7px;margin-top:14px}.field label{font-size:12px;font-weight:800}.field input,.field select,.field textarea{width:100%;border:1px solid #34425f;border-radius:12px;padding:12px;background:#0b1324;color:#fff;font:inherit}.btn{margin-top:18px;border:0;border-radius:12px;padding:12px 18px;background:#0ea5e9;color:#fff;font-weight:800;cursor:pointer}.ok{padding:14px;border-radius:12px;background:#063f35;border:1px solid #0f766e;margin-bottom:16px}.err{color:#fca5a5;font-size:12px}a{color:#67e8f9}
    </style>
</head>
<body>
<main class="wrap"><section class="card">
    <h1>{{ $app->name }} Support</h1>
    <p class="muted">Submit an app-specific request. Your reference will identify {{ $app->name }} automatically.</p>
    @if(session('support_request_submitted'))<div class="ok">Request received. Reference: <strong>{{ session('support_request_submitted') }}</strong></div>@endif
    <form method="post" action="{{ route('public.support-request.store',['appSlug'=>$app->slug]) }}">@csrf
        <div class="field"><label>Category</label><select name="category" required>
            <option value="technical">Technical problem</option><option value="account">Account and login</option><option value="privacy">Privacy or data request</option><option value="account_deletion">Account deletion</option><option value="content">Content report</option><option value="copyright">Copyright complaint</option><option value="advertising">Advertising complaint</option><option value="ministry">Ministry enquiry</option><option value="general">General app enquiry</option><option value="other">Other</option>
        </select></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="err">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Name (optional)</label><input name="display_name" value="{{ old('display_name') }}"></div>
        <div class="field"><label>Subject</label><input name="subject" value="{{ old('subject') }}" required></div>
        <div class="field"><label>Message</label><textarea name="message" rows="7" required>{{ old('message') }}</textarea></div>
        <div class="field"><label>App version (optional)</label><input name="app_version" value="{{ old('app_version') }}"></div>
        <div class="field"><label>Platform (optional)</label><input name="platform" value="{{ old('platform','Android') }}"></div>
        <button class="btn" type="submit">Submit support request</button>
    </form>
    @if(!empty($profile['technical_support_email']))<p class="muted">Email alternative: <a href="mailto:{{ $profile['technical_support_email'] }}?subject=[{{ strtoupper($app->slug) }}] App Support">{{ $profile['technical_support_email'] }}</a></p>@endif
</section></main>
</body></html>
