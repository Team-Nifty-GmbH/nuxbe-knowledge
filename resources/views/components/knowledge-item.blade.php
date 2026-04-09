@props([
    'items',
    'package',
    'selectedPackageDoc' => null,
])

@foreach ($items as $item)
    @if (($item['type'] ?? '') === 'file')
        <div
            class="{{ $selectedPackageDoc && $selectedPackageDoc['package'] === $package && $selectedPackageDoc['path'] === $item['relative_path'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : '' }} cursor-pointer rounded px-2 py-1 text-sm hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            wire:click="selectPackageDoc('{{ $package }}', '{{ $item['relative_path'] }}')"
            x-on:click="sidebarOpen = false"
        >
            {{ $item['name'] }}
        </div>
    @elseif (($item['type'] ?? '') === 'directory')
        <div x-data="{ subOpen: false }" class="w-full">
            <div
                class="flex cursor-pointer items-center gap-1 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
                x-on:click="subOpen = !subOpen"
            >
                <x-icon
                    name="chevron-right"
                    class="h-3 w-3 transition-transform"
                    x-bind:class="subOpen && 'rotate-90'"
                />
                <span class="text-sm dark:text-gray-300">
                    {{ $item['name'] }}
                </span>
            </div>

            <div
                x-show="subOpen"
                x-cloak
                class="ml-4 space-y-0.5 border-l border-gray-100 dark:border-gray-700"
            >
                <x-nuxbe-knowledge::knowledge-item
                    :items="$item['children']"
                    :package="$package"
                    :selectedPackageDoc="$selectedPackageDoc"
                />
            </div>
        </div>
    @endif
@endforeach
