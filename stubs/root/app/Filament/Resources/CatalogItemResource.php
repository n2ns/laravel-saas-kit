<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogItemResource\Pages\EditCatalogItem;
use App\Filament\Resources\CatalogItemResource\Pages\ListCatalogItems;
use App\Filament\Resources\CatalogItemResource\RelationManagers\DetailTranslationsRelationManager;
use App\Filament\Resources\CatalogItemResource\RelationManagers\PrivacyPoliciesRelationManager;
use App\Filament\Resources\CatalogItemResource\RelationManagers\TaxonomyAssignmentsRelationManager;
use App\Filament\Resources\CatalogItemResource\RelationManagers\TranslationsRelationManager;
use App\Models\CatalogItem;
use App\Models\CatalogTaxonomyTerm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = '产品管理';

    protected static ?string $navigationLabel = '产品资料';

    protected static ?string $modelLabel = '产品资料';

    protected static ?string $pluralModelLabel = '产品资料';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('基础资料')
                            ->schema([
                                TextInput::make('code')
                                    ->label('资料编码')
                                    ->disabled(),
                                Select::make('status')
                                    ->label('内容状态')
                                    ->options([
                                        CatalogItem::STATUS_DRAFT => '草稿',
                                        CatalogItem::STATUS_PUBLISHED => '已发布',
                                        CatalogItem::STATUS_ARCHIVED => '已归档',
                                    ])
                                    ->required(),
                                Select::make('primary_group_term_id')
                                    ->label('一级分类')
                                    ->options(fn (): array => self::primaryGroupOptions())
                                    ->searchable()
                                    ->required(),
                                Toggle::make('is_visible')
                                    ->label('公开可见'),
                                TextInput::make('sort_order')
                                    ->label('列表排序')
                                    ->numeric()
                                    ->default(0),
                                DateTimePicker::make('published_at')
                                    ->label('发布时间'),
                            ])
                            ->columns(2),

                        Section::make('展示资料')
                            ->relationship('profile')
                            ->schema([
                                TextInput::make('product_type')
                                    ->label('交付形态')
                                    ->maxLength(50),
                                TextInput::make('segment')
                                    ->label('业务分组')
                                    ->maxLength(100),
                                TextInput::make('theme_profile')
                                    ->label('主题 Profile')
                                    ->maxLength(100),
                                TextInput::make('version')
                                    ->label('版本')
                                    ->maxLength(50),
                                Select::make('release_status')
                                    ->label('发布状态')
                                    ->options([
                                        'stable' => 'Stable',
                                        'beta' => 'Beta',
                                        'alpha' => 'Alpha',
                                        'preview' => 'Preview',
                                        'draft' => 'Draft',
                                    ]),
                                Select::make('development_status')
                                    ->label('开发状态')
                                    ->options([
                                        'launched' => '已上线',
                                        CatalogItem::DEVELOPMENT_SHELVED => '搁置',
                                    ]),
                                TextInput::make('image')
                                    ->label('主图')
                                    ->maxLength(255),
                                TextInput::make('thumbnail')
                                    ->label('缩略图')
                                    ->maxLength(255),
                                TextInput::make('icon')
                                    ->label('图标')
                                    ->maxLength(255),
                                Textarea::make('links')
                                    ->label('链接 JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('facts')
                                    ->label('事实 JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('media')
                                    ->label('媒体 JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('aliases')
                                    ->label('别名 JSON')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json']),
                                Textarea::make('seo_payload')
                                    ->label('SEO JSON')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json']),
                            ])
                            ->columns(2),

                        Section::make('详情模板')
                            ->relationship('detail')
                            ->schema([
                                TextInput::make('template_key')
                                    ->label('模板 Key')
                                    ->maxLength(100),
                                TextInput::make('schema_version')
                                    ->label('Schema 版本')
                                    ->numeric()
                                    ->default(1),
                                Textarea::make('structure_payload')
                                    ->label('结构 JSON')
                                    ->rows(12)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsed(),

                        Section::make('首页展示')
                            ->schema([
                                Toggle::make('show_on_homepage')
                                    ->label('首页展示')
                                    ->helperText('开启后进入首页产品区。'),
                                TextInput::make('homepage_sort_order')
                                    ->label('首页排序')
                                    ->numeric()
                                    ->helperText('数字越小越靠前；为空时作为兜底排序。'),
                            ])
                            ->columns(2),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('资料摘要')
                            ->schema([
                                Placeholder::make('display_name')
                                    ->label('名称')
                                    ->content(fn (?CatalogItem $record): string => self::displayName($record)),
                                Placeholder::make('primary_group')
                                    ->label('一级分类')
                                    ->content(fn (?CatalogItem $record): string => self::primaryGroupLabel($record)),
                                Placeholder::make('updated_at')
                                    ->label('最后修改')
                                    ->content(fn (?CatalogItem $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ])
                            ->visible(fn ($record) => $record !== null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('code')
                    ->label('资料编码')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('名称')
                    ->getStateUsing(fn (CatalogItem $record): string => self::displayName($record)),
                TextColumn::make('primary_group')
                    ->label('一级分类')
                    ->badge()
                    ->getStateUsing(fn (CatalogItem $record): string => self::primaryGroupLabel($record)),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CatalogItem::STATUS_DRAFT => '草稿',
                        CatalogItem::STATUS_PUBLISHED => '已发布',
                        CatalogItem::STATUS_ARCHIVED => '已归档',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CatalogItem::STATUS_PUBLISHED => 'success',
                        CatalogItem::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('is_visible')
                    ->label('公开')
                    ->boolean(),
                IconColumn::make('show_on_homepage')
                    ->label('首页')
                    ->boolean(),
                TextColumn::make('homepage_sort_order')
                    ->label('首页排序')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('列表排序')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('最后更新')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('status')
                    ->label('内容状态')
                    ->options([
                        CatalogItem::STATUS_DRAFT => '草稿',
                        CatalogItem::STATUS_PUBLISHED => '已发布',
                        CatalogItem::STATUS_ARCHIVED => '已归档',
                    ]),
                TernaryFilter::make('is_visible')
                    ->label('公开可见'),
                TernaryFilter::make('show_on_homepage')
                    ->label('首页展示'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('编辑'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TranslationsRelationManager::class,
            DetailTranslationsRelationManager::class,
            PrivacyPoliciesRelationManager::class,
            TaxonomyAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogItems::route('/'),
            'edit' => EditCatalogItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['profile', 'detail', 'translations', 'taxonomyTerms.taxonomy']);
    }

    private static function displayName(?CatalogItem $record): string
    {
        return $record?->getLocalized('name') ?: '-';
    }

    private static function primaryGroupLabel(?CatalogItem $record): string
    {
        if (! $record instanceof CatalogItem) {
            return '-';
        }

        $term = $record->relationLoaded('taxonomyTerms')
            ? $record->taxonomyTerms->first(
                fn (CatalogTaxonomyTerm $term): bool => $term->taxonomy?->code === 'primary_group'
            )
            : $record->taxonomyTerms()
                ->whereHas('taxonomy', fn (Builder $query) => $query->where('code', 'primary_group'))
                ->first();

        return $term?->name ?? $term?->code ?? '-';
    }

    public static function primaryGroupTermId(?CatalogItem $record): ?int
    {
        if (! $record instanceof CatalogItem) {
            return null;
        }

        $term = $record->relationLoaded('taxonomyTerms')
            ? $record->taxonomyTerms->first(
                fn (CatalogTaxonomyTerm $term): bool => $term->taxonomy?->code === 'primary_group'
            )
            : $record->taxonomyTerms()
                ->whereHas('taxonomy', fn (Builder $query) => $query->where('code', 'primary_group'))
                ->first();

        return $term?->id;
    }

    public static function primaryGroupOptions(): array
    {
        return CatalogTaxonomyTerm::query()
            ->whereHas('taxonomy', fn (Builder $query) => $query->where('code', 'primary_group'))
            ->where('code', '!=', 'concept_project')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function formatJson(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_string($state)) {
            return $state;
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function decodeJson(mixed $state): ?array
    {
        if ($state === null || trim((string) $state) === '') {
            return null;
        }

        $decoded = json_decode((string) $state, true);

        return is_array($decoded) ? $decoded : null;
    }
}
