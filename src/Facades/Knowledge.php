<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Facades;

use Illuminate\Support\Facades\Facade;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

/**
 * @method static void registerDocs(string $package, string $path, string $label, string $icon = 'book-open')
 * @method static array getRegisteredPackages()
 * @method static array getDocsTree(string $package)
 * @method static ?string renderDoc(string $package, string $relativePath)
 * @method static array getAllDocsTrees()
 */
class Knowledge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return KnowledgeManager::class;
    }
}
