<div class="py-6">

    <div wire:loading class="fixed top-0 left-0 right-0 z-50">
        <div class="bg-indigo-600 text-white px-4 py-2 text-center animate-pulse">
            Calling API...
        </div>
    </div>

    <div wire:loading.remove>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-xl rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $counter = 1; @endphp
                            @foreach($items as $k => $v)
                                <tr class="@if($loop->even) bg-gray-50 @endif hover:bg-gray-100 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="font-medium">{{ $counter++ }}</div>
                                        <div class="text-xs text-gray-400">{{ $v->created_at }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-4">
                                            <!-- Request Details -->
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-md
                                                    @if($v['method'] === 'GET') bg-blue-100 text-blue-800
                                                    @elseif($v['method'] === 'POST') bg-green-100 text-green-800
                                                    @elseif($v['method'] === 'PUT') bg-yellow-100 text-yellow-800
                                                    @elseif($v['method'] === 'DELETE') bg-red-100 text-red-800
                                                    @endif">
                                                    {{ $v["method"] }}
                                                </span>
                                                <span class="text-sm font-mono text-gray-600">{{ $v["url"] }}</span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-md
                                                    @if(str_starts_with($v['status_code'], '2')) bg-green-100 text-green-800
                                                    @elseif(str_starts_with($v['status_code'], '4')) bg-orange-100 text-orange-800
                                                    @elseif(str_starts_with($v['status_code'], '5')) bg-red-100 text-red-800
                                                    @endif">
                                                    {{ $v["status_code"] }}
                                                </span>
                                            </div>

                                            <!-- Token -->
                                            <div class="text-xs text-gray-500 font-mono break-all">
                                                {{ $v["token"] }}
                                            </div>

                                            <!-- Input/Output -->
                                            <div class="grid md:grid-cols-2 gap-4">
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase">Input</div>
                                                    <div class="bg-gray-50 p-3 rounded-md text-xs font-mono overflow-x-auto">
                                                        <pre>@json($v['input'], JSON_PRETTY_PRINT)</pre>
                                                    </div>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase">Output</div>
                                                    <div class="bg-gray-50 p-3 rounded-md text-xs font-mono overflow-x-auto">
                                                        <pre>@json($v['output'], JSON_PRETTY_PRINT)</pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
