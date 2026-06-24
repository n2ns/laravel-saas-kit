<?php

namespace App\Filament\Resources\CatalogItemResource\Pages;

use App\Filament\Resources\CatalogItemResource;
use Filament\Resources\Pages\ListRecords;

class ListCatalogItems extends ListRecords
{
    protected static string $resource = CatalogItemResource::class;
}
