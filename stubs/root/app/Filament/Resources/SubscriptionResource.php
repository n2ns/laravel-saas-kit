<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages\CreateSubscription;
use App\Filament\Resources\SubscriptionResource\Pages\EditSubscription;
use App\Filament\Resources\SubscriptionResource\Pages\ListSubscriptions;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = '商业运营';

    protected static ?string $navigationLabel = '订阅管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('type')
                    ->label('Subscription Type')
                    ->helperText('Automatically set to the product code.')
                    ->default('default')
                    ->required()
                    ->readOnly(),
                Select::make('plan_id')
                    ->relationship(
                        'plan',
                        'name',
                        fn ($query) => $query
                            ->where('tier', '!=', 'free')
                            ->with('product')
                            ->orderBy('product_id')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product?->name} - {$record->name}")
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        /** @var Plan|null $plan */
                        $plan = Plan::find($state);
                        if ($plan && $plan->product) {
                            // @phpstan-ignore-next-line property.notFound (Plan::product() is BelongsTo<Product>; find() multi-type confuses PHPStan)
                            $set('type', $plan->product->code);
                        } else {
                            $set('type', 'default');
                        }
                    }),
                TextInput::make('stripe_id')
                    ->required(),
                Select::make('stripe_status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                        'incomplete' => 'Incomplete',
                        'incomplete_expired' => 'Incomplete Expired',
                        'paused' => 'Paused',
                    ])
                    ->default('active')
                    ->required(),
                TextInput::make('stripe_price'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('current_period_ends_at'),
                DateTimePicker::make('ends_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'plan.product']))
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.product.name')
                    ->label('Product')
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                TextColumn::make('stripe_id')
                    ->searchable(),
                TextColumn::make('stripe_status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled', 'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('stripe_price')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('current_period_ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} <{$record->email}>")
                    ->searchable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }
}
