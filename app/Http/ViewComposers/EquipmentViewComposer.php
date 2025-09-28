<?php

namespace App\Http\ViewComposers;

use App\Enums\EquipmentStatus;
use Illuminate\View\View;

class EquipmentViewComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'equipmentStatuses' => EquipmentStatus::options(),
        ]);
    }
}