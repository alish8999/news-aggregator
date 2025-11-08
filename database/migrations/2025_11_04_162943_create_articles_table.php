<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('source_id')->constrained('sources')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('author_id')->nullable()->constrained('authors')->onDelete('set null');

            // Article content
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('article_url', 512)->unique();
            $table->string('image_url', 512)->nullable();
            $table->timestamp('published_at');

            $table->timestamps();

            // ===== OPTIMIZED INDEXES =====

            // 1. Individual indexes for filtering
            $table->index('source_id', 'idx_articles_source');
            $table->index('category_id', 'idx_articles_category');
            $table->index('author_id', 'idx_articles_author');

            // 2. Composite indexes for common query patterns
            $table->index(['source_id', 'published_at'], 'idx_articles_source_published');
            $table->index(['category_id', 'published_at'], 'idx_articles_category_published');
            $table->index(['author_id', 'published_at'], 'idx_articles_author_published');

            // 3. Composite index for date range queries and cursor pagination
            $table->index(['published_at', 'id'], 'idx_articles_published_id');
        });

        // 4. Add descending index for published_at (PostgreSQL)
        // This is CRUCIAL for feed performance with ORDER BY published_at DESC
        DB::statement('CREATE INDEX idx_articles_published_desc ON articles (published_at DESC, id DESC)');

        // 5. Add tsvector column for full-text search
        DB::statement('ALTER TABLE articles ADD COLUMN search_vector tsvector');

        // 6. Create GIN index for full-text search
        DB::statement('CREATE INDEX idx_articles_search ON articles USING GIN (search_vector)');

        // 7. Create trigger to automatically update search_vector
        DB::statement("
            CREATE OR REPLACE FUNCTION articles_search_trigger() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english', COALESCE(NEW.title, '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(NEW.description, '')), 'B');
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql;
        ");

        DB::statement('
            CREATE TRIGGER articles_search_update BEFORE INSERT OR UPDATE
            ON articles FOR EACH ROW EXECUTE FUNCTION articles_search_trigger();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS articles_search_update ON articles');
        DB::statement('DROP FUNCTION IF EXISTS articles_search_trigger()');
        Schema::dropIfExists('articles');
    }
};
