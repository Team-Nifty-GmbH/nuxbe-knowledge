<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->string('visibility_mode')->default('public')->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropColumn('visibility_mode');
        });
    }
};
