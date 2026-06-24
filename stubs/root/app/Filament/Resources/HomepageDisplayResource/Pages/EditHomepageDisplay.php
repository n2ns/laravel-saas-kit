<?php

namespace App\Filament\Resources\HomepageDisplayResource\Pages;

use App\Filament\Resources\HomepageDisplayResource;
use Filament\Resources\Pages\EditRecord;

class EditHomepageDisplay extends EditRecord
{
    protected static string $resource = HomepageDisplayResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return '首页展示设置已更新';
    }
}
