<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\RelationManagers\OauthAccountsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\SessionsRelationManager;
use App\Models\Subscription;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password(),
                TextInput::make('avatar'),
                TextInput::make('registration_source')
                    ->label('Source')
                    ->disabled(),
                TextInput::make('auth_epoch')
                    ->label('Auth Epoch')
                    ->numeric()
                    ->helperText('Increment to invalidate all active JWTs for this user.'),
                DateTimePicker::make('banned_at')
                    ->label('Banned At')
                    ->helperText('Set a date to ban the user. Clear to unban.'),
                Toggle::make('is_admin')
                    ->label('Administrator')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'subscriptions as active_subscriptions_count' => fn (Builder $query) => $query->whereIn('stripe_status', self::visibleSubscriptionStatuses()),
            ]))
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? 'U').'&background=random'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->grow(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                TextColumn::make('subscription_status')
                    ->label('Subscriptions')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => ((int) $record->active_subscriptions_count > 0) ? 'subscribed' : 'none')
                    ->color(fn (?string $state): string => match (true) {
                        $state === 'subscribed' => 'info',
                        default => 'gray',
                    })
                    ->url(fn (User $record): ?string => ((int) $record->active_subscriptions_count > 0)
                        ? SubscriptionResource::getUrl('index', [
                            'tableFilters' => [
                                'user_id' => [
                                    'value' => (string) $record->id,
                                ],
                            ],
                        ])
                        : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->getStatus() ?? 'active')
                    ->color(fn (?string $state): string => match ($state) {
                        'banned' => 'danger',
                        'expired' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('registration_source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'web' => 'info',
                        'vscode' => 'success',
                        'chrome' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('auth_epoch')
                    ->label('Epoch')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            OauthAccountsRelationManager::class,
            SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    private static function visibleSubscriptionStatuses(): array
    {
        return [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
            Subscription::STATUS_PAST_DUE,
        ];
    }
}
