@extends('layouts.app')

@section('title', __('get_started.title'))
@section('meta_description', __('get_started.subtitle'))

@section('content')
<div class="get-started-page">
    <div class="py-10 bg-white min-h-screen relative overflow-hidden">
        <!-- Subtle Background Highlights -->
        <div class="absolute top-0 left-1/4 w-[700px] h-[700px] bg-orange-200/40 blur-[160px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 relative z-10">
            <!-- Header Section -->
            <div class="mb-6 pl-4 animate-fade-in border-l-2 border-[#a34f1f]">
                <h1 class="text-2xl md:text-5xl font-bold text-slate-950 mb-2 tracking-tight uppercase">
                    {{ __('get_started.title') }}
                </h1>
                <p class="text-sm md:text-base text-slate-500 max-w-2xl leading-relaxed font-normal">
                    {{ __('get_started.subtitle') }}
                </p>
            </div>

            <div class="space-y-6" x-data="{
                active: ['products'],
                toggle(path) {
                    if (this.active.includes(path)) {
                        this.active = this.active.filter(i => i !== path);
                    } else {
                        this.active.push(path);
                    }
                }
            }">
                @foreach($paths as $path)
                    <x-get-started.path-panel
                        :path-key="$path['path_key']"
                        :icon="$path['icon']"
                        :color="$path['color']"
                        :tag="$path['tag']"
                        :title="$path['title']"
                        :description="$path['description']"
                        :items="$path['items']"
                        :cta-label="$path['cta_label']"
                        :cta-url="$path['cta_url']"
                        :status-label="$path['status_label']"
                        :status-value="$path['status_value']"
                        :hint="$path['hint']"
                    />
                @endforeach
            </div>

            <!-- Footer Tagline -->
            <div class="mt-24 text-center opacity-20">
                <p class="text-slate-500 text-[10px] font-bold tracking-[0.6em] uppercase">
                    System Framework <span class="text-slate-700">Ready</span>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
