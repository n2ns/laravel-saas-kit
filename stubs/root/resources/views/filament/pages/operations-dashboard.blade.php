<x-filament-panels::page>
    @include('filament.pages.partials.data-center-styles')

    <div class="df-page">
        <div class="df-grid">
            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Monthly revenue</div>
                <div class="df-metric-value">${{ number_format((float) $businessStats['month_revenue'], 2) }}</div>
                <div class="df-metric-note">{{ number_format($businessStats['active_subscriptions']) }} active subscriptions</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Users</div>
                <div class="df-metric-value">{{ number_format($businessStats['users']) }}</div>
                <div class="df-metric-note">{{ number_format($businessStats['paid_orders']) }} paid orders</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Content status</div>
                <div class="df-metric-value">{{ number_format($contentStats['published_posts']) }}</div>
                <div class="df-metric-note">{{ number_format($contentStats['draft_posts']) }}  drafts, {{ number_format($contentStats['missing_translation_posts']) }} missing translations</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Product events today</div>
                <div class="df-metric-value">{{ number_format((int) $productEventsToday->sum()) }}</div>
                <div class="df-metric-note">From configured product clients</div>
            </div>
        </div>

        <div class="df-grid">
            <section class="df-card">
                <div class="df-card-heading">
                    <h2 class="df-card-title">Business summary</h2>
                </div>
                <div class="df-card-body df-list">
                    <div class="df-row"><span class="df-row-label">Users</span><span class="df-row-value">{{ number_format($businessStats['users']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">paid orders</span><span class="df-row-value">{{ number_format($businessStats['paid_orders']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">failed orders</span><span class="df-row-value">{{ number_format($businessStats['failed_orders']) }}</span></div>
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading">
                    <h2 class="df-card-title">Product events today</h2>
                </div>
                <div class="df-card-body df-list">
                    @foreach($productEventsToday as $clientId => $events)
                        <div class="df-row">
                            <span class="df-row-label">{{ strtoupper($clientId) }}</span>
                            <span class="df-row-value">{{ number_format((int) $events) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading">
                    <h2 class="df-card-title">Needs attention</h2>
                </div>
                <div class="df-card-body df-list">
                    <div class="df-row"><span class="df-row-label">Published content missing translations</span><span class="df-row-value">{{ number_format($contentStats['missing_translation_posts']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">failed orders</span><span class="df-row-value">{{ number_format($businessStats['failed_orders']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">Products in catalog</span><span class="df-row-value">{{ number_format($contentStats['products']) }}</span></div>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
