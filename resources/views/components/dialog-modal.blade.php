@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <div class="font-heading text-lg font-semibold text-gray-900">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm text-gray-600">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end gap-2 px-6 py-4 bg-washed-100 border-t border-outline-200 text-end">
        {{ $footer }}
    </div>
</x-modal>
