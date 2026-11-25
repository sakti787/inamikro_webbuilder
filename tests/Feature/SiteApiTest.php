<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
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
        AppSetting::setValue('auto_approve', '1');

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

    public function test_publish_creates_pending_site_when_auto_approve_is_disabled_and_stays_404_publicly(): void
    {
        AppSetting::setValue('auto_approve', '0');

        $user = User::factory()->create([
            'business_name' => 'Warung Menunggu',
            'slug' => 'warung-menunggu',
        ]);
        $existingSite = Site::factory()->for($user)->create([
            'template_id' => 'warung',
            'slug' => $user->slug,
            'status' => 'draft',
            'revision_note' => 'Tolong perjelas jam buka toko.',
        ]);
        $payload = $this->validSitePayload([
            'content' => [
                'nama' => 'Warung Menunggu',
            ],
            'rendered_html' => '<!doctype html><html><body><h1>Warung Menunggu</h1><script>alert(1)</script></body></html>',
        ]);

        $response = $this->actingAs($user)->postJson('/api/site/publish', $payload);

        $response->assertOk();
        $response->assertJsonPath('site.status', 'pending');
        $response->assertJsonPath('site.revision_note', null);

        $freshSite = $existingSite->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('pending', $freshSite->status);
        $this->assertNull($freshSite->published_at);
        $this->assertNull($freshSite->revision_note);
        $this->assertStringContainsString('Warung Menunggu', (string) $freshSite->rendered_html);
        $this->assertStringNotContainsString('alert(1)', (string) $freshSite->rendered_html);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $freshSite->id,
            'actor_id' => $user->id,
            'title' => 'Diminta publikasi',
            'description' => 'UMKM menekan tombol Publikasikan.',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'actor_id' => $user->id,
            'title' => 'Pengajuan publikasi baru',
            'who' => 'Warung Menunggu',
            'kind' => 'general',
        ]);

        $this->get('/warung-menunggu')->assertNotFound();
    }

    public function test_publish_immediately_publishes_when_auto_approve_is_enabled(): void
    {
        AppSetting::setValue('auto_approve', '1');

        $user = User::factory()->create([
            'business_name' => 'Warung Otomatis',
            'slug' => 'warung-otomatis',
        ]);
        $existingSite = Site::factory()->for($user)->create([
            'template_id' => 'warung',
            'slug' => $user->slug,
            'status' => 'draft',
            'revision_note' => 'Catatan revisi sebelumnya.',
        ]);
        $payload = $this->validSitePayload([
            'content' => [
                'nama' => 'Warung Otomatis',
            ],
            'rendered_html' => '<!doctype html><html><body><h1>Warung Otomatis</h1></body></html>',
        ]);

        $response = $this->actingAs($user)->postJson('/api/site/publish', $payload);

        $response->assertOk();
        $response->assertJsonPath('site.status', 'published');
        $response->assertJsonPath('site.revision_note', null);

        $freshSite = $existingSite->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('published', $freshSite->status);
        $this->assertNotNull($freshSite->published_at);
        $this->assertNull($freshSite->revision_note);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $freshSite->id,
            'actor_id' => $user->id,
            'title' => 'Disetujui otomatis',
            'description' => 'Auto setujui aktif: 4 dari 4 syarat terpenuhi.',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'actor_id' => $user->id,
            'title' => 'Disetujui otomatis',
            'who' => 'Warung Otomatis',
            'kind' => 'auto',
        ]);

        $this->get('/warung-otomatis')
            ->assertOk()
            ->assertSee('Warung Otomatis');
    }

    public function test_republishing_already_published_site_keeps_it_published_even_when_auto_approve_is_disabled(): void
    {
        AppSetting::setValue('auto_approve', '0');

        $originalPublishedAt = now()->subDays(2)->startOfSecond();
        $user = User::factory()->create([
            'business_name' => 'Warung Sudah Tayang',
            'slug' => 'warung-sudah-tayang',
        ]);
        $site = Site::factory()->for($user)->published()->create([
            'template_id' => 'warung',
            'slug' => $user->slug,
            'published_at' => $originalPublishedAt,
            'revision_note' => 'Catatan lama yang harus bersih.',
            'rendered_html' => '<!doctype html><html><body><h1>Versi Lama</h1></body></html>',
        ]);

        $payload = $this->validSitePayload([
            'content' => [
                'nama' => 'Warung Sudah Tayang Baru',
            ],
            'rendered_html' => '<!doctype html><html><body><h1>Versi Baru Tayang</h1><script>alert(1)</script></body></html>',
        ]);

        $response = $this->actingAs($user)->postJson('/api/site/publish', $payload);

        $response->assertOk();
        $response->assertJsonPath('site.status', 'published');
        $response->assertJsonPath('site.revision_note', null);

        $freshSite = $site->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('published', $freshSite->status);
        $this->assertNull($freshSite->revision_note);
        $this->assertTrue($freshSite->published_at?->equalTo($originalPublishedAt));
        $this->assertStringContainsString('Versi Baru Tayang', (string) $freshSite->rendered_html);
        $this->assertStringNotContainsString('alert(1)', (string) $freshSite->rendered_html);
        $this->assertSame(0, SiteHistory::count());
        $this->assertSame(0, AdminLog::count());

        $this->get('/warung-sudah-tayang')
            ->assertOk()
            ->assertSee('Versi Baru Tayang');
    }

    public function test_editing_pending_site_updates_content_in_place_and_keeps_status_pending(): void
    {
        $user = User::factory()->create([
            'business_name' => 'Toko Antrean',
            'slug' => 'toko-antrean',
        ]);
        $site = Site::factory()->for($user)->create([
            'template_id' => 'toko',
            'slug' => $user->slug,
            'status' => 'pending',
            'brand_color' => '#0d9488',
            'content' => $this->validSitePayload(['template_id' => 'toko', 'content' => ['nama' => 'Toko Antrean Lama']])['content'],
            'rendered_html' => '<!doctype html><html><body><h1>Toko Antrean Lama</h1></body></html>',
            'revision_note' => 'Catatan revisi sebelumnya',
        ]);

        $showResponse = $this->actingAs($user)->getJson('/api/site');
        $showResponse->assertOk();
        $showResponse->assertJsonPath('site.revision_note', 'Catatan revisi sebelumnya');

        $updatePayload = $this->validSitePayload([
            'template_id' => 'toko',
            'brand_color' => '#2563eb',
            'content' => [
                'nama' => 'Toko Antrean Diperbarui',
                'wa' => '',
            ],
            'rendered_html' => '<!doctype html><html><body><h1>Toko Antrean Diperbarui</h1><script>alert(9)</script></body></html>',
        ]);

        $upsertResponse = $this->actingAs($user)->putJson('/api/site', $updatePayload);

        $upsertResponse->assertOk();
        $upsertResponse->assertJsonPath('site.status', 'pending');
        $upsertResponse->assertJsonPath('site.brand_color', '#2563eb');
        $upsertResponse->assertJsonPath('site.content.nama', 'Toko Antrean Diperbarui');
        $upsertResponse->assertJsonPath('site.revision_note', 'Catatan revisi sebelumnya');

        $freshSite = $site->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('pending', $freshSite->status);
        $this->assertSame('#2563eb', $freshSite->brand_color);
        $this->assertSame('Toko Antrean Diperbarui', $freshSite->content['nama']);
        $this->assertStringContainsString('Toko Antrean Diperbarui', (string) $freshSite->rendered_html);
        $this->assertStringNotContainsString('alert(9)', (string) $freshSite->rendered_html);

        $this->get('/toko-antrean')->assertNotFound();
    }

    public function test_disabled_template_blocks_new_selection_but_allows_existing_site_edits(): void
    {
        AppSetting::setValue('auto_approve', '0');

        TemplateSetting::create([
            'template_id' => 'toko-2',
            'is_active' => false,
        ]);
        TemplateSetting::create([
            'template_id' => 'warung-2',
            'is_active' => false,
        ]);

        $newUser = User::factory()->create(['slug' => 'umkm-baru']);
        $existingUser = User::factory()->create(['slug' => 'umkm-lama']);
        $existingSite = Site::factory()->for($existingUser)->create([
            'template_id' => 'toko-2',
            'slug' => $existingUser->slug,
            'status' => 'draft',
        ]);

        $blockedDraftResponse = $this->actingAs($newUser)->putJson('/api/site', $this->validSitePayload([
            'template_id' => 'toko-2',
        ]));
        $blockedDraftResponse->assertUnprocessable();
        $blockedDraftResponse->assertJsonValidationErrors(['template_id']);
        $this->assertSame(
            'Template yang dipilih sedang dinonaktifkan oleh admin.',
            $blockedDraftResponse->json('errors.template_id.0'),
        );
        $this->assertNull($newUser->fresh()->site);

        $blockedPublishResponse = $this->actingAs($newUser)->postJson('/api/site/publish', $this->validSitePayload([
            'template_id' => 'toko-2',
        ]));
        $blockedPublishResponse->assertUnprocessable();
        $blockedPublishResponse->assertJsonValidationErrors(['template_id']);
        $this->assertSame(
            'Template yang dipilih sedang dinonaktifkan oleh admin.',
            $blockedPublishResponse->json('errors.template_id.0'),
        );

        $allowedDraftResponse = $this->actingAs($existingUser)->putJson('/api/site', $this->validSitePayload([
            'template_id' => 'toko-2',
            'content' => [
                'nama' => 'Toko Lama Tetap Bisa Edit',
            ],
        ]));
        $allowedDraftResponse->assertOk();
        $allowedDraftResponse->assertJsonPath('site.template_id', 'toko-2');
        $allowedDraftResponse->assertJsonPath('site.content.nama', 'Toko Lama Tetap Bisa Edit');

        $allowedPublishResponse = $this->actingAs($existingUser)->postJson('/api/site/publish', $this->validSitePayload([
            'template_id' => 'toko-2',
            'content' => [
                'nama' => 'Toko Lama Tetap Bisa Publish',
            ],
        ]));
        $allowedPublishResponse->assertOk();
        $allowedPublishResponse->assertJsonPath('site.template_id', 'toko-2');
        $allowedPublishResponse->assertJsonPath('site.status', 'pending');

        $switchBlockedResponse = $this->actingAs($existingUser)->putJson('/api/site', $this->validSitePayload([
            'template_id' => 'warung-2',
        ]));
        $switchBlockedResponse->assertUnprocessable();
        $switchBlockedResponse->assertJsonValidationErrors(['template_id']);
        $this->assertSame(
            'Template yang dipilih sedang dinonaktifkan oleh admin.',
            $switchBlockedResponse->json('errors.template_id.0'),
        );
        $this->assertSame('toko-2', $existingSite->fresh()?->template_id);
    }

    public function test_site_api_hides_internal_admin_note_across_show_upsert_and_publish(): void
    {
        AppSetting::setValue('auto_approve', '0');

        $user = User::factory()->create([
            'business_name' => 'Usaha Terawasi',
            'slug' => 'usaha-terawasi',
        ]);
        $site = Site::factory()->for($user)->create([
            'template_id' => 'warung',
            'slug' => $user->slug,
            'status' => 'draft',
            'admin_note' => 'Catatan rahasia internal pendamping.',
            'revision_note' => 'Mohon lengkapi jam buka.',
        ]);

        $showResponse = $this->actingAs($user)->getJson('/api/site');
        $showResponse->assertOk();
        $showResponse->assertJsonPath('site.revision_note', 'Mohon lengkapi jam buka.');
        $showResponse->assertJsonMissingPath('site.admin_note');
        $showResponse->assertDontSee('Catatan rahasia internal pendamping.');

        $upsertResponse = $this->actingAs($user)->putJson('/api/site', $this->validSitePayload());
        $upsertResponse->assertOk();
        $upsertResponse->assertJsonMissingPath('site.admin_note');
        $upsertResponse->assertDontSee('Catatan rahasia internal pendamping.');

        $publishResponse = $this->actingAs($user)->postJson('/api/site/publish', $this->validSitePayload());
        $publishResponse->assertOk();
        $publishResponse->assertJsonMissingPath('site.admin_note');
        $publishResponse->assertDontSee('Catatan rahasia internal pendamping.');

        $this->assertSame('Catatan rahasia internal pendamping.', $site->fresh()?->admin_note);
    }

    public function test_publish_truncates_long_business_name_for_admin_log_who_column(): void
    {
        AppSetting::setValue('auto_approve', '0');

        $user = User::factory()->create([
            'business_name' => 'Usaha Panjang',
            'slug' => 'usaha-panjang',
        ]);
        $longBusinessName = 'Warung '.str_repeat('é', 193);
        $this->assertSame(200, mb_strlen($longBusinessName));

        $response = $this->actingAs($user)->postJson('/api/site/publish', $this->validSitePayload([
            'content' => [
                'nama' => $longBusinessName,
            ],
        ]));

        $response->assertOk();
        $response->assertJsonPath('site.status', 'pending');

        $log = AdminLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertLessThanOrEqual(150, mb_strlen($log->who));
        $this->assertSame(mb_substr($longBusinessName, 0, 150), $log->who);
    }
}
