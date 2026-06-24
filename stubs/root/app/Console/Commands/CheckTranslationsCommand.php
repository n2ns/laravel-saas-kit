<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Plan;
use App\Models\Product;
use App\Support\LocaleProfile;
use Illuminate\Console\Command;

class CheckTranslationsCommand extends Command
{
    protected $signature = 'translations:check {--locale= : Check specific locale only}';

    protected $description = 'Check translation coverage for Product and Blog post models';

    protected array $locales;

    public function handle(): int
    {
        $targetLocale = $this->option('locale');

        $this->info('📊 Translation Coverage Report');
        $this->newLine();

        $this->checkModel(Product::class, 'Product', ['name', 'subtitle'], $targetLocale);
        // Plan excluded: plan names (Free/Pro/Enterprise) are universal and don't need translation
        $this->checkModel(BlogPost::class, 'Blog', ['title', 'content'], $targetLocale);

        return Command::SUCCESS;
    }

    public function __construct()
    {
        parent::__construct();
        $this->locales = LocaleProfile::supported();
    }

    protected function checkModel(string $modelClass, string $label, array $fields, ?string $targetLocale): void
    {
        $items = $modelClass::with([
            'translations' => fn ($query) => $query->whereIn('locale', $this->locales),
        ])->get();
        $total = $items->count();

        $this->info("━━━ {$label} ({$total} total) ━━━");

        $locales = $targetLocale ? [$targetLocale] : $this->locales;

        foreach ($locales as $locale) {
            $coverage = 0;
            $missing = [];

            foreach ($items as $item) {
                $hasTranslation = $this->hasTranslation($item, $locale, $fields[0]);
                if ($hasTranslation) {
                    $coverage++;
                } else {
                    $identifier = $item->code ?? $item->slug ?? $item->id;
                    $missing[] = $identifier;
                }
            }

            $percent = $total > 0 ? (int) round(($coverage / $total) * 100) : 0;
            $status = $percent === 100 ? '✅' : ($percent >= 50 ? '⚠️' : '❌');

            $this->line("  {$status} {$locale}: {$coverage}/{$total} ({$percent}%)");

            if (! empty($missing) && count($missing) <= 5) {
                $this->line('     Missing: '.implode(', ', $missing));
            } elseif (! empty($missing)) {
                $this->line('     Missing: '.implode(', ', array_slice($missing, 0, 3)).'... (+'.(count($missing) - 3).' more)');
            }
        }

        $this->newLine();
    }

    protected function hasTranslation($item, string $locale, string $field): bool
    {
        $defaultLocale = LocaleProfile::default();
        if ($locale === $defaultLocale) {
            $translation = $item->translations->where('locale', $defaultLocale)->first();
            if ($translation && ! empty($translation->$field)) {
                return true;
            }

            // Fallback to main table for default locale
            return ! empty($item->$field);
        }

        // For other locales, must have translation record
        $translation = $item->translations->where('locale', $locale)->first();

        return $translation && ! empty($translation->$field);
    }
}
