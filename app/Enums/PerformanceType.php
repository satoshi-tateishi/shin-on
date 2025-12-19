<?php

namespace App\Enums;

enum PerformanceType: string
{
    case THEATER = '演劇';
    case MUSICAL = 'ミュージカル';
    case READING = 'リーディング';
    case DANCE = 'ダンス';
    case CONCERT = 'コンサート';
    case EVENT = 'イベント';
    case OTHER = 'その他';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::THEATER => 'bg-blue-100 text-blue-800',
            self::MUSICAL => 'bg-purple-100 text-purple-800',
            self::READING => 'bg-amber-100 text-amber-800',
            self::DANCE => 'bg-pink-100 text-pink-800',
            self::CONCERT => 'bg-green-100 text-green-800',
            self::EVENT => 'bg-indigo-100 text-indigo-800',
            self::OTHER => 'bg-gray-100 text-gray-800',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
