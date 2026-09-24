<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Testimonials become image-only, so the existing text-based rows are
        // removed along with their uploaded avatars before the columns go.
        $existing = DB::table('testimonials')->get();

        foreach ($existing as $row) {
            if ($row->image && file_exists(public_path($row->image))) {
                unlink(public_path($row->image));
            }
        }

        DB::table('testimonials')->delete();

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['name', 'location', 'rating', 'message']);
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('location')->nullable()->after('name');
            $table->tinyInteger('rating')->default(5)->after('image');
            $table->text('message')->nullable()->after('rating');
        });
    }
};
