<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * blog_slug_redirects: every previous slug of a post, so /blog/{old-slug} 301-redirects to the
 * current URL instead of 404-ing (shared and indexed links keep working after a slug change).
 *
 * blog_comments: moderated visitor comments (pending → approved/spam) with one level of admin
 * replies via parent_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // blogs was created outside the migrations, so match whatever integer type its id uses.
        $blogIdIsBig = str_contains(strtolower($this->blogIdType()), 'bigint');

        Schema::create('blog_slug_redirects', function (Blueprint $table) use ($blogIdIsBig) {
            $table->id();
            $blogIdIsBig ? $table->unsignedBigInteger('blog_id') : $table->unsignedInteger('blog_id');
            $table->string('old_slug', 191)->unique();
            $table->timestamps();

            $table->foreign('blog_id')->references('id')->on('blogs')->cascadeOnDelete();
        });

        Schema::create('blog_comments', function (Blueprint $table) use ($blogIdIsBig) {
            $table->id();
            $blogIdIsBig ? $table->unsignedBigInteger('blog_id') : $table->unsignedInteger('blog_id');
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // admin who replied
            $table->string('name', 100);
            $table->string('email', 191);
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_admin')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('blog_id')->references('id')->on('blogs')->cascadeOnDelete();
            $table->index(['blog_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_slug_redirects');
    }

    private function blogIdType(): string
    {
        foreach (Schema::getColumns('blogs') as $column) {
            if ($column['name'] === 'id') {
                return (string) $column['type'];
            }
        }

        return 'int unsigned';
    }
};
