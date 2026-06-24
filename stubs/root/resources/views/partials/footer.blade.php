{{-- Footer Component --}}
<footer class="border-t border-white/20 pt-16 pb-8 text-white" style="background-color: #374151">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Brand -->
            <div class="md:col-span-1 space-y-6">
                <div>
                    <a href="{{ localized_route('home') }}" class="inline-flex items-center gap-3 mb-3 hover:opacity-90 transition-opacity">
                        <img src="{{ asset('favicon-512.png') }}" alt="{{ config('app.name') }}" class="h-7 w-7 rounded">
                        <span class="font-heading text-lg font-bold text-white">{{ config('app.name') }}</span>
                    </a>
                    <p class="text-white/85 text-sm leading-relaxed">
                        {{ __('messages.footer.company_desc') }}
                    </p>
                </div>
            </div>

            <div>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ localized_route('about') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('messages.nav.about') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('products.index') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('messages.nav.products') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('blog.index') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('messages.nav.blog') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ asset('sitemap.xml') }}" target="_blank"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('messages.footer.sitemap') }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal Links -->
            <div>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ localized_route('privacy') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('privacy.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('terms') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('terms.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('refund') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('refund.nav') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('account-access') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            Account Access
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('support') }}"
                            class="text-white/85 hover:text-[#fca85a] transition-colors text-sm">
                            {{ __('messages.nav.support') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div>
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
                                class="flex items-center space-x-2 text-sm {{ app()->getLocale() == $locale ? 'text-[#fca85a] font-normal' : 'text-white/85 hover:text-white transition-colors' }}">
                                <span class="fi fi-{{ $info['flag'] }}"></span>
                                <span>{{ $info['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="pt-8 border-t border-white/10">
            <!-- Copyright -->
            <div class="text-center text-white/70 text-xs">
                <p>&copy; {{ date('Y') }} {{ config('app.company_name', config('app.name')) }}. {{ __('messages.footer.rights_reserved') }}</p>
            </div>
        </div>
    </div>
</footer>
