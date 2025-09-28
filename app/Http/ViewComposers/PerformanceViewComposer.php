<?php

namespace App\Http\ViewComposers;

use App\Enums\PerformanceStatus;
use App\Enums\PerformanceType;
use Illuminate\View\View;

class PerformanceViewComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'performanceTypes' => PerformanceType::options(),
            'performanceStatuses' => PerformanceStatus::options(),
        ]);
    }
}