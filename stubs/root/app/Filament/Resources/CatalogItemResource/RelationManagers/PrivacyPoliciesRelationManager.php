<?php

namespace App\Filament\Resources\CatalogItemResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrivacyPoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'privacyPolicies';

    protected static ?string $title = '隐私政策';

    protected static ?string $recordTitleAttribute = 'locale';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->label('语言')
                    ->options([
                        'en' => 'English',
                        'zh_CN' => '中文',
                        'es' => 'Español',
                        'de' => 'Deutsch',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $livewire) => $rule->where('catalog_item_id', $livewire->ownerRecord->id)),
                TextInput::make('title')
                    ->label('标题')
                    ->maxLength(255),
                TextInput::make('updated_label')
                    ->label('更新说明')
                    ->maxLength(255),
                DatePicker::make('effective_date')
                    ->label('生效日期'),
                Textarea::make('sections')
                    ->label('政策 Sections JSON')
                    ->rows(14)
                    ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                    ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                    ->rules(['nullable', 'json'])
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->label('元数据 JSON')
                    ->rows(6)
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
                TextColumn::make('title')
                    ->label('标题')
                    ->limit(40),
                TextColumn::make('effective_date')
                    ->label('生效日期')
                    ->date(),
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
