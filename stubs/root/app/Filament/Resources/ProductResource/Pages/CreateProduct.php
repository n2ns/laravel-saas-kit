<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return '产品创建成功';
    }

    protected function getValidationErrorNotificationTitle(): ?string
    {
        return '创建失败：请检查表单输入';
    }
}
