<div>
    <div wire:loading class="fixed top-0 left-0 right-0 z-50">
        <div class="animate-pulse bg-primary-600 px-4 py-2 text-center text-sm text-white">
            Memuatkan log API...
        </div>
    </div>

    <div wire:loading.remove>
        <div class="space-y-4">
            @php $counter = 1; @endphp
            @foreach($logs as $k => $v)
                <div class="myds-card p-5">
                    <!-- Request Details -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-400">#{{ $counter++ }}</span>

                        <span @class([
                            'myds-badge',
                            'border-primary-200 bg-primary-50 text-primary-700' => $v['method'] === 'GET',
                            'border-success-200 bg-success-50 text-success-700' => $v['method'] === 'POST',
                            'border-warning-200 bg-warning-50 text-warning-700' => $v['method'] === 'PUT',
                            'border-danger-200 bg-danger-50 text-danger-700' => $v['method'] === 'DELETE',
                            'border-outline-200 bg-washed-100 text-gray-600' => ! in_array($v['method'], ['GET', 'POST', 'PUT', 'DELETE']),
                        ])>
                            {{ $v["method"] }}
                        </span>

                        <span class="break-all font-mono text-sm text-gray-700">{{ $v["url"] }}</span>

                        <span @class([
                            'myds-badge',
                            'border-success-200 bg-success-50 text-success-700' => str_starts_with($v['status_code'], '2'),
                            'border-warning-200 bg-warning-50 text-warning-700' => str_starts_with($v['status_code'], '4'),
                            'border-danger-200 bg-danger-50 text-danger-700' => str_starts_with($v['status_code'], '5'),
                            'border-outline-200 bg-washed-100 text-gray-600' => ! str_starts_with($v['status_code'], '2') && ! str_starts_with($v['status_code'], '4') && ! str_starts_with($v['status_code'], '5'),
                        ])>
                            {{ $v["status_code"] }}
                        </span>
                    </div>

                    <div class="mt-2 text-xs text-gray-400">
                        {{ \Carbon\Carbon::parse($v->created_at)->format('j F Y g:i A') }}
                        <span class="ml-1 text-gray-500">({{ \Carbon\Carbon::parse($v->created_at)->diffForHumans() }}) oleh {{ $v->user->name }}</span>
                    </div>

                    <!-- Token -->
                    @if(!empty($v["token"]))
                        <div class="mt-2 break-all font-mono text-xs text-gray-400">
                            {{ $v["token"] }}
                        </div>
                    @endif

                    <!-- Input/Output -->
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-1">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Input</div>
                            <div class="overflow-x-auto rounded-lg border border-outline-200 bg-washed-100 p-3 font-mono text-xs text-gray-700">
                                <pre>@json($v['input'], JSON_PRETTY_PRINT)</pre>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Output</div>
                            <div class="overflow-x-auto rounded-lg border border-outline-200 bg-washed-100 p-3 font-mono text-xs text-gray-700">
                                <pre>@json($v['output'], JSON_PRETTY_PRINT)</pre>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
