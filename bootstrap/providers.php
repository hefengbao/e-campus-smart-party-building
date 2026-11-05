<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\BranchPanelProvider;
use App\Providers\Filament\CollegePanelProvider;
use App\Providers\Filament\MemberPanelProvider;
use App\Providers\Filament\OrgPanelProvider;
use App\Providers\Filament\SysadminPanelProvider;
use App\Providers\Filament\TestPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    BranchPanelProvider::class,
    CollegePanelProvider::class,
    MemberPanelProvider::class,
    OrgPanelProvider::class,
    SysadminPanelProvider::class,
    TestPanelProvider::class,
    HorizonServiceProvider::class,
];
