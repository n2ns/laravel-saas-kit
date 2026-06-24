<x-filament-panels::page>
    @include('filament.pages.partials.data-center-styles')

    <div class="df-page">
        <div class="df-grid">
            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">本月收入</div>
                <div class="df-metric-value">${{ number_format((float) $businessStats['month_revenue'], 2) }}</div>
                <div class="df-metric-note">{{ number_format($businessStats['active_subscriptions']) }} 活跃订阅</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">用户</div>
                <div class="df-metric-value">{{ number_format($businessStats['users']) }}</div>
                <div class="df-metric-note">{{ number_format($businessStats['paid_orders']) }} 已支付订单</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">内容状态</div>
                <div class="df-metric-value">{{ number_format($contentStats['published_posts']) }}</div>
                <div class="df-metric-note">{{ number_format($contentStats['draft_posts']) }} 草稿，{{ number_format($contentStats['missing_translation_posts']) }} 篇缺翻译</div>
            </div>

            <div class="df-card df-card-pad df-metric">
                <div class="df-metric-label">产品事件今日</div>
                <div class="df-metric-value">{{ number_format((int) $productEventsToday->sum()) }}</div>
                <div class="df-metric-note">来自已配置产品客户端</div>
            </div>
        </div>

        <div class="df-grid">
            <section class="df-card">
                <div class="df-card-heading">
                    <h2 class="df-card-title">业务摘要</h2>
                </div>
                <div class="df-card-body df-list">
                    <div class="df-row"><span class="df-row-label">用户</span><span class="df-row-value">{{ number_format($businessStats['users']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">已支付订单</span><span class="df-row-value">{{ number_format($businessStats['paid_orders']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">失败订单</span><span class="df-row-value">{{ number_format($businessStats['failed_orders']) }}</span></div>
                </div>
            </section>

            <section class="df-card">
                <div class="df-card-heading">
                    <h2 class="df-card-title">产品事件今日</h2>
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
                    <h2 class="df-card-title">需要关注</h2>
                </div>
                <div class="df-card-body df-list">
                    <div class="df-row"><span class="df-row-label">缺少翻译的已发布内容</span><span class="df-row-value">{{ number_format($contentStats['missing_translation_posts']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">失败订单</span><span class="df-row-value">{{ number_format($businessStats['failed_orders']) }}</span></div>
                    <div class="df-row"><span class="df-row-label">产品在库</span><span class="df-row-value">{{ number_format($contentStats['products']) }}</span></div>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
