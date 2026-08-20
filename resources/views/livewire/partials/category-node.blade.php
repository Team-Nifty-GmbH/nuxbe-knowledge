@php($depth = $depth ?? 0)
<div
    x-data="{ open: @js(mb_strlen($search) > 0) || false }"
    x-init="$watch('$wire.search', value => { if (value.length > 0) open = true })"
>
    <div class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" x-on:click="open = !open">
        <x-icon name="chevron-right" class="{{ $depth === 0 ? 'h-4 w-4' : 'h-3 w-3' }} transition-transform" x-bind:class="open && 'rotate-90'" />
        <span class="flex-1 text-sm {{ $depth === 0 ? 'font-medium dark:text-gray-200' : 'dark:text-gray-300' }}">{{ $category['name'] }}</span>
        <x-button icon="plus" color="gray" flat xs wire:click.stop="newArticle({{ $category['id'] }})" x-on:click="sidebarOpen = false" />
    </div>
    <div x-show="open" x-cloak class="{{ $depth === 0 ? 'ml-5' : 'ml-4' }} space-y-0.5">
        @foreach ($category['articles'] ?? [] as $article)
            <div
                class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 {{ $selectedArticleId === $article['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }}"
                wire:click="selectArticle({{ $article['id'] }})" x-on:click="sidebarOpen = false"
            >
                @unless ($article['is_published'] ?? true)
                    <x-badge flat light color="amber" xs :text="__('Draft')" />
                @endunless
                {{ $article['title'] }}
            </div>
        @endforeach

        @foreach ($category['children'] ?? [] as $child)
            @include('nuxbe-knowledge::livewire.partials.category-node', [
                'category' => $child,
                'depth' => $depth + 1,
            ])
        @endforeach
    </div>
</div>
