@props([
    'name',
    'label' => null,
    'required' => false,
    'placeholder' => null,
    'options' => [],
    'selected' => null,
])

@php
    $selectClasses = 'w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500';
    $errorClasses = $errors->has($name) ? 'border-red-300' : '';
    $selectedValue = old($name, $selected);
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if($label)
        <label for="{{ $name }}" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->except('class') }}
        class="{{ $selectClasses }} {{ $errorClasses }}"
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $value => $text)
            <option value="{{ $value }}" {{ $selectedValue == $value ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach

        {{ $slot }}
    </select>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
