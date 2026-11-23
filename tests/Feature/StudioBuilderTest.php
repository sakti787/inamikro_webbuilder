<?php

namespace Tests\Feature;

use App\Livewire\Builder\Studio;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudioBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Website $website;

    private Theme $theme;

    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist.index',
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
            'name' => 'Bakso Sedap Malam',
            'slug' => 'bakso-sedap-malam',
            'whatsapp' => '081234567890',
        ]);

        $this->website = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $this->template->id,
            'slug' => 'bakso-sedap-malam',
            'status' => 'draft',
        ]);

        $this->theme = Theme::create([
            'website_id' => $this->website->id,
            'primary_color' => '#2563EB',
            'secondary_color' => '#1E40AF',
            'background_color' => '#FFFFFF',
            'text_color' => '#1F2937',
            'font_family' => 'Inter',
            'button_style' => 'rounded',
            'border_radius' => '8px',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_builder_and_preview(): void
    {
        $this->get('/dashboard/builder')->assertRedirect('/login');
        $this->get("/builder/preview/{$this->website->id}")->assertRedirect('/login');
    }

    public function test_umkm_can_access_builder_studio_with_website_and_theme_loaded(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard/builder');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Studio::class);
        $response->assertSee('Bakso Sedap Malam');
        $response->assertSee('draft');

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->assertSet('templateId', $this->template->id)
            ->assertSet('primaryColor', '#2563EB')
            ->assertSet('secondaryColor', '#1E40AF')
            ->assertSet('fontFamily', 'Inter')
            ->assertSet('deviceView', 'desktop')
            ->assertSee('Bakso Sedap Malam')
            ->assertSee('Simpan Tema')
            ->assertSee('Terbitkan Website');
    }

    public function test_umkm_can_update_theme_colors_and_fonts(): void
    {
        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->set('primaryColor', '#EF4444')
            ->set('secondaryColor', '#B91C1C')
            ->set('fontFamily', 'Poppins')
            ->call('saveTheme')
            ->assertHasNoErrors()
            ->assertDispatched('theme-saved')
            ->assertSee('Tema website berhasil disimpan.');

        $this->assertDatabaseHas('themes', [
            'website_id' => $this->website->id,
            'primary_color' => '#EF4444',
            'secondary_color' => '#B91C1C',
            'font_family' => 'Poppins',
        ]);
    }

    public function test_theme_validation_rejects_invalid_hex_colors(): void
    {
        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->set('primaryColor', 'invalid-color')
            ->call('saveTheme')
            ->assertHasErrors(['primaryColor']);

        $this->assertDatabaseHas('themes', [
            'website_id' => $this->website->id,
            'primary_color' => '#2563EB',
        ]);
    }

    public function test_umkm_can_change_template_and_persist_to_website(): void
    {
        $newTemplate = Template::create([
            'name' => 'Kuliner Nusantara',
            'slug' => 'culinary',
            'view_path' => 'templates.culinary.index',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->call('changeTemplate', $newTemplate->id)
            ->assertSet('templateId', $newTemplate->id)
            ->assertDispatched('template-changed')
            ->assertSee('Template berhasil diubah');

        $this->assertEquals($newTemplate->id, $this->website->fresh()->template_id);
    }

    public function test_umkm_cannot_change_to_inactive_template(): void
    {
        $inactiveTemplate = Template::create([
            'name' => 'Template Lawas',
            'slug' => 'lawas',
            'view_path' => 'templates.lawas.index',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->call('changeTemplate', $inactiveTemplate->id)
            ->assertStatus(404);

        $this->assertEquals($this->template->id, $this->website->fresh()->template_id);
    }

    public function test_umkm_can_toggle_website_status_between_draft_and_published(): void
    {
        $this->assertEquals('draft', $this->website->status);

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->call('togglePublish')
            ->assertDispatched('status-toggled')
            ->assertSee('Website berhasil diterbitkan');

        $this->assertEquals('published', $this->website->fresh()->status);
        $this->assertNotNull($this->website->fresh()->published_at);

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->call('togglePublish')
            ->assertDispatched('status-toggled')
            ->assertSee('Website berhasil diubah menjadi Draft');

        $this->assertEquals('draft', $this->website->fresh()->status);
    }

    public function test_umkm_can_switch_device_views(): void
    {
        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->call('setDeviceView', 'tablet')
            ->assertSet('deviceView', 'tablet')
            ->call('setDeviceView', 'mobile')
            ->assertSet('deviceView', 'mobile')
            ->call('setDeviceView', 'desktop')
            ->assertSet('deviceView', 'desktop')
            ->call('setDeviceView', 'unsupported')
            ->assertSet('deviceView', 'desktop');
    }

    public function test_umkm_can_view_internal_preview_iframe_route(): void
    {
        $response = $this->actingAs($this->user)->get(route('builder.preview', $this->website));

        $response->assertStatus(200);
        $response->assertSee('--color-primary: #2563EB', false);
        $response->assertSee('--color-secondary: #1E40AF', false);
        $response->assertSee('--font-family: \'Inter\', sans-serif', false);
        $response->assertSee('window.addEventListener(\'message\'', false);
        $response->assertSee('Bakso Sedap Malam');
    }

    public function test_umkm_cannot_access_another_umkms_preview(): void
    {
        $otherUser = User::factory()->create(['role' => 'umkm']);
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'name' => 'Toko Sebelah',
            'slug' => 'toko-sebelah',
            'whatsapp' => '089876543210',
        ]);
        $otherWebsite = Website::create([
            'business_id' => $otherBusiness->id,
            'template_id' => $this->template->id,
            'slug' => 'toko-sebelah',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('builder.preview', $otherWebsite));

        $response->assertStatus(403);
    }

    public function test_builder_studio_always_isolates_authenticated_umkms_website(): void
    {
        $otherUser = User::factory()->create(['role' => 'umkm']);
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'name' => 'Usaha Lain',
            'slug' => 'usaha-lain',
            'whatsapp' => '089999999999',
        ]);
        $otherWebsite = Website::create([
            'business_id' => $otherBusiness->id,
            'template_id' => $this->template->id,
            'slug' => 'usaha-lain',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->user)
            ->test(Studio::class)
            ->assertSee('Bakso Sedap Malam')
            ->assertDontSee('Usaha Lain')
            ->assertSet('website.id', $this->website->id);

        Livewire::actingAs($otherUser)
            ->test(Studio::class)
            ->assertSee('Usaha Lain')
            ->assertDontSee('Bakso Sedap Malam')
            ->assertSet('website.id', $otherWebsite->id);
    }
}
