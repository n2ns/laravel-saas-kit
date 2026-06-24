@php
    $apiKey = $record->key;
@endphp

<div class="fi-ta-text-item">
    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-clipboard-document"
        label="Copy API Key"
        size="sm"
        tooltip="Copy API Key"
        x-on:click.prevent.stop="
            window.navigator.clipboard.writeText(@js($apiKey))
            $tooltip(@js('API Key copied!'), {
                theme: $store.theme,
                timeout: 1500,
            })
        "
    />
</div>
