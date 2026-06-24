<?php

namespace App\Filament\Resources\CatalogItemResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

    protected static ?string $title = '多语言资料';

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

                Section::make('基础文案')
                    ->schema([
                        TextInput::make('name')
                            ->label('名称')
                            ->maxLength(255),
                        TextInput::make('card_tag')
                            ->label('卡片标签')
                            ->maxLength(255),
                        TextInput::make('cta_label')
                            ->label('CTA 文案')
                            ->maxLength(255),
                        Textarea::make('short_description')
                            ->label('短描述')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('long_description')
                            ->label('长描述')
                            ->rows(8)
                            ->columnSpanFull(),
                        TagsInput::make('tags')
                            ->label('标签'),
                        TagsInput::make('key_points')
                            ->label('要点'),
                    ])
                    ->columns(2),

                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO 标题')
                            ->maxLength(255),
                        Textarea::make('seo_description')
                            ->label('SEO 描述')
                            ->rows(3),
                        Textarea::make('seo_payload')
                            ->label('SEO JSON')
                            ->rows(6)
                            ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                            ->dehydrateStateUsing(fn ($state): ?array => self::decodeJson($state))
                            ->rules(['nullable', 'json'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                TextColumn::make('name')
                    ->label('名称')
                    ->limit(40),
                TextColumn::make('card_tag')
                    ->label('标签')
                    ->limit(30),
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
