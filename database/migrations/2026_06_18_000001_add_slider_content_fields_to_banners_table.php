<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable()->after('image');
            $table->string('highlight_text')->nullable()->after('title');
            $table->text('description')->nullable()->after('highlight_text');
            $table->string('button_text')->nullable()->after('description');
            $table->string('button_link')->nullable()->after('button_text');
            $table->string('image_alt')->nullable()->after('button_link');
            $table->integer('sort_order')->nullable()->default(0)->after('image_alt');
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['title', 'highlight_text', 'description', 'button_text', 'button_link', 'image_alt', 'sort_order']);
        });
    }
};
