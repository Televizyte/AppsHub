<?php

namespace Tests\Unit;

use App\Support\Ads\AdResolver;
use PHPUnit\Framework\TestCase;

class AdResolverContractTest extends TestCase
{
    public function test_genuinely_missing_profile_fails_closed_across_canonical_contract(): void
    {
        $contract = AdResolver::resolveContract([], []);

        foreach (['enabled', 'formats', 'units', 'tabs', 'native_in_list', 'interstitial'] as $key) {
            $this->assertArrayHasKey($key, $contract);
        }

        $this->assertFalse($contract['enabled']);
        $this->assertSame([
            'banner' => false,
            'native' => false,
            'interstitial' => false,
        ], $contract['formats']);
        $this->assertSame([
            'banner' => null,
            'native' => null,
            'interstitial' => null,
        ], $contract['units']);
        $this->assertFalse($contract['native_in_list']['enabled']);

        foreach ($contract['tabs'] as $policy) {
            $this->assertFalse($policy['banner']);
            $this->assertFalse($policy['native']);
            $this->assertFalse($policy['interstitial']);
            $this->assertSame('disabled', $policy['banner_config']['placement']);
            $this->assertFalse($policy['native_config']['enabled']);
            $this->assertFalse($policy['interstitial_config']['enabled']);
        }

        foreach (['watch.player.live', 'webview.active', 'form.active', 'authentication.active'] as $key) {
            $this->assertArrayHasKey($key, $contract['tabs']);
            $this->assertTrue($contract['tabs'][$key]['protected']);
        }
    }

    public function test_missing_profile_gate_cannot_be_bypassed_by_enabled_rules(): void
    {
        $contract = AdResolver::resolveContract([], [
            $this->rule(1, 'tab', 'home', true, true, true, true),
            $this->rule(2, 'route', 'home.action.articles', true, true, true, true, $this->allOverrides()),
        ]);

        foreach (['home', 'home.action.articles'] as $key) {
            $this->assertFalse($contract['tabs'][$key]['banner']);
            $this->assertFalse($contract['tabs'][$key]['native']);
            $this->assertFalse($contract['tabs'][$key]['interstitial']);
            $this->assertFalse($contract['tabs'][$key]['native_config']['enabled']);
            $this->assertFalse($contract['tabs'][$key]['interstitial_config']['enabled']);
        }
    }

    public function test_existing_enabled_profile_preserves_enabled_defaults(): void
    {
        $contract = AdResolver::resolveContract(['ads_enabled' => true], []);

        $this->assertTrue($contract['enabled']);
        $this->assertSame([
            'banner' => true,
            'native' => true,
            'interstitial' => true,
        ], $contract['formats']);
        $this->assertTrue($contract['tabs']['home']['banner']);
        $this->assertTrue($contract['tabs']['home']['native']);
    }

    public function test_existing_disabled_profile_remains_disabled(): void
    {
        $contract = AdResolver::resolveContract(['ads_enabled' => false], []);

        $this->assertFalse($contract['enabled']);
        $this->assertSame([
            'banner' => false,
            'native' => false,
            'interstitial' => false,
        ], $contract['formats']);
    }

    public function test_master_switch_blocks_every_tab_and_route(): void
    {
        $contract = $this->resolve(['ads_enabled' => false], [
            $this->rule(1, 'tab', 'home', true, true, true, true),
            $this->rule(2, 'route', 'home.action.articles', true, true, true, true, $this->allOverrides()),
        ]);

        $this->assertFalse($contract['enabled']);
        $this->assertSame(['banner' => false, 'native' => false, 'interstitial' => false], $contract['formats']);
        foreach ($contract['tabs'] as $policy) {
            $this->assertFalse($policy['banner']);
            $this->assertFalse($policy['native']);
            $this->assertFalse($policy['interstitial']);
        }
    }

    public function test_global_banner_switch_blocks_child_override(): void
    {
        $contract = $this->resolve([
            'meta_json' => ['ad_formats' => ['banner' => false]],
        ], [
            $this->rule(1, 'route', 'home.action.articles', true, true, false, false, ['override' => ['banner' => true]]),
        ]);

        $this->assertFalse($contract['tabs']['home.action.articles']['banner']);
    }

