<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use App\Services\SiteHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a valid complete site payload for publish and upsert tests.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSitePayload(array $overrides = []): array
    {
        $base = [
            'template_id' => 'warung',
            'brand_color' => '#dc2626',
            'content' => [
                'template' => 'warung',
                'variant' => false,
                'brand' => '#dc2626',
                'nama' => 'Dapur Bu Sari',
                'tagline' => 'Masakan rumahan segar setiap hari',
                'kategori' => 'Kuliner rumahan',
                'deskripsi' => 'Masakan rumahan dengan bumbu pilihan.',
                'heroImg' => '/storage/sites/1/hero.jpg',
                'heroName' => 'hero.jpg',
                'heroSize' => '42 KB',
                'wa' => '081234567890',
                'ig' => 'dapurbusari',
                'alamat' => 'Jl. Melati No. 12, Bekasi',
                'jam' => 'Setiap hari, 08.00 - 21.00',
                'produk' => [
                    [
                        'nama' => 'Nasi Ayam Bakar',
                        'harga' => '25000',
                        'ket' => 'Ayam kampung, sambal matah',
                        'img' => '/storage/sites/1/ayam.jpg',
                        'imgName' => 'ayam.jpg',
                        'imgSize' => '28 KB',
                    ],
                ],
            ],
            'rendered_html' => '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Dapur Bu Sari</title></head><body><h1>Dapur Bu Sari</h1></body></html>',
        ];

        return array_replace_recursive($base, $overrides);
    }

    public function test_guest_cannot_access_site_api_endpoints(): void
    {
        $this->getJson('/api/site')->assertUnauthorized();
        $this->putJson('/api/site', [])->assertUnauthorized();
        $this->postJson('/api/site/publish', [])->assertUnauthorized();
        $this->deleteJson('/api/site')->assertUnauthorized();
    }

    public function test_umkm_can_get_empty_or_existing_site_with_account_slug(): void
    {
        $user = User::factory()->create([
            'business_name' => 'Warung Mbak Sari',
            'slug' => 'warung-mbak-sari',
        ]);
        $otherUser = User::factory()->create([
            'business_name' => 'Toko Sebelah',
            'slug' => 'toko-sebelah',
        ]);
        Site::factory()->for($otherUser)->create([
            'slug' => $otherUser->slug,
            'template_id' => 'toko',
        ]);

        $emptyResponse = $this->actingAs($user)->getJson('/api/site');

        $emptyResponse->assertOk();
        $emptyResponse->assertJson([
            'site' => null,
            'slug' => 'warung-mbak-sari',
        ]);
        $this->assertStringEndsWith('/warung-mbak-sari', $emptyResponse->json('public_url'));

        $site = Site::factory()->for($user)->create([
            'slug' => $user->slug,
            'template_id' => 'warung',
            'brand_color' => '#dc2626',
            'content' => ['nama' => 'Warung Mbak Sari', 'produk' => []],
        ]);

        $existingResponse = $this->actingAs($user)->getJson('/api/site');

        $existingResponse->assertOk();
        $existingResponse->assertJsonPath('site.id', $site->id);
        $existingResponse->assertJsonPath('site.slug', 'warung-mbak-sari');
        $existingResponse->assertJsonPath('site.template_id', 'warung');
        $existingResponse->assertJsonPath('slug', 'warung-mbak-sari');
    }

    public function test_umkm_can_save_draft_and_slug_remains_locked_to_user_slug(): void
    {
        $user = User::factory()->create([
            'business_name' => 'Kopi Senja',
            'slug' => 'kopi-senja',
        ]);
        $payload = $this->validSitePayload([
            'template_id' => 'warung-2',
            'brand_color' => '#d97706',
            'slug' => 'slug-palsu-berbahaya',
            'content' => [
                'nama' => 'Nama Baru Berubah',
            ],
        ]);

        $response = $this->actingAs($user)->putJson('/api/site', $payload);

        $response->assertOk();
        $response->assertJsonPath('slug', 'kopi-senja');
        $response->assertJsonPath('site.slug', 'kopi-senja');
        $response->assertJsonPath('site.status', 'draft');
        $response->assertJsonPath('site.template_id', 'warung-2');
        $response->assertJsonPath('site.content.nama', 'Nama Baru Berubah');

        $site = $user->fresh()->site;

        $this->assertNotNull($site);
        $this->assertSame('kopi-senja', $site->slug);
        $this->assertSame('kopi-senja', $user->fresh()->slug);
        $this->assertSame('draft', $site->status);
        $this->assertSame('Nama Baru Berubah', $site->content['nama']);
    }

    public function test_save_site_rejects_base64_images_in_content(): void
    {
        $user = User::factory()->create([
            'slug' => 'toko-aman',
        ]);

        $heroBase64Payload = $this->validSitePayload([
            'content' => [
                'heroImg' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ',
            ],
        ]);

        $heroResponse = $this->actingAs($user)->putJson('/api/site', $heroBase64Payload);

        $heroResponse->assertUnprocessable();
        $heroResponse->assertJsonValidationErrors(['content.heroImg']);
        $this->assertDatabaseCount('sites', 0);

        $productBase64Payload = $this->validSitePayload([
            'content' => [
                'heroImg' => '/storage/sites/'.$user->id.'/hero.jpg',
                'produk' => [
                    [
                        'nama' => 'Es Kopi',
                        'harga' => '15000',
                        'ket' => 'Dingin',
                        'img' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD',
                    ],
                ],
            ],
        ]);

        $productResponse = $this->actingAs($user)->putJson('/api/site', $productBase64Payload);

        $productResponse->assertUnprocessable();
        $productResponse->assertJsonValidationErrors(['content.produk.0.img']);
        $this->assertDatabaseCount('sites', 0);
    }

    public function test_validation_failures_on_secondary_content_fields_return_indonesian_messages(): void
    {
        $user = User::factory()->create(['slug' => 'validasi-sekunder']);

        $invalidPayload = $this->validSitePayload([
            'template_id' => 'template-liar',
            'brand_color' => 'merah',
            'content' => [
                'template' => str_repeat('t', 51),
                'variant' => 'bukan-boolean',
                'brand' => str_repeat('b', 21),
                'tagline' => str_repeat('a', 501),
                'kategori' => str_repeat('k', 121),
                'deskripsi' => str_repeat('d', 5001),
                'heroName' => str_repeat('h', 256),
                'heroSize' => str_repeat('s', 51),
                'ig' => str_repeat('i', 101),
                'alamat' => str_repeat('j', 501),
                'updatedAt' => 'bukan-angka',
                'produk' => [
                    [
                        'nama' => 'Produk Valid',
                        'harga' => ['array-tidak-valid'],
                        'ket' => str_repeat('p', 501),
                        'imgName' => str_repeat('n', 256),
                        'imgSize' => str_repeat('z', 51),
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->putJson('/api/site', $invalidPayload);

        $response->assertUnprocessable();
        $errors = $response->json('errors');

        $this->assertSame('Template yang dipilih tidak valid.', $errors['template_id'][0]);
        $this->assertSame('Format warna brand harus berupa kode hex 6 digit (contoh: #dc2626).', $errors['brand_color'][0]);
        $this->assertSame('ID template konten maksimal 50 karakter.', $errors['content.template'][0]);
        $this->assertSame('Status varian template harus berupa nilai benar atau salah.', $errors['content.variant'][0]);
        $this->assertSame('Warna brand konten maksimal 20 karakter.', $errors['content.brand'][0]);
        $this->assertSame('Tagline maksimal 500 karakter.', $errors['content.tagline'][0]);
        $this->assertSame('Kategori usaha maksimal 120 karakter.', $errors['content.kategori'][0]);
        $this->assertSame('Deskripsi usaha maksimal 5000 karakter.', $errors['content.deskripsi'][0]);
        $this->assertSame('Nama file foto utama maksimal 255 karakter.', $errors['content.heroName'][0]);
        $this->assertSame('Ukuran foto utama maksimal 50 karakter.', $errors['content.heroSize'][0]);
        $this->assertSame('Akun Instagram maksimal 100 karakter.', $errors['content.ig'][0]);
        $this->assertSame('Alamat usaha maksimal 500 karakter.', $errors['content.alamat'][0]);
        $this->assertSame('Waktu pembaruan konten harus berupa angka.', $errors['content.updatedAt'][0]);
        $this->assertSame('Harga produk harus berupa teks.', $errors['content.produk.0.harga'][0]);
        $this->assertSame('Keterangan produk maksimal 500 karakter.', $errors['content.produk.0.ket'][0]);
        $this->assertSame('Nama file foto produk maksimal 255 karakter.', $errors['content.produk.0.imgName'][0]);
        $this->assertSame('Ukuran foto produk maksimal 50 karakter.', $errors['content.produk.0.imgSize'][0]);
        $this->assertDatabaseCount('sites', 0);
    }

    public function test_publish_requires_all_four_checklist_items(): void
    {
        $user = User::factory()->create(['slug' => 'usaha-cek']);

        $missingNama = $this->validSitePayload();
        $missingNama['content']['nama'] = '';

        $resNama = $this->actingAs($user)->postJson('/api/site/publish', $missingNama);
        $resNama->assertUnprocessable();
        $resNama->assertJsonValidationErrors(['content.nama']);
        $this->assertSame('Nama usaha wajib diisi sebelum publikasi.', $resNama->json('errors')['content.nama'][0]);

        $missingWa = $this->validSitePayload();
        $missingWa['content']['wa'] = '';

        $resWa = $this->actingAs($user)->postJson('/api/site/publish', $missingWa);
        $resWa->assertUnprocessable();
        $resWa->assertJsonValidationErrors(['content.wa']);
        $this->assertSame('Nomor WhatsApp wajib diisi sebelum publikasi.', $resWa->json('errors')['content.wa'][0]);

        $missingJam = $this->validSitePayload();
        $missingJam['content']['jam'] = '';

        $resJam = $this->actingAs($user)->postJson('/api/site/publish', $missingJam);
        $resJam->assertUnprocessable();
        $resJam->assertJsonValidationErrors(['content.jam']);
        $this->assertSame('Jam buka wajib diisi sebelum publikasi.', $resJam->json('errors')['content.jam'][0]);

        $missingProduk = $this->validSitePayload();
        $missingProduk['content']['produk'] = [
            ['nama' => '', 'harga' => '10000', 'ket' => ''],
        ];

        $resProduk = $this->actingAs($user)->postJson('/api/site/publish', $missingProduk);
        $resProduk->assertUnprocessable();
        $resProduk->assertJsonValidationErrors(['content.produk']);
        $this->assertSame('Minimal satu produk wajib diisi sebelum publikasi.', $resProduk->json('errors')['content.produk'][0]);

        $missingHtml = $this->validSitePayload();
        unset($missingHtml['rendered_html']);

        $resHtml = $this->actingAs($user)->postJson('/api/site/publish', $missingHtml);
        $resHtml->assertUnprocessable();
        $resHtml->assertJsonValidationErrors(['rendered_html']);

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_umkm_can_publish_site_and_sanitizer_strips_xss_scripts_and_inline_handlers(): void
    {
        $user = User::factory()->create([
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
        ]);

        $dirtyHtml = <<<'HTML'
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dapur Bu Sari — Masakan Rumahan</title>
<style>:root{--b:#dc2626}.a > .b::before{content:"×"}</style>
<script>alert(1)</script>
</head>
<body data-jam="Setiap hari, 08.00 - 21.00">
<div onclick="evil()">Menu Pilihan 📍</div>
<img src="https://example.com/foto.jpg" onerror="stealCookie()">
<a href="javascript:alert(1)">Pesan</a>
<a href="vbscript:msgbox(1)">Vbs</a>
<a href="  &#106;&#97;vascript:alert(9)">Obfuscated</a>
<iframe src="https://evil.example/frame"></iframe>
<object data="https://evil.example/obj"></object>
<embed src="https://evil.example/emb">
<svg><script>alert(2)</script><animate onbegin="alert(3)" attributeName="href" to="javascript:alert(3)"></animate><set attributeName="onmouseover" to="alert(4)"></set><animateMotion></animateMotion><animateTransform></animateTransform><foreignObject><p>foreign</p></foreignObject></svg>
<script>console.log("liar")</script>
</body>
</html>
HTML;

        $payload = $this->validSitePayload([
            'rendered_html' => $dirtyHtml,
        ]);

        $response = $this->actingAs($user)->postJson('/api/site/publish', $payload);

        $response->assertOk();
        $response->assertJsonPath('site.status', 'published');
        $response->assertJsonPath('site.slug', 'dapur-bu-sari');
        $this->assertStringEndsWith('/dapur-bu-sari', $response->json('public_url'));

        $site = $user->fresh()->site;

        $this->assertNotNull($site);
        $this->assertSame('published', $site->status);
        $this->assertNotNull($site->published_at);
        $this->assertSame('dapur-bu-sari', $site->slug);

        $sanitized = $site->rendered_html;

        $this->assertNotNull($sanitized);
        $this->assertStringNotContainsString('alert(1)', $sanitized);
        $this->assertStringNotContainsString('alert(2)', $sanitized);
        $this->assertStringNotContainsString('alert(3)', $sanitized);
        $this->assertStringNotContainsString('alert(4)', $sanitized);
        $this->assertStringNotContainsString('alert(9)', $sanitized);
        $this->assertStringNotContainsString('stealCookie', $sanitized);
        $this->assertStringNotContainsString('console.log("liar")', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('onbegin', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('vbscript:', $sanitized);
        $this->assertStringNotContainsString('<iframe', $sanitized);
        $this->assertStringNotContainsString('<object', $sanitized);
        $this->assertStringNotContainsString('<embed', $sanitized);
        $this->assertStringNotContainsString('<animate', $sanitized);
        $this->assertStringNotContainsString('<set', $sanitized);
        $this->assertStringNotContainsString('<foreignObject', $sanitized);

        $this->assertStringContainsString('Dapur Bu Sari — Masakan Rumahan', $sanitized);
        $this->assertStringContainsString('Menu Pilihan 📍', $sanitized);
        $this->assertStringContainsString('.a > .b::before{content:"×"}', $sanitized);

        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $sanitized, $scriptMatches);
        $this->assertCount(1, $scriptMatches[1]);
        $this->assertSame(SiteHtmlSanitizer::CLIENT_JS, $scriptMatches[1][0]);

        $expectedHash = 'sha256-qT05fhz7y368jdWBO5JU1DgDNQ/hvvwvv6w8w57G1Ig=';
        $this->assertSame($expectedHash, SiteHtmlSanitizer::cspScriptHash());
        $this->assertStringContainsString("script-src '{$expectedHash}'", SiteHtmlSanitizer::cspHeader());
    }

    public function test_editing_published_site_with_incomplete_fields_saves_draft_without_overwriting_valid_rendered_html(): void
    {
        $user = User::factory()->create(['slug' => 'butik-ayu']);
        $originalHtml = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Butik Ayu</title></head><body><h1>Butik Ayu Valid</h1></body></html>';
        $site = Site::factory()->for($user)->published()->create([
            'slug' => $user->slug,
            'template_id' => 'butik',
            'brand_color' => '#0f172a',
            'content' => $this->validSitePayload()['content'],
            'rendered_html' => $originalHtml,
        ]);

        $incompleteUpdate = $this->validSitePayload([
            'template_id' => 'butik',
            'brand_color' => '#0f172a',
            'content' => [
                'nama' => 'Butik Ayu Terbaru',
                'wa' => '',
            ],
            'rendered_html' => '<!DOCTYPE html><html><body><h1>Draft Tidak Lengkap</h1></body></html>',
        ]);

        $response = $this->actingAs($user)->putJson('/api/site', $incompleteUpdate);

        $response->assertOk();

        $freshSite = $site->fresh();

        $this->assertNotNull($freshSite);
        $this->assertSame('published', $freshSite->status);
        $this->assertSame('Butik Ayu Terbaru', $freshSite->content['nama']);
        $this->assertEmpty($freshSite->content['wa'] ?? '');
        $this->assertSame($originalHtml, $freshSite->rendered_html);

        $completeUpdate = $this->validSitePayload([
            'template_id' => 'butik',
            'brand_color' => '#0f172a',
            'content' => [
                'nama' => 'Butik Ayu Terbaru',
                'wa' => '081299998888',
            ],
            'rendered_html' => '<!DOCTYPE html><html><body><h1>Butik Ayu Diperbarui</h1><script>alert(1)</script></body></html>',
        ]);

        $completeResponse = $this->actingAs($user)->putJson('/api/site', $completeUpdate);

        $completeResponse->assertOk();

        $updatedSite = $site->fresh();

        $this->assertNotNull($updatedSite);
        $this->assertSame('published', $updatedSite->status);
        $this->assertStringContainsString('Butik Ayu Diperbarui', $updatedSite->rendered_html);
        $this->assertStringNotContainsString('alert(1)', $updatedSite->rendered_html);
    }

    public function test_delete_site_removes_record_and_user_media_directory(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['slug' => 'warung-hapus']);
        $otherUser = User::factory()->create(['slug' => 'warung-lain']);

        $site = Site::factory()->for($user)->create(['slug' => $user->slug]);
        $otherSite = Site::factory()->for($otherUser)->create(['slug' => $otherUser->slug]);

        Storage::disk('public')->put("sites/{$user->id}/hero.jpg", 'user-image-data');
        Storage::disk('public')->put("sites/{$otherUser->id}/hero.jpg", 'other-image-data');

        $response = $this->actingAs($user)->deleteJson('/api/site');

        $response->assertOk();
        $response->assertJson(['deleted' => true]);
        $this->assertModelMissing($site);
        $this->assertModelExists($otherSite);
        $this->assertFalse(Storage::disk('public')->exists("sites/{$user->id}"));
        $this->assertTrue(Storage::disk('public')->exists("sites/{$otherUser->id}/hero.jpg"));
    }
}
