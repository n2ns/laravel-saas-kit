{{-- Navigation Header Component --}}
<nav class="fixed top-0 w-full z-50 transition-all duration-300 bg-bg-body/80 backdrop-blur-md border-b border-white/5"
     x-data="{ mobileMenuOpen: false, langMenuOpen: false, loginOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $langMap = [
                'en'    => ['flag' => 'us', 'label' => 'EN', 'prefix' => ''],
                'es'    => ['flag' => 'es', 'label' => 'ES', 'prefix' => 'es'],
                'de'    => ['flag' => 'de', 'label' => 'DE', 'prefix' => 'de'],
                'zh_CN' => ['flag' => 'cn', 'label' => '中文', 'prefix' => 'cn'],
            ];
            $activeLang = app()->getLocale();
            $currentPath = request()->path();
            $basePath = \App\Support\LocaleProfile::stripPrefixFromPath($currentPath);
            $basePath = $basePath ?: '/';
            $adminUrl = url(config('app.admin_path', 'admin'));
        @endphp
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center gap-12">
                <!-- Logo -->
                <a href="{{ localized_route('home') }}" class="flex items-center group">
                    <img src="{{ asset('favicon-512.png') }}" alt="{{ config('app.name') }}" class="h-10 w-10 group-hover:opacity-80 transition-opacity">
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ localized_route('home') }}"
                       class="relative px-2 py-2 font-normal text-[15px] leading-6 {{ request()->routeIs('home') ? 'text-white' : 'text-slate-400 hover:text-white' }} transition-colors group">
                        {{ __('messages.nav.home') }}
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform {{ request()->routeIs('home') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }} transition-transform origin-left"></span>
                    </a>
                    <a href="{{ localized_route('about') }}"
                       class="relative px-2 py-2 font-normal text-[15px] leading-6 {{ request()->routeIs('about') ? 'text-white' : 'text-slate-400 hover:text-white' }} transition-colors group">
                        {{ __('messages.nav.about') }}
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform {{ request()->routeIs('about') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }} transition-transform origin-left"></span>
                    </a>
                    <a href="{{ localized_route('products.index') }}"
                       class="relative px-2 py-2 font-normal text-[15px] leading-6 {{ request()->routeIs('products.*') ? 'text-white' : 'text-slate-400 hover:text-white' }} transition-colors group">
                        {{ __('messages.nav.products') }}
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform {{ request()->routeIs('products.*') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }} transition-transform origin-left"></span>
                    </a>
                    <a href="{{ localized_route('blog.index') }}"
                       class="relative px-2 py-2 font-normal text-[15px] leading-6 {{ request()->routeIs('blog.*') ? 'text-white' : 'text-slate-400 hover:text-white' }} transition-colors group">
                        {{ __('messages.nav.blog') }}
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform {{ request()->routeIs('blog.*') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }} transition-transform origin-left"></span>
                    </a>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-8">
                <!-- Language Switcher (Desktop) -->
                <div class="relative" x-data="{ langOpen: false }" @click.outside="langOpen = false">
                    <button @click="langOpen = !langOpen"
                            class="flex items-center gap-1.5 px-2 py-2 text-slate-400 hover:text-white transition-colors rounded-lg hover:bg-white/5">
                        <span class="fi fi-{{ $langMap[$activeLang]['flag'] ?? 'us' }} text-sm"></span>
                        <span class="text-[13px] font-normal">{{ $langMap[$activeLang]['label'] ?? 'EN' }}</span>
                        <svg class="w-3 h-3 opacity-50 transition-transform duration-200" :class="{ 'rotate-180': langOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="langOpen"
                         style="display:none"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute right-0 top-full mt-1 w-[86px] bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl shadow-black/50 py-1 z-50 overflow-hidden">
                        @foreach($langMap as $locale => $info)
                        @php
                            $prefixPath = $info['prefix'] ? '/' . $info['prefix'] : '';
                            $urlPath = $prefixPath . ($basePath === '/' ? '' : '/' . ltrim($basePath, '/'));
                        @endphp
                        <a href="{{ url($urlPath) }}"
                           class="flex items-center gap-2 px-2.5 py-2 text-sm transition-colors {{ $activeLang === $locale ? 'text-primary-400 bg-primary-500/10' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            <span class="fi fi-{{ $info['flag'] }}"></span>
                            <span>{{ $info['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Auth Button -->
                @auth
                    <div class="flex items-center gap-4">
                        <a href="{{ localized_route('get-started') }}"
                           class="bg-[#1a73e8] px-6 py-2.5 rounded text-[15px] leading-6 text-white font-bold hover:opacity-90 transition-opacity whitespace-nowrap hidden md:inline-block">
                            {{ __('Get Started') }}
                        </a>

                        <a href="{{ localized_route('dashboard') }}"
                           class="transition-transform hover:scale-105"
                           aria-label="{{ __('Dashboard') }}">
                            <img src="{{ auth()->user()['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()['name']) }}"
                                 alt="{{ auth()->user()['name'] }}"
                                 class="w-8 h-8 rounded-full border-2 border-white/10 hover:border-white/30 transition-colors">
                        </a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ $adminUrl }}"
                               class="text-slate-400 hover:text-white transition-colors font-normal">
                                <i class="fa-solid fa-gauge-high mr-1"></i>
                                {{ __('Manage') }}
                            </a>
                        @endif
                    </div>
                @else
                    <button @click="loginOpen = true"
                       class="text-slate-400 hover:text-white transition-colors text-[15px] leading-6 font-normal mr-4">
                        {{ __('messages.nav.login') }}
                    </button>
                    <a href="{{ localized_route('get-started') }}"
                       class="bg-[#1a73e8] px-6 py-2.5 rounded text-[15px] leading-6 text-white font-bold hover:opacity-90 transition-opacity whitespace-nowrap">
                        {{ __('Get Started') }}
                    </a>
                @endauth

            </div>

            <!-- Mobile Menu Button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-400 hover:text-white transition-colors">
                <i class="fa-solid fa-bars text-lg" x-show="!mobileMenuOpen"></i>
                <i class="fa-solid fa-xmark text-lg" x-show="mobileMenuOpen"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
             style="display: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="md:hidden pt-1 pb-4 border-t border-white/5 bg-bg-body">
            <div class="flex flex-col space-y-1">
                <a href="{{ localized_route('home') }}"
                   class="font-normal text-[15px] leading-[22px] px-4 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-primary-500/10 text-primary-400' : 'text-slate-400 hover:text-white' }}">
                    {{ __('messages.nav.home') }}
                </a>
                <a href="{{ localized_route('about') }}"
                   class="font-normal text-[15px] leading-[22px] px-4 py-2 rounded-lg {{ request()->routeIs('about') ? 'bg-primary-500/10 text-primary-400' : 'text-slate-400 hover:text-white' }}">
                    {{ __('messages.nav.about') }}
                </a>
                <a href="{{ localized_route('products.index') }}"
                   class="font-normal text-[15px] leading-[22px] px-4 py-2 rounded-lg {{ request()->routeIs('products.index') ? 'bg-primary-500/10 text-primary-400' : 'text-slate-400 hover:text-white' }}">
                    {{ __('messages.nav.products') }}
                </a>
                <a href="{{ localized_route('blog.index') }}"
                   class="font-normal text-[15px] leading-[22px] px-4 py-2 rounded-lg {{ request()->routeIs('blog.*') ? 'bg-primary-500/10 text-primary-400' : 'text-slate-400 hover:text-white' }}">
                    {{ __('messages.nav.blog') }}
                </a>
                <div class="grid grid-cols-4 gap-2 px-4 pt-4 border-t border-white/5">
                    @foreach($langMap as $locale => $info)
                        @php
                            $prefixPath = $info['prefix'] ? '/' . $info['prefix'] : '';
                            $urlPath = $prefixPath . ($basePath === '/' ? '' : '/' . ltrim($basePath, '/'));
                        @endphp
                        <a href="{{ url($urlPath) }}" class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-sm {{ app()->getLocale() == $locale ? 'bg-primary-500/10 text-primary-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <span class="fi fi-{{ $info['flag'] }}"></span><span>{{ $info['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <!-- Mobile Auth Button -->
                <div class="px-4 pt-4 border-t border-white/5 space-y-3">
                        @auth
                            <a href="{{ localized_route('get-started') }}"
                               class="bg-[#1a73e8] px-6 py-3 rounded text-[18px] leading-[28px] text-white font-bold hover:opacity-90 transition-opacity w-full block text-center">
                                {{ __('Get Started') }}
                            </a>

                            <a href="{{ localized_route('dashboard') }}"
                           class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg bg-gradient-to-r from-primary-600 to-primary-500 text-white font-normal">
                            <img src="{{ auth()->user()['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()['name']) }}"
                                 alt="{{ auth()->user()['name'] }}"
                                 class="w-5 h-5 rounded-full">
                            {{ __('Dashboard') }}
                        </a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ $adminUrl }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg border border-white/10 text-white font-normal hover:bg-white/5 transition-all">
                                <i class="fa-solid fa-gauge-high"></i>
                                {{ __('Manage Panel') }}
                            </a>
                        @endif
                    @else
                        <div class="flex flex-col gap-3">
                            <button @click="loginOpen = true"
                               class="text-slate-400 font-normal px-4 py-2 hover:text-white text-center">
                                {{ __('messages.nav.login') }}
                            </button>
                            <a href="{{ localized_route('get-started') }}"
                               class="bg-[#1a73e8] px-6 py-3 rounded text-[18px] leading-[28px] text-white font-bold hover:opacity-90 transition-opacity block text-center">
                                {{ __('Get Started') }}
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <template x-teleport="body">
        <div x-show="loginOpen"
             style="display: none;"
             x-effect="if(loginOpen) {
                 setTimeout(() => {
                     if(typeof google !== 'undefined' && document.getElementById('google_btn_container_modal')) {
                         google.accounts.id.renderButton(
                             document.getElementById('google_btn_container_modal'),
                             {
                                 type: 'standard',
                                 shape: 'pill',
                                 theme: 'filled_blue',
                                 text: 'signin_with',
                                 size: 'large',
                                 logo_alignment: 'left',
                                 width: 320
                             }
                         );
                     }
                 }, 50);
             }"
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">

            <!-- Backdrop -->
            <div x-show="loginOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-bg-body/80 backdrop-blur-sm transition-opacity"
                 @click="loginOpen = false"></div>

            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <!-- Modal Panel -->
                <div x-show="loginOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-2xl bg-slate-900 border border-white/10 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">

                    <!-- Close Button -->
                    <button @click="loginOpen = false" class="absolute right-4 top-4 text-slate-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>

                    <div class="px-8 pb-12 pt-10 text-center">
                        <div class="mx-auto mb-10 flex items-center justify-center gap-3">
                            <img src="{{ asset('favicon-512.png') }}" alt="{{ config('app.name') }}" class="h-10 w-10 opacity-90 hover:opacity-100 transition-opacity duration-300">
                            <span class="text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                        </div>

                        <h3 class="text-2xl md:text-4xl font-bold tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-white via-white to-slate-500 mb-4 font-bold" id="modal-title">
                            {{ __('Welcome back') }}
                        </h3>

                        <div class="mb-10">
                            <p class="text-[17px] leading-relaxed text-slate-400 max-w-[320px] mx-auto">
                                {{ __('Sign in to access your account and products.') }}
                            </p>
                        </div>

                        {{-- Action Area --}}
                        <div class="flex flex-col gap-6 justify-center items-center">
                            <div id="google_btn_container_modal" class="flex justify-center transform scale-110 hover:scale-[1.12] transition-transform duration-200"></div>

                            <p class="text-[13px] font-semibold uppercase tracking-widest text-slate-600 mt-6">
                                {{ __('Secure account access') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</nav>
