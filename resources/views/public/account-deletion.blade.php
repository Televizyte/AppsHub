<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Delete {{ $app->name }} account</title>
<style>*{box-sizing:border-box}body{margin:0;background:#08101f;color:#eef2ff;font-family:Inter,system-ui,sans-serif}.wrap{max-width:820px;margin:auto;padding:30px 16px}.card{background:#10182b;border:1px solid #26324c;border-radius:22px;padding:24px}.muted{color:#aeb9d0;line-height:1.65}.field{display:grid;gap:7px;margin-top:14px}.field label{font-size:12px;font-weight:800}.field input,.field textarea{width:100%;border:1px solid #34425f;border-radius:12px;padding:12px;background:#0b1324;color:#fff;font:inherit}.btn{margin-top:18px;border:0;border-radius:12px;padding:12px 18px;background:#db2777;color:#fff;font-weight:800;cursor:pointer}.ok{padding:14px;border-radius:12px;background:#063f35;border:1px solid #0f766e;margin-bottom:16px}.err{color:#fca5a5;font-size:12px}.check{display:flex;gap:9px;align-items:flex-start;margin-top:14px}a{color:#67e8f9}</style></head>
<body><main class="wrap"><section class="card">
<h1>Delete your {{ $app->name }} account</h1>
<p class="muted">You may delete your account inside the app through Account or Settings, or submit this web request if you cannot access the app. {{ $profile['operator_name'] }} may verify your identity before processing the request.</p>
<p class="muted">Eligible profile information, comments, likes, follows, saved items, notification tokens and other account-linked data will normally be deleted. Limited security, fraud-prevention, dispute, accounting or legal records may be retained where required or permitted by law. Requests are normally completed within <strong>{{ $profile['account_deletion_period_days'] }} days</strong> after verification.</p>
@if(session('deletion_request_submitted'))<div class="ok">Your deletion request has been received. Reference: <strong>{{ session('deletion_request_submitted') }}</strong></div>@endif
<form method="post" action="{{ route('public.account-deletion.store',['appSlug'=>$app->slug]) }}">@csrf
<div class="field"><label>Account email</label><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="err">{{ $message }}</span>@enderror</div>
<div class="field"><label>Display name (optional)</label><input name="display_name" value="{{ old('display_name') }}"></div>
<div class="field"><label>Reason or additional information (optional)</label><textarea name="reason" rows="5">{{ old('reason') }}</textarea></div>
<label class="check"><input type="checkbox" name="confirm" value="1" required><span>I understand that this is a permanent account-deletion request and that identity verification may be required.</span></label>@error('confirm')<span class="err">{{ $message }}</span>@enderror
<button class="btn" type="submit">Submit deletion request</button></form>
<p class="muted" style="margin-top:20px">App support: <a href="mailto:{{ $profile['technical_support_email'] }}?subject=[{{ strtoupper($app->slug) }}] Account Deletion">{{ $profile['technical_support_email'] }}</a></p>
</section></main></body></html>
