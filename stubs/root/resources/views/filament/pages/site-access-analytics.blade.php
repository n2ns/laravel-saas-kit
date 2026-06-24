<x-filament-panels::page>
    @include('filament.pages.partials.data-center-styles')

    <div class="df-page">
        <div class="df-toolbar">
            <p class="df-muted">全站页面访问、来源、语言、UTM 和 CTA 点击</p>
            <select wire:model.live="dateRange" class="df-select">
                <option value="1">最近 24 小时</option>
                <option value="7">最近 7 天</option>
                <option value="15">最近 15 天</option>
                <option value="30">最近 30 天</option>
                <option value="90">最近 90 天</option>
            </select>
        </div>

        <div class="df-grid">
            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">总浏览</div>
                <div class="df-metric-value">{{ number_format($summary['views']) }}</div>
                <div class="df-metric-note">{{ $summary['range_label'] }}</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">独立访客</div>
                <div class="df-metric-value">{{ number_format($summary['visitors']) }}</div>
                <div class="df-metric-note">按来源去重后的访客</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">有访问页面</div>
                <div class="df-metric-value">{{ number_format($summary['pages']) }}</div>
                <div class="df-metric-note">至少有一次访问记录</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">CTA 点击</div>
                <div class="df-metric-value">{{ number_format($summary['events']) }}</div>
                <div class="df-metric-note">{{ $summary['range_label'] }}</div>
            </div>
        </div>

        <section class="df-card">
            <div class="df-card-heading">
                <h2 class="df-card-title">访问趋势</h2>
                <span class="df-muted">每日浏览</span>
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
                <h2 class="df-card-title">Top 页面</h2>
                <span class="df-muted">按浏览量排序</span>
            </div>
            <div class="df-table-wrap">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>页面</th>
                            <th>类型</th>
                            <th class="df-number">浏览</th>
                            <th class="df-number">访客</th>
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
                            <tr><td colspan="4" class="df-empty">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="df-card">
            <div class="df-card-heading">
                <h2 class="df-card-title">Top 点击</h2>
                <span class="df-muted">按事件量排序</span>
            </div>
            <div class="df-table-wrap">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>事件</th>
                            <th>目标</th>
                            <th class="df-number">点击</th>
                            <th class="df-number">访客</th>
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
                            <tr><td colspan="4" class="df-empty">暂无点击数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="df-grid">
            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">来源类型</h2></div>
                <div class="df-card-body df-list">
                    @forelse($sources as $source)
                        <div class="df-row">
                            <span class="df-row-label">{{ $source->source_type }}</span>
                            <span class="df-row-value">{{ number_format((int) $source->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">暂无数据</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">来源域名</h2></div>
                <div class="df-card-body df-list">
                    @forelse($referrers as $referrer)
                        <div class="df-row">
                            <span class="df-row-label">{{ $referrer->referrer_host }}</span>
                            <span class="df-row-value">{{ number_format((int) $referrer->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">暂无 referrer</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">活动来源 (UTM)</h2></div>
                <div class="df-card-body df-list">
                    @forelse($utmCampaigns as $campaign)
                        <div class="df-row">
                            <span class="df-row-label">{{ $campaign->utm_source }} / {{ $campaign->utm_medium ?? '-' }} / {{ $campaign->utm_campaign ?? '-' }}</span>
                            <span class="df-row-value">{{ number_format((int) $campaign->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">暂无 UTM 数据</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">语言分布</h2></div>
                <div class="df-card-body df-list">
                    @forelse($locales as $locale)
                        <div class="df-row">
                            <span class="df-row-label">{{ $locale->locale }}</span>
                            <span class="df-row-value">{{ number_format((int) $locale->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">暂无语言数据</div>
                    @endforelse
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading"><h2 class="df-card-title">国家分布</h2></div>
                <div class="df-card-body df-list">
                    @forelse($countries as $country)
                        <div class="df-row">
                            <span class="df-row-label">{{ $country->country_code }}</span>
                            <span class="df-row-value">{{ number_format((int) $country->views) }}</span>
                        </div>
                    @empty
                        <div class="df-empty">暂无国家数据</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
