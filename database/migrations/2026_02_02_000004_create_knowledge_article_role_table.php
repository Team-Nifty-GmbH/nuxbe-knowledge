<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_role', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('permission_level')->default('read');
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_role');
    }
};
