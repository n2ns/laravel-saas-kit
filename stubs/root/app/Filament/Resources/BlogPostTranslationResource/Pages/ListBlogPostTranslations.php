<?php

namespace App\Filament\Resources\BlogPostTranslationResource\Pages;

use App\Filament\Resources\BlogPostTranslationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPostTranslations extends ListRecords
{
    protected static string $resource = BlogPostTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
