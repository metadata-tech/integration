@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-outline-300 text-gray-900 focus:border-primary-600 focus:ring-primary-600 rounded-lg shadow-sm text-sm disabled:bg-washed-100 disabled:text-gray-500']) !!}>
    {{ $slot }}
</select>
