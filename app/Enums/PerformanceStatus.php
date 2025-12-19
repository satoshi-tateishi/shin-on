<?php

namespace App\Enums;

enum PerformanceStatus: string
{
    case PLANNING = 'planning';
    case PREPARATION = 'preparation';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => '企画中',
            self::PREPARATION => '準備中',
            self::IN_PROGRESS => '進行中',
            self::COMPLETED => '完了',
            self::CANCELLED => 'キャンセル',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'bg-yellow-100 text-yellow-800',
            self::PREPARATION => 'bg-blue-100 text-blue-800',
            self::IN_PROGRESS => 'bg-green-100 text-green-800',
            self::COMPLETED => 'bg-gray-100 text-gray-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
