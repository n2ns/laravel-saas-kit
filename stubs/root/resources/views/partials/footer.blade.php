{{-- Footer Component --}}
<footer class="bg-bg-body border-t border-white/5 pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Brand -->
            <div class="md:col-span-1 space-y-6">
                <div>
                    <a href="{{ localized_route('home') }}" class="inline-flex items-center gap-3 mb-3 hover:opacity-90 transition-opacity">
                        <img src="{{ asset('favicon-512.png') }}" alt="{{ config('app.name') }}" class="h-7 w-7 rounded">
                        <span class="font-heading text-lg font-bold text-white">{{ config('app.name') }}</span>
                    </a>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        {{ __('messages.footer.company_desc') }}
                    </p>
                </div>

            </div>

            <div>
                <h5 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">
                    {{ __('messages.footer.company') }}</h5>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ localized_route('about') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('messages.nav.about') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('products.index') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('messages.nav.products') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('blog.index') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('messages.nav.blog') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ asset('sitemap.xml') }}" target="_blank"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('messages.footer.sitemap') }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal Links -->
            <div>
                <h5 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">
                    {{ __('messages.footer.legal') }}</h5>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ localized_route('privacy') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('privacy.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('terms') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('terms.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('refund') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('refund.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('account-access') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            Account Access
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('support') }}"
                            class="text-slate-400 hover:text-primary-400 transition-colors text-sm">
                            {{ __('messages.nav.support') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h5 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">{{ __('Language') }}</h5>
                @php
                    $footerLangMap = [
                        'en'    => ['flag' => 'us', 'label' => 'English', 'prefix' => ''],
                        'es'    => ['flag' => 'es', 'label' => 'Español', 'prefix' => 'es'],
                        'de'    => ['flag' => 'de', 'label' => 'Deutsch', 'prefix' => 'de'],
                        'zh_CN' => ['flag' => 'cn', 'label' => '中文', 'prefix' => 'cn'],
                    ];
                    $currentPath = request()->path();
                    $basePath = \App\Support\LocaleProfile::stripPrefixFromPath($currentPath);
                    $basePath = $basePath ?: '/';
                @endphp
                <ul class="space-y-3">
                    @foreach ($footerLangMap as $locale => $info)
                        @php
                            $prefixPath = $info['prefix'] ? '/' . $info['prefix'] : '';
                            $urlPath = $prefixPath . ($basePath === '/' ? '' : '/' . ltrim($basePath, '/'));
                        @endphp
                        <li>
                            <a href="{{ url($urlPath) }}"
                                class="flex items-center space-x-2 text-sm {{ app()->getLocale() == $locale ? 'text-primary-400 font-normal' : 'text-slate-400 hover:text-white transition-colors' }}">
                                <span class="fi fi-{{ $info['flag'] }}"></span>
                                <span>{{ $info['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="pt-8 border-t border-white/5">
            <!-- Trust Badges -->
            <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-10 mb-8">
                <!-- Payment Partners -->
                <div class="flex flex-wrap justify-center items-center gap-6 text-slate-400">
                    <span class="text-base font-normal mr-2">{{ __('messages.footer.payments_by') }}</span>

                    <!-- Stripe -->
                    <a href="https://stripe.com" target="_blank" rel="noopener noreferrer" class="group" title="Stripe">
                        <div class="h-12 px-4 flex items-center gap-3 group-hover:opacity-80 transition-all">
                            <svg class="w-6 h-6 text-[#635BFF]" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.594-7.305h.003z" />
                            </svg>
                            <span class="text-base font-bold text-slate-300">Stripe</span>
                        </div>
                    </a>

                    <!-- Lemon Squeezy -->
                    <a href="https://lemonsqueezy.com" target="_blank" rel="noopener noreferrer" class="group"
                        title="Lemon Squeezy">
                        <div class="h-12 px-4 flex items-center gap-3 group-hover:opacity-80 transition-all">
                            <svg class="w-6 h-6 text-[#FFC233]" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM15.5 16.5L12 14L8.5 16.5L9.5 12.5L6.5 10H10.5L12 6L13.5 10H17.5L14.5 12.5L15.5 16.5Z" />
                            </svg>
                            <span class="text-base font-bold text-slate-300">Lemon Squeezy</span>
                        </div>
                    </a>

                    <!-- Payoneer -->
                    <a href="https://payoneer.com" target="_blank" rel="noopener noreferrer" class="group"
                        title="Payoneer">
                        <div class="h-12 px-4 flex items-center gap-3 group-hover:opacity-80 transition-all">
                            <svg class="w-6 h-6 text-[#FF4800]" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M1.474 3.31c.234 1.802 1.035 5.642 1.398 7.263.095.459.201.853.298 1.013.501.865.907-.287.907-.287C5.644 6.616 3.17 3.597 2.38 2.787c-.139-.15-.384-.332-.608-.396-.32-.095-.374.086-.374.236.01.148.065.565.075.682zm21.835-1.463c.31.224 1.386 1.355 0 1.526-1.984.234-5.76.373-12.022 5.61C8.92 10.968 3.607 16.311.76 22.957a.181.181 0 01-.216.106c-.255-.074-.714-.352-.48-1.418.32-1.44 3.201-8.938 10.817-15.552 2.485-2.155 8.416-7.232 12.426-4.245z" />
                            </svg>
                            <span class="text-base font-bold text-slate-300">Payoneer</span>
                        </div>
                    </a>
                </div>

                <!-- Security Badges -->
                <div class="flex items-center gap-4 border-l border-white/10 pl-8">
                    <div class="flex items-center gap-3 text-slate-400">
                        <i data-lucide="shield-check" class="w-6 h-6 text-emerald-500"></i>
                        <span class="text-base font-normal">{{ __('messages.footer.ssl_secured') }}</span>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-white/5 pt-8 text-center text-slate-400 text-xs">
                <p>&copy; {{ date('Y') }} {{ config('app.company_name', config('app.name')) }}. {{ __('messages.footer.rights_reserved') }}</p>
            </div>

        </div>
    </div>
    </div>
</footer>
