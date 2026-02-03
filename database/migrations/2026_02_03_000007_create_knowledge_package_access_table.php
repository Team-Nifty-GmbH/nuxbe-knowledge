<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_package_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_package_setting_id')->constrained('knowledge_package_settings')->cascadeOnDelete();
            $table->string('accessible_type');
            $table->unsignedBigInteger('accessible_id');
            $table->timestamps();

            $table->unique(['knowledge_package_setting_id', 'accessible_type', 'accessible_id'], 'knowledge_package_access_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_package_access');
    }
};
