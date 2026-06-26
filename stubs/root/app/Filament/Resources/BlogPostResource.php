<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages\EditBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\ManageBlogPosts;
use App\Models\BlogPost;
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
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class BlogPostResource extends Resource
{
    use Translatable;

    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $breadcrumb = 'Blog';

    protected static ?string $navigationLabel = '博客';

    protected static ?string $modelLabel = '博客文章';

    protected static ?string $pluralModelLabel = '博客文章';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 4,
            ])
            ->components([
                Section::make('博客文章内容')
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
                        Select::make('type')
                            ->label('文章类型')
                            ->options(BlogPost::typeOptions('zh_CN'))
                            ->required()
                            ->default(BlogPost::defaultType()),

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

                        Toggle::make('is_pinned')
                            ->label('置顶')
                            ->default(false),

                        TextInput::make('pin_order')
                            ->label('置顶排序')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('数字越小越靠前，仅对置顶文章生效。'),

                        DateTimePicker::make('pinned_until')
                            ->label('置顶截止时间')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i:s'),

                        TagsInput::make('topics')
                            ->label('主题标签')
                            ->suggestions(BlogPost::topicOptions('zh_CN'))
                            ->placeholder('输入或选择主题标签'),

                        TagsInput::make('geo_tags')
                            ->label('地区代码')
                            ->placeholder('例如 US、GB、SG')
                            ->helperText('可选 ISO 3166-1 alpha-2 国家/地区代码，用于 SEO 和相关推荐。'),

                        TagsInput::make('seo_keywords')
                            ->label('SEO 关键词')
                            ->placeholder('输入关键词'),

                        TagsInput::make('related_slugs')
                            ->label('相关文章 Slug')
                            ->placeholder('输入相关文章 slug'),

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
                TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'guide' => 'primary',
                        'announcement' => 'success',
                        'changelog' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => BlogPost::typeOptions('zh_CN')[$state] ?? $state),
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
                IconColumn::make('is_pinned')
                    ->label('置顶')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('pin_order')
                    ->label('置顶排序')
                    ->sortable(),
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
            ->filters([])
            ->recordActions([
                Action::make('publish')
                    ->label('发布')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (BlogPost $record): bool => $record->status === 'draft')
                    ->action(function (BlogPost $record): void {
                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);

                        Notification::make()
                            ->title('文章已发布')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->label('编辑')
                    ->url(fn (BlogPost $record): string => static::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->label('删除'),
                Action::make('preview')
                    ->label('预览')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (BlogPost $record): string => route('admin.blog-posts.preview', [
                        'blogPost' => $record,
                        'locale' => app()->getLocale(),
                    ]))
                    ->openUrlInNewTab(),
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
            'index' => ManageBlogPosts::route('/'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('viewDailyStats as views_total', 'views')
            ->withSum([
                'viewDailyStats as views_7d' => fn (Builder $query) => $query
                    ->where('visit_date', '>=', now()->subDays(6)->toDateString()),
            ], 'views');
    }

    private static function getPublishedArticleUrl(BlogPost $record): ?string
    {
        if ($record->status !== 'published' || ! $record->published_at?->lte(now())) {
            return null;
        }

        return localized_route('blog.show', [
            'locale' => 'en',
            'slug' => $record->slug,
        ]);
    }
}
