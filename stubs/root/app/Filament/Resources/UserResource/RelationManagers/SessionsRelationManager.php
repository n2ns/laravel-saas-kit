<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\UserSessionResource;
use App\Models\UserSession;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'User Sessions';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->recordTitleAttribute('sid')
            ->modifyQueryUsing(fn ($query) => $query->withCount('tokens'))
            ->columns([
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (UserSession $record): string => $record->isRevoked() ? 'revoked' : 'active')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('product_code')
                    ->label('Product')
                    ->badge()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client')
                    ->sortable(),
                TextColumn::make('platform')
                    ->badge()
                    ->placeholder('unknown'),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->placeholder('unknown'),
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
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->recordActions([
                UserSessionResource::revokeAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    UserSessionResource::revokeBulkAction(),
                ]),
            ]);
    }
}
