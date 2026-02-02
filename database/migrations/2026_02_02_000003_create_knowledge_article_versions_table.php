<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->longText('content_markdown')->nullable();
            $table->unsignedInteger('version_number');
            $table->string('change_summary')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_versions');
    }
};
