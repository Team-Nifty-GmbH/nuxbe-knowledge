<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillKnowledgeArticleSources extends Command
{
    protected $signature = 'knowledge:backfill-article-sources';

    protected $description = 'Copies the ticket/order an article was condensed from onto the article itself';

    public function handle(): int
    {
        // flux-ai owns this table and may not be installed. Reading it here is a
        // one-off migration of its data into a column this package owns from now on.
        if (! Schema::hasTable('flux_ai_condensed_sources')) {
            $this->components->warn('No flux_ai_condensed_sources table, nothing to backfill.');

            return self::SUCCESS;
        }

        $updated = DB::table('knowledge_articles')
            ->join(
                'flux_ai_condensed_sources',
                'flux_ai_condensed_sources.knowledge_article_id',
                '=',
                'knowledge_articles.id'
            )
            ->whereNull('knowledge_articles.source_type')
            ->update([
                'knowledge_articles.source_type' => DB::raw('flux_ai_condensed_sources.source_type'),
                'knowledge_articles.source_id' => DB::raw('flux_ai_condensed_sources.source_id'),
            ]);

        $this->components->info($updated.' article(s) linked to their source.');

        return self::SUCCESS;
    }
}
