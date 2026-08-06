{{-- Shared renderer for LPM SVK/APKV results: expects $result --}}
@if (isset($result['status']) && $result['status'] == false)
    <div class="mt-6 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
        {{ $result['message'] ?? 'Ralat tidak diketahui' }}
    </div>
@elseif (isset($result[0]))
    <div class="mt-6 border-t border-outline-200 pt-5">
        <div class="flex items-center gap-2">
            <h3 class="font-heading text-sm font-semibold text-gray-900">Hasil</h3>
            <span class="myds-badge-success">Berjaya</span>
        </div>

        <dl class="mt-3 divide-y divide-outline-200 rounded-lg border border-outline-200 text-sm">
            <div class="flex justify-between gap-4 px-4 py-2.5">
                <dt class="text-gray-500">Pelajar</dt>
                <dd class="font-medium text-gray-900 text-end">{{ $result[0]['pelajar'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-4 py-2.5">
                <dt class="text-gray-500">No KP</dt>
                <dd class="font-medium text-gray-900">{{ $result[0]['kp'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-4 py-2.5">
                <dt class="text-gray-500">Tahun</dt>
                <dd class="font-medium text-gray-900">{{ $result[0]['tahun'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-4 py-2.5">
                <dt class="text-gray-500">Ringkasan</dt>
                <dd class="font-medium text-gray-900 text-end">{{ $result[0]['ringkasan'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-4 py-2.5">
                <dt class="text-gray-500">Angka Giliran</dt>
                <dd class="font-medium text-gray-900">{{ $result[0]['akg'] ?? '—' }}</dd>
            </div>
        </dl>

        @if (! empty($result[0]['data']))
            <div class="mt-4 overflow-hidden rounded-lg border border-outline-200">
                <table class="myds-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kod</th>
                            <th>Gred</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result[0]['data'] as $v)
                            <tr>
                                <td>{{ $v['nama'] }}</td>
                                <td>{{ $v['kod'] }}</td>
                                <td class="font-medium text-gray-900">{{ $v['gred'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@else
    <x-result-panel :result="$result" />
@endif
