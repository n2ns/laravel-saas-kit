<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('admin.blog-posts.preview', [
                    'blogPost' => $this->record,
                    'locale' => app()->getLocale(),
                ]))
                ->openUrlInNewTab(),
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save(shouldRedirect: false)),
            DeleteAction::make()->label('Delete'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Blog post updated';
    }

    protected function getValidationErrorNotificationTitle(): ?string
    {
        return 'Save failed: the form contains errors';
    }
}
