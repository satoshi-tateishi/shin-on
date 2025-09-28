<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case UPCOMING = 'upcoming';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => '予定',
            self::IN_PROGRESS => '進行中',
            self::COMPLETED => '完了',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UPCOMING => 'bg-gray-100 text-gray-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::COMPLETED => 'bg-green-100 text-green-800',
        };
    }
}