<div class="flex h-full gap-6" x-data="{ versionA: null, versionB: null, sidebarOpen: true }">
    {{-- Sidebar --}}
    <div x-bind:class="!sidebarOpen && 'hidden lg:block'" class="w-full shrink-0 overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:w-72">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold dark:text-gray-100">{{ __('Knowledge Base') }}</h2>
            <div class="flex gap-1">
                <x-button icon="plus" color="primary" flat sm wire:click="newArticle" x-on:click="sidebarOpen = false" />
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
                        <x-button icon="plus" color="gray" flat xs wire:click.stop="newArticle({{ $category['id'] }})" x-on:click="sidebarOpen = false" />
                    </div>
                    <div x-show="open" x-cloak class="ml-5 space-y-0.5">
                        @foreach ($category['articles'] ?? [] as $article)
                            <div
                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $article['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                wire:click="selectArticle({{ $article['id'] }})" x-on:click="sidebarOpen = false"
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
                                    <x-button icon="plus" color="gray" flat xs wire:click.stop="newArticle({{ $child['id'] }})" x-on:click="sidebarOpen = false" />
                                </div>
                                <div x-show="childOpen" x-cloak class="ml-4 space-y-0.5">
                                    @foreach ($child['articles'] ?? [] as $childArticle)
                                        <div
                                            class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $childArticle['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                            wire:click="selectArticle({{ $childArticle['id'] }})" x-on:click="sidebarOpen = false"
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
                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedPackageDoc && $selectedPackageDoc['package'] === $package && $selectedPackageDoc['path'] === $item['relative_path'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                wire:click="selectPackageDoc('{{ $package }}', '{{ $item['relative_path'] }}')" x-on:click="sidebarOpen = false"
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
                                                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedPackageDoc && $selectedPackageDoc['package'] === $package && $selectedPackageDoc['path'] === $child['relative_path'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                                                wire:click="selectPackageDoc('{{ $package }}', '{{ $child['relative_path'] }}')" x-on:click="sidebarOpen = false"
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
    <div x-bind:class="sidebarOpen && 'hidden lg:block'" class="w-full flex-1 overflow-y-auto rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800 lg:w-auto">
        @if ($editing)
            {{-- Edit Mode --}}
            <div class="space-y-4">
                @if ($articleForm->id)
                    <div class="flex items-center justify-between">
                        <x-button icon="arrow-left" color="gray" flat sm class="lg:hidden" x-on:click="sidebarOpen = true" />
                        <div class="w-48">
                            <x-select.styled
                                wire:model="languageId"
                                x-on:select="$wire.switchLanguage()"
                                select="label:name|value:id"
                                :options="$languages"
                                sm
                            />
                        </div>
                    </div>
                @else
                    <x-button icon="arrow-left" color="gray" flat sm class="lg:hidden" x-on:click="sidebarOpen = true" />
                @endif

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

                <div>
                    <x-flux::features.media.upload-form-object
                        :text="__('File Attachments')"
                        wire:model="attachments"
                        :multiple="true"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('PDFs, documents and other files available as downloads.') }}</p>
                </div>

                <x-toggle wire:model="articleForm.is_published" :label="__('Published')" />

                @if ($articleForm->id)
                    <x-input wire:model="articleForm.change_summary" :label="__('Change Summary')" :placeholder="__('What changed?')" />
                @endif

                <div class="flex justify-end gap-2">
                    <x-button :text="__('Cancel')" color="secondary" flat wire:click="$set('editing', false)" />
                    <x-button :text="__('Save')" color="primary" wire:click="saveArticle" />
                </div>
            </div>
        @elseif ($selectedArticleId)
            {{-- View Mode --}}
            <div>
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <x-button icon="arrow-left" color="gray" flat sm class="lg:hidden" x-on:click="sidebarOpen = true" />
                        <h1 class="text-2xl font-bold dark:text-gray-100">{{ $articleForm->title }}</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-48">
                            <x-select.styled
                                wire:model="languageId"
                                x-on:select="$wire.switchLanguage()"
                                select="label:name|value:id"
                                :options="$languages"
                                sm
                            />
                        </div>
                        <x-button :text="__('Edit')" icon="pencil" color="primary" flat wire:click="editArticle" />
                        <x-button :text="__('Version History')" icon="clock" color="gray" flat x-on:click="$modalOpen('version-history-modal'); $wire.loadVersions()" />
                        <x-button :text="__('Delete')" icon="trash" color="red" flat wire:flux-confirm.type.error="{{ __('wire:confirm.delete', ['model' => __('Article')]) }}" wire:click="deleteArticle" />
                    </div>
                </div>

                <div class="prose max-w-none dark:prose-invert">
                    {!! $articleForm->content !!}
                </div>

                @if ($attachments->stagedFiles)
                    <div class="mt-6 border-t pt-4 dark:border-gray-700">
                        <h3 class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Attachments') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($attachments->stagedFiles as $file)
                                <a
                                    href="{{ $file['url'] ?? $file['preview_url'] ?? '#' }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    <x-icon name="paper-clip" class="h-4 w-4 text-gray-400" />
                                    {{ $file['name'] ?? $file['file_name'] ?? __('File') }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @elseif ($selectedPackageDoc)
            {{-- Package Doc View --}}
            <div x-data="{ lightboxSrc: null }">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-button icon="arrow-left" color="gray" flat sm class="lg:hidden" x-on:click="sidebarOpen = true" />
                        <x-badge color="gray" :text="$selectedPackageDoc['label']" />
                        <x-icon name="lock-closed" class="h-4 w-4 text-gray-400" />
                    </div>
                    <div class="w-48">
                        <x-select.styled
                            wire:model="languageId"
                            x-on:select="$wire.switchLanguage()"
                            select="label:name|value:id"
                            :options="$languages"
                            sm
                        />
                    </div>
                </div>
                <div
                    class="prose max-w-none [&_img]:cursor-pointer [&_img]:rounded [&_img]:shadow-sm [&_img]:transition [&_img]:hover:opacity-80 [&_img]:hover:shadow-md dark:prose-invert"
                    x-on:click="
                        if ($event.target.tagName === 'IMG') {
                            lightboxSrc = $event.target.src;
                            return;
                        }
                        const link = $event.target.closest('a[data-doc-link]');
                        if (link) {
                            $event.preventDefault();
                            $wire.selectPackageDoc('{{ $selectedPackageDoc['package'] }}', link.dataset.docLink);
                        }
                    "
                >
                    {!! $selectedPackageDoc['html'] !!}
                </div>

                {{-- Lightbox --}}
                <div
                    x-show="lightboxSrc"
                    x-cloak
                    x-on:keydown.escape.window="lightboxSrc = null"
                    class="fixed inset-0 z-50 flex items-center justify-center p-8"
                >
                    <div
                        x-show="lightboxSrc"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        x-on:click="lightboxSrc = null"
                        class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                    ></div>
                    <img
                        x-bind:src="lightboxSrc"
                        x-show="lightboxSrc"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="scale-90 opacity-0"
                        x-transition:enter-end="scale-100 opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="scale-100 opacity-100"
                        x-transition:leave-end="scale-90 opacity-0"
                        x-on:click.stop
                        class="relative max-h-full max-w-full rounded-lg shadow-2xl"
                    />
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="flex h-full flex-col text-gray-400 dark:text-gray-500">
                <x-button icon="arrow-left" color="gray" flat sm class="self-start lg:hidden" x-on:click="sidebarOpen = true" />
                <div class="flex flex-1 items-center justify-center">
                    <div class="text-center">
                        <x-icon name="book-open" class="mx-auto h-12 w-12" />
                        <p class="mt-2">{{ __('Select an article') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Version History Modal --}}
    <x-modal id="version-history-modal" size="xl">
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
                        <p class="text-xs text-gray-400">{{ \Illuminate\Support\Carbon::parse($version['created_at'])->format('d.m.Y H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <x-button :text="__('Restore')" icon="arrow-uturn-left" color="primary" flat xs wire:click="restoreVersion({{ $version['id'] }})" x-on:click="$modalClose('version-history-modal')" />
                        <x-button text="A" color="gray" flat xs x-on:click="versionA = {{ $version['id'] }}" x-bind:class="versionA === {{ $version['id'] }} && 'ring-2 ring-primary-500'" />
                        <x-button text="B" color="gray" flat xs x-on:click="versionB = {{ $version['id'] }}" x-bind:class="versionB === {{ $version['id'] }} && 'ring-2 ring-primary-500'" />
                    </div>
                </div>
            @endforeach
        </div>

        <template x-if="versionA && versionB">
            <div class="mt-4">
                <x-button :text="__('Compare Versions')" color="primary" x-on:click="$wire.compareVersions(versionA, versionB).then(() => { $modalClose('version-history-modal'); $modalOpen('version-comparison-modal'); })" />
            </div>
        </template>

        <x-slot:footer>
            <x-button :text="__('Cancel')" color="secondary" flat x-on:click="$modalClose('version-history-modal')" />
        </x-slot:footer>
    </x-modal>

    {{-- Version Comparison Modal --}}
    @if ($comparisonVersions)
        <x-modal id="version-comparison-modal" size="full">
            <x-slot:title>{{ __('Version Comparison') }}</x-slot:title>

            <div class="grid grid-cols-[1fr_auto_1fr] gap-6">
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="font-semibold dark:text-gray-200">v{{ $comparisonVersions['a']['version_number'] }} — {{ $comparisonVersions['a']['title'] }}</span>
                        <x-button :text="__('Restore')" icon="arrow-uturn-left" color="primary" flat sm wire:click="restoreVersion({{ $comparisonVersions['a']['id'] }})" x-on:click="$modalClose('version-comparison-modal')" />
                    </div>
                    <div class="prose max-w-none rounded border p-4 dark:prose-invert dark:border-gray-700">
                        {!! $comparisonVersions['a']['content'] !!}
                    </div>
                </div>
                <div class="w-px bg-gray-200 dark:bg-gray-700"></div>
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="font-semibold dark:text-gray-200">v{{ $comparisonVersions['b']['version_number'] }} — {{ $comparisonVersions['b']['title'] }}</span>
                        <x-button :text="__('Restore')" icon="arrow-uturn-left" color="primary" flat sm wire:click="restoreVersion({{ $comparisonVersions['b']['id'] }})" x-on:click="$modalClose('version-comparison-modal')" />
                    </div>
                    <div class="prose max-w-none rounded border p-4 dark:prose-invert dark:border-gray-700">
                        {!! $comparisonVersions['b']['content'] !!}
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <x-button :text="__('Close')" color="secondary" flat x-on:click="$modalClose('version-comparison-modal')" />
            </x-slot:footer>
        </x-modal>
    @endif
</div>
