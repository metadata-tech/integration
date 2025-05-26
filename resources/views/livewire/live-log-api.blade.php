<div>
    {{-- Be like water. --}}{{--

    <form wire:submit="fetch">

        <label class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">PageIndex</span>
            </div>
            <input type="text" wire:model="pageIndex" placeholder="pageIndex" class="input input-bordered input-primary w-full max-w-xs" />
        </label>

        <label class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">PageSize</span>
            </div>
            <input type="text" wire:model="pageSize" placeholder="pageSize" class="input input-bordered input-primary w-full max-w-xs" />
        </label>




        <p class="text-2xl">URL </p>


        <button class="btn" type="submit">Save</button>
    </form>--}}

    <div wire:loading wire:loading>
        Calling API
    </div>

    <div wire:loading.remove>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-auto">
                <thead>
                <tr>
                    <th>#</th>
                    <th>item</th>
                </tr>
                </thead>
                @php $counter = 1; @endphp

                @foreach($items as $k => $v)

                    <tr>
                        <td>
                            {{ $counter++ }}
                            {{ $v->created_at }}
                        </td>
                        <td>

                            <table class="table-auto">
                                <thead></thead>

                                <tbody>
                                    <tr>
                                        <td>
                                            {{ $v["method"] }} {{ $v["url"] }}

                                            <span
                                                @if(\Illuminate\Support\Str::startsWith($v["status_code"],'2')) class="text-green-800"
                                                @elseif(\Illuminate\Support\Str::startsWith($v["status_code"],'4')) class="text-orange-800"
                                                @elseif(\Illuminate\Support\Str::startsWith($v["status_code"],'5')) class="text-red-800"
                                                @endif>
                                            {{ $v["status_code"] }}
                                            </span>
                                            <br/>

                                            {{ $v["token"] }} <br/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table class="table-auto">
                                                <thead>
                                                <th width="50%">Input</th>
                                                <th width="50%">Output</th>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td class="align-top">
                                                        @dump($v['input'])
                                                    </td>
                                                    <td class="align-top">
                                                        @dump($v['output'])
                                                    </td>
                                                </tr>
                                                </tbody>

                                            </table>
                                        </td>
                                    </tr>
                                </tbody>

                            </table>
                        </td>

                    </tr>
                @endforeach
            </table>
        </div>
    </div>

</div>
