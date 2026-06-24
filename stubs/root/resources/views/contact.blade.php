@extends('layouts.app')

@section('title', __('contact.title'))
@section('meta_description', __('contact.hero_subtitle'))

@section('content')
<!-- Hero Section -->
<section class="py-10 bg-gradient-to-br from-slate-900 via-primary-950 to-slate-950 border-b border-white/5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-500/10 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left pt-4 relative z-10">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-5xl font-bold text-white mb-6 leading-tight tracking-tight">
                {{ __('contact.hero_title') }}
            </h1>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
            <!-- Contact Form -->
            <div>
                <h2 class="text-lg md:text-2xl font-bold text-white mb-8 tracking-tight">
                    {{ __('contact.send_message') }}
                </h2>
                <form action="#" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-base font-normal text-text-secondary mb-2">
                                {{ __('contact.form_name') }} *
                            </label>
                            <input type="text" id="name" name="name" required
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/50 border border-white/10 focus:ring-2 focus:ring-primary-500 focus:border-transparent text-white transition-all">
                        </div>
                        <div>
                            <label for="email" class="block text-base font-normal text-text-secondary mb-2">
                            {{ __('contact.form_email') }} *
                        </label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/50 border border-white/10 focus:ring-2 focus:ring-primary-500 focus:border-transparent text-white transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label for="subject" class="block text-base font-normal text-slate-300 mb-2">
                            {{ __('contact.form_subject') }}
                        </label>
                        <input type="text" id="subject" name="subject"
                               class="w-full px-4 py-3 rounded-xl bg-slate-900/50 border border-white/10 focus:ring-2 focus:ring-primary-500 focus:border-transparent text-white transition-all">
                    </div>
                    
                    <div>
                        <label for="message" class="block text-base font-normal text-slate-300 mb-2">
                            {{ __('contact.form_message') }} *
                        </label>
                        <textarea id="message" name="message" rows="5" required
                                  placeholder="{{ __('contact.form_message_placeholder') }}"
                                  class="w-full px-4 py-3 rounded-xl bg-slate-900/50 border border-white/10 focus:ring-2 focus:ring-primary-500 focus:border-transparent text-white transition-all resize-none"></textarea>
                    </div>
                    
                    <button type="submit" class="px-8 py-4 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold w-full md:w-auto shadow-lg shadow-primary-500/20 transition-all">
                        {{ __('contact.form_send') }}
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div>
                <h2 class="text-lg md:text-2xl font-bold text-white mb-8 tracking-tight">
                    {{ __('contact.contact_info') }}
                </h2>
                
                <div class="space-y-8">
                    <!-- Email -->
                    <div class="flex items-start space-x-5">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="mail" class="w-5 h-5 text-primary-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white mb-1">{{ __('contact.email_label') }}</h4>
                            <a href="mailto:{{ config('app.company_email') }}" class="text-text-tertiary hover:text-white transition-colors block">{{ config('app.company_email') }}</a>
                            <a href="mailto:{{ config('app.support_email') }}" class="text-text-tertiary hover:text-white transition-colors block">{{ config('app.support_email') }}</a>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="flex items-start space-x-5">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="building-2" class="w-5 h-5 text-primary-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white mb-1">{{ __('contact.address_label') }}</h4>
                            <p class="text-text-tertiary leading-relaxed">
                                {{ config('app.company_name') }}<br>
                                {{ config('app.company_address') ?: __('Update COMPANY_ADDRESS before launch') }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Business Hours -->
                    <div class="flex items-start space-x-5">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-primary-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white mb-1">{{ __('contact.hours_label') }}</h4>
                            <p class="text-text-tertiary">
                                {{ __('contact.hours_weekdays') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Note -->
                <div class="mt-10 p-6 bg-slate-900/50 border border-white/5 rounded-2xl">
                    <p class="text-slate-500 text-sm">
                        {{ __('contact.response_note') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
