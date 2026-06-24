<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSessionResource\Pages\ListUserSessions;
use App\Models\User;
use App\Models\UserSession;
use App\Services\Auth\UserSessionRevoker;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class UserSessionResource extends Resource
{
    protected static ?string $model = UserSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = '用户管理';

    protected static ?string $navigationLabel = '用户会话';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')->withCount('tokens'))
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn (UserSession $record): ?string => $record->user
                        ? UserResource::getUrl('edit', ['record' => $record->user])
                        : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (UserSession $record): string => $record->isRevoked() ? 'revoked' : 'active')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('product_code')
                    ->label('Product')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('platform')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->placeholder('unknown'),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->searchable()
                    ->placeholder('unknown'),
                TextColumn::make('device_id_hash')
                    ->label('Device Hash')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? substr($state, 0, 12) : null)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tokens_count')
                    ->label('Tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revoked_reason')
                    ->label('Revoked Reason')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revoked_at')
                    ->label('Revoked At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Active')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('revoked_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('revoked_at'),
                    ),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} <{$record->email}>")
                    ->searchable(),
                SelectFilter::make('product_code')
                    ->label('Product')
                    ->options(fn (): array => self::distinctOptions('product_code')),
                SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn (): array => self::distinctOptions('client_id')),
                SelectFilter::make('platform')
                    ->options(fn (): array => self::distinctOptions('platform')),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->recordActions([
                self::revokeAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::revokeBulkAction(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserSessions::route('/'),
        ];
    }

    public static function revokeAction(): Action
    {
        return Action::make('revoke')
            ->label('撤销')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (UserSession $record): bool => ! $record->isRevoked())
            ->action(function (UserSession $record): void {
                app(UserSessionRevoker::class)->revoke($record, 'admin_revoked');

                Notification::make()
                    ->title('会话已撤销')
                    ->success()
                    ->send();
            });
    }

    public static function revokeBulkAction(): BulkAction
    {
        return BulkAction::make('revoke_selected')
            ->label('批量撤销')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $count = app(UserSessionRevoker::class)->revokeMany($records, 'admin_bulk_revoked');

                Notification::make()
                    ->title("已撤销 {$count} 个会话")
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return UserSession::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }
}
