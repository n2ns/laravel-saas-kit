<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostTranslationResource\Pages\CreateBlogPostTranslation;
use App\Filament\Resources\BlogPostTranslationResource\Pages\EditBlogPostTranslation;
use App\Filament\Resources\BlogPostTranslationResource\Pages\ListBlogPostTranslations;
use App\Models\BlogPostTranslation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BlogPostTranslationResource extends Resource
{
    protected static ?string $model = BlogPostTranslation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $navigationLabel = '文章翻译';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = '文章翻译';

    protected static ?string $pluralModelLabel = '博客文章翻译';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('blog_post_id')
                    ->label('博客文章')
                    ->relationship('blogPost', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('locale')
                    ->label('语言')
                    ->options([
                        'zh_CN' => '中文 (Chinese)',
                        'es' => 'Español (Spanish)',
                        'de' => 'Deutsch (German)',
                    ])
                    ->required(),
                TextInput::make('title')
                    ->label('标题')
                    ->maxLength(255),
                MarkdownEditor::make('content')
                    ->label('正文')
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->label('摘要')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('blogPost:id,title'))
            ->columns([
                TextColumn::make('blogPost.title')
                    ->label('原始文章')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('locale')
                    ->label('语言')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'zh_CN' => 'danger',
                        'es' => 'warning',
                        'de' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->label('按语言过滤')
                    ->options([
                        'zh_CN' => '中文',
                        'es' => 'Español',
                        'de' => 'Deutsch',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPostTranslations::route('/'),
            'create' => CreateBlogPostTranslation::route('/create'),
            'edit' => EditBlogPostTranslation::route('/{record}/edit'),
        ];
    }
}
