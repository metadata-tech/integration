<div wire:poll.60s>
    @php
        $slugs = array_keys($integrations);
        $count = count($slugs);
        $results = $health['results'];

        $rowH = 62;
        $padY = 18;
        $height = max($padY * 2 + $count * $rowH, 340);
        $hubY = $height / 2;

        $cableStartX = 252;
        $nodeX = 620;
        $nodeW = 348;
    @endphp

    <div class="myds-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-200 px-5 py-4">
            <div>
                <h3 class="font-heading text-base font-semibold text-gray-900">Peta Integrasi</h3>
                <p class="mt-0.5 text-sm text-gray-500">Status capaian API agensi luar secara langsung. Klik nod untuk ke halaman semakan.</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-success-600"></span>Aktif</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-warning-600"></span>Ralat terpencil</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-danger-600"></span>Gagal</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-gray-300"></span>Tiada URL</span>
                </div>

                <button type="button" wire:click="checkNow" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-outline-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-washed-100 disabled:opacity-50">
                    <svg wire:loading.class="animate-spin" class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span wire:loading.remove>Semak semula</span>
                    <span wire:loading>Menyemak...</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto px-2 py-2">
            <svg viewBox="0 0 1000 {{ $height }}" class="w-full min-w-[760px]" role="img" aria-label="Peta status integrasi API">
                {{-- Cables --}}
                @foreach ($slugs as $i => $slug)
                    @php
                        $y = $padY + $i * $rowH + $rowH / 2;
                        $midX = ($cableStartX + $nodeX) / 2;
                        $path = "M {$cableStartX} {$hubY} C {$midX} {$hubY}, {$midX} {$y}, " . ($nodeX - 4) . " {$y}";
                        $status = $results[$slug]['status'] ?? 'unknown';
                    @endphp

                    <path d="{{ $path }}" fill="none" stroke="#E4E4E7" stroke-width="2" />

                    @if ($status === 'up')
                        <path d="{{ $path }}" fill="none" stroke="#16A34A" stroke-width="2"
                              stroke-linecap="round" class="cable-flow" />
                    @elseif ($status === 'warning')
                        <path d="{{ $path }}" fill="none" stroke="#D97706" stroke-width="2"
                              stroke-linecap="round" class="cable-flow" />
                    @elseif ($status === 'down')
                        <path d="{{ $path }}" fill="none" stroke="#DC2626" stroke-width="2"
                              stroke-linecap="round" class="cable-pulse" />
                    @endif
                @endforeach

                {{-- SPP hub --}}
                <g>
                    <rect x="42" y="{{ $hubY - 42 }}" width="210" height="84" rx="16"
                          fill="#FFFFFF" stroke="#D4D4D8" stroke-width="1.5" />
                    <image href="{{ asset('img/jatanegara.png') }}"
                           x="60" y="{{ $hubY - 26 }}" width="52" height="52"
                           preserveAspectRatio="xMidYMid meet" />
                    <text x="124" y="{{ $hubY - 2 }}" font-size="16" font-weight="600" fill="#18181B" font-family="inherit">SPP</text>
                    <text x="124" y="{{ $hubY + 17 }}" font-size="11" fill="#71717A" font-family="inherit">MySPP Integration</text>
                    <circle cx="{{ $cableStartX }}" cy="{{ $hubY }}" r="4" fill="#2563EB" />
                </g>

                {{-- Agency nodes --}}
                @foreach ($slugs as $i => $slug)
                    @php
                        $integration = $integrations[$slug];
                        $y = $padY + $i * $rowH + $rowH / 2;
                        $result = $results[$slug] ?? ['status' => 'unknown', 'detail' => 'Tiada maklumat', 'host' => null];
                        $status = $result['status'];

                        $dotColor = match ($status) {
                            'up' => '#16A34A',
                            'warning' => '#D97706',
                            'down' => '#DC2626',
                            default => '#A1A1AA',
                        };
                        $detail = $result['detail'];
                        $detailColor = match ($status) {
                            'down' => '#DC2626',
                            'warning' => '#D97706',
                            default => '#71717A',
                        };
                    @endphp

                    <a href="{{ route("integrasi.{$slug}") }}" class="map-node">
                        <g>
                            <title>{{ $integration['title'] }}{{ $result['host'] ? ' — ' . $result['host'] : '' }}</title>

                            <rect x="{{ $nodeX }}" y="{{ $y - 24 }}" width="{{ $nodeW }}" height="48" rx="12"
                                  fill="#FFFFFF" stroke="#E4E4E7" stroke-width="1.5" class="map-node-box" />

                            <circle cx="{{ $nodeX + 26 }}" cy="{{ $y }}" r="15" fill="#EFF6FF" />
                            <text x="{{ $nodeX + 26 }}" y="{{ $y + 3.5 }}" text-anchor="middle"
                                  font-size="9.5" font-weight="600" fill="#1D4ED8" font-family="inherit">{{ $integration['code'] }}</text>

                            <text x="{{ $nodeX + 52 }}" y="{{ $y - 3 }}" font-size="13" font-weight="600" fill="#18181B" font-family="inherit">
                                {{ $integration['label'] }}
                            </text>
                            <text x="{{ $nodeX + 52 }}" y="{{ $y + 14 }}" font-size="10.5" fill="{{ $detailColor }}" font-family="inherit">
                                {{ $detail }}
                            </text>

                            <circle cx="{{ $nodeX + $nodeW - 20 }}" cy="{{ $y }}" r="4.5" fill="{{ $dotColor }}"
                                    @class(['cable-pulse' => $status === 'down']) />
                        </g>
                    </a>
                @endforeach
            </svg>
        </div>

        <div class="border-t border-outline-200 px-5 py-2.5 text-xs text-gray-400">
            Status berdasarkan log API 15 minit terkini; tanpa trafik, semakan capaian terus dibuat. Terakhir disemak: {{ $health['checked_at'] }}
        </div>
    </div>
</div>
