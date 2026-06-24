<?php

namespace N2ns\SaasKit\Tests;

use N2ns\SaasKit\Console\InstallCommand;

class InstallCommandTest extends TestCase
{
    public function test_install_command_is_registered(): void
    {
        $this->assertInstanceOf(InstallCommand::class, $this->app->make(InstallCommand::class));
    }

    public function test_template_files_are_packaged(): void
    {
        $root = realpath(__DIR__.'/../stubs/root');

        $this->assertNotFalse($root);
        $this->assertFileExists($root.'/app/Providers/AppServiceProvider.php');
        $this->assertFileExists($root.'/app/Providers/Filament/AdminPanelProvider.php');
        $this->assertFileExists($root.'/.env.example');
        $this->assertFileExists($root.'/.env.testing');
        $this->assertFileExists($root.'/routes/web.php');
        $this->assertFileExists($root.'/routes/api.php');
        $this->assertFileExists($root.'/resources/views/home.blade.php');
        $this->assertFileExists($root.'/database/migrations/0001_01_01_000000_create_identity_tables.php');
    }

    public function test_package_exposes_test_script(): void
    {
        $composer = json_decode(
            (string) file_get_contents(__DIR__.'/../composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('phpunit', $composer['scripts']['test'] ?? null);
        $this->assertArrayNotHasKey('version', $composer);
    }

    public function test_legacy_mcp_routes_are_disabled_by_default(): void
    {
        $routes = (string) file_get_contents(__DIR__.'/../stubs/root/routes/api.php');

        $this->assertStringContainsString("env('SAAS_KIT_LEGACY_MCP_ROUTES', false)", $routes);
        $this->assertStringContainsString("prefix('mcp')->group", $routes);
    }

    public function test_post2site_documentation_is_packaged(): void
    {
        $this->assertFileExists(__DIR__.'/../docs/POST2SITE.md');
    }
}
