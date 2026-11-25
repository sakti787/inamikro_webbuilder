<?php

namespace Database\Seeders;

use App\Http\Requests\Api\SaveSiteRequest;
use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
use App\Models\User;
use App\Services\SiteHtmlSanitizer;
use Illuminate\Database\Seeder;

class DemoUmkmSeeder extends Seeder
{
    /**
     * Seed demo admin, UMKM accounts, default settings, templates, sites, and logs idempotently.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@inamikro.id'],
            [
                'name' => 'Admin Pendamping',
                'business_name' => 'Tim INAmikro',
                'slug' => 'tim-inamikro',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        foreach (SaveSiteRequest::ALLOWED_TEMPLATES as $templateId) {
            TemplateSetting::updateOrCreate(
                ['template_id' => $templateId],
                ['is_active' => true],
            );
        }

        AppSetting::setValue('auto_approve', '0');
        AppSetting::setValue('admin_name', 'Tim INAmikro');
        AppSetting::setValue('admin_whatsapp', '6281234567890');

        $sari = User::updateOrCreate(
            ['email' => 'sari@inamikro.id'],
            [
                'name' => 'Sari Rahmawati',
                'business_name' => 'Dapur Bu Sari',
                'slug' => 'dapur-bu-sari',
                'password' => 'password',
                'role' => 'umkm',
            ],
        );

        $budi = User::updateOrCreate(
            ['email' => 'budi@inamikro.id'],
            [
                'name' => 'Budi Santoso',
                'business_name' => 'Sigap Teknik',
                'slug' => 'sigap-teknik',
                'password' => 'password',
                'role' => 'umkm',
            ],
        );

        $sariContent = [
            'template' => 'warung',
            'variant' => false,
            'brand' => '#dc2626',
            'nama' => 'Dapur Bu Sari',
            'tagline' => 'Masakan rumahan hangat, bersih, dan siap antar setiap hari',
            'kategori' => 'Kuliner rumahan',
            'deskripsi' => 'Menyediakan nasi kotak, lauk rumahan segar, dan paket katering harian untuk keluarga maupun acara kantor.',
            'heroImg' => '',
            'wa' => '6281234567001',
            'ig' => 'dapurbusari.id',
            'alamat' => 'Jl. Melati No. 14, Kelurahan Sukamaju, Bekasi',
            'jam' => 'Setiap hari, 07.00 - 20.00',
            'produk' => [
                [
                    'nama' => 'Paket Nasi Ayam Bakar',
                    'harga' => '25000',
                    'ket' => 'Ayam bakar bumbu kecap, lalapan segar, dan sambal terasi.',
                    'img' => '',
                ],
                [
                    'nama' => 'Rendang Daging Rumahan',
                    'harga' => '45000',
                    'ket' => 'Porsi 250 gram dimasak perlahan dengan santan kental dan rempah.',
                    'img' => '',
                ],
                [
                    'nama' => 'Tumpeng Mini Syukuran',
                    'harga' => '38000',
                    'ket' => 'Nasi kuning lengkap dengan perkedel, orek tempe, dan telur balado.',
                    'img' => '',
                ],
            ],
        ];

        $existingSariSite = Site::query()->where('user_id', $sari->id)->first();

        $sariSite = Site::updateOrCreate(
            ['user_id' => $sari->id],
            [
                'template_id' => 'warung',
                'slug' => $sari->slug,
                'status' => 'published',
                'brand_color' => '#dc2626',
                'content' => $sariContent,
                'rendered_html' => SiteHtmlSanitizer::sanitize($this->buildDemoHtml($sariContent)),
                'admin_note' => 'Profil usaha dan menu lengkap, siap tayang.',
                'revision_note' => null,
                'published_at' => $existingSariSite?->published_at ?? now(),
            ],
        );

        $budiContent = [
            'template' => 'jasa-2',
            'variant' => true,
            'brand' => '#0284c7',
            'nama' => 'Sigap Teknik',
            'tagline' => 'Servis AC, kulkas, dan instalasi listrik panggilan bergaransi',
            'kategori' => 'Jasa perbaikan rumah',
            'deskripsi' => 'Teknisi berpengalaman siap datang ke rumah atau tempat usaha dengan estimasi biaya transparan sebelum pengerjaan.',
            'heroImg' => '',
            'wa' => '6281234567002',
            'ig' => 'sigapteknik',
            'alamat' => 'Jl. Raya Margonda No. 88, Depok',
            'jam' => 'Senin - Sabtu, 08.00 - 18.00',
            'produk' => [
                [
                    'nama' => 'Cuci & Cek Tekanan AC',
                    'harga' => '85000',
                    'ket' => 'Pembersihan unit indoor dan outdoor lengkap dengan cek freon.',
                    'img' => '',
                ],
                [
                    'nama' => 'Perbaikan Kulkas & Freezer',
                    'harga' => '150000',
                    'ket' => 'Diagnosa kerusakan kompresor, termostat, dan penggantian suku cadang.',
                    'img' => '',
                ],
            ],
        ];

        $budiSite = Site::updateOrCreate(
            ['user_id' => $budi->id],
            [
                'template_id' => 'jasa-2',
                'slug' => $budi->slug,
                'status' => 'pending',
                'brand_color' => '#0284c7',
                'content' => $budiContent,
                'rendered_html' => SiteHtmlSanitizer::sanitize($this->buildDemoHtml($budiContent)),
                'admin_note' => null,
                'revision_note' => null,
                'published_at' => null,
            ],
        );

        SiteHistory::firstOrCreate(
            [
                'site_id' => $sariSite->id,
                'title' => 'Diminta publikasi',
            ],
            [
                'actor_id' => $sari->id,
                'description' => 'UMKM menekan tombol Publikasikan.',
            ],
        );

        SiteHistory::firstOrCreate(
            [
                'site_id' => $sariSite->id,
                'title' => 'Disetujui tayang',
            ],
            [
                'actor_id' => $admin->id,
                'description' => 'Admin pendamping menyetujui situs untuk tayang.',
            ],
        );

        SiteHistory::firstOrCreate(
            [
                'site_id' => $budiSite->id,
                'title' => 'Diminta publikasi',
            ],
            [
                'actor_id' => $budi->id,
                'description' => 'UMKM menekan tombol Publikasikan.',
            ],
        );

        AdminLog::firstOrCreate(
            [
                'title' => 'Disetujui tayang',
                'who' => 'Dapur Bu Sari',
                'kind' => 'approve',
            ],
            [
                'actor_id' => $admin->id,
                'description' => 'Admin pendamping menyetujui situs untuk tayang.',
            ],
        );

        AdminLog::firstOrCreate(
            [
                'title' => 'Pengajuan publikasi baru',
                'who' => 'Sigap Teknik',
                'kind' => 'general',
            ],
            [
                'actor_id' => $budi->id,
                'description' => 'UMKM menekan tombol Publikasikan.',
            ],
        );
    }

    /**
     * Render a clean standalone HTML document for seeded demo microsites.
     *
     * @param  array{
     *     brand: string,
     *     nama: string,
     *     tagline: string,
     *     kategori: string,
     *     deskripsi: string,
     *     wa: string,
     *     ig: string,
     *     alamat: string,
     *     jam: string,
     *     produk: array<int, array{nama: string, harga: string, ket: string}>
     * }  $content
     */
    private function buildDemoHtml(array $content): string
    {
        $brand = e($content['brand']);
        $nama = e($content['nama']);
        $tagline = e($content['tagline']);
        $kategori = e($content['kategori']);
        $deskripsi = e($content['deskripsi']);
        $wa = e($content['wa']);
        $ig = e($content['ig']);
        $alamat = e($content['alamat']);
        $jam = e($content['jam']);
        $waText = rawurlencode("Halo {$content['nama']}, saya ingin bertanya mengenai produk/layanan Anda.");

        $productCards = '';
        foreach ($content['produk'] as $product) {
            $productName = e($product['nama']);
            $productPrice = 'Rp'.number_format((int) $product['harga'], 0, ',', '.');
            $productDesc = e($product['ket']);

            $productCards .= <<<HTML
            <article class="card" data-prod data-nama="{$productName}">
              <h3>{$productName}</h3>
              <p class="price">{$productPrice}</p>
              <p class="desc">{$productDesc}</p>
            </article>
            HTML;
        }

        return <<<HTML
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$nama} — {$kategori}</title>
  <style>
    :root { --brand: {$brand}; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, -apple-system, sans-serif; color: #1f2937; background: #f8fafc; line-height: 1.5; }
    .hero { background: var(--brand); color: #fff; padding: 48px 20px; }
    .wrap { max-width: 920px; margin: 0 auto; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; background: rgba(255,255,255,.18); font-size: 13px; margin-bottom: 12px; }
    h1 { margin: 0 0 8px; font-size: 32px; line-height: 1.2; }
    .tagline { margin: 0 0 20px; opacity: .95; font-size: 17px; }
    .cta { display: inline-block; background: #fff; color: var(--brand); font-weight: 700; text-decoration: none; padding: 10px 18px; border-radius: 8px; }
    main { padding: 32px 20px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
    .card h3 { margin: 0 0 6px; font-size: 17px; }
    .price { margin: 0 0 6px; font-weight: 700; color: var(--brand); }
    .desc { margin: 0; color: #4b5563; font-size: 14px; }
    .info { margin-top: 28px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; font-size: 14px; }
  </style>
</head>
<body data-jam="{$jam}" data-share-text="{$nama} — {$tagline}">
  <header class="hero">
    <div class="wrap">
      <span class="badge">{$kategori}</span>
      <h1>{$nama}</h1>
      <p class="tagline">{$tagline}</p>
      <a class="cta" href="https://wa.me/{$wa}?text={$waText}" target="_blank" rel="noopener">Hubungi via WhatsApp</a>
    </div>
  </header>
  <main class="wrap">
    <section>
      <h2>Tentang Kami</h2>
      <p>{$deskripsi}</p>
    </section>
    <section>
      <h2>Produk &amp; Layanan</h2>
      <div class="grid">{$productCards}</div>
    </section>
    <section class="info">
      <p><strong>Jam Operasional:</strong> {$jam}</p>
      <p><strong>Alamat:</strong> {$alamat}</p>
      <p><strong>Instagram:</strong> @{$ig}</p>
    </section>
  </main>
</body>
</html>
HTML;
    }
}
