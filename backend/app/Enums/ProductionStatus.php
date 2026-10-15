<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductionStatus: string implements HasLabel
{
    case Running = 'running';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Running => 'Играе се',
            self::Closed => 'Свалена от репертоара',
        };
    }
}
