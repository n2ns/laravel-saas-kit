@extends('layouts.app')

@section('title', 'Product Account — Secure Sign-In')
@section('meta_description', 'Product Account provides secure authentication for your product site using Google Sign-In.')

@section('content')
<div class="bg-bg-body">

    <!-- Hero -->
    <section class="py-16 md:py-24 border-b border-black/[0.08] relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-primary-500/8 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute inset-0 bg-dot-grid opacity-[0.025] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl">
                <p class="section-label mb-3">Sign-In</p>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-950 mb-6 leading-tight tracking-tighter">
                    Product Account
                </h1>
                <p class="text-slate-600 max-w-2xl leading-relaxed text-base md:text-lg">
                    Product Account is the identity layer for this product site.
                    Sign in with Google to access your dashboard, subscriptions, and product features.
                </p>
            </div>
        </div>
    </section>

    <!-- What is Product Account -->
    <section class="section-lg border-b border-black/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="section-label mb-3">About</p>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-950 mb-6 tracking-tight">What is Product Account?</h2>
                <div class="space-y-4 text-slate-600 leading-relaxed">
                    <p>
                        Product Account is the authentication platform that powers sign-in for this site.
                        Use one Google account to access the web dashboard, subscriptions, API access, and any connected clients.
                    </p>
                    <p>
                        We use Google Sign-In (OAuth 2.0) as the authentication method. This site only receives your name,
                        email address, and profile picture from Google — nothing else. We do not request access to your Gmail,
                        Google Drive, or any other Google services.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products using this account -->
    <section class="section-lg border-b border-black/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="section-label mb-3">Products</p>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-950 mb-10 tracking-tight">Products Using This Account</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Starter Product -->
                <div class="ui-card p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-primary-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-slate-950 font-semibold mb-1">Starter Product</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Replace this starter product with your own product name, billing plans, blog content, and connected clients.
                                Sign in to manage subscriptions and access product features.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Web Dashboard -->
                <div class="ui-card p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-slate-950 font-semibold mb-1">Web Dashboard</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Manage subscriptions, orders, device sessions, API keys, and account settings from the web dashboard.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- How we use Google Sign-In -->
    <section class="section-lg border-b border-black/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="section-label mb-3">Privacy & Security</p>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-950 mb-4 tracking-tight">How We Use Your Google Data</h2>
                <p class="text-slate-600 leading-relaxed mb-8">
                    When you sign in with Google, this site requests access to your basic profile information
                    using the <code class="text-primary-300 bg-white px-1 rounded">openid</code>,
                    <code class="text-primary-300 bg-white px-1 rounded">email</code>, and
                    <code class="text-primary-300 bg-white px-1 rounded">profile</code> scopes only.
                    The table below explains exactly what we receive and why.
                </p>

                <!-- Data use table -->
                <div class="rounded-xl border border-black/10 overflow-hidden mb-8">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-black/10 bg-white">
                                <th class="text-left px-5 py-3 text-slate-700 font-semibold">Data received from Google</th>
                                <th class="text-left px-5 py-3 text-slate-700 font-semibold">How we use it</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06]">
                            <tr>
                                <td class="px-5 py-3 text-slate-950 font-mono text-xs">email</td>
                                <td class="px-5 py-3 text-slate-600">Primary identifier for your Product Account. Used to uniquely identify you and send transactional emails such as subscription receipts.</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-3 text-slate-950 font-mono text-xs">name</td>
                                <td class="px-5 py-3 text-slate-600">Displayed as your display name in the dashboard and connected client interfaces.</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-3 text-slate-950 font-mono text-xs">picture</td>
                                <td class="px-5 py-3 text-slate-600">Displayed as your avatar in the dashboard and connected client interfaces. No image data is stored by this site — only the URL is saved.</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-3 text-slate-950 font-mono text-xs">sub (Google ID)</td>
                                <td class="px-5 py-3 text-slate-600">A stable unique identifier issued by Google. Used to link your Google identity to your Product Account, enabling sign-in even if you change your email address.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 text-slate-600 text-sm leading-relaxed">
                    <p>
                        <strong class="text-slate-950">We do not access any other Google services.</strong>
                        This site does not request access to Gmail, Google Drive, Google Calendar, Google Contacts,
                        or any other Google API beyond basic identity verification.
                    </p>
                    <p>
                        The Google OAuth token is used only at the moment of sign-in to retrieve the above information.
                        It is not stored after authentication is complete.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer CTA -->
    <section class="section-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-slate-600 leading-relaxed">
                    For questions about your account, data, or privacy, see our
                    <a href="{{ localized_route('privacy') }}" class="text-primary-400 hover:text-primary-300 underline underline-offset-2">Privacy Policy</a>
                    or contact us via the
                    <a href="{{ localized_route('support') }}" class="text-primary-400 hover:text-primary-300 underline underline-offset-2">Support page</a>.
                </p>
            </div>
        </div>
    </section>

</div>
@endsection
