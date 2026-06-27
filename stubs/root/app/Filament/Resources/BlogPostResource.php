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

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $breadcrumb = 'Blog';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?string $modelLabel = 'Blog post';

    protected static ?string $pluralModelLabel = 'Blog post';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 4,
            ])
            ->components([
                Section::make('Blog post content')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
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
                            ->label('Slug')
                            ->required()
                            ->unique(BlogPost::class, 'slug', ignoreRecord: true),

                        Textarea::make('excerpt')
                            ->label('Excerpt')
                            ->rows(4),

                        MarkdownEditor::make('content')
                            ->label('Body content')
                            ->minHeight('36rem')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 3,
                    ]),

                Section::make('Settings')
                    ->schema([
                        Select::make('type')
                            ->label('Post type')
                            ->options(BlogPost::typeOptions('en'))
                            ->required()
                            ->default(BlogPost::defaultType()),

                        Select::make('status')
                            ->label('Publication status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),

                        DateTimePicker::make('published_at')
                            ->label('Published at')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i:s'),

                        Toggle::make('is_pinned')
                            ->label('Pinned')
                            ->default(false),

                        TextInput::make('pin_order')
                            ->label('Pin order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Lower numbers appear first; only applies to pinned posts.'),

                        DateTimePicker::make('pinned_until')
                            ->label('Pinned until')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i:s'),

                        TagsInput::make('topics')
                            ->label('Topics')
                            ->suggestions(BlogPost::topicOptions('en'))
                            ->placeholder('Enter or choose topics'),

                        TagsInput::make('geo_tags')
                            ->label('Region codes')
                            ->placeholder('For example US, GB, SG')
                            ->helperText('Optional ISO 3166-1 alpha-2 country or region codes for SEO and related recommendations.'),

                        TagsInput::make('seo_keywords')
                            ->label('SEO keywords')
                            ->placeholder('Enter keywords'),

                        TagsInput::make('related_slugs')
                            ->label('Related post slugs')
                            ->placeholder('Enter related post slugs'),

                        FileUpload::make('thumbnail')
                            ->label('Cover image')
                            ->image()
                            ->disk('public')
                            ->helperText('Use a 16:9 landscape image such as 1200x675 or 1600x900. Square images will be cropped in frontend cover areas.')
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
                    ->label('Cover')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->url(fn (BlogPost $record): ?string => static::getPublishedArticleUrl($record), true),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'guide' => 'primary',
                        'announcement' => 'success',
                        'changelog' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => BlogPost::typeOptions('en')[$state] ?? $state),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'published' => 'Published',
                    }),
                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('pin_order')
                    ->label('Pin order')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('views_total')
                    ->label('Views')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('views_7d')
                    ->label('7 days')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state))
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (BlogPost $record): bool => $record->status === 'draft')
                    ->action(function (BlogPost $record): void {
                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Post published')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->label('Edit')
                    ->url(fn (BlogPost $record): string => static::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->label('Delete'),
                Action::make('preview')
                    ->label('Preview')
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
                        ->label('Delete selected'),
                ])->label('Bulk actions'),
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
