@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'text-sm text-danger-600']) }}>{{ $message }}</p>
@enderror
