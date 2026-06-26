{{--
    Product CTA Button Component

    Usage: <x-products.cta-button :cta="..." :product-code="..." :has-paid-plans="..." />

    Props:
      - cta: CTA configuration array
      - productCode: fallback product slug for pricing CTA
      - hasPaidPlans: controls default pricing label
      - class: optional extra classes
--}}

@props([
    'cta' => [],
    'productCode' => null,
    'hasPaidPlans' => false,
])

@php
    $ctaType = $cta['type'] ?? 'primary';
    $ctaLabel = $cta['label'] ?? '';
    $ctaUrl = $cta['url'] ?? '#';

    if ($ctaType === 'pricing') {
        $pricingCode = $cta['slug'] ?? $productCode;
        if ($pricingCode) {
            $ctaUrl = localized_route('catalog.pricing', ['slug' => $pricingCode]);
        }

        $ctaLabel = $ctaLabel ?: ($hasPaidPlans ? __('products.cta.pricing') : __('products.cta.install'));
    } else {
        $ctaLabel = $ctaLabel ?: __($cta['label_key'] ?? "products.cta.{$ctaType}");
    }

    $target = match ($ctaType) {
        'install', 'download', 'github', 'npm', 'trial', 'external' => '_blank',
        default => '_self',
    };

    $buttonStyle = match ($ctaType) {
        'github', 'npm', 'external' => 'inline-flex items-center justify-center w-36 py-3 rounded-full bg-white text-slate-950 border border-black/10 hover:bg-orange-50 hover:border-orange-200 font-bold text-base transition-all duration-300 shadow-lg shadow-orange-900/5 group/gh',
        default => 'inline-flex items-center justify-center w-44 py-3 rounded-full bg-[#a34f1f] hover:opacity-90 text-white font-bold text-base shadow-lg shadow-orange-900/20 transition-all hover:-translate-y-1',
    };

    $icon = match ($ctaType) {
        'github' => '<svg class="w-6 h-6 transition-transform group-hover/gh:scale-110" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" /></svg>',
        'install' => '<i data-lucide="download" class="ml-2 h-[18px] w-[18px]"></i>',
        'download' => '<i data-lucide="arrow-down-to-line" class="ml-2 h-[18px] w-[18px]"></i>',
        'pricing' => '<i data-lucide="tags" class="ml-2 h-[18px] w-[18px]"></i>',
        'npm' => '<i data-lucide="package" class="ml-2 h-[18px] w-[18px]"></i>',
        'trial' => '<i data-lucide="play" class="ml-2 h-[18px] w-[18px]"></i>',
        'external' => '<i data-lucide="external-link" class="ml-2 h-[18px] w-[18px]"></i>',
        default => '',
    };
@endphp

<a
    href="{{ $ctaUrl }}"
    target="{{ $target }}"
    rel="{{ $target === '_blank' ? 'noopener noreferrer' : '' }}"
    class="{{ $buttonStyle }} {{ $attributes->get('class', '') }}"
    data-site-event="cta_click"
    data-site-event-type="{{ $ctaType }}"
    data-site-target-url="{{ $ctaUrl }}"
    @if($productCode) data-site-catalog-code="{{ $productCode }}" @endif
    @if($target === '_blank') title="Open external link" @endif
>
    {{ $ctaLabel }}
    {!! $icon !!}
</a>
