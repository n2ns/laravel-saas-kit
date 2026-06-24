<?php

namespace App\Providers\Filament;

use App\Filament\Pages\OperationsDashboard;
use App\Filament\Pages\SiteAccessAnalytics;
use App\Http\Middleware\SetFilamentLocale;
use App\Support\LocaleProfile;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('app.admin_path', 'admin'))
            ->login()
            // Desktop-optimized color scheme
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Sky,
            ])
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Dark)
            // Desktop-first layout optimizations
            ->maxContentWidth(Width::Full)
            ->sidebarWidth('14rem')
            ->sidebarCollapsibleOnDesktop()
            // Navigation organization
            ->navigationGroups([
                '数据中心' => NavigationGroup::make()
                    ->label('数据中心')
                    ->icon('heroicon-o-chart-bar'),
                'System' => NavigationGroup::make()
                    ->label('System')
                    ->icon('heroicon-o-shield-check'),
                'Products' => NavigationGroup::make()
                    ->label('Products')
                    ->icon('heroicon-o-language'),
                '用户管理' => NavigationGroup::make()
                    ->label('用户管理')
                    ->icon('heroicon-o-users'),
                '产品管理' => NavigationGroup::make()
                    ->label('产品管理')
                    ->icon('heroicon-o-rectangle-stack'),
                '商业运营' => NavigationGroup::make()
                    ->label('商业运营')
                    ->icon('heroicon-o-currency-dollar'),
                '内容管理' => NavigationGroup::make()
                    ->label('内容管理')
                    ->icon('heroicon-o-document-text'),
                '系统配置' => NavigationGroup::make()
                    ->label('系统配置')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            // Desktop-optimized compact CSS
            ->renderHook(
                'panels::body.start',
                fn () => new HtmlString('<style>
                    /* Ensure notifications are visible */
                    .fi-notifications { z-index: 9999 !important; }
                    
                    /* Compact layout for desktop with overflow protection */
                    .fi-main { 
                        padding: 0.75rem 1.5rem !important; 
                        max-width: 100vw !important;
                        overflow-x: hidden !important;
                    }
                    .fi-main-ctn { 
                        gap: 0.5rem !important; 
                        max-width: 100% !important;
                    }
                    
                    /* Smaller base font for density */
                    .fi-body { font-size: 0.875rem !important; }
                    
                    /* Tighter table rows */
                    .fi-ta-row { --c-table-row-padding-y: 0.5rem; }
                    .fi-ta-cell { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
                    
                    /* Striped tables */
                    .fi-ta-row:nth-child(even) { background-color: rgba(0, 0, 0, 0.02); }
                    .dark .fi-ta-row:nth-child(even) { background-color: rgba(255, 255, 255, 0.02); }
                    
                    /* Hover effect */
                    .fi-ta-row:hover { background-color: rgba(251, 191, 36, 0.05) !important; }
                    
                    /* Compact form fields */
                    .fi-fo-field-wrp { margin-bottom: 0.75rem !important; }
                    
                    /* Smaller badges */
                    .fi-badge { font-size: 0.75rem !important; padding: 0.125rem 0.5rem !important; }
                    
                    /* Compact sidebar */
                    .fi-sidebar-nav-item { padding: 0.375rem 0.5rem !important; }
                    
                    /* Better scrollbars for desktop */
                    ::-webkit-scrollbar { width: 8px; height: 8px; }
                    ::-webkit-scrollbar-track { background: transparent; }
                    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 4px; }
                    ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.3); }
                    .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
                    .dark ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
                    
                    /* Notification Fix */
                    .fi-notifications { z-index: 99999 !important; }
                </style>
                <script>
                    document.addEventListener("livewire:init", () => {
                        window.Livewire.on("form-validation-error", () => {
                            new FilamentNotification()
                                .title("验证失败")
                                .body("请检查表单输入")
                                .danger()
                                .send()
                        })
                    })
                </script>')
            )
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                OperationsDashboard::class,
                SiteAccessAnalytics::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                SetFilamentLocale::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(LocaleProfile::supported()));
    }
}
