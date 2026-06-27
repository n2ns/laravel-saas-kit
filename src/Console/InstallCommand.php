<?php

namespace N2ns\SaasKit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'saas-kit:install
        {--force : Overwrite existing Laravel starter files}';

    protected $description = 'Install the SaaS Kit template into the current Laravel application.';

    /**
     * Files copied from the template are application files. Composer metadata is
     * merged instead of overwritten so the host project remains the package root.
     *
     * @var array<int, string>
     */
    private array $neverCopy = [
        'composer.json',
        'composer.lock',
        'vendor',
        'node_modules',
        '.git',
    ];

    public function handle(): int
    {
        $source = realpath(__DIR__.'/../../stubs/root');

        if ($source === false) {
            $this->error('SaaS Kit stubs were not found.');

            return self::FAILURE;
        }

        $files = $this->templateFiles($source);
        $conflicts = $this->conflictingFiles($files);

        if ($conflicts !== [] && ! $this->option('force')) {
            $this->error('The install would overwrite existing files. Re-run with --force for a fresh Laravel application.');
            foreach (array_slice($conflicts, 0, 20) as $file) {
                $this->line("  - {$file}");
            }
            if (count($conflicts) > 20) {
                $this->line('  - ...');
            }

            return self::FAILURE;
        }

        if ($this->option('force')) {
            $this->prepareFreshTarget();
        }

        foreach ($files as $relativePath => $absolutePath) {
            $target = base_path($relativePath);
            File::ensureDirectoryExists(dirname($target));
            File::copy($absolutePath, $target);
        }

        $this->mergeComposerJson($source);
        $this->runComposerDumpAutoload();

        $this->info('SaaS Kit files installed.');
        $this->line('Next steps:');
        $this->line('  1. Review .env.example and configure Google, Stripe, mail, storage, and app URLs.');
        $this->line('  2. In a brand-new app, run php artisan migrate:fresh --force.');
        $this->line('  3. Run php artisan passport:keys if Passport keys are not present.');
        $this->line('  4. Run php artisan passport:ensure-social-client --create and copy the printed client values into .env.');
        $this->line('  5. Run npm install && npm run build for frontend assets.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function templateFiles(string $source): array
    {
        $files = [];

        foreach (File::allFiles($source, true) as $file) {
            $relativePath = Str::replaceFirst($source.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if ($this->shouldSkip($relativePath)) {
                continue;
            }

            $files[$relativePath] = $file->getPathname();
        }

        ksort($files);

        return $files;
    }

    private function shouldSkip(string $relativePath): bool
    {
        foreach ($this->neverCopy as $skip) {
            if ($relativePath === $skip || Str::startsWith($relativePath, $skip.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $files
     * @return array<int, string>
     */
    private function conflictingFiles(array $files): array
    {
        return array_values(array_filter(array_keys($files), fn (string $file): bool => File::exists(base_path($file))));
    }

    private function prepareFreshTarget(): void
    {
        $paths = [
            'app',
            'bootstrap/app.php',
            'bootstrap/providers.php',
            'config',
            'database/factories',
            'database/migrations',
            'database/seeders',
            'deploy',
            'public',
            'resources',
            'routes',
            'scripts',
            'tests',
            '.env.example',
            '.env.testing',
            'DEPLOYMENT.md',
            'NEW_SITE_CHECKLIST.md',
            'README.md',
            'SCRIPTS.md',
            'package-lock.json',
            'package.json',
            'phpstan.neon',
            'phpunit.xml',
            'vite.config.js',
        ];

        foreach ($paths as $path) {
            $target = base_path($path);

            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
            } elseif (File::exists($target)) {
                File::delete($target);
            }
        }
    }

    private function mergeComposerJson(string $source): void
    {
        $composerPath = base_path('composer.json');
        $templateComposerPath = $source.'/composer.json';

        if (! File::exists($composerPath) || ! File::exists($templateComposerPath)) {
            $this->warn('composer.json was not merged because the host or template composer.json file is missing.');

            return;
        }

        $composer = json_decode((string) File::get($composerPath), true, 512, JSON_THROW_ON_ERROR);
        $template = json_decode((string) File::get($templateComposerPath), true, 512, JSON_THROW_ON_ERROR);

        Arr::set($composer, 'autoload.files', $this->uniqueValues(array_merge(
            Arr::get($composer, 'autoload.files', []),
            Arr::get($template, 'autoload.files', [])
        )));

        foreach (Arr::get($template, 'autoload.psr-4', []) as $namespace => $path) {
            if (! Arr::has($composer, "autoload.psr-4.{$namespace}")) {
                Arr::set($composer, "autoload.psr-4.{$namespace}", $path);
            }
        }

        foreach (Arr::get($template, 'config.allow-plugins', []) as $plugin => $allowed) {
            if (! Arr::has($composer, "config.allow-plugins.{$plugin}")) {
                Arr::set($composer, "config.allow-plugins.{$plugin}", $allowed);
            }
        }

        File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /**
     * @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    private function uniqueValues(array $values): array
    {
        return array_values(array_unique(array_filter($values)));
    }

    private function runComposerDumpAutoload(): void
    {
        $process = new Process(['composer', 'dump-autoload'], base_path());
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warn('composer dump-autoload did not complete. Run it manually before using the installed app.');

            return;
        }

        $this->line(trim($process->getOutput()));
    }
}
