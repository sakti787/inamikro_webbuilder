<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure role column on users if not already present
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('umkm')->after('password');
            });
        }

        // 2. business_categories
        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. businesses
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('business_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->string('logo')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->json('operating_hours')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
        });

        // 4. templates
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('thumbnail')->nullable();
            $table->string('view_path');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. websites
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('status', 30)->default('draft'); // draft, published, unpublished
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('business_id');
            $table->index('template_id');
        });

        // 6. themes
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->string('primary_color', 20)->default('#3B82F6');
            $table->string('secondary_color', 20)->default('#1E40AF');
            $table->string('background_color', 20)->default('#FFFFFF');
            $table->string('text_color', 20)->default('#1F2937');
            $table->string('font_family', 100)->default('Inter');
            $table->string('button_style', 50)->default('rounded');
            $table->string('border_radius', 20)->default('8px');
            $table->timestamps();

            $table->index('website_id');
        });

        // 7. banners
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('image');
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('button_text', 100)->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('business_id');
        });

        // 8. product_categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->timestamps();

            $table->index('business_id');
            $table->index(['business_id', 'slug']);
        });

        // 9. products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('discount_price', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('label', 50)->nullable();
            $table->json('variant_options')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('business_id');
            $table->index('category_id');
            $table->index(['business_id', 'slug']);
        });

        // 10. product_images
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });

        // 11. business_galleries
        Schema::create('business_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('image');
            $table->string('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('business_id');
        });

        // 12. testimonials
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('customer_name');
            $table->text('content');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('business_id');
        });

        // 13. website_visits
        Schema::create('website_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->string('visitor_hash', 64)->nullable();
            $table->text('referrer')->nullable();
            $table->timestamps();

            $table->index('website_id');
            $table->index('visited_at');
        });

        // 14. whatsapp_clicks
        Schema::create('whatsapp_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamp('clicked_at');
            $table->string('source', 50)->default('landing_page');
            $table->timestamps();

            $table->index('website_id');
            $table->index('product_id');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_clicks');
        Schema::dropIfExists('website_visits');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('business_galleries');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('websites');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('business_categories');

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
