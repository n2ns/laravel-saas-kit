<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages\CreateApiKey;
use App\Filament\Resources\ApiKeyResource\Pages\EditApiKey;
use App\Filament\Resources\ApiKeyResource\Pages\ListApiKeys;
use App\Models\ApiKey;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = '系统配置';

    protected static ?string $navigationLabel = 'API 密钥';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. MCP Server Key'),
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => 'df_'.Str::random(40))
                    ->password()
                    ->revealable()
                    ->helperText('This key is stored in plaintext for integration purposes.')
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at')
                    ->default(fn () => now()->addYear())
                    ->helperText('Leave blank for no expiration.'),
                DateTimePicker::make('revoked_at')
                    ->label('Revoked At')
                    ->helperText('Set a date to revoke this key immediately.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->key),
                ViewColumn::make('copy_key')
                    ->label('')
                    ->view('filament.tables.columns.copy-api-key')
                    ->width('1%'),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : null),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiKeys::route('/'),
            'create' => CreateApiKey::route('/create'),
            'edit' => EditApiKey::route('/{record}/edit'),
        ];
    }
}
