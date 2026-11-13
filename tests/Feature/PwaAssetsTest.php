<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_pwa_manifest_icons_offline_page_and_service_worker_are_available(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertFileExists(public_path('icons/acr-icon.svg'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('sw.js'));

        $serviceWorker = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString('staticPrefixes.some', $serviceWorker);
    }

    public function test_web_responses_include_security_headers_and_are_not_cached(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Affi Campbellpur Rides')
            ->assertSee('Safe, reliable bike rides in Attock')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_urdu_locale_renders_translated_copy_and_rtl_direction(): void
    {
        $this->withSession(['locale' => 'ur'])->get(route('home'))
            ->assertOk()
            ->assertSee('افی کیمبل پور رائیڈز')
            ->assertSee('dir="rtl"', false);
    }

    public function test_language_switch_does_not_redirect_to_an_external_referer(): void
    {
        $this->withHeader('referer', 'https://malicious.example/')->get(route('language.switch', 'ur'))
            ->assertRedirect(route('home'));
    }
}
