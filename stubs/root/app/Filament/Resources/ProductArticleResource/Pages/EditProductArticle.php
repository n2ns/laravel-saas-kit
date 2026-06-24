<?php

namespace App\Filament\Resources\ProductArticleResource\Pages;

use App\Filament\Resources\ProductArticleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProductArticle extends EditRecord
{
    protected static string $resource = ProductArticleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('预览')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('admin.product-articles.preview', [
                    'blogPost' => $this->record,
                    'locale' => app()->getLocale(),
                ]))
                ->openUrlInNewTab(),
            Action::make('save')
                ->label('保存')
                ->icon('heroicon-o-check')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save(shouldRedirect: false)),
            DeleteAction::make()->label('删除'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '产品文章更新成功';
    }

    protected function getValidationErrorNotificationTitle(): ?string
    {
        return '保存失败：表单内容有误';
    }
}
