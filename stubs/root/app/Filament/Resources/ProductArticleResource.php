<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductArticleResource\Pages\EditProductArticle;
use App\Filament\Resources\ProductArticleResource\Pages\ManageProductArticles;
use App\Models\BlogPost;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class ProductArticleResource extends Resource
{
    use Translatable;

    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $breadcrumb = 'Product Articles';

    protected static ?string $navigationLabel = '产品文章';

    protected static ?string $modelLabel = '产品文章';

    protected static ?string $pluralModelLabel = '产品文章';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 4,
            ])
            ->components([
                Section::make('产品文章内容')
                    ->schema([
                        TextInput::make('title')
                            ->label('标题')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $slug = Str::slug($state);
                                    if (! empty($slug)) {
                                        $set('slug', $slug);
                                    }
                                }
                            }),

                        TextInput::make('slug')
                            ->label('固定链接 (Slug)')
                            ->required()
                            ->unique(BlogPost::class, 'slug', ignoreRecord: true),

                        Textarea::make('excerpt')
                            ->label('摘要')
                            ->rows(4),

                        MarkdownEditor::make('content')
                            ->label('正文内容')
                            ->minHeight('36rem')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 3,
                    ]),

                Section::make('设置')
                    ->schema([
                        Select::make('content_scope')
                            ->label('所属产品')
                            ->options(fn (): array => Product::query()
                                ->with('catalogItem')
                                ->whereIn('code', Product::SELLABLE_CODES)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (Product $product): array => [BlogPost::productContentScope($product->code) => $product->name])
                                ->all())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('type')
                            ->label('文章类型')
                            ->options([
                                'guide' => '使用指南',
                                'announcement' => '产品公告',
                                'changelog' => '更新日志',
                            ])
                            ->required()
                            ->default('guide'),

                        Select::make('status')
                            ->label('发布状态')
                            ->options([
                                'draft' => '草稿',
                                'published' => '已发布',
                            ])
                            ->required()
                            ->default('draft'),

                        DateTimePicker::make('published_at')
                            ->label('发布时间')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i:s'),

                        FileUpload::make('thumbnail')
                            ->label('封面图片')
                            ->image()
                            ->disk('public')
                            ->helperText('建议上传 16:9 横向图片，例如 1200x675 或 1600x900。正方形图片会在前端封面区域被裁切。')
                            ->directory('blog-post-thumbnails'),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 1,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('封面')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->url(fn (BlogPost $record): ?string => static::getPublishedArticleUrl($record), true),
                TextColumn::make('content_scope')
                    ->label('产品')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('product:', '', $state) : '-'),
                TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'announcement' => 'success',
                        'changelog' => 'warning',
                        'guide' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'announcement' => '产品公告',
                        'changelog' => '更新日志',
                        'guide' => '使用指南',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '草稿',
                        'published' => '已发布',
                    }),
                TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('views_total')
                    ->label('阅读')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('views_7d')
                    ->label('7天')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('content_scope')
                    ->label('所属产品')
                    ->options(fn (): array => Product::query()
                        ->with('catalogItem')
                        ->whereIn('code', Product::SELLABLE_CODES)
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (Product $product): array => [BlogPost::productContentScope($product->code) => $product->name])
                        ->all()),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('预览')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (BlogPost $record): string => route('admin.product-articles.preview', [
                        'blogPost' => $record,
                        'locale' => app()->getLocale(),
                    ]))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label('编辑')
                    ->url(fn (BlogPost $record): string => static::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->label('删除'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('批量删除'),
                ])->label('批量操作'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductArticles::route('/'),
            'edit' => EditProductArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('content_scope', 'like', 'product:%')
            ->withSum('viewDailyStats as views_total', 'views')
            ->withSum([
                'viewDailyStats as views_7d' => fn (Builder $query) => $query
                    ->where('visit_date', '>=', now()->subDays(6)->toDateString()),
            ], 'views');
    }

    private static function getPublishedArticleUrl(BlogPost $record): ?string
    {
        $productCode = $record->productCode();

        if (
            $record->type !== 'guide' ||
            $record->status !== 'published' ||
            ! $record->published_at?->lte(now()) ||
            ! $productCode
        ) {
            return null;
        }

        return localized_route('catalog.guides.show', [
            'locale' => 'en',
            'productCode' => $productCode,
            'slug' => $record->slug,
        ]);
    }
}
