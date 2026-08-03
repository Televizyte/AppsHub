<?php

namespace App\Support\Legal;

use App\Models\App;

final class LegalTemplateRegistry
{
    public const VERSION = '2026.07.29.1';

    public static function definitions(): array
    {
        return [
            'privacy' => ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'summary' => 'How personal information, device data, notifications, analytics, advertising and account data are handled.'],
            'terms' => ['slug' => 'terms-of-use', 'title' => 'Terms of Use', 'summary' => 'Rules for accounts, content, community features, advertisements, availability and acceptable use.'],
            'support' => ['slug' => 'support', 'title' => 'Support Information', 'summary' => 'Technical support, app-user enquiries and ministry contact responsibilities.'],
            'community' => ['slug' => 'community-guidelines', 'title' => 'Community Guidelines', 'summary' => 'Standards for comments, reports, moderation and account enforcement.'],
            'copyright' => ['slug' => 'copyright-policy', 'title' => 'Copyright Policy', 'summary' => 'Copyright ownership, permissions and infringement-reporting procedures.'],
            'content-usage' => ['slug' => 'content-usage', 'title' => 'Content Usage Policy', 'summary' => 'Personal-use permissions, attribution, downloads and prohibited redistribution.'],
            'disclaimer' => ['slug' => 'disclaimer', 'title' => 'Disclaimer', 'summary' => 'Service availability, third-party services, ministry boundaries and informational limitations.'],
            'data-safety' => ['slug' => 'data-safety', 'title' => 'Google Play Data Safety Summary', 'summary' => 'A plain-language summary of the app data practices declared in Google Play.'],
        ];
    }

    public static function keyFromPublicSlug(string $slug): ?string
    {
        foreach (self::definitions() as $key => $definition) {
            if ($slug === $definition['slug'] || $slug === $key) {
                return $key;
            }
        }

        return match ($slug) {
            'terms-and-conditions' => 'terms',
            'privacy' => 'privacy',
            'community' => 'community',
            'copyright' => 'copyright',
            default => null,
        };
    }

    public static function publicSlug(string $key): string
    {
        return (string) (self::definitions()[$key]['slug'] ?? $key);
    }

    public static function publicBaseUrl(App $app): string
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $configured = trim((string) data_get($branding, 'legal.profile.legal_public_base_url', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $host = rtrim((string) env('APPSHUB_PUBLIC_URL', config('app.url')), '/');
        return $host.'/'.$app->slug;
    }

    public static function publicUrl(App $app, string $key): string
    {
        return self::publicBaseUrl($app).'/'.self::publicSlug($key);
    }

    public static function defaultDocument(string $key): array
    {
        $definition = self::definitions()[$key] ?? abort(404);

        return [
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'content' => self::content($key),
            'effective_date' => now()->toDateString(),
            'last_updated' => now()->toIso8601String(),
            'published' => false,
            'inheritance_mode' => 'inherited',
            'template_version' => self::VERSION,
        ];
    }

    public static function effectiveDocument(App $app, string $key): array
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $stored = data_get($branding, 'legal.documents.'.$key);
        $default = self::defaultDocument($key);

        if (! is_array($stored)) {
            return $default;
        }

        return array_merge($default, $stored);
    }

    public static function bootstrapPayload(App $app): array
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $profile = LegalDocumentRenderer::profile($app);
        $documents = [];

        foreach (self::definitions() as $key => $definition) {
            $document = self::effectiveDocument($app, $key);
            $documents[$key] = [
                'key' => $key,
                'slug' => $definition['slug'],
                'title' => $document['title'],
                'summary' => $document['summary'],
                'published' => (bool) ($document['published'] ?? false),
                'url' => self::publicUrl($app, $key),
                'template_version' => (string) ($document['template_version'] ?? self::VERSION),
                'inheritance_mode' => (string) ($document['inheritance_mode'] ?? 'inherited'),
                'last_updated' => (string) ($document['last_updated'] ?? ''),
            ];
        }

        return [
            'public_base_url' => self::publicBaseUrl($app),
            'profile' => [
                'operator_name' => $profile['operator_name'],
                'support_email' => $profile['technical_support_email'],
                'privacy_email' => $profile['privacy_contact_email'],
                'account_deletion_period_days' => $profile['account_deletion_period_days'],
                'jurisdiction' => $profile['jurisdiction'],
            ],
            'documents' => $documents,
            'account_deletion' => [
                'enabled' => true,
                'url' => self::publicBaseUrl($app).'/account-deletion',
                'processing_period_days' => $profile['account_deletion_period_days'],
            ],
            'support' => [
                'enabled' => true,
                'url' => self::publicBaseUrl($app).'/support',
                'form_url' => self::publicBaseUrl($app).'/support-request',
                'email' => $profile['technical_support_email'],
            ],
            'ministry' => [
                'name' => $profile['ministry_name'],
                'email' => $profile['ministry_email'],
                'phone' => $profile['ministry_phone'],
                'address' => $profile['ministry_address'],
                'latitude' => $profile['ministry_latitude'],
                'longitude' => $profile['ministry_longitude'],
            ],
            'raw' => data_get($branding, 'legal', []),
        ];
    }

    private static function content(string $key): string
    {
        return match ($key) {
            'privacy' => <<<'MD'
# Privacy Policy

**Effective date:** {{effective_date}}  
**Last updated:** {{last_updated}}

{{operator_name}} operates {{app_name}}. This policy explains how information is handled when you use the application and related services.

## Information we may process

Depending on the features enabled for {{app_name}}, we may process account details, profile information, comments and other user submissions, likes, saved items, device and notification tokens, app diagnostics, security logs, support enquiries and account-deletion requests. Advertising and analytics providers may process device identifiers, ad interactions and diagnostic information in accordance with their own policies.

## Why information is used

Information is used to provide and secure the service, authenticate users, remember preferences, deliver notifications, operate community features, respond to support requests, prevent abuse, improve reliability, display advertisements where enabled and comply with legal obligations.

## Sharing and service providers

Information may be processed by infrastructure, authentication, notification, analytics, advertising and security providers used to operate the service. We do not sell users' personal information. We may disclose information where required by law, to protect users or the service, or in connection with a lawful business reorganisation.

## Retention

We retain information only for as long as reasonably necessary for the purposes described above. Some security, fraud-prevention, dispute, accounting or legal records may be retained after account deletion where required or permitted by law.

## Your choices and rights

You may request access, correction or deletion of eligible personal information by contacting {{privacy_contact_email}}. Account-deletion information is available at {{account_deletion_url}}. Requests are normally processed within {{account_deletion_period_days}} days after any required identity verification.

## Children

Users should meet the age requirements shown in the app's store listing and applicable law. A parent or guardian may contact us about a child's information.

## Security

We apply reasonable technical and organisational safeguards, but no internet or storage system can be guaranteed completely secure.

## Contact

Privacy and data requests: {{privacy_contact_email}}  
Technical support: {{technical_support_email}}  
Operator: {{operator_name}}  
Jurisdiction: {{jurisdiction}}
MD,
            'terms' => <<<'MD'
# Terms of Use

**Effective date:** {{effective_date}}  
**Last updated:** {{last_updated}}

These Terms govern use of {{app_name}}, operated technically by {{operator_name}}.

## Acceptance

By accessing or using the service, you agree to these Terms and the Privacy Policy at {{privacy_url}}. If you do not agree, do not use the service.

## Accounts

You are responsible for accurate account information, safeguarding your credentials and activity performed through your account. We may restrict, suspend or terminate access for abuse, fraud, unlawful conduct or material violations of these Terms.

## Permitted use

Use the service for lawful, personal and authorised purposes. Do not interfere with the service, bypass security, scrape protected content, impersonate others, upload malicious material, harass users, misuse community features or infringe intellectual-property rights.

## Content and ministry information

Content is provided for information, education, inspiration and ministry engagement. Technical operation by {{operator_name}} does not make the ministry responsible for app infrastructure, accounts, advertising, data processing, outages or technical support. Ministry enquiries should use the ministry details presented in the app.

## Advertising and third-party services

The service may display advertisements and use third-party providers. We do not control every third-party service, offer or destination. Users should review relevant third-party terms before engaging with them.

## Availability and changes

Features may change, be suspended or become unavailable. We may update these Terms and will publish the revised effective date.

## Disclaimer and liability

The service is provided on an "as available" basis to the extent permitted by law. Nothing in these Terms excludes rights that cannot lawfully be excluded. To the maximum extent permitted by law, {{operator_name}} is not liable for indirect or consequential loss arising from use or inability to use the service.

## Contact and governing law

Technical and account support: {{technical_support_email}}  
These Terms are governed by the laws of {{jurisdiction}}, subject to applicable mandatory consumer protections.
MD,
            'support' => <<<'MD'
# Support Information

{{app_name}} separates **app support** from **ministry enquiries**.

## App and technical support

Use the support form below for login problems, account questions, privacy requests, content reports, advertising complaints, copyright reports and technical issues. Every request receives an app-specific reference so {{operator_name}} can identify its source.

Email: {{technical_support_email}}

## Privacy and account deletion

Privacy and data requests: {{privacy_contact_email}}  
Account deletion: {{account_deletion_url}}  
Target processing period: within {{account_deletion_period_days}} days after any required verification.

## Ministry enquiries

Ministry name: {{ministry_name}}  
Email: {{ministry_email}}  
Phone: {{ministry_phone}}  
Address: {{ministry_address}}

Ministry contacts handle pastoral, prayer, programme, event and ministry-related enquiries. {{operator_name}} handles application infrastructure and user support.
MD,
            'community' => <<<'MD'
# Community Guidelines

These guidelines apply wherever {{app_name}} allows comments, reactions, reports or other user participation.

## Be respectful

Do not post harassment, threats, hate, sexual exploitation, graphic abuse, spam, impersonation, fraud, malicious links or content that violates another person's privacy or rights.

## Keep contributions relevant

Use community features for constructive discussion connected to the content and purpose of the app. Do not repeatedly promote unrelated products, services or channels.

## Reporting and moderation

Users may report content through the app-support process. {{operator_name}} may review, hide or remove content, restrict features, preserve evidence and suspend accounts where reasonably necessary to protect users, the service or legal rights.

## Appeals

For a moderation question, contact {{technical_support_email}} and include the originating app, your account email and any reference number.
MD,
            'copyright' => <<<'MD'
# Copyright Policy

Content available through {{app_name}} may be owned by {{copyright_owner}}, the relevant ministry, licensed publishers, creators or other rights holders.

## Permitted access

Access does not transfer ownership. Except where expressly enabled, content may not be copied, sold, republished, rebroadcast, modified, scraped or distributed for commercial use without permission from the relevant rights holder.

## Copyright reports

A report should identify the protected work, the material complained of, its location in the app, the reporter's contact information and a good-faith statement that the use is unauthorised. Send reports through the app-support form or to {{technical_support_email}}.

We may remove or restrict material while a report is reviewed and may act against repeat infringement.
MD,
            'content-usage' => <<<'MD'
# Content Usage Policy

Unless a feature clearly states otherwise, {{app_name}} content is provided for personal, non-commercial viewing, reading, listening and sharing through the app's authorised tools.

You may not remove ownership notices, falsely claim authorship, resell content, create unauthorised broadcasts, systematically download or scrape materials, or use content in a misleading, unlawful or harmful context.

Offline downloads, saves and shares remain subject to the rights of the relevant owner and any limits shown in the app. Permission requests should be directed through {{technical_support_email}} or the relevant rights holder.
MD,
            'disclaimer' => <<<'MD'
# Disclaimer

{{app_name}} provides ministry, educational, inspirational and informational content. It is not a substitute for professional medical, legal, financial, emergency or mental-health advice.

Technical operation is provided by {{operator_name}}. Ministry organisations and speakers are responsible for their own ministry content and official communications, while {{operator_name}} is responsible for app infrastructure and technical support within its control.

The service may contain advertisements, external media or third-party services. Availability, accuracy and uninterrupted access cannot be guaranteed. Use emergency services or qualified professionals where appropriate.
MD,
            'data-safety' => <<<'MD'
# Google Play Data Safety Summary

This summary supports, but does not replace, the formal Google Play Data Safety declaration for {{app_name}}.

Depending on enabled features, the app may process account information, user-generated content, app activity, device or notification identifiers, diagnostics, support requests and advertising data. Processing may be required for app functionality, account management, security, communications, analytics and advertising.

Data practices must be reviewed whenever AppsHub capabilities, SDKs or providers change. The current Privacy Policy is available at {{privacy_url}}. Privacy questions may be sent to {{privacy_contact_email}}.
MD,
            default => '',
        };
    }
}
