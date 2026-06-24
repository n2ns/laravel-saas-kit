<?php

namespace App\Filament\Resources\BlogPostTranslationResource\Pages;

use App\Filament\Resources\BlogPostTranslationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPostTranslation extends EditRecord
{
    protected static string $resource = BlogPostTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
