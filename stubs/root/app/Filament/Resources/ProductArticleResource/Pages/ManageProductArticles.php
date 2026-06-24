<?php

namespace App\Filament\Resources\ProductArticleResource\Pages;

use App\Filament\Resources\ProductArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageProductArticles extends ManageRecords
{
    use Translatable;

    protected static string $resource = ProductArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make()->label('新建产品文章'),
        ];
    }
}
