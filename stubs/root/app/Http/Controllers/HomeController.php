<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\ProductService;
use App\Support\LocaleProfile;

class HomeController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index()
    {
        $featuredProducts = $this->productService->getHomepageProducts();
        $landingProduct = count($featuredProducts) === 1 ? $featuredProducts[0] : null;
        $isSingleProductLanding = $landingProduct !== null;

        $locale = app()->getLocale();
        $defaultLocale = LocaleProfile::default();
        $latestBlogPosts = BlogPost::with('author')
            ->published()
            ->companyBlog()
            ->withLocalizedTranslations($locale)
            ->when($locale !== $defaultLocale, function ($query) use ($locale) {
                $query->whereHas('translations', function ($q) use ($locale) {
                    $q->where('locale', $locale);
                });
            })
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        return view('home', compact(
            'featuredProducts',
            'landingProduct',
            'isSingleProductLanding',
            'latestBlogPosts'
        ));
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function privacy()
    {
        return view('privacy');
    }

    public function terms()
    {
        return view('terms');
    }

    public function refund()
    {
        return view('refund');
    }

    public function accountAccess()
    {
        return view('account-access');
    }

    public function getStarted()
    {
        $catalogItems = $this->productService->getProducts();

        $products = array_values(array_filter($catalogItems, fn (array $p): bool => $p['is_product']));

        $toItem = fn (array $p): array => ['title' => $p['title'], 'description' => $p['description']];

        $paths = [
            [
                'path_key' => 'products',
                'icon' => 'fa-rocket',
                'color' => 'blue',
                'tag' => __('get_started.paths.products.tag'),
                'title' => __('get_started.paths.products.title'),
                'description' => __('get_started.paths.products.desc'),
                'items' => array_map($toItem, $products),
                'cta_label' => __('get_started.paths.products.cta'),
                'cta_url' => localized_route('products.index', ['category' => 'application-product']),
                'status_label' => __('get_started.paths.products.status_label'),
                'status_value' => __('get_started.paths.products.status_value'),
                'hint' => __('get_started.paths.products.hint'),
            ],
        ];

        return view('get_started', compact('paths'));
    }

    public function account()
    {
        return view('account');
    }
}
