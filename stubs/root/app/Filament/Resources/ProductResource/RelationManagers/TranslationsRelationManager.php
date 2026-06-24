<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TranslationsRelationManager extends RelationManager
{
    protected static string $relationship = 'translations';

    protected static ?string $recordTitleAttribute = 'locale';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->options([
                        'en' => 'English',
                        'zh_CN' => '中文 (Chinese)',
                        'es' => 'Español (Spanish)',
                        'de' => 'Deutsch (German)',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $livewire) => $rule->where('catalog_item_id', $livewire->ownerRecord->catalog_item_id)
                    ),

                Section::make('Basic Translations')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255),
                        Textarea::make('short_description')
                            ->label('Subtitle')
                            ->rows(2),
                        TextInput::make('card_tag')
                            ->label('Card Tag'),
                        TextInput::make('cta_label')
                            ->label('CTA Label'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('locale')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'en' => 'success',
                        'zh_CN' => 'danger',
                        'es' => 'warning',
                        'de' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('name')
                    ->limit(30),
                TextColumn::make('card_tag')
                    ->label('Tag')
                    ->limit(20),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
