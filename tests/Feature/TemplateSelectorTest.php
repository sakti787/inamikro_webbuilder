<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\TemplateSelector;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateSelectorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Website $website;

    private Theme $theme;

    private Template $templateMinimalist;

    private Template $templateFood;

    private Template $templateModern;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateMinimalist = Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist.index',
            'thumbnail' => 'templates/minimalist.png',
            'description' => 'Template bersih dan elegan untuk produk fashion, aksesoris, dan kerajinan tangan.',
            'is_active' => true,
        ]);

        $this->templateFood = Template::create([
            'name' => 'Food & Beverage',
            'slug' => 'food',
            'view_path' => 'templates.food.index',
            'thumbnail' => 'templates/food.png',
            'description' => 'Template menggugah selera untuk resto, kafe, bakery, dan usaha kuliner.',
            'is_active' => true,
        ]);

        $this->templateModern = Template::create([
            'name' => 'Modern Business',
            'slug' => 'modern',
            'view_path' => 'templates.modern.index',
            'thumbnail' => 'templates/modern.png',
            'description' => 'Template modern dan profesional untuk skincare, herbal, ritel, dan aneka jasa.',
            'is_active' => true,
        ]);

        $category = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);

        $this->user = User::factory()->create(['role' => 'umkm']);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'name' => 'Dapur Nusantara',
            'slug' => 'dapur-nusantara',
            'whatsapp' => '081234567890',
        ]);

        $this->website = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $this->templateMinimalist->id,
            'slug' => 'dapur-nusantara',
            'status' => 'draft',
        ]);

        $this->theme = Theme::create([
            'website_id' => $this->website->id,
            'primary_color' => '#0F172A',
            'secondary_color' => '#334155',
            'font_family' => 'Inter',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_template_selector(): void
    {
        $this->get('/dashboard/template')->assertRedirect('/login');
    }

    public function test_non_umkm_cannot_access_template_selector(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard/template')
            ->assertForbidden();
    }

    public function test_umkm_can_view_template_selector_with_all_active_templates(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard/template')
            ->assertOk()
            ->assertSee('Pilihan Tampilan')
            ->assertSee('Minimalist Store')
            ->assertSee('Food & Beverage')
            ->assertSee('Modern Business')
            ->assertSee('Fashion, Kriya, Aksesoris, & Lifestyle')
            ->assertSee('Kuliner, Makanan, Minuman, Kafe, Katering, & Frozen Food')
            ->assertSee('Toko Kelontong, Bengkel, Laundry, Ritel, & Jasa Umum')
            ->assertSee('Sedang Digunakan')
            ->assertSee('Draft / Belum Terbit')
            ->assertSee('Lihat Toko Online')
            ->assertSee('Terbitkan Website');
    }

    public function test_umkm_can_select_food_template_and_curated_presets_are_applied(): void
    {
        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', $this->templateFood->id)
            ->assertHasNoErrors()
            ->assertSee("Tampilan toko berhasil diubah ke {$this->templateFood->name}!");

        $this->assertEquals($this->templateFood->id, $this->website->fresh()->template_id);

        $theme = $this->website->fresh()->theme;
        $this->assertEquals('#DC2626', $theme->primary_color);
        $this->assertEquals('#B91C1C', $theme->secondary_color);
        $this->assertEquals('Poppins', $theme->font_family);
    }

    public function test_umkm_can_select_modern_template_and_curated_presets_are_applied(): void
    {
        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', $this->templateModern->id)
            ->assertHasNoErrors()
            ->assertSee("Tampilan toko berhasil diubah ke {$this->templateModern->name}!");

        $this->assertEquals($this->templateModern->id, $this->website->fresh()->template_id);

        $theme = $this->website->fresh()->theme;
        $this->assertEquals('#2563EB', $theme->primary_color);
        $this->assertEquals('#1D4ED8', $theme->secondary_color);
        $this->assertEquals('Plus Jakarta Sans', $theme->font_family);
    }

    public function test_umkm_can_select_minimalist_template_and_curated_presets_are_applied(): void
    {
        // First set to food
        $this->website->update(['template_id' => $this->templateFood->id]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', $this->templateMinimalist->id)
            ->assertHasNoErrors()
            ->assertSee("Tampilan toko berhasil diubah ke {$this->templateMinimalist->name}!");

        $this->assertEquals($this->templateMinimalist->id, $this->website->fresh()->template_id);

        $theme = $this->website->fresh()->theme;
        $this->assertEquals('#0F172A', $theme->primary_color);
        $this->assertEquals('#334155', $theme->secondary_color);
        $this->assertEquals('Inter', $theme->font_family);
    }

    public function test_selecting_non_existent_template_fails(): void
    {
        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', 99999)
            ->assertStatus(404);
    }

    public function test_tenant_isolation_template_change_only_affects_own_website(): void
    {
        $otherUser = User::factory()->create(['role' => 'umkm']);
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'category_id' => $this->business->category_id,
            'name' => 'Usaha Tetangga',
            'slug' => 'usaha-tetangga',
            'whatsapp' => '081298765432',
        ]);
        $otherWebsite = Website::create([
            'business_id' => $otherBusiness->id,
            'template_id' => $this->templateMinimalist->id,
            'slug' => 'usaha-tetangga',
            'status' => 'draft',
        ]);
        $otherTheme = Theme::create([
            'website_id' => $otherWebsite->id,
            'primary_color' => '#0F172A',
            'secondary_color' => '#334155',
            'font_family' => 'Inter',
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', $this->templateFood->id);

        $this->assertEquals($this->templateFood->id, $this->website->fresh()->template_id);
        $this->assertEquals($this->templateMinimalist->id, $otherWebsite->fresh()->template_id);
        $this->assertEquals('#0F172A', $otherTheme->fresh()->primary_color);
    }

    public function test_umkm_cannot_publish_when_validation_fails_such_as_zero_products(): void
    {
        $this->assertEquals('draft', $this->website->status);
        $this->assertNull($this->website->published_at);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('togglePublish')
            ->assertSee('Minimal harus memiliki 1 produk aktif sebelum publikasi.')
            ->assertSee('Belum Siap Diterbitkan');

        $this->assertEquals('draft', $this->website->fresh()->status);
        $this->assertNull($this->website->fresh()->published_at);
    }

    public function test_umkm_can_publish_when_validation_passes(): void
    {
        // Add an active product
        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Rendang Sapi',
            'slug' => 'rendang-sapi',
            'price' => 50000,
            'is_available' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('togglePublish')
            ->assertSee('Selamat! Website toko online Anda berhasil diterbitkan.')
            ->assertSee('Website Aktif / Terbit');

        $this->assertEquals('published', $this->website->fresh()->status);
        $this->assertNotNull($this->website->fresh()->published_at);
    }

    public function test_umkm_can_unpublish_back_to_draft(): void
    {
        $this->website->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class)
            ->call('togglePublish')
            ->assertSee('Website toko berhasil diubah menjadi Draft.')
            ->assertSee('Draft / Belum Terbit');

        $this->assertEquals('draft', $this->website->fresh()->status);
        $this->assertNull($this->website->fresh()->published_at);
    }
}
