@if(($relatedPosts ?? collect())->isNotEmpty())
    <section class="mt-20 pt-10 border-t border-slate-200/60" aria-label="{{ __('messages.blog.related_title') }}">
        <h2 class="text-xl md:text-2xl font-bold text-slate-950 tracking-tight mb-8">{{ __('messages.blog.related_title') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($relatedPosts as $related)
                <a href="{{ localized_route('blog.show', ['slug' => $related->slug, 'locale' => $contentLocale ?? app()->getLocale()]) }}"
                   class="group flex gap-4 p-4 rounded-2xl bg-white border border-slate-200/60 hover:border-primary-400/40 hover:shadow-lg transition-all">
                    @if($related->thumbnail)
                        <img src="{{ Storage::disk('public')->url($related->thumbnail) }}"
                             alt="{{ $related->getTranslation('title', $contentLocale ?? app()->getLocale()) }}"
                             loading="lazy" decoding="async"
                             class="w-24 h-24 object-cover rounded-xl flex-shrink-0">
                    @endif
                    <div class="min-w-0">
                        <span class="text-[11px] uppercase tracking-widest text-primary-500 font-medium">{{ $related->typeLabel($contentLocale ?? app()->getLocale()) }}</span>
                        <h3 class="text-sm md:text-base font-semibold text-slate-950 leading-snug mt-1 group-hover:text-primary-600 transition-colors line-clamp-2">
                            {{ $related->getTranslation('title', $contentLocale ?? app()->getLocale()) }}
                        </h3>
                        @if($excerpt = $related->getTranslation('excerpt', $contentLocale ?? app()->getLocale()))
                            <p class="text-xs text-slate-500 mt-1.5 line-clamp-2">{{ $excerpt }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
