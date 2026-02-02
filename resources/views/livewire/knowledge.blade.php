<div class="flex h-full gap-6" x-data="{ showHistory: false, versionA: null, versionB: null }">
    {{-- Sidebar --}}
    <div class="w-72 shrink-0 overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold dark:text-gray-100">{{ __('Knowledge Base') }}</h2>
            <div class="flex gap-1">
                <x-button icon="plus" color="primary" flat sm wire:click="newArticle" />
            </div>
        </div>

        <div class="mb-4">
            <x-input wire:model.live.debounce.300ms="search" :placeholder="__('Search...')" icon="magnifying-glass" />
        </div>

        {{-- User Categories --}}
        <div class="space-y-1">
            @foreach ($categories as $category)
                <div x-data="{ open: true }">
                    <div class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" x-on:click="open = !open">
                        <x-icon name="chevron-right" class="h-4 w-4 transition-transform" x-bind:class="open && 'rotate-90'" />
                        <span class="flex-1 text-sm font-medium dark:text-gray-200">{{ $category['name'] }}</span>
                        <x-button icon="plus" color="gray" flat xs wire:click.stop="newArticle({{ $category['id'] }})" />
                    </div>
                    <div x-show="open" x-cloak class="ml-5 space-y-0.5">
                        @foreach ($category['articles'] ?? [] as $article)
                            <div
                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $article['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                wire:click="selectArticle({{ $article['id'] }})"
                            >
                                {{ $article['title'] }}
                            </div>
                        @endforeach

                        {{-- Child Categories --}}
                        @foreach ($category['children'] ?? [] as $child)
                            <div x-data="{ childOpen: true }">
                                <div class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" x-on:click="childOpen = !childOpen">
                                    <x-icon name="chevron-right" class="h-3 w-3 transition-transform" x-bind:class="childOpen && 'rotate-90'" />
                                    <span class="flex-1 text-sm dark:text-gray-300">{{ $child['name'] }}</span>
                                    <x-button icon="plus" color="gray" flat xs wire:click.stop="newArticle({{ $child['id'] }})" />
                                </div>
                                <div x-show="childOpen" x-cloak class="ml-4 space-y-0.5">
                                    @foreach ($child['articles'] ?? [] as $childArticle)
                                        <div
                                            class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $childArticle['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                            wire:click="selectArticle({{ $childArticle['id'] }})"
                                        >
                                            {{ $childArticle['title'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Uncategorized Articles --}}
        @if (count($uncategorizedArticles))
            <div class="mt-2 space-y-0.5">
                @foreach ($uncategorizedArticles as $article)
                    <div
                        class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $article['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                        wire:click="selectArticle({{ $article['id'] }})"
                    >
                        {{ $article['title'] }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Package Docs --}}
        @foreach ($packageDocs as $package => $config)
            <div class="mt-4 border-t pt-4 dark:border-gray-700" x-data="{ open: false }">
                <div class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" x-on:click="open = !open">
                    <x-icon name="chevron-right" class="h-4 w-4 transition-transform" x-bind:class="open && 'rotate-90'" />
                    <x-icon name="lock-closed" class="h-3 w-3 text-gray-400" />
                    <span class="flex-1 text-sm font-medium dark:text-gray-200">{{ $config['label'] }}</span>
                </div>
                <div x-show="open" x-cloak class="ml-5 space-y-0.5">
                    @foreach ($config['tree'] as $item)
                        @if (($item['type'] ?? '') === 'file')
                            <div
                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                wire:click="selectPackageDoc('{{ $package }}', '{{ $item['relative_path'] }}')"
                            >
                                {{ $item['name'] }}
                            </div>
                        @elseif (($item['type'] ?? '') === 'directory')
                            <div x-data="{ subOpen: false }">
                                <div class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" x-on:click="subOpen = !subOpen">
                                    <x-icon name="chevron-right" class="h-3 w-3 transition-transform" x-bind:class="subOpen && 'rotate-90'" />
                                    <span class="text-sm dark:text-gray-300">{{ $item['name'] }}</span>
                                </div>
                                <div x-show="subOpen" x-cloak class="ml-4 space-y-0.5">
                                    @foreach ($item['children'] ?? [] as $child)
                                        @if (($child['type'] ?? '') === 'file')
                                            <div
                                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                                wire:click="selectPackageDoc('{{ $package }}', '{{ $child['relative_path'] }}')"
                                            >
                                                {{ $child['name'] }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        @if ($editing)
            {{-- Edit Mode --}}
            <div class="space-y-4">
                <x-input wire:model="articleForm.title" :label="__('Title')" />

                <x-select.styled
                    wire:model="articleForm.categories"
                    :label="__('Categories')"
                    multiple
                    select="label:label|value:id"
                    unfiltered
                    :request="['url' => route('search', \FluxErp\Models\Category::class), 'method' => 'POST', 'params' => ['where' => [['model_type', '=', morph_alias(\TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle::class)]]]]"
                />

                <x-flux::editor wire:model="articleForm.content" :label="__('Content')" />

                <x-toggle wire:model="articleForm.is_published" :label="__('Published')" />

                @if ($articleForm->id)
                    <x-input wire:model="articleForm.change_summary" :label="__('Change Summary')" :placeholder="__('What changed?')" />
                @endif

                <div class="flex gap-2">
                    <x-button :text="__('Save')" color="primary" wire:click="saveArticle" />
                    <x-button :text="__('Cancel')" color="secondary" flat wire:click="$set('editing', false)" />
                </div>
            </div>
        @elseif ($selectedArticleId)
            {{-- View Mode --}}
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h1 class="text-2xl font-bold dark:text-gray-100">{{ $articleForm->title }}</h1>
                    <div class="flex gap-2">
                        <x-button :text="__('Edit')" icon="pencil" color="primary" flat wire:click="editArticle" />
                        <x-button :text="__('Version History')" icon="clock" color="gray" flat x-on:click="showHistory = true; $wire.loadVersions()" />
                        <x-button :text="__('Delete')" icon="trash" color="red" flat wire:flux-confirm.type.error="{{ __('wire:confirm.delete', ['model' => __('Article')]) }}" wire:click="deleteArticle" />
                    </div>
                </div>

                <div class="prose max-w-none dark:prose-invert">
                    {!! $articleForm->content !!}
                </div>
            </div>
        @elseif ($selectedPackageDoc)
            {{-- Package Doc View --}}
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <x-badge color="gray" :text="$selectedPackageDoc['label']" />
                    <x-icon name="lock-closed" class="h-4 w-4 text-gray-400" />
                </div>
                <div class="prose max-w-none dark:prose-invert">
                    {!! $selectedPackageDoc['html'] !!}
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">
                <div class="text-center">
                    <x-icon name="book-open" class="mx-auto h-12 w-12" />
                    <p class="mt-2">{{ __('Select an article') }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Version History Modal --}}
    <x-modal id="version-history-modal" size="xl" x-show="showHistory" x-cloak>
        <x-slot:title>{{ __('Version History') }}</x-slot:title>

        <div class="space-y-2">
            @foreach ($versions as $version)
                <div class="flex items-center justify-between rounded border p-3 dark:border-gray-700">
                    <div>
                        <span class="font-medium dark:text-gray-200">v{{ $version['version_number'] }}</span>
                        <span class="ml-2 text-sm text-gray-500">{{ $version['title'] }}</span>
                        @if ($version['change_summary'])
                            <p class="text-sm text-gray-400">{{ $version['change_summary'] }}</p>
                        @endif
                        <p class="text-xs text-gray-400">{{ $version['created_at'] }}</p>
                    </div>
                    <div class="flex gap-1">
                        <x-button text="A" color="gray" flat xs x-on:click="versionA = {{ $version['id'] }}" x-bind:class="versionA === {{ $version['id'] }} && 'ring-2 ring-primary-500'" />
                        <x-button text="B" color="gray" flat xs x-on:click="versionB = {{ $version['id'] }}" x-bind:class="versionB === {{ $version['id'] }} && 'ring-2 ring-primary-500'" />
                    </div>
                </div>
            @endforeach
        </div>

        <template x-if="versionA && versionB">
            <div class="mt-4">
                <x-button :text="__('Compare Versions')" color="primary" x-on:click="$wire.compareVersions(versionA, versionB)" />
            </div>
        </template>

        @if ($diffHtml)
            <div class="mt-4 overflow-auto rounded border p-4 dark:border-gray-700">
                {!! $diffHtml !!}
            </div>
        @endif

        <x-slot:footer>
            <x-button :text="__('Cancel')" color="secondary" flat x-on:click="showHistory = false" />
        </x-slot:footer>
    </x-modal>
</div>