    public function test_disabled_parent_cannot_be_bypassed_by_child(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'tab', 'home', false, false, false, false),
            $this->rule(2, 'route', 'home.action.articles', true, true, true, true, $this->allOverrides()),
        ]);

        $child = $contract['tabs']['home.action.articles'];
        $this->assertFalse($child['enabled']);
        $this->assertFalse($child['banner']);
        $this->assertFalse($child['native']);
        $this->assertFalse($child['interstitial']);
    }

    public function test_protected_routes_suppress_all_formats(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'tab', 'watch', true, true, true, true),
            $this->rule(2, 'route', 'watch.player.live', true, true, true, true, $this->allOverrides()),
        ]);

        $policy = $contract['tabs']['watch.player.live'];
        $this->assertTrue($policy['protected']);
        $this->assertFalse($policy['banner']);
        $this->assertFalse($policy['native']);
        $this->assertFalse($policy['interstitial']);
        $this->assertSame('disabled', $policy['banner_config']['placement']);
        $this->assertFalse($policy['native_config']['enabled']);
        $this->assertFalse($policy['interstitial_config']['enabled']);
    }

    public function test_parentless_protected_routes_are_explicit_safe_policies(): void
    {
        $contract = $this->resolve();

        foreach (['webview.active', 'form.active', 'authentication.active'] as $key) {
            $this->assertArrayHasKey($key, $contract['tabs']);
            $this->assertTrue($contract['tabs'][$key]['protected']);
            $this->assertFalse($contract['tabs'][$key]['banner']);
            $this->assertFalse($contract['tabs'][$key]['native']);
            $this->assertFalse($contract['tabs'][$key]['interstitial']);
        }
    }

    public function test_exact_route_override_applies_only_within_parent_permissions(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'tab', 'home', true, true, false, true),
            $this->rule(2, 'route', 'home.action.articles', true, true, true, false, $this->allOverrides()),
        ]);

        $policy = $contract['tabs']['home.action.articles'];
        $this->assertTrue($policy['banner']);
        $this->assertFalse($policy['native']);
        $this->assertFalse($policy['interstitial']);
    }

    public function test_default_route_policies_are_materialized(): void
    {
        $contract = $this->resolve();

        $this->assertArrayHasKey('home.native.after_quick_access', $contract['tabs']);
        $this->assertTrue($contract['tabs']['home.native.after_quick_access']['native']);
        $this->assertSame('default.route.home.native.after_quick_access', $contract['tabs']['home.native.after_quick_access']['source']);
    }

    public function test_default_route_policy_keys_are_canonical_and_colon_free(): void
    {
        $defaults = AdResolver::defaultRoutePlacementPolicies();

        foreach (array_keys($defaults) as $key) {
            $this->assertStringNotContainsString(':', $key);
        }

        $this->assertArrayHasKey('explore.games', $defaults);
        $this->assertArrayHasKey('explore.games.dominion_match', $defaults);
        $this->assertArrayHasKey('game.future_games', $defaults);
        $this->assertArrayHasKey('quiz', $defaults);
    }

    public function test_default_route_policy_literals_are_unique(): void
    {
        preg_match_all("/^ {12}'([^']+)'\\s*=>/m", $this->methodSource('defaultRoutePlacementPolicies'), $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function test_public_ad_wrappers_preserve_canonical_array_returns(): void
    {
        $global = new \ReflectionMethod(AdResolver::class, 'globalInterstitialForApp');
        $tabs = new \ReflectionMethod(AdResolver::class, 'tabsAdsForApp');

        $this->assertSame('array', (string) $global->getReturnType());
        $this->assertSame('array', (string) $tabs->getReturnType());
        $this->assertStringContainsString(
            "return self::adsForApp(\$appId)['interstitial'];",
            $this->methodSource('globalInterstitialForApp')
        );
        $this->assertStringContainsString(
            "return self::adsForApp(\$appId)['tabs'];",
            $this->methodSource('tabsAdsForApp')
        );
    }

    public function test_side_effect_free_screen_resolver_keeps_exact_and_protected_behavior(): void
    {
        $policies = $this->resolve()['tabs'];

        $exact = AdResolver::resolveScreenContract($policies, 'home', 'home.native.after_quick_access');
        $this->assertSame('home.native.after_quick_access', $exact['resolved_key']);
        $this->assertTrue($exact['native']);

        $protected = AdResolver::resolveScreenContract($policies, 'watch', 'watch.player.live');
        $this->assertSame('watch.player.live', $protected['resolved_key']);
        $this->assertTrue($protected['protected']);
        $this->assertFalse($protected['banner']);
        $this->assertFalse($protected['native']);
        $this->assertFalse($protected['interstitial']);
    }

    public function test_stored_route_policy_overrides_route_default(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'route', 'home.native.after_quick_access', true, false, false, false, ['override' => ['native' => true]]),
        ]);

        $policy = $contract['tabs']['home.native.after_quick_access'];
        $this->assertFalse($policy['native']);
        $this->assertSame('ad_rules.route.home.native.after_quick_access', $policy['source']);
    }

    public function test_typed_cooldown_column_is_used_when_settings_do_not_override_it(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'route', 'home.action.articles', true, false, false, true, ['override' => ['interstitial' => true]], 45),
        ]);

        $this->assertSame(45, $contract['tabs']['home.action.articles']['interstitial_config']['cooldown_seconds']);
    }

    public function test_settings_cooldown_precedes_typed_column_and_numeric_values_are_clamped(): void
    {
        $contract = $this->resolve([
            'meta_json' => [
                'native_in_list' => ['every' => 0, 'start_after' => -8, 'max_per_list' => -1],
                'interstitial' => ['maximum_per_session' => -4],
            ],
        ], [
            $this->rule(1, 'route', 'home.action.articles', true, false, false, true, [
                'override' => ['interstitial' => true],
                'interstitial' => ['cooldown_seconds' => -50],
            ], 90),
        ]);

        $this->assertSame(1, $contract['native_in_list']['every']);
        $this->assertSame(0, $contract['native_in_list']['start_after']);
        $this->assertSame(0, $contract['native_in_list']['max_per_list']);
        $this->assertSame(0, $contract['interstitial']['maximum_per_session']);
        $this->assertSame(1, $contract['tabs']['home.action.articles']['interstitial_config']['cooldown_seconds']);
    }

    public function test_unit_ids_are_returned_in_canonical_structure(): void
    {
        $contract = $this->resolve([
            'banner_unit_id' => 'banner-id',
            'native_unit_id' => 'native-id',
            'interstitial_unit_id' => 'interstitial-id',
        ]);

        $this->assertSame([
            'banner' => 'banner-id',
            'native' => 'native-id',
            'interstitial' => 'interstitial-id',
        ], $contract['units']);
    }

    public function test_no_enabled_format_has_disabled_nested_configuration(): void
    {
        $contract = $this->resolve([], [
            $this->rule(1, 'tab', 'home', true, true, true, true),
            $this->rule(2, 'route', 'home.custom', true, true, true, true, [
                'override' => ['banner' => true, 'native' => true, 'interstitial' => true],
                'banner' => ['placement' => 'disabled'],
                'native' => ['enabled' => false],
                'interstitial' => ['enabled' => false],
            ]),
        ]);

        foreach ($contract['tabs'] as $policy) {
            $this->assertFalse($policy['banner'] && $policy['banner_config']['placement'] === 'disabled');
            $this->assertFalse($policy['native'] && ! $policy['native_config']['enabled']);
            $this->assertFalse($policy['interstitial'] && ! $policy['interstitial_config']['enabled']);
        }
    }

    public function test_duplicate_canonical_keys_resolve_to_last_ordered_rule(): void
    {
        $contract = $this->resolve([], [
            $this->rule(2, 'tab', '.HOME.', true, false, false, false),
            $this->rule(1, 'tab', 'home', true, true, true, false),
        ]);

        $this->assertFalse($contract['tabs']['home']['banner']);
        $this->assertFalse($contract['tabs']['home']['native']);
    }

    public function test_malformed_profile_policy_metadata_falls_back_safely(): void
    {
        $contract = $this->resolve(['meta_json' => ['ad_policy' => 'invalid']]);

        $this->assertTrue($contract['tabs']['home']['enabled']);
    }

    private function resolve(array $profile = [], array $rules = []): array
    {
        return AdResolver::resolveContract(array_merge(['ads_enabled' => true], $profile), $rules);
    }

    private function methodSource(string $method): string
    {
        $reflection = new \ReflectionMethod(AdResolver::class, $method);
        $lines = file($reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    private function rule(
        int $id,
        string $scopeType,
        string $scopeKey,
        bool $enabled,
        bool $banner,
        bool $native,
        bool $interstitial,
        array $settings = [],
        int $cooldown = 120
    ): array {
        return [
            'id' => $id,
            'scope_type' => $scopeType,
            'scope_key' => $scopeKey,
            'is_enabled' => $enabled,
            'banner_enabled' => $banner,
            'native_enabled' => $native,
            'interstitial_enabled' => $interstitial,
            'interstitial_cooldown_seconds' => $cooldown,
            'settings_json' => $settings,
        ];
    }

    private function allOverrides(): array
    {
        return ['override' => [
            'placement' => true,
            'banner' => true,
            'native' => true,
            'interstitial' => true,
        ]];
    }
}
