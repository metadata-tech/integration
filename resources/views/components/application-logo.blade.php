<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-2.5']) }}>
    <x-application-mark class="h-9 w-auto" />
    <span class="text-start">
        <span class="block font-heading text-base font-semibold leading-tight text-gray-900">{{ config('app.name', 'MySPP Integration') }}</span>
        <span class="block text-xs leading-tight text-gray-500">Suruhanjaya Perkhidmatan Pendidikan</span>
    </span>
</span>
