<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppSupportRequest;
use App\Support\Legal\LegalDocumentRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountDeletionController extends Controller
{
    public function show(string $appSlug): Response
    {
        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();

        return response()->view('public.account-deletion', [
            'app' => $app,
            'branding' => is_array($app->branding_json) ? $app->branding_json : [],
            'profile' => LegalDocumentRenderer::profile($app),
        ]);
    }

    public function store(Request $request, string $appSlug): RedirectResponse
    {
        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();
        $profile = LegalDocumentRenderer::profile($app);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'display_name' => ['nullable', 'string', 'max:160'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ]);

        $reference = strtoupper(Str::limit(str_replace('-', '', $app->slug), 8, '')).'-DEL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $record = [
            'app_id' => $app->id,
            'user_id' => optional($request->user())->id,
            'reference' => $reference,
            'category' => 'account_deletion',
            'status' => 'new',
            'verification_status' => 'required',
            'email' => strtolower(trim($validated['email'])),
            'display_name' => trim((string) ($validated['display_name'] ?? '')),
            'subject' => 'Account deletion request',
            'message' => trim((string) ($validated['reason'] ?? '')),
            'source' => 'web',
            'submitted_route' => '/'.$app->slug.'/account-deletion',
            'due_at' => now()->addDays(max(1, (int) $profile['account_deletion_period_days'])),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'meta_json' => [
                'app_name' => $app->name,
                'app_slug' => $app->slug,
                'processing_period_days' => $profile['account_deletion_period_days'],
            ],
        ];

        if (Schema::hasTable('app_support_requests')) {
            AppSupportRequest::query()->create($record);
        } else {
            Storage::disk('local')->append(
                'account-deletion-requests/'.$app->slug.'.jsonl',
                json_encode($record + ['created_at' => now()->toIso8601String()], JSON_UNESCAPED_SLASHES)
            );
        }

        try {
            if ($profile['technical_support_email'] !== '') {
                $subject = '['.strtoupper($app->slug).'][ACCOUNT-DELETION] '.$reference;
                $body = "App: {$app->name}
App slug: {$app->slug}
Reference: {$reference}
Account email: {$validated['email']}
Display name: ".($validated['display_name'] ?? '')."
Due within: {$profile['account_deletion_period_days']} days

Reason: ".($validated['reason'] ?? '');
                Mail::raw($body, fn ($message) => $message->to($profile['technical_support_email'])->subject($subject));
            }
        } catch (\Throwable $error) {
            report($error);
        }

        return back()->with('deletion_request_submitted', $reference);
    }
}
