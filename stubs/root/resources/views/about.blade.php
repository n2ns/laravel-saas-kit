@extends('layouts.app')

@section('title', __('about.title'))
@section('meta_description', __('about.hero_subtitle'))

@section('content')
<div class="about-page bg-bg-body">
    <section class="pt-10 pb-16 md:pt-16 md:pb-24 border-b border-black/10 relative overflow-hidden bg-gradient-to-b from-[#fff4e8] to-[#fff9f2]">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-orange-200/40 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute inset-0 bg-dot-grid opacity-[0.025] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl">
                <p class="section-label">{{ __('about.template_label') }}</p>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-950 mb-6 leading-tight tracking-tighter">
                    {{ __('about.hero_title') }}
                </h1>
                <p class="text-slate-600 max-w-2xl leading-relaxed text-base md:text-lg">
                    {{ __('about.hero_subtitle') }}
                </p>
            </div>
        </div>
    </section>

    <section class="pt-10 pb-16 md:pt-16 md:pb-24 border-b border-black/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="ui-card ui-card-lift p-6 rounded-2xl">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 border border-orange-200 flex items-center justify-center mb-4">
                        <i data-lucide="layout-template" class="w-6 h-6 text-[#a34f1f]"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-950 mb-2 tracking-tight">{{ __('about.section_overview_title') }}</h2>
                    <p class="text-slate-600 text-sm leading-relaxed">{{ __('about.section_overview_body') }}</p>
                </div>

                <div class="ui-card ui-card-lift p-6 rounded-2xl">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 border border-orange-200 flex items-center justify-center mb-4">
                        <i data-lucide="target" class="w-6 h-6 text-[#a34f1f]"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-950 mb-2 tracking-tight">{{ __('about.section_mission_title') }}</h2>
                    <p class="text-slate-600 text-sm leading-relaxed">{{ __('about.section_mission_body') }}</p>
                </div>

                <div class="ui-card ui-card-lift p-6 rounded-2xl">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 border border-orange-200 flex items-center justify-center mb-4">
                        <i data-lucide="mail" class="w-6 h-6 text-[#a34f1f]"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-950 mb-2 tracking-tight">{{ __('about.section_contact_title') }}</h2>
                    <p class="text-slate-600 text-sm leading-relaxed">{{ __('about.section_contact_body') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pt-8 pb-10 md:pt-12 md:pb-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-orange-100/40"></div>
        <div class="absolute inset-0 bg-dot-grid opacity-[0.025] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-2xl">
                <h2 class="text-2xl md:text-3xl font-bold text-slate-950 mb-4 tracking-tight">
                    {{ __('about.cta_title') }}
                </h2>
                <p class="text-slate-600 text-base leading-relaxed mb-8">
                    {{ __('about.cta_subtitle') }}
                </p>
                <a href="{{ localized_route('support') }}"
                   class="btn-primary px-8 py-3.5 text-base">
                    {{ __('about.cta_button') }}
                </a>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-slate-700 text-[10px]">{{ __('messages.footer.disclaimer') }}</p>
    </div>
</div>
@endsection
