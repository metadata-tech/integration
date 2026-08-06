@php
    $integrations = collect(config('integrations'))
        ->map(fn ($integration, $slug) => ['route' => "integrasi.{$slug}", 'label' => $integration['label']])
        ->values()
        ->all();

    $admin = [
        ['route' => 'admin.users.index', 'label' => 'Pengguna'],
        ['route' => 'admin.roles.index', 'label' => 'Peranan'],
        ['route' => 'admin.permissions.index', 'label' => 'Kebenaran'],
        ['route' => 'logapi.index', 'label' => 'Log API'],
    ];
@endphp

<nav class="flex h-full flex-col gap-y-6 overflow-y-auto bg-white px-4 py-6">
    <div>
        <a href="{{ route('dashboard') }}"
           @class([
               'group flex items-center gap-x-2.5 rounded-lg px-3 py-2 text-sm font-medium transition',
               'bg-primary-50 text-primary-700' => request()->routeIs('dashboard'),
               'text-gray-700 hover:bg-washed-100 hover:text-gray-900' => ! request()->routeIs('dashboard'),
           ])>
            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
            </svg>
            Utama
        </a>
    </div>

    <div>
        <p class="px-3 text-xs font-medium uppercase tracking-wider text-gray-400">Integrasi</p>
        <ul class="mt-2 space-y-0.5">
            @foreach ($integrations as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'block rounded-lg px-3 py-2 text-sm transition',
                           'bg-primary-50 font-medium text-primary-700' => request()->routeIs($item['route']),
                           'text-gray-700 hover:bg-washed-100 hover:text-gray-900' => ! request()->routeIs($item['route']),
                       ])>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div>
        <p class="px-3 text-xs font-medium uppercase tracking-wider text-gray-400">Pentadbiran</p>
        <ul class="mt-2 space-y-0.5">
            @foreach ($admin as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'block rounded-lg px-3 py-2 text-sm transition',
                           'bg-primary-50 font-medium text-primary-700' => request()->routeIs($item['route']),
                           'text-gray-700 hover:bg-washed-100 hover:text-gray-900' => ! request()->routeIs($item['route']),
                       ])>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
