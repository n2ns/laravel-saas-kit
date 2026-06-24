<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageDisplayResource\Pages\EditHomepageDisplay;
use App\Filament\Resources\HomepageDisplayResource\Pages\ListHomepageDisplays;
use App\Models\CatalogItem;
use App\Models\CatalogTaxonomyTerm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HomepageDisplayResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|UnitEnum|null $navigationGroup = '产品管理';

    protected static ?string $navigationLabel = '首页展示';

    protected static ?string $modelLabel = '首页展示';

    protected static ?string $pluralModelLabel = '首页展示';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('首页展示')
                    ->schema([
                        Placeholder::make('code')
                            ->label('资料编码')
                            ->content(fn (?CatalogItem $record): string => $record?->code ?? '-'),
                        Placeholder::make('display_name')
                            ->label('名称')
                            ->content(fn (?CatalogItem $record): string => self::displayName($record)),
                        Toggle::make('show_on_homepage')
                            ->label('首页展示'),
                        TextInput::make('homepage_sort_order')
                            ->label('首页排序')
                            ->numeric()
                            ->helperText('数字越小越靠前；为空时作为兜底排序。'),
                        Toggle::make('is_visible')
                            ->label('公开可见')
                            ->helperText('首页只读取公开且已发布的产品资料。'),
                    ])
                    ->columns(2),
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
                IconColumn::make('is_visible')
                    ->label('公开')
                    ->boolean(),
                IconColumn::make('status')
                    ->label('已发布')
                    ->boolean()
                    ->getStateUsing(fn (CatalogItem $record): bool => $record->status === CatalogItem::STATUS_PUBLISHED),
                ToggleColumn::make('show_on_homepage')
                    ->label('首页展示'),
                TextInputColumn::make('homepage_sort_order')
                    ->label('首页排序')
                    ->type('number')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('列表排序')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('homepage_sort_order')
            ->filters([
                TernaryFilter::make('show_on_homepage')
                    ->label('首页展示'),
                TernaryFilter::make('is_visible')
                    ->label('公开可见'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('编辑'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageDisplays::route('/'),
            'edit' => EditHomepageDisplay::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'taxonomyTerms.taxonomy']);
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
}
