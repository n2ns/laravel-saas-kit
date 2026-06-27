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

    protected static string|UnitEnum|null $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Catalog Items';

    protected static ?string $modelLabel = 'Catalog Items';

    protected static ?string $pluralModelLabel = 'Catalog Items';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Base details')
                            ->schema([
                                TextInput::make('code')
                                    ->label('Item code')
                                    ->disabled(),
                                Select::make('status')
                                    ->label('Content status')
                                    ->options([
                                        CatalogItem::STATUS_DRAFT => 'Draft',
                                        CatalogItem::STATUS_PUBLISHED => 'Published',
                                        CatalogItem::STATUS_ARCHIVED => 'Archived',
                                    ])
                                    ->required(),
                                Select::make('primary_group_term_id')
                                    ->label('Primary category')
                                    ->options(fn (): array => self::primaryGroupOptions())
                                    ->searchable()
                                    ->required(),
                                Toggle::make('is_visible')
                                    ->label('Public'),
                                TextInput::make('sort_order')
                                    ->label('List order')
                                    ->numeric()
                                    ->default(0),
                                DateTimePicker::make('published_at')
                                    ->label('Published at'),
                            ])
                            ->columns(2),

                        Section::make('Display details')
                            ->relationship('profile')
                            ->schema([
                                TextInput::make('product_type')
                                    ->label('Delivery type')
                                    ->maxLength(50),
                                TextInput::make('segment')
                                    ->label('Business group')
                                    ->maxLength(100),
                                TextInput::make('theme_profile')
                                    ->label('Theme profile')
                                    ->maxLength(100),
                                TextInput::make('version')
                                    ->label('Version')
                                    ->maxLength(50),
                                Select::make('release_status')
                                    ->label('Publication status')
                                    ->options([
                                        'stable' => 'Stable',
                                        'beta' => 'Beta',
                                        'alpha' => 'Alpha',
                                        'preview' => 'Preview',
                                        'draft' => 'Draft',
                                    ]),
                                Select::make('development_status')
                                    ->label('Development status')
                                    ->options([
                                        'launched' => 'Launched',
                                        CatalogItem::DEVELOPMENT_SHELVED => 'Shelved',
                                    ]),
                                TextInput::make('image')
                                    ->label('Hero image')
                                    ->maxLength(255),
                                TextInput::make('thumbnail')
                                    ->label('Thumbnail')
                                    ->maxLength(255),
                                TextInput::make('icon')
                                    ->label('Icon')
                                    ->maxLength(255),
                                Textarea::make('links')
                                    ->label('Links JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('facts')
                                    ->label('Facts JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('media')
                                    ->label('Media JSON')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                                Textarea::make('aliases')
                                    ->label('Aliases JSON')
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

                        Section::make('Detail template')
                            ->relationship('detail')
                            ->schema([
                                TextInput::make('template_key')
                                    ->label('Template key')
                                    ->maxLength(100),
                                TextInput::make('schema_version')
                                    ->label('Schema Version')
                                    ->numeric()
                                    ->default(1),
                                Textarea::make('structure_payload')
                                    ->label('Structure JSON')
                                    ->rows(12)
                                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                                    ->rules(['nullable', 'json'])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsed(),

                        Section::make('Homepage display')
                            ->schema([
                                Toggle::make('show_on_homepage')
                                    ->label('Homepage display')
                                    ->helperText('When enabled, this item appears in the homepage product section.'),
                                TextInput::make('homepage_sort_order')
                                    ->label('Homepage order')
                                    ->numeric()
                                    ->helperText('Lower numbers appear first; blank values are used as fallback order.'),
                            ])
                            ->columns(2),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Item summary')
                            ->schema([
                                Placeholder::make('display_name')
                                    ->label('Name')
                                    ->content(fn (?CatalogItem $record): string => self::displayName($record)),
                                Placeholder::make('primary_group')
                                    ->label('Primary category')
                                    ->content(fn (?CatalogItem $record): string => self::primaryGroupLabel($record)),
                                Placeholder::make('updated_at')
                                    ->label('Last modified')
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
                    ->label('Item code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Name')
                    ->getStateUsing(fn (CatalogItem $record): string => self::displayName($record)),
                TextColumn::make('primary_group')
                    ->label('Primary category')
                    ->badge()
                    ->getStateUsing(fn (CatalogItem $record): string => self::primaryGroupLabel($record)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CatalogItem::STATUS_DRAFT => 'Draft',
                        CatalogItem::STATUS_PUBLISHED => 'Published',
                        CatalogItem::STATUS_ARCHIVED => 'Archived',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CatalogItem::STATUS_PUBLISHED => 'success',
                        CatalogItem::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('is_visible')
                    ->label('Public')
                    ->boolean(),
                IconColumn::make('show_on_homepage')
                    ->label('Homepage')
                    ->boolean(),
                TextColumn::make('homepage_sort_order')
                    ->label('Homepage order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('List order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('status')
                    ->label('Content status')
                    ->options([
                        CatalogItem::STATUS_DRAFT => 'Draft',
                        CatalogItem::STATUS_PUBLISHED => 'Published',
                        CatalogItem::STATUS_ARCHIVED => 'Archived',
                    ]),
                TernaryFilter::make('is_visible')
                    ->label('Public'),
                TernaryFilter::make('show_on_homepage')
                    ->label('Homepage display'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit'),
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
