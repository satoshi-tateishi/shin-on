@props([
    'type' => 'text',
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'maxlength' => null,
])

@php
    $inputClasses = 'w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500';
    $errorClasses = $errors->has($name) ? 'border-red-300' : '';
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

    @if($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $placeholder ? "placeholder=$placeholder" : '' }}
            {{ $maxlength ? "maxlength=$maxlength" : '' }}
            {{ $attributes->except('class') }}
            class="{{ $inputClasses }} {{ $errorClasses }}"
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }}
            {{ $placeholder ? "placeholder=$placeholder" : '' }}
            {{ $maxlength ? "maxlength=$maxlength" : '' }}
            {{ $attributes->except('class') }}
            class="{{ $inputClasses }} {{ $errorClasses }}"
        >
    @endif

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror

    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif
</div>
