@props(['title', 'count', 'icon', 'color' => 'blue', 'href' => null, 'active' => false])

@php
    $iconColors = [
        'blue' => 'text-blue-400',
        'orange' => 'text-orange-400',
        'green' => 'text-green-400',
    ];

    $textColors = [
        'blue' => 'text-blue-900',
        'orange' => 'text-orange-900',
        'green' => 'text-green-900',
    ];

    $ringColors = [
        'blue' => 'ring-blue-500',
        'orange' => 'ring-orange-500',
        'green' => 'ring-green-500',
    ];

    $iconColor = $iconColors[$color] ?? $iconColors['blue'];
    $textColor = $textColors[$color] ?? $textColors['blue'];
    $ringColor = $ringColors[$color] ?? $ringColors['blue'];

    $baseClasses = 'block bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow';
    $activeClasses = $active ? "ring-2 $ringColor" : '';

    $tag = $href ? 'a' : 'div';
    $attributes = $href ? $attributes->merge(['href' => $href]) : $attributes;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => "$baseClasses $activeClasses"]) }}>
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $icon !!}
                </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">{{ $title }}</dt>
                    <dd class="text-lg font-medium {{ $textColor }}">{{ $count }}</dd>
                </dl>
            </div>
        </div>
    </div>
</{{ $tag }}>