@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-danger-200 bg-danger-50 p-4']) }}>
        <div class="font-medium text-sm text-danger-700">{{ __('Whoops! Something went wrong.') }}</div>

        <ul class="mt-2 list-disc list-inside text-sm text-danger-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
