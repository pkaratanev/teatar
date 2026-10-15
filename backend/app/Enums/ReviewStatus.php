<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReviewStatus: string implements HasLabel
{
    case Visible = 'visible';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Visible => 'Видимо',
            self::Hidden => 'Скрито от модератор',
        };
    }
}
