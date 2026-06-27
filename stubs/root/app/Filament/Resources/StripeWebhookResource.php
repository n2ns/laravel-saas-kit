<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StripeWebhookResource\Pages;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\WebhookClient\Models\WebhookCall;
use UnitEnum;

class StripeWebhookResource extends Resource
{
    protected static ?string $model = WebhookCall::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Stripe Webhooks';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Event Name')
                            ->disabled(),
                        TextInput::make('url')
                            ->label('Recipient URL')
                            ->disabled(),
                        TextInput::make('created_at')
                            ->label('Received At')
                            ->disabled(),
                    ])->columns(3),

                Section::make('Payload')
                    ->schema([
                        Textarea::make('payload')
                            ->label('JSON Payload')
                            ->columnSpanFull()
                            ->rows(20)
                            ->disabled()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                    ]),

                Section::make('Exception Information')
                    ->collapsed(fn ($record) => $record && $record->exception === null)
                    ->collapsible()
                    ->schema([
                        Textarea::make('exception')
                            ->label('Exception Details')
                            ->columnSpanFull()
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('payload.type')
                    ->label('Event Type')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'completed') => 'success',
                        str_contains($state, 'failed') => 'danger',
                        str_contains($state, 'deleted') => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('payload.id')
                    ->label('Stripe ID')
                    ->copyable()
                    ->searchable(),

                IconColumn::make('exception')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(fn ($record) => $record->exception !== null),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // Read only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStripeWebhooks::route('/'),
            'view' => Pages\ViewStripeWebhook::route('/{record}'),
        ];
    }
}
