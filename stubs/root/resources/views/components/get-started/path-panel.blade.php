{{--
    Get Started accordion panel.

    Usage:
      <x-get-started.path-panel
          path-key="products"
          icon="fa-rocket"
          color="blue"
          :tag="$tag"
          :title="$title"
          :description="$description"
          :items="$items"
          :cta-label="$ctaLabel"
          :cta-url="$ctaUrl"
          :cta-external="false"
          :status-label="$statusLabel"
          :status-value="$statusValue"
          :hint="$hint"
      />

    Expects an Alpine `active` array + `toggle(path)` method in an ancestor x-data scope.
--}}

@props([
    'pathKey',
    'icon',
    'color',
    'tag',
    'title',
    'description',
    'items' => [],
    'ctaLabel',
    'ctaUrl',
    'ctaExternal' => false,
    'statusLabel',
    'statusValue',
    'hint',
])

@php
    // Tailwind's content scanner needs full literal class names, so colors are
    // mapped to hardcoded strings here rather than built dynamically.
    $palette = match ($color) {
        'emerald' => [
            'bg' => 'bg-emerald-500', 'hover' => 'hover:bg-emerald-400', 'text' => 'text-emerald-400',
            'border' => 'border-emerald-500/20', 'indicator' => 'bg-emerald-500', 'shadow' => 'shadow-emerald-500/30',
            'border_active' => 'border-emerald-500/50', 'badge' => 'bg-emerald-500/10',
        ],
        'amber' => [
            'bg' => 'bg-amber-500', 'hover' => 'hover:bg-amber-400', 'text' => 'text-amber-400',
            'border' => 'border-amber-500/20', 'indicator' => 'bg-amber-500', 'shadow' => 'shadow-amber-500/30',
            'border_active' => 'border-amber-500/50', 'badge' => 'bg-amber-500/10',
        ],
        default => [
            'bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-400', 'text' => 'text-blue-400',
            'border' => 'border-blue-500/20', 'indicator' => 'bg-blue-500', 'shadow' => 'shadow-blue-500/30',
            'border_active' => 'border-blue-500/50', 'badge' => 'bg-blue-500/10',
        ],
    };
@endphp

<div class="group/panel transition-all duration-300 relative overflow-hidden border border-white/10 rounded"
     :class="active.includes('{{ $pathKey }}') ? 'bg-slate-900 border-white/20 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.8)]' : 'bg-[#0f172a] hover:bg-slate-900 hover:border-white/15'">

    <div class="absolute left-0 top-0 bottom-0 w-1 transition-all duration-300"
         :class="active.includes('{{ $pathKey }}') ? '{{ $palette['indicator'] }}' : 'bg-transparent group-hover/panel:bg-white/10'"></div>

    <button @click="toggle('{{ $pathKey }}')"
            type="button"
            class="w-full text-left py-4 px-8 lg:px-12 flex items-center gap-6 relative z-10 outline-none">
        <div class="w-8 h-8 flex items-center justify-center border transition-all duration-300 bg-slate-800/50 border-white/5"
             :class="active.includes('{{ $pathKey }}') ? '{{ $palette['border_active'] }} {{ $palette['text'] }}' : 'text-slate-500'">
            <i class="fa-solid {{ $icon }} text-base"></i>
        </div>

        <div class="flex-grow">
            <div class="flex items-center gap-4">
                <h2 class="font-bold transition-colors duration-300 tracking-tight text-[15px] md:text-[17px]"
                    :class="active.includes('{{ $pathKey }}') ? 'text-white' : 'text-slate-400 group-hover/panel:text-slate-300'">
                    {{ $title }}
                </h2>
                <span class="px-1.5 py-0.5 bg-slate-800 text-[8px] font-bold tracking-[0.1em] uppercase text-slate-500 text-[11px]"
                      :class="active.includes('{{ $pathKey }}') ? '{{ $palette['badge'] }} {{ $palette['text'] }} border {{ $palette['border'] }}' : ''">
                    {{ $tag }}
                </span>
            </div>
        </div>

        <div class="w-8 h-8 flex items-center justify-center transition-all duration-300 text-slate-600"
             :class="active.includes('{{ $pathKey }}') ? 'rotate-180 {{ $palette['text'] }}' : 'group-hover/panel:text-slate-400'">
            <i class="fa-solid fa-chevron-down text-xs"></i>
        </div>
    </button>

    <div x-show="active.includes('{{ $pathKey }}')" x-collapse x-cloak>
        <div class="px-8 lg:px-12 pt-6 pb-12">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 items-start">
                <!-- Left: Detailed Info -->
                <div class="md:col-span-7 space-y-10">
                    <p class="text-slate-200 leading-relaxed max-w-2xl font-normal tracking-wide text-[13px] md:text-sm">
                        {{ $description }}
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-12">
                        @foreach($items as $item)
                            <div class="flex items-start gap-3.5 text-slate-300 group/feature">
                                <i class="fa-solid fa-check mt-1 {{ $palette['text'] }} group-hover/feature:scale-125 transition-transform"></i>
                                <div class="leading-relaxed">
                                    <span class="font-semibold text-white">{{ $item['title'] }}</span>
                                    @if(! empty($item['description']))
                                        <span class="block text-xs text-slate-400 mt-0.5">{{ $item['description'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Action Dashboard -->
                <div class="md:col-span-5 self-start">
                    <div class="bg-slate-900 border border-white/10 p-7 lg:p-8 relative shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)] ring-1 ring-white/5 rounded-md">
                        <div class="mb-6 border-b border-white/5 pb-5">
                            <div class="font-bold text-slate-500 uppercase tracking-widest mb-2 opacity-70 text-[11px]">
                                {{ $statusLabel }}
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="{{ $palette['text'] }} tracking-wider uppercase text-[13px] font-bold">
                                    {{ $statusValue }}
                                </div>
                                <div class="w-2 h-2 {{ $palette['indicator'] }} rounded-full animate-pulse shadow-[0_0_10px_rgba(0,0,0,0.4)]"></div>
                            </div>
                        </div>

                        <p class="text-sm text-slate-400 leading-relaxed mb-10 opacity-70 italic">
                            {{ $hint }}
                        </p>

                        <a href="{{ $ctaUrl }}" @if($ctaExternal) target="_blank" rel="noopener noreferrer" @endif
                           class="w-full inline-flex items-center justify-center px-6 py-4 {{ $palette['bg'] }} {{ $palette['hover'] }} text-white text-sm font-bold transition-all uppercase tracking-widest group/btn shadow-xl {{ $palette['shadow'] }}">
                            <span>{{ $ctaLabel }}</span>
                            <i class="fa-solid fa-arrow-right ml-3 text-[10px] transition-transform group-hover/btn:translate-x-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
