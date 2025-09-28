@props(['status', 'type' => 'performance'])

@php
    $badgeClass = '';
    $label = '';

    if ($type === 'performance') {
        $performanceStatus = \App\Enums\PerformanceStatus::tryFrom($status);
        $badgeClass = $performanceStatus?->color() ?? 'bg-gray-100 text-gray-800';
        $label = $performanceStatus?->label() ?? $status;
    } elseif ($type === 'phase') {
        $phaseStatus = \App\Enums\PhaseStatus::tryFrom($status);
        $badgeClass = $phaseStatus?->color() ?? 'bg-gray-100 text-gray-800';
        $label = $phaseStatus?->label() ?? $status;
    } elseif ($type === 'equipment') {
        $equipmentStatus = \App\Enums\EquipmentStatus::tryFrom($status);
        $badgeClass = $equipmentStatus?->color() ?? 'bg-gray-100 text-gray-800';
        $label = $equipmentStatus?->label() ?? $status;
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $badgeClass"]) }}>
    {{ $label }}
</span>