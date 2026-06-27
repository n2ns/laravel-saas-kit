<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Product created';
    }

    protected function getValidationErrorNotificationTitle(): ?string
    {
        return 'Create failed: please check the form input';
    }
}
