<?php

namespace App\Enums;

enum DriverStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'Неактивен',
            self::ACTIVE   => 'Активен',
        };
    }
}
