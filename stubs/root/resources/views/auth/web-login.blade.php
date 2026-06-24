@extends('layouts.app')

@section('title', __('Login'))

@section('content')
<section class="relative overflow-hidden bg-bg-body">
    <div class="absolute inset-0 bg-dot-grid opacity-35"></div>
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary-400/50 to-transparent"></div>

    <div class="relative mx-auto grid min-h-[calc(100vh-160px)] max-w-7xl grid-cols-1 items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-16">
        <div class="max-w-2xl">
            <p class="section-label mb-5">{{ __('Account Access') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-white md:text-5xl">
                {{ __('Sign in to your account') }}
            </h1>
            <p class="mt-6 max-w-xl text-base leading-8 text-slate-400 md:text-lg">
                {{ __('Manage subscriptions, product access, usage insights, and support from one secure account.') }}
            </p>

            <div class="mt-9 grid max-w-xl grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm font-semibold text-white">{{ __('Licenses') }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Keep product access in sync.') }}</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm font-semibold text-white">{{ __('Billing') }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Review plans and invoices.') }}</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm font-semibold text-white">{{ __('Support') }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Connect requests to your account.') }}</p>
                </div>
            </div>
        </div>

        <div class="mx-auto w-full max-w-[440px]">
            <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-8">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04]">
                        <img src="{{ asset('favicon-512.png') }}" alt="{{ config('app.name') }}" class="h-12 w-12">
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-white">
                        {{ __('Welcome back') }}
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-slate-400">
                        {{ __('Use your Google account to continue securely.') }}
                    </p>
                </div>

                @if(session('error'))
                    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-center text-sm text-red-300">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-center text-sm text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="flex justify-center">
                    {{-- This div is automatically targeted by the GIS script loaded in layout. --}}
                    <div class="g_id_signin"
                         data-type="standard"
                         data-shape="pill"
                         data-theme="filled_blue"
                         data-text="continue_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                </div>

                <div class="mt-7 rounded-lg border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-center text-xs leading-5 text-slate-500">
                        {{ __('This site only uses basic Google profile information for authentication and account access.') }}
                    </p>
                </div>

                <p class="mt-7 text-center text-sm text-slate-400">
                    {{ __('Browse as Guest') }}
                    <span class="text-slate-600">/</span>
                    <a href="{{ localized_route('home') }}" class="font-medium text-primary-400 transition-colors hover:text-primary-300">
                        {{ __('Return to Home') }}
                    </a>
                </p>

                <div class="mt-8 border-t border-white/10 pt-5 text-center text-xs leading-6 text-slate-500">
                    <p>{{ __('By signing in, you agree to our') }}</p>
                    <p>
                        <a href="{{ localized_route('terms') }}" class="text-slate-300 transition-colors hover:text-white">{{ __('Terms of Service') }}</a>
                        <span class="text-slate-600"> & </span>
                        <a href="{{ localized_route('privacy') }}" class="text-slate-300 transition-colors hover:text-white">{{ __('Privacy Policy') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
