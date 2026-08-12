<?php

namespace Tests\Unit;

use App\Models\MaterialRequest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Tests\TestCase;

class ArchitectureConventionTest extends TestCase
{
    public function test_php_class_names_match_their_file_names(): void
    {
        foreach ($this->phpFiles(app_path()) as $file) {
            $contents = file_get_contents($file);
            if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $namespace)
                || ! preg_match('/^(?:abstract\s+)?(?:final\s+)?(?:class|trait|enum)\s+(\w+)/m', $contents, $class)) {
                continue;
            }

            $fqcn = $namespace[1].'\\'.$class[1];
            $this->assertSame(pathinfo($file, PATHINFO_FILENAME), $class[1], "Nama file dan class {$fqcn} harus sama persis.");
            $this->assertTrue(class_exists($fqcn) || trait_exists($fqcn) || enum_exists($fqcn), "{$fqcn} harus dapat di-autoload.");
            $this->assertSame(realpath($file), realpath((new ReflectionClass($fqcn))->getFileName()));
        }
    }

    public function test_controllers_delegate_validation_to_form_requests(): void
    {
        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $contents = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/(?:request\(\)|\$\w+)\s*->\s*validate\s*\(/', $contents, "Controller {$file} masih memakai inline validation.");
        }
    }

    public function test_routes_are_split_by_domain_and_cacheable(): void
    {
        $web = file_get_contents(base_path('routes/web.php'));
        foreach (['auth.php', 'warehouse.php', 'procurement.php', 'finance.php', 'accounting.php', 'assets-and-services.php'] as $routeFile) {
            $this->assertStringContainsString($routeFile, $web);
            $this->assertFileExists(base_path('routes/'.$routeFile));
        }

        $this->artisan('route:cache')->assertSuccessful();
        $this->artisan('route:clear')->assertSuccessful();
    }

    public function test_document_status_values_use_uppercase_machine_codes(): void
    {
        $this->assertSame('PENDING', MaterialRequest::PENDING);
        $this->assertSame('APPROVED', MaterialRequest::APPROVED);
        $this->assertSame('REJECTED', MaterialRequest::REJECTED);
        $this->assertSame(0, MaterialRequest::whereRaw('BINARY status != BINARY UPPER(status)')->count());
    }

    public function test_schema_uses_fixed_point_numbers_and_no_legacy_intermediate_tables(): void
    {
        $legacyTables = ['admin_namagudang', 'invoicelpbs', 'invoicelpbdetails', 'lpbdetails', 'kategoribahan'];

        foreach ($this->phpFiles(database_path('migrations')) as $file) {
            $contents = file_get_contents($file);
            $this->assertStringNotContainsString('$table->double(', $contents, "Migration {$file} masih memakai floating-point untuk data bisnis.");
            foreach ($legacyTables as $legacyTable) {
                $this->assertDoesNotMatchRegularExpression(
                    "/['\"]".preg_quote($legacyTable, '/')."['\"]/",
                    $contents,
                    "Migration {$file} masih memakai nama tabel legacy {$legacyTable}."
                );
            }
        }
    }

    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
