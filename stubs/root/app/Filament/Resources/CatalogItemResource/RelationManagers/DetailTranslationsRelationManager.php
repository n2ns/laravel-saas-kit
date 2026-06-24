<?php

namespace App\Filament\Resources\CatalogItemResource\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailTranslationsRelationManager extends RelationManager
{
    protected static string $relationship = 'detailTranslations';

    protected static ?string $title = '详情正文';

    protected static ?string $recordTitleAttribute = 'locale';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('detail_sections')
                    ->label('详情正文 JSON')
                    ->rows(14)
                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                    ->rules(['nullable', 'json'])
                    ->columnSpanFull(),
                Textarea::make('localized_payload')
                    ->label('本地化 Payload JSON')
                    ->rows(14)
                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                    ->rules(['nullable', 'json'])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('locale')
                    ->label('语言')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('最后更新')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
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
