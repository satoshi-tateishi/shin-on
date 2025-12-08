@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    // Base classes
    $baseClasses = 'inline-flex items-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';

    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm',
        'lg' => 'px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base',
        default => 'px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm',
    };

    // Variant classes
    $variantClasses = match($variant) {
        'primary' => 'bg-blue-600 border border-transparent text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500',
        'success' => 'bg-green-600 border border-transparent text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 border border-transparent text-white hover:bg-red-700 focus:ring-red-500',
        'purple' => 'bg-purple-600 border border-transparent text-white hover:bg-purple-700 focus:ring-purple-500',
        'warning' => 'bg-yellow-500 border border-transparent text-white hover:bg-yellow-600 focus:ring-yellow-500',
        default => 'bg-blue-600 border border-transparent text-white hover:bg-blue-700 focus:ring-blue-500',
    };

    // Disabled classes
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses} {$disabledClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif
