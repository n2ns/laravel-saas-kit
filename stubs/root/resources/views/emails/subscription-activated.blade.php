<x-mail::message>
# Subscription active

Your {{ config('app.name') }} subscription is now active.

**Product:** {{ $plan->product->name ?? config('app.name') }}<br>
**Plan:** {{ $plan->name }}

Your product access has been enabled for this account. You can review your subscription, orders, and product access from your dashboard.

<x-mail::button :url="url('/dashboard')">
Open Dashboard
</x-mail::button>

If this purchase was unexpected, contact support so we can help review the account.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
