<?php

namespace App\Filament\Resources\StripeWebhookResource\Pages;

use App\Filament\Resources\StripeWebhookResource;
use Filament\Resources\Pages\ListRecords;

class ListStripeWebhooks extends ListRecords
{
    protected static string $resource = StripeWebhookResource::class;
}
