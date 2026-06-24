<?php

namespace App\Filament\Resources\CatalogItemResource\RelationManagers;

use App\Models\CatalogTaxonomyTerm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxonomyAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'taxonomyAssignments';

    protected static ?string $title = '分类标签';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_taxonomy_id')
                    ->label('分类维度')
                    ->relationship('taxonomy', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('catalog_taxonomy_term_id', null)),
                Select::make('catalog_taxonomy_term_id')
                    ->label('分类项')
                    ->options(fn (Get $get): array => CatalogTaxonomyTerm::query()
                        ->where('catalog_taxonomy_id', $get('catalog_taxonomy_id'))
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->searchable(),
                Select::make('source')
                    ->label('来源')
                    ->options([
                        'manual' => 'Manual',
                        'seed' => 'Seed',
                        'factory' => 'Factory',
                    ])
                    ->default('manual'),
                Textarea::make('note')
                    ->label('备注')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('taxonomy.name')
                    ->label('分类维度')
                    ->badge(),
                TextColumn::make('term.name')
                    ->label('分类项'),
                TextColumn::make('source')
                    ->label('来源')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('最后更新')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
