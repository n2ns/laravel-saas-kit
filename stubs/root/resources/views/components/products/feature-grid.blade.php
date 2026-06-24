{{--
    Feature Grid Component (Laravel 12 Anonymous Component)

    Usage: <x-products.feature-grid :section="$section" :content="$sectionsContent" />

    @props array $section Configuration array with:
        - title_key / features_title: Section title (key or text)
        - data_source: Key in $content array to get features from
        - columns: Grid columns (2 or 3)
        - size: Card size (normal or large)
        - icons: Icon mapping array [key => lucide-icon-name]
        - colors: Color mapping array [key => tailwind-color]

    @props array $content Sections content from database (sections_content)
--}}

@props(['section', 'content' => []])

@php
    // Zero-Style Refactoring: Structure is Master
    // Use the keys defined in $section['icons'] to ensure the UI structure 
    // and icons are consistent across all languages.
    
    $iconsMap = $section['icons'] ?? [];
    $dataSource = $section['data_source'] ?? 'features';
    $sourceContent = $content[$dataSource] ?? [];
    
    // We iterate over the structure defined in base.php (the icons map)
    // If icons map is empty, we fallback to iterating over content (old behavior)
    $iteratorKeys = !empty($iconsMap) ? array_keys($iconsMap) : array_keys($sourceContent);

    // Fault tolerance: drop keys that have no content in this locale so we never
    // render empty cards (and the column count below stays correct).
    $iteratorKeys = array_values(array_filter($iteratorKeys, function ($key) use ($sourceContent) {
        $feature = $sourceContent[$key] ?? [];

        return filled($feature['title'] ?? null)
            || filled($feature['name'] ?? null)
            || filled($feature['desc'] ?? null)
            || filled($feature['description'] ?? null)
            || filled($feature['sites'] ?? null);
    }));

    $featureCount = count($iteratorKeys);

    // Choose a column count that divides evenly to avoid an orphan last row:
    // multiples of 3 use 3 columns, other even counts use 2 (e.g. 4 -> 2x2),
    // everything else falls back to 3.
    $columns = ($featureCount % 3 === 0) ? 3 : (($featureCount % 2 === 0) ? 2 : 3);

    // Get section title
    $localizedTitleKey = "{$dataSource}_title";
    $title = $content[$localizedTitleKey] ?? $section['features_title'] ?? null;
    if (!$title && isset($section['title_key'])) {
        $title = $content[$section['title_key']] ?? __($section['title_key']);
        if ($title === $section['title_key']) $title = null;
    }

    $gridCols = $columns === 2
        ? 'grid-cols-1 md:grid-cols-2'
        : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';

    $flagMap = [
        'cambodia' => 'kh',
        'vietnam' => 'vn',
        'laos' => 'la',
        'thailand' => 'th',
        'indonesia' => 'id',
        'malaysia' => 'my',
        'singapore' => 'sg',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'feature-cards-section mb-10']) }}>
    {{-- Optional Section Title (hidden when there are no cards to show) --}}
    @if($title && $featureCount > 0)
        <div class="flex items-center gap-3 mb-4">
            <div class="h-px flex-1 bg-black/[0.08]"></div>
            <h2 class="text-[15px] md:text-[17px] font-semibold text-slate-950 tracking-tight">{{ $title }}</h2>
            <div class="h-px flex-1 bg-black/[0.08]"></div>
        </div>
    @endif

    @if($featureCount > 0 && $dataSource === 'coverage')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($iteratorKeys as $key)
            @php
                $feature = $sourceContent[$key] ?? [];
                $flagCode = $flagMap[$key] ?? null;
                $featureTitle = $feature['title'] ?? $feature['name'] ?? '';
                $featureDesc = $feature['desc'] ?? $feature['description'] ?? '';
                $featureSites = $feature['sites'] ?? null;
            @endphp

            <div class="group min-h-[178px] rounded-lg border border-black/10 bg-white p-5 shadow-xl shadow-orange-900/5 transition duration-200 hover:-translate-y-1 hover:border-orange-200 hover:bg-orange-50">
                <div class="mb-5 flex items-start justify-between gap-5">
                    <h3 class="pt-1 text-base font-semibold text-slate-950 tracking-tight">{{ $featureTitle }}</h3>
                    @if($flagCode)
                        <span class="fi fi-{{ $flagCode }} text-[46px] leading-none rounded-[3px] shadow-md shadow-black/40"></span>
                    @endif
                </div>

                <p class="text-base text-slate-600 leading-relaxed">{{ $featureDesc }}</p>

                @if($featureSites)
                    <div class="mt-4 flex items-start gap-2 border-t border-sky-300/15 pt-3 text-[13px] font-medium leading-5 text-slate-700">
                        <i data-lucide="globe-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#a34f1f]"></i>
                        <span>{{ $featureSites }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @elseif($featureCount > 0)
    <div class="feature-cards-grid grid {{ $gridCols }} gap-4">
        @foreach($iteratorKeys as $key)
            @php
                $feature = $sourceContent[$key] ?? [];
                
                // Icon from base.php mapping (Master) or feature-specific override
                $icon = $feature['icon'] ?? $iconsMap[$key] ?? 'check-circle';
                
                $featureTitle = $feature['title'] ?? $feature['name'] ?? '';
                $featureDesc = $feature['desc'] ?? $feature['description'] ?? '';
            @endphp

            <div class="group flex min-h-[148px] flex-col items-start rounded-lg border border-black/10 bg-white p-5 shadow-xl shadow-orange-900/5 transition duration-200 hover:-translate-y-1 hover:border-orange-200 hover:bg-orange-50">
                {{-- Icon Container --}}
                <div class="mb-4 flex h-9 w-9 items-center justify-center rounded-md border border-orange-200 bg-orange-100 shadow-sm shadow-orange-900/10">
                    <i data-lucide="{{ $icon }}" class="h-[18px] w-[18px] text-[var(--brand-primary)]"></i>
                </div>

                {{-- Title --}}
                <h3 class="text-sm font-semibold text-slate-950 mb-2 tracking-tight">{{ $featureTitle }}</h3>

                {{-- Description --}}
                <p class="text-sm text-slate-600 leading-relaxed">{{ $featureDesc }}</p>
            </div>
        @endforeach
    </div>
    @endif
</div>
