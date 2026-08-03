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

class SupportRequestController extends Controller
{
    public function show(string $appSlug): Response
    {
        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();

        return response()->view('public.support-request', [
            'app' => $app,
            'profile' => LegalDocumentRenderer::profile($app),
        ]);
    }

    public function store(Request $request, string $appSlug): RedirectResponse
    {
        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'category' => ['required', 'in:technical,account,privacy,account_deletion,content,copyright,advertising,ministry,general,other'],
            'email' => ['required', 'email', 'max:190'],
            'display_name' => ['nullable', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:220'],
            'message' => ['required', 'string', 'max:5000'],
            'app_version' => ['nullable', 'string', 'max:80'],
            'platform' => ['nullable', 'string', 'max:40'],
        ]);

        $prefix = strtoupper(Str::limit(str_replace('-', '', $app->slug), 8, ''));
        $reference = $prefix.'-'.strtoupper(substr($validated['category'], 0, 4)).'-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $profile = LegalDocumentRenderer::profile($app);
        $days = max(1, (int) $profile['account_deletion_period_days']);
        $isDeletion = $validated['category'] === 'account_deletion';

        $record = [
            'app_id' => $app->id,
            'user_id' => optional($request->user())->id,
            'reference' => $reference,
            'category' => $validated['category'],
            'status' => 'new',
            'verification_status' => $isDeletion ? 'required' : 'not_required',
            'email' => strtolower(trim($validated['email'])),
            'display_name' => trim((string) ($validated['display_name'] ?? '')),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'source' => 'web',
            'app_version' => trim((string) ($validated['app_version'] ?? '')),
            'platform' => trim((string) ($validated['platform'] ?? '')),
            'submitted_route' => '/'.$app->slug.'/support-request',
            'due_at' => $isDeletion ? now()->addDays($days) : null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'meta_json' => [
                'app_name' => $app->name,
                'app_slug' => $app->slug,
                'operator' => $profile['operator_name'],
            ],
        ];

        if (Schema::hasTable('app_support_requests')) {
            AppSupportRequest::query()->create($record);
        } else {
            Storage::disk('local')->append(
                'support-requests/'.$app->slug.'.jsonl',
                json_encode($record + ['created_at' => now()->toIso8601String()], JSON_UNESCAPED_SLASHES)
            );
        }

        try {
            $recipient = $validated['category'] === 'ministry' && $profile['ministry_email'] !== ''
                ? $profile['ministry_email']
                : $profile['technical_support_email'];
            if ($recipient !== '') {
                $subject = '['.strtoupper($app->slug).']['.strtoupper($validated['category']).'] '.$reference.' - '.$validated['subject'];
                $body = "App: {$app->name}
App slug: {$app->slug}
Reference: {$reference}
Category: {$validated['category']}
From: {$validated['email']}
Name: ".($validated['display_name'] ?? '')."
App version: ".($validated['app_version'] ?? '')."
Platform: ".($validated['platform'] ?? '')."

".$validated['message'];
                Mail::raw($body, fn ($message) => $message->to($recipient)->subject($subject));
            }
        } catch (\Throwable $error) {
            report($error);
        }

        return back()->with('support_request_submitted', $reference);
    }
}
