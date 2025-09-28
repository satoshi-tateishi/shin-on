<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case RESERVED = 'reserved';
    case CHECKED_OUT = 'checked_out';
    case CHECKED_IN = 'checked_in';

    public function label(): string
    {
        return match ($this) {
            self::RESERVED => '予約済み',
            self::CHECKED_OUT => '出庫中',
            self::CHECKED_IN => '返却済み',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RESERVED => 'bg-blue-100 text-blue-800',
            self::CHECKED_OUT => 'bg-orange-100 text-orange-800',
            self::CHECKED_IN => 'bg-green-100 text-green-800',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}