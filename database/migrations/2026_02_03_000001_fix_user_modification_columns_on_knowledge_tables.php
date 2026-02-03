<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->string('created_by')->nullable()->change();
            $table->string('updated_by')->nullable()->change();
            $table->string('deleted_by')->nullable()->change();
        });

        Schema::table('knowledge_article_versions', function (Blueprint $table): void {
            $table->string('created_by')->nullable()->change();

            if (! Schema::hasColumn('knowledge_article_versions', 'updated_by')) {
                $table->string('updated_by')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->unsignedBigInteger('updated_by')->nullable()->change();
            $table->unsignedBigInteger('deleted_by')->nullable()->change();
        });

        Schema::table('knowledge_article_versions', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->dropColumn('updated_by');
        });
    }
};
