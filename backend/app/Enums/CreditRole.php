<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CreditRole: string implements HasLabel
{
    case Actor = 'actor';
    case Director = 'director';
    case Playwright = 'playwright';
    case Translator = 'translator';
    case SetDesigner = 'set_designer';
    case CostumeDesigner = 'costume_designer';
    case Composer = 'composer';
    case Choreographer = 'choreographer';
    case LightingDesigner = 'lighting_designer';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Actor => 'Актьор',
            self::Director => 'Режисьор',
            self::Playwright => 'Драматург',
            self::Translator => 'Превод',
            self::SetDesigner => 'Сценография',
            self::CostumeDesigner => 'Костюми',
            self::Composer => 'Музика',
            self::Choreographer => 'Хореография',
            self::LightingDesigner => 'Светлинен дизайн',
            self::Other => 'Друго',
        };
    }
}
