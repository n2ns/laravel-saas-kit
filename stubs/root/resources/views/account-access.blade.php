@extends('layouts.app')

@section('title', 'SaaS Starter Account Access')
@section('meta_description', 'SaaS Starter account access for products, licenses, subscriptions, support, and Google Sign-In authentication.')

@section('content')
<section class="py-10 bg-gradient-to-br from-[#fff4e8] to-[#fff9f2] border-b border-black/10 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-500/10 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left pt-4 relative z-10">
        <div class="max-w-3xl">
            <p class="section-label mb-4">Account access</p>
            <h1 class="text-2xl md:text-5xl font-bold text-slate-950 mb-6 leading-tight tracking-tight">
                SaaS Starter Account Access
            </h1>
            <p class="text-base md:text-lg text-text-tertiary max-w-2xl leading-relaxed">
                SaaS Starter provides account-based access for products, licenses, subscriptions, purchased services, and support. Replace this starter copy with the exact account-access scope for your site.
            </p>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-base  max-w-4xl">
            <div class="space-y-10 text-left">
                <div>
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        What This App Does
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg">
                        SaaS Starter provides account-based access to products, product guides, subscriptions, and license management for this site.
                    </p>
                </div>

                <div>
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        Why Google Sign-In Is Used
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg">
                        Google Sign-In lets users create or access a SaaS Starter account without a separate password. After signing in, users can manage product licenses and subscriptions, open purchased product services, view account information, and receive product support.
                    </p>
                </div>

                <div>
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        Google Data Requested
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg">
                        SaaS Starter requests only basic Google Sign-In information: your Google account identifier, email address, name, and profile picture. This information is used for authentication, account access, fraud prevention, license lookup, subscription status, and support.
                    </p>
                </div>

                <div>
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        Google Data Not Accessed
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg">
                        SaaS Starter does not request access to Gmail, Google Drive, Google Calendar, Contacts, Photos, payment data, or other Google user content. We do not sell Google user data or use it for advertising.
                    </p>
                </div>

                <div class="rounded-2xl border border-black/10 bg-white p-6 not-prose">
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        Privacy and Terms
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg mb-5">
                        {{ config('app.name') }} handles Google Sign-In data according to our Privacy Policy and Terms of Service.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ url('/privacy') }}" class="btn-secondary px-5 py-3 text-sm rounded-xl">
                            Privacy Policy
                        </a>
                        <a href="{{ url('/terms') }}" class="btn-secondary px-5 py-3 text-sm rounded-xl">
                            Terms of Service
                        </a>
                    </div>
                </div>

                <div class="mt-12 p-6 bg-white border border-black/10 rounded-2xl not-prose">
                    <p class="text-slate-500 text-sm m-0 leading-relaxed">
                        <strong class="text-text-secondary">{{ config('app.company_name') }}</strong><br>
                        @if(config('app.company_address'))
                            {{ config('app.company_address') }}<br>
                        @endif
                        Email: <a href="mailto:{{ config('app.support_email') }}" class="text-primary-400 hover:text-primary-300 transition-colors">{{ config('app.support_email') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
