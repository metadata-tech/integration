<div class="flex min-h-screen flex-col items-center bg-washed-100 pt-6 sm:justify-center sm:pt-0">
    <div>
        {{ $logo }}
    </div>

    <div class="mt-6 w-full overflow-hidden border border-outline-200 bg-white px-6 py-6 shadow-card sm:max-w-md sm:rounded-xl">
        {{ $slot }}
    </div>

    <p class="mt-6 pb-6 text-xs text-gray-400">
        &copy; {{ date('Y') }} Suruhanjaya Perkhidmatan Pelajaran (SPP)
    </p>
</div>
