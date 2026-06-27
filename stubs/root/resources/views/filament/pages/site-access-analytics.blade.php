<x-filament-panels::page>
    @include('filament.pages.partials.data-center-styles')

    <div class="df-page">
        <div class="df-toolbar">
            <p class="df-muted">Site page views, sources, languages, UTM, and CTA clicks</p>
            <select wire:model.live="dateRange" class="df-select">
                <option value="1">Last 24 hours</option>
                <option value="7">Last 7 days</option>
                <option value="15">Last 15 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>

        <div class="df-grid">
            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Total views</div>
                <div class="df-metric-value">{{ number_format($summary['views']) }}</div>
                <div class="df-metric-note">{{ $summary['range_label'] }}</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Unique visitors</div>
                <div class="df-metric-value">{{ number_format($summary['visitors']) }}</div>
                <div class="df-metric-note">Deduplicated visitors by source</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">Viewed pages</div>
                <div class="df-metric-value">{{ number_format($summary['pages']) }}</div>
                <div class="df-metric-note">At least one recorded visit</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">CTA clicks</div>
                <div class="df-metric-value">{{ number_format($summary['events']) }}</div>
                <div class="df-metric-note">{{ $summary['range_label'] }}</div>
            </div>
        </div>

        <section class="df-card">
            <div class="df-card-heading">
                <h2 class="df-card-title">Visit trend</h2>
                <span class="df-muted">Daily views</span>
            </div>
            <div class="df-card-body">
                @php
                    $maxTrend = max(1, (int) $trendRows->max('views'));
                @endphp
                <div class="df-trend" style="--df-days: {{ $trendRows->count() }};">
                    @foreach($trendRows as $row)
                        @php $height = max(4, round(($row['views'] / $maxTrend) * 100)); @endphp
                        <div class="df-trend-item">
                            <div class="df-trend-bar">
                                <div class="df-trend-fill" style="height: {{ $height }}%"></div>
                            </div>
                            <div class="df-trend-meta">
                                <div>{{ number_format($row['views']) }}</div>
                                <div>{{ $row['date'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="df-card">
            <div class="df-card-heading">
                <h2 class="df-card-title">Top pages</h2>
                <span class="df-muted">Sorted by views</span>
            </div>
            <div class="df-table-wrap">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Type</th>
                            <th class="df-number">Views</th>
                            <th class="df-number">Visitors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topPages as $row)
                            <tr>
                                <td>
                                    <div>{{ $row->blogPost?->title ?? $row->catalogItem?->getLocalized('name') ?? $row->path }}</div>
                                    <div class="df-muted">{{ $row->path }}</div>
                                </td>
                                <td>{{ $row->page_type }}</td>
                                <td class="df-number">{{ number_format((int) $row->views) }}</td>
                                <td class="df-number">{{ number_format((int) $row->visitors) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="df-empty">No data yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="df-card">
            <div class="df-card-heading">
                <h2 class="df-card-title">Top clicks</h2>
                <span class="df-muted">Sorted by event count</span>
            </div>
            <div class="df-table-wrap">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Target</th>
                            <th class="df-number">Clicks</th>
                            <th class="df-number">Visitors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>
                                    <div>{{ $event->event_name }}</div>
                                    <div class="df-muted">{{ $event->event_type }}{{ $event->catalogItem ? ' / '.$event->catalogItem->code : '' }}</div>
                                </td>
                                <td class="df-muted">{{ $event->target_url ?? '-' }}</td>
                                <td class="df-number">{{ number_format((int) $event->events) }}</td>
                                <td class="df-number">{{ number_format((int) $event->visitors) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="df-empty">No click data yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="df-grid">
            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">Source types</h2></div>
                <div class="df-card-body df-list">
                    @forelse($sources as $source)
                        <div class="df-row">
                            <span class="df-row-label">{{ $source->source_type }}</span>
                            <span class="df-row-value">{{ number_format((int) $source->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">No data yet</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">Referrer domains</h2></div>
                <div class="df-card-body df-list">
                    @forelse($referrers as $referrer)
                        <div class="df-row">
                            <span class="df-row-label">{{ $referrer->referrer_host }}</span>
                            <span class="df-row-value">{{ number_format((int) $referrer->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">No referrers yet</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">Campaign sources (UTM)</h2></div>
                <div class="df-card-body df-list">
                    @forelse($utmCampaigns as $campaign)
                        <div class="df-row">
                            <span class="df-row-label">{{ $campaign->utm_source }} / {{ $campaign->utm_medium ?? '-' }} / {{ $campaign->utm_campaign ?? '-' }}</span>
                            <span class="df-row-value">{{ number_format((int) $campaign->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">No UTM data yet</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">Language distribution</h2></div>
                <div class="df-card-body df-list">
                    @forelse($locales as $locale)
                        <div class="df-row">
                            <span class="df-row-label">{{ $locale->locale }}</span>
                            <span class="df-row-value">{{ number_format((int) $locale->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">No language data yet</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">Country distribution</h2></div>
                <div class="df-card-body df-list">
                    @forelse($countries as $country)
                        <div class="df-row">
                            <span class="df-row-label">{{ $country->country_code }}</span>
                            <span class="df-row-value">{{ number_format((int) $country->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">No country data yet</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
