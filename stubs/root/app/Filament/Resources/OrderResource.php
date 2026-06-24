<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = '商业运营';

    protected static ?string $navigationLabel = '订单记录';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('subscription_id')
                    ->relationship('subscription', 'id'),
                Select::make('gateway_id')
                    ->relationship('gateway', 'name')
                    ->required(),
                TextInput::make('order_number')
                    ->required(),
                Select::make('type')
                    ->options([
                        'new' => 'New Purchase',
                        'renewal' => 'Renewal',
                        'upgrade' => 'Upgrade',
                        'downgrade' => 'Downgrade',
                        'refund' => 'Refund',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'partial_refund' => 'Partial Refund',
                    ])
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('refunded_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('currency')
                    ->required(),
                TextInput::make('provider_order_id'),
                TextInput::make('provider_invoice_id'),
                TextInput::make('provider_payment_id'),
                Textarea::make('provider_data')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('refunded_at'),
                Textarea::make('refund_reason')
                    ->columnSpanFull(),
                Textarea::make('billing_snapshot')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'plan', 'subscription', 'gateway']))
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subscription.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gateway.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('order_number')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value) {
                        'subscription' => 'info',
                        'lifetime' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value) {
                        'completed', 'paid' => 'success',
                        'pending' => 'warning',
                        'refunded', 'failed' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('refunded_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('provider_order_id')
                    ->searchable(),
                TextColumn::make('provider_invoice_id')
                    ->searchable(),
                TextColumn::make('provider_payment_id')
                    ->searchable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('refunded_at')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
