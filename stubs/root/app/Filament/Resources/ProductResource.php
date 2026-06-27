<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers\PlansRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\TranslationsRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Product Listings';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // 2:1 layout
            ->components([
                // Main content column
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Basic information')
                            ->schema([
                                Select::make('catalog_item_id')
                                    ->label('Catalog Items')
                                    ->relationship('catalogItem', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('code')
                                    ->label('Product code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Placeholder::make('catalog_name')
                                    ->label('Catalog item name')
                                    ->content(fn (?Product $record): string => $record?->name ?? '-'),
                            ])->columns(2),
                    ]),

                // Sidebar
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status and sorting')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Enabled')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Display weight')
                                    ->numeric()
                                    ->default(0),
                                Select::make('pause_reason')
                                    ->label('Subscription control')
                                    ->options([
                                        null => '✅ Normal (subscriptions allowed)',
                                        'maintenance' => '🔧 Maintenance',
                                        'payment_upgrade' => '💳 Payment system upgrade',
                                        'coming_soon' => '🚀 Coming soon',
                                        'region_restricted' => '🌍 Region restricted',
                                    ])
                                    ->placeholder('Normal')
                                    ->helperText('Set a non-empty value to pause new subscriptions'),
                            ]),

                        Section::make('Advanced integrations')
                            ->schema([
                                TextInput::make('stripe_product_id')
                                    ->label('Stripe product ID')
                                    ->maxLength(255),
                                TextInput::make('pricing_page_url')
                                    ->label('External pricing page')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('mcp_server_url')
                                    ->label('MCP server URL')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('mcp_api_key')
                                    ->label('MCP API Key')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255),
                                Textarea::make('metadata')
                                    ->label('Metadata (JSON)')
                                    ->helperText('Additional JSON data such as source metadata or metrics')
                                    ->rows(5)
                                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                    ->dehydrateStateUsing(fn ($state) => json_decode($state, true) ?? []),
                            ])->collapsed(),

                        Section::make('Record summary')
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('Created at')
                                    ->content(fn (Product $record): ?string => $record->created_at?->translatedFormat('Y-m-d H:i') ?? '-'),

                                Placeholder::make('updated_at')
                                    ->label('Last modified')
                                    ->content(fn (Product $record): ?string => $record->updated_at?->diffForHumans() ?? '-'),
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
                    ->label('Product code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('card_tag')
                    ->label('Tags'),
                IconColumn::make('is_active')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pause_reason')
                    ->label('Subscription status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'maintenance' => '🔧 Maintenance',
                        'payment_upgrade' => '💳 Upgrade',
                        'coming_soon' => '🚀 Coming soon',
                        'region_restricted' => '🌍 Restricted',
                        default => $state,
                    } : '✅ Normal')
                    ->color(fn (?string $state): string => $state ? 'warning' : 'success'),
                TextColumn::make('translations_count')
                    ->label('Translations')
                    ->counts('translations'),
                TextColumn::make('plans_count')
                    ->label('Plans')
                    ->counts('plans'),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Filter by status'),
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
            PlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withLocalizedTranslations(app()->getLocale());
    }
}
