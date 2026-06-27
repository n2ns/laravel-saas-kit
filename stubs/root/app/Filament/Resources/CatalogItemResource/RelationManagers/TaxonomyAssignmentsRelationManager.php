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

    protected static ?string $title = 'Taxonomy tags';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_taxonomy_id')
                    ->label('Taxonomy dimension')
                    ->relationship('taxonomy', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('catalog_taxonomy_term_id', null)),
                Select::make('catalog_taxonomy_term_id')
                    ->label('Taxonomy item')
                    ->options(fn (Get $get): array => CatalogTaxonomyTerm::query()
                        ->where('catalog_taxonomy_id', $get('catalog_taxonomy_id'))
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->searchable(),
                Select::make('source')
                    ->label('Source')
                    ->options([
                        'manual' => 'Manual',
                        'seed' => 'Seed',
                        'factory' => 'Factory',
                    ])
                    ->default('manual'),
                Textarea::make('note')
                    ->label('Notes')
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
                    ->label('Taxonomy dimension')
                    ->badge(),
                TextColumn::make('term.name')
                    ->label('Taxonomy item'),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Last updated')
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
