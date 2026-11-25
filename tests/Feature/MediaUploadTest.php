<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private const string MINIMAL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const string MINIMAL_JPEG_BASE64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    private function fakeImage(string $name = 'hero.jpg'): File
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $binary = base64_decode($extension === 'png' ? self::MINIMAL_PNG_BASE64 : self::MINIMAL_JPEG_BASE64, true);

        return UploadedFile::fake()->createWithContent($name, (string) $binary);
    }

    public function test_guest_cannot_upload_or_delete_media(): void
    {
        Storage::fake('public');

        $this->postJson('/api/media', [
            'image' => $this->fakeImage('hero.jpg'),
        ])->assertUnauthorized();

        $this->deleteJson('/api/media', [
            'url' => '/storage/sites/1/hero.jpg',
        ])->assertUnauthorized();
    }

    public function test_umkm_can_upload_valid_image_to_own_directory(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['slug' => 'warung-upload']);
        $file = $this->fakeImage('hero.jpg');

        $response = $this->actingAs($user)->postJson('/api/media', [
            'image' => $file,
        ]);

        $response->assertCreated();

        $url = $response->json('url');

        $this->assertIsString($url);
        $this->assertStringStartsWith("/storage/sites/{$user->id}/", $url);

        $relativePath = substr($url, strlen('/storage/'));

        Storage::disk('public')->assertExists($relativePath);
    }

    public function test_upload_rejects_non_image_or_oversized_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['slug' => 'warung-validasi']);

        $missingResponse = $this->actingAs($user)->postJson('/api/media', []);
        $missingResponse->assertUnprocessable();
        $missingResponse->assertJsonValidationErrors('image');
        $this->assertSame('File gambar wajib dipilih.', $missingResponse->json('errors.image.0'));

        $nonImage = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $nonImageResponse = $this->actingAs($user)->postJson('/api/media', [
            'image' => $nonImage,
        ]);
        $nonImageResponse->assertUnprocessable();
        $nonImageResponse->assertJsonValidationErrors('image');

        $oversizedImage = $this->fakeImage('besar.jpg')->size(5121);
        $oversizedResponse = $this->actingAs($user)->postJson('/api/media', [
            'image' => $oversizedImage,
        ]);
        $oversizedResponse->assertUnprocessable();
        $oversizedResponse->assertJsonValidationErrors('image');
        $this->assertSame('Ukuran gambar maksimal 5 MB.', $oversizedResponse->json('errors.image.0'));
    }

    public function test_umkm_can_delete_own_uploaded_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['slug' => 'warung-hapus-media']);
        $uploadResponse = $this->actingAs($user)->postJson('/api/media', [
            'image' => $this->fakeImage('produk.png'),
        ]);
        $uploadResponse->assertCreated();

        $url = (string) $uploadResponse->json('url');
        $relativePath = substr($url, strlen('/storage/'));

        Storage::disk('public')->assertExists($relativePath);

        $deleteResponse = $this->actingAs($user)->deleteJson('/api/media', [
            'url' => $url,
        ]);

        $deleteResponse->assertOk();
        $deleteResponse->assertExactJson(['deleted' => true]);
        Storage::disk('public')->assertMissing($relativePath);
    }

    #[TestWith(['/storage/sites/%OTHER%/secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/../%OTHER%/secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/..\\%OTHER%\\secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/%2e%2e/%OTHER%/secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/%2E%2E%5C%OTHER%/secret.jpg'])]
    #[TestWith(['/storage/other/secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/.'])]
    #[TestWith(['/storage/sites/%USER%/..'])]
    #[TestWith(['/storage/sites/%USER%/sub/secret.jpg'])]
    #[TestWith(['/storage/sites/%USER%/bad name.jpg'])]
    #[TestWith(['/storage/sites/%USER%/bad?name.jpg'])]
    public function test_umkm_cannot_delete_another_users_media_or_use_path_traversal(string $urlTemplate): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['slug' => 'umkm-satu']);
        $otherUser = User::factory()->create(['slug' => 'umkm-dua']);

        Storage::disk('public')->put("sites/{$otherUser->id}/secret.jpg", 'secret-image-bytes');

        $maliciousUrl = str_replace(
            ['%USER%', '%OTHER%'],
            [(string) $user->id, (string) $otherUser->id],
            $urlTemplate,
        );

        $response = $this->actingAs($user)->deleteJson('/api/media', [
            'url' => $maliciousUrl,
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Akses ditolak.');
        Storage::disk('public')->assertExists("sites/{$otherUser->id}/secret.jpg");
    }
}
