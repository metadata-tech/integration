<div x-data="{ mastheadOpen: false }" class="bg-washed-100 border-b border-outline-200 text-sm text-gray-700">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6">
        <button type="button"
                @click="mastheadOpen = ! mastheadOpen"
                class="flex w-full flex-wrap items-center gap-x-1.5 gap-y-0.5 py-1.5 text-left outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
            {{-- Simplified Malaysian flag --}}
            <svg class="h-3.5 w-6 shrink-0 rounded-[2px] border border-outline-200" viewBox="0 0 28 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="28" height="14" fill="#FFFFFF"/>
                <g fill="#CC0001">
                    <rect y="0" width="28" height="1"/>
                    <rect y="2" width="28" height="1"/>
                    <rect y="4" width="28" height="1"/>
                    <rect y="6" width="28" height="1"/>
                    <rect y="8" width="28" height="1"/>
                    <rect y="10" width="28" height="1"/>
                    <rect y="12" width="28" height="1"/>
                </g>
                <rect width="14" height="8" fill="#010066"/>
                <circle cx="5.5" cy="4" r="2.6" fill="#FFCC00"/>
                <circle cx="6.4" cy="4" r="2.2" fill="#010066"/>
                <path fill="#FFCC00" d="M10 4l1.8.55-1.5 1.05.05-1.85-1.1 1.5-.05-1.85-1.1 1.5.6-1.75-1.6.95 1.25-1.4-1.85.15 1.7-.75-1.7-.75 1.85.15L6.4 .1l1.6.95-.6-1.75 1.1 1.5.05-1.85 1.1 1.5-.05-1.85 1.5 1.05L9.3 1.2 11 .75 9.85 2.2l1.85-.15-1.7.75 1.7.75-1.85-.15L11 4.85 10 4z" transform="translate(0.5 2.2) scale(0.62)"/>
            </svg>

            <span class="font-medium">Portal Rasmi Kerajaan Malaysia</span>
            <span class="inline-flex items-center gap-0.5 text-primary-600 hover:text-primary-700 hover:underline">
                Kenal pasti begini
                <svg class="size-3.5 transition-transform" :class="{ 'rotate-180': mastheadOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                </svg>
            </span>
        </button>

        <div x-cloak x-show="mastheadOpen" x-transition.opacity.duration.200ms class="grid gap-4 pb-4 pt-2 sm:grid-cols-2 lg:max-w-4xl">
            <div class="flex items-start gap-2.5">
                <svg class="mt-0.5 size-5 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m-18.432 0A8.959 8.959 0 013 12c0-.778.099-1.533.284-2.253"/>
                </svg>
                <p class="text-xs leading-relaxed text-gray-600">
                    <span class="block font-medium text-gray-900">Laman web rasmi kerajaan berakhir dengan .gov.my</span>
                    Jika pautan tidak berakhir dengan <span class="font-medium">.gov.my</span>, segera tinggalkan laman web tersebut walaupun ia kelihatan serupa.
                </p>
            </div>
            <div class="flex items-start gap-2.5">
                <svg class="mt-0.5 size-5 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                <p class="text-xs leading-relaxed text-gray-600">
                    <span class="block font-medium text-gray-900">Laman web selamat menggunakan HTTPS</span>
                    Cari ikon kunci (<svg class="inline size-3 -mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>) atau <span class="font-medium">https://</span> sebagai langkah berjaga-jaga. Jika tiada, jangan kongsi sebarang maklumat sensitif.
                </p>
            </div>
        </div>
    </div>
</div>
