@props(['result', 'title' => 'Hasil'])

@php
    $status = is_array($result) ? ($result['status'] ?? null) : null;
    $isError = in_array($status, ['error', false, 'false'], true);
    $isSuccess = in_array($status, ['success', true, 'true'], true);
    $message = is_array($result) ? ($result['message'] ?? null) : null;

    // Some APIs return `message` as an array/object; coerce to text so Blade can escape it.
    if ($message !== null && ! is_scalar($message)) {
        $message = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
@endphp

<div {{ $attributes->merge(['class' => 'mt-6 border-t border-outline-200 pt-5']) }}>
    <div class="flex items-center gap-2">
        <h3 class="font-heading text-sm font-semibold text-gray-900">{{ $title }}</h3>

        @if ($isError)
            <span class="myds-badge-danger">Ralat</span>
        @elseif ($isSuccess)
            <span class="myds-badge-success">Berjaya</span>
        @endif
    </div>

    @if ($message)
        <p @class([
            'mt-2 rounded-lg border px-4 py-3 text-sm',
            'border-danger-200 bg-danger-50 text-danger-700' => $isError,
            'border-success-200 bg-success-50 text-success-700' => $isSuccess,
            'border-outline-200 bg-washed-100 text-gray-700' => ! $isError && ! $isSuccess,
        ])>
            {{ $message }}
        </p>
    @endif

    <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-relaxed text-gray-100">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</div>
