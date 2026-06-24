{{-- Cookie Consent Banner --}}
<div x-data="cookieConsent()" x-show="showBanner" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-0 left-0 right-0 z-50">

    <div class="w-full">
        <div class="bg-slate-200/80 backdrop-blur-xl border-t border-black/10 px-4 py-4 shadow-2xl shadow-orange-900/10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">

                    {{-- Left: Icon + Text --}}
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-orange-100 border border-orange-200 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="cookie" class="w-4 h-4 sm:w-5 sm:h-5 text-[#a34f1f]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-slate-950 font-semibold text-sm sm:text-base mb-1">{{ __('messages.cookie.title') }}</p>
                            <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">
                                {{ __('messages.cookie.description') }}
                                <a href="{{ localized_route('privacy') }}"
                                   class="text-[#a34f1f] hover:underline whitespace-nowrap">{{ __('messages.cookie.privacy_link') }}</a>
                            </p>
                        </div>
                    </div>

                    {{-- Right: Buttons - Full width on mobile --}}
                    <div class="flex items-center gap-2 sm:gap-3 w-full lg:w-auto flex-shrink-0">
                        <button @click="decline()"
                                class="flex-1 lg:flex-none px-4 sm:px-5 py-2 sm:py-2.5 text-xs font-bold text-slate-600 hover:text-slate-950 border border-black/10 hover:border-orange-200 rounded-full transition-all uppercase tracking-wider whitespace-nowrap">
                            {{ __('messages.cookie.decline') }}
                        </button>
                        <button @click="accept()"
                                class="flex-1 lg:flex-none px-4 sm:px-6 py-2 sm:py-2.5 text-xs font-bold text-white bg-[#a34f1f] hover:opacity-90 rounded-full transition-all uppercase tracking-wider shadow-lg shadow-orange-900/20 whitespace-nowrap">
                            {{ __('messages.cookie.accept') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cookieConsent', () => ({
        showBanner: false,

        init() {
            // Check if user has already made a choice
            const consent = localStorage.getItem('cookie_consent');
            if (!consent) {
                // Show banner after a short delay for better UX
                setTimeout(() => {
                    this.showBanner = true;
                }, 1000);
            }
        },

        accept() {
            localStorage.setItem('cookie_consent', 'accepted');
            this.showBanner = false;
        },

        decline() {
            localStorage.setItem('cookie_consent', 'declined');
            this.showBanner = false;
            // Disable non-essential cookies
        }
    }));
});
</script>
