@props(['title', 'count', 'icon', 'color' => 'blue', 'href' => null, 'active' => false])

@php
    $iconColors = [
        'blue' => 'text-blue-400',
        'orange' => 'text-orange-400',
        'green' => 'text-green-400',
    ];

    $textColors = [
        'blue' => 'text-blue-900 dark:text-blue-300',
        'orange' => 'text-orange-900 dark:text-orange-300',
        'green' => 'text-green-900 dark:text-green-300',
    ];

    $ringColors = [
        'blue' => 'ring-blue-500',
        'orange' => 'ring-orange-500',
        'green' => 'ring-green-500',
    ];

    $iconColor = $iconColors[$color] ?? $iconColors['blue'];
    $textColor = $textColors[$color] ?? $textColors['blue'];
    $ringColor = $ringColors[$color] ?? $ringColors['blue'];

    $baseClasses = 'block bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700';
    $activeClasses = $active ? "ring-2 $ringColor" : '';

    $tag = $href ? 'a' : 'div';
    $attributes = $href ? $attributes->merge(['href' => $href]) : $attributes;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => "$baseClasses $activeClasses"]) }}>
    <div class="p-3 sm:p-5">
        <!-- モバイル: 2行表示 -->
        <div class="flex flex-col items-center text-center sm:hidden">
            <svg class="h-5 w-5 {{ $iconColor }} mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icon !!}
            </svg>
            <div class="text-xs">
                <span class="font-medium text-gray-500 dark:text-gray-400">{{ $title }}</span>
                <span class="font-medium {{ $textColor }} ml-1">{{ $count }}</span>
            </div>
        </div>
        <!-- デスクトップ: 横並び -->
        <div class="hidden sm:flex sm:items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $icon !!}
                </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $title }}</dt>
                    <dd class="text-lg font-medium {{ $textColor }}">{{ $count }}</dd>
                </dl>
            </div>
        </div>
    </div>
</{{ $tag }}>