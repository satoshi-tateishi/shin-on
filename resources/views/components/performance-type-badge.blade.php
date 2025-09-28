@props(['type'])

@php
    $performanceType = \App\Enums\PerformanceType::tryFrom($type);
    $badgeClass = $performanceType?->color() ?? 'bg-gray-100 text-gray-800';
    $label = $performanceType?->label() ?? $type;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $badgeClass"]) }}>
    {{ $label }}
</span>