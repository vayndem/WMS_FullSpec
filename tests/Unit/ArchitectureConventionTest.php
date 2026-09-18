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

    public function test_git_paths_and_app_references_are_case_exact(): void
    {
        $canonicalClasses = [];
        foreach ($this->phpFiles(app_path()) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], substr($file, strlen(app_path()) + 1));
            $fqcn = 'App\\'.$relative;
            $canonicalClasses[strtolower($fqcn)] = $fqcn;
        }

        foreach ([app_path(), database_path(), base_path('routes'), base_path('tests')] as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                $contents = file_get_contents($file);
                preg_match_all('/^use\s+(App\\\\[^;\s]+)\s*;|\\\\(App\\\\(?:Models|Services|Policies|Http|Providers|Traits|Exports)\\\\[A-Za-z0-9_\\\\]+)/m', $contents, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $reference = $match[1] ?: $match[2];
                    $canonical = $canonicalClasses[strtolower($reference)] ?? null;
                    if ($canonical !== null) {
                        $this->assertSame($canonical, $reference, "Referensi {$reference} pada {$file} memiliki casing yang salah; gunakan {$canonical}.");
                    }
                }
            }
        }

        exec('git ls-files app database routes tests', $trackedFiles, $exitCode);
        $this->assertSame(0, $exitCode, 'Daftar file Git harus dapat dibaca untuk audit casing PSR-4.');
        foreach (array_filter($trackedFiles, fn ($path) => str_ends_with($path, '.php')) as $trackedFile) {
            $contents = file_get_contents(base_path($trackedFile));
            if (preg_match('/^(?:abstract\s+|final\s+)?(?:class|trait|enum|interface)\s+(\w+)/m', $contents, $class)) {
                $this->assertSame($class[1].'.php', basename($trackedFile), "Path yang dicatat Git {$trackedFile} tidak case-sensitive terhadap class {$class[1]}.");
            }
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
        foreach (['auth.php', 'administration.php', 'warehouse.php', 'procurement.php', 'finance.php', 'accounting.php', 'assets-and-services.php', 'sales.php'] as $routeFile) {
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

    public function test_display_paths_never_allocate_a_document_number(): void
    {
        $pelanggar = [];

        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $isi = file_get_contents($file);
            $nama = basename($file);

            preg_match_all(
                '/public function (index|create|edit|show|preview|print|report\w*|pdf|excel)\s*\([^)]*\)[^{]*\{(.*?)\n    \}/s',
                $isi,
                $metode,
                PREG_SET_ORDER
            );

            foreach ($metode as $m) {
                if (preg_match('/->(external|internal|financial)\(/', $m[2], $panggilan)) {
                    $pelanggar[] = "{$nama}::{$m[1]}() memanggil ->{$panggilan[1]}() yang MENGALOKASIKAN nomor; jalur tampilan wajib memakai ->preview()";
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            "Nomor dokumen hanya boleh dialokasikan saat dokumen benar-benar disimpan:\n" . implode("\n", $pelanggar)
        );
    }

    public function test_every_blade_form_targets_an_existing_route_with_a_matching_verb(): void
    {
        $rute = $this->rutePerNama();
        $pelanggar = [];

        foreach ($this->bladeFiles() as $rel => $path) {
            $isi = file_get_contents($path);

            preg_match_all('/route\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/', $isi, $rujukan);

            foreach (array_unique($rujukan[1]) as $nama) {
                if (!isset($rute[$nama])) {
                    $pelanggar[] = "{$rel}: route('{$nama}') tidak terdaftar";
                }
            }

            preg_match_all(
                '/<form\b[^>]*?action="\{\{\s*route\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"][^}]*\}\}"(.*?)<\/form>/s',
                $isi,
                $formulir,
                PREG_SET_ORDER
            );

            foreach ($formulir as $form) {
                $nama = $form[1];
                $badan = $form[2];

                if (!isset($rute[$nama])) {
                    continue;
                }

                $verb = preg_match('/@method\(\s*[\'"](\w+)[\'"]/', $badan, $m) ? strtoupper($m[1]) : 'POST';

                if (str_contains($form[0], 'method="GET"') || str_contains($form[0], "method=\"get\"")) {
                    continue;
                }

                if (!str_contains($rute[$nama], $verb)) {
                    $pelanggar[] = "{$rel}: form ke '{$nama}' memakai {$verb}, route hanya menerima {$rute[$nama]}";
                }

                if (!str_contains($badan, '@csrf')) {
                    $pelanggar[] = "{$rel}: form ke '{$nama}' tidak memasang @csrf";
                }
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    public function test_no_dangling_route_or_view_reference_in_app_code(): void
    {
        $rute = $this->rutePerNama();
        $pelanggar = [];

        foreach ($this->phpFiles(app_path()) as $file) {
            $isi = file_get_contents($file);
            $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);

            if (preg_match_all('/(?<![>:$\w])route\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/', $isi, $m)) {
                foreach (array_unique($m[1]) as $nama) {
                    if (!isset($rute[$nama])) {
                        $pelanggar[] = "{$rel}: route('{$nama}') tidak terdaftar";
                    }
                }
            }

            if (preg_match_all('/(?<![>:$\w])view\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/', $isi, $m)) {
                foreach (array_unique($m[1]) as $v) {
                    $berkas = resource_path('views/' . str_replace('.', '/', $v) . '.blade.php');

                    if (!file_exists($berkas)) {
                        $pelanggar[] = "{$rel}: view('{$v}') tidak ada";
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    public function test_every_form_request_is_authorized_somewhere(): void
    {
        $endpointTamu = [
            'LoginRequest',
            'ResetPasswordRequest',
            'SendResetLinkRequest',
        ];

        $kontroler = '';

        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $kontroler .= file_get_contents($file);
        }

        $pelanggar = [];

        foreach ($this->phpFiles(app_path('Http/Requests')) as $file) {
            $isi = file_get_contents($file);
            $nama = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($nama, $endpointTamu, true)) {
                continue;
            }

            if (!preg_match('/function authorize\(\)[^{]*\{(.*?)\n    \}/s', $isi, $m)) {
                continue;
            }

            if (!preg_match('/^\s*return\s+true\s*;\s*$/m', $m[1])) {
                continue;
            }

            preg_match_all(
                '/public function (\w+)\s*\(\s*' . preg_quote($nama, '/') . '\s+\$\w+[^)]*\)[^{]*\{(.*?)\n    \}/s',
                $kontroler,
                $pemakai,
                PREG_SET_ORDER
            );

            if ($pemakai === []) {
                $pelanggar[] = "{$nama}: authorize() mengembalikan true dan tidak ada controller yang memakainya";
                continue;
            }

            foreach ($pemakai as $metode) {
                if (!str_contains($metode[2], '$this->authorize(') && !str_contains($metode[2], 'abort_unless')) {
                    $pelanggar[] = "{$nama}: authorize() mengembalikan true dan {$metode[1]}() juga tidak mengotorisasi apa pun";
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            "Setiap endpoint wajib dijaga, entah di Form Request atau di controller:\n" . implode("\n", $pelanggar)
        );
    }

    public function test_destructive_actions_are_never_reachable_by_get(): void
    {
        $bahaya = [];
        $verbSalah = [];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $rute) {
            $nama = $rute->getName();

            if (!$nama) {
                continue;
            }

            $metode = implode('|', $rute->methods());

            if (preg_match('/(destroy|delete|hapus|batalkan|void|reverse|reversal|cancel)/i', $nama)
                && str_contains($metode, 'GET')) {
                $bahaya[] = "{$nama} [{$metode}]";
            }

            if (str_ends_with($nama, '.destroy') && !str_contains($metode, 'DELETE')) {
                $verbSalah[] = "{$nama} [{$metode}]";
            }
        }

        $this->assertSame(
            [],
            $bahaya,
            "Aksi destruktif tidak boleh dapat dipicu lewat GET; crawler, prefetch browser, atau sekadar klik tautan akan menjalankannya:\n"
                . implode("\n", $bahaya)
        );

        $this->assertSame(
            [],
            $verbSalah,
            "Route bernama *.destroy harus memakai verb DELETE:\n" . implode("\n", $verbSalah)
        );
    }

    public function test_every_has_many_relation_names_its_foreign_key(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach (glob(app_path('Models/*.php')) as $file) {
            $isi = file_get_contents($file);
            $model = basename($file, '.php');
            $pola = '/function\s+(\w+)\s*\([^)]*\)[^{]*\{\s*return\s+\$this->(hasMany|hasOne|hasManyThrough|belongsToMany)\(([^;]+)\);/s';

            if (!preg_match_all($pola, $isi, $cocok, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($cocok as $relasi) {
                $diperiksa++;
                $argumen = preg_replace('/\[[^\]]*\]/', '', $relasi[3]);

                if (substr_count($argumen, ',') < 1) {
                    $pelanggar[] = "{$model}::{$relasi[1]}() -> {$relasi[2]}() tanpa foreign key eksplisit";
                }
            }
        }

        $this->assertGreaterThan(50, $diperiksa, 'Pemindaian relasi tidak menjangkau cukup model.');

        $this->assertSame(
            [],
            $pelanggar,
            "Eloquent menebak foreign key hasMany/hasOne dari NAMA CLASS PEMILIK, bukan dari kolom yang benar-benar ada. "
                . "Begitu class di-rename, relasi diam-diam menunjuk kolom lain tanpa error. Sebutkan foreign key-nya:\n"
                . implode("\n", $pelanggar)
        );
    }

    public function test_every_table_name_written_as_a_string_actually_exists(): void
    {
        $pola = [
            'DB::table' => '/DB::table\(\s*[\'"]([a-z0-9_]+)(?:\s+as\s+\w+)?[\'"]/i',
            'join' => '/->(?:join|leftJoin|rightJoin|joinSub|crossJoin)\(\s*[\'"]([a-z0-9_]+)(?:\s+as\s+\w+)?[\'"]/i',
            'from' => '/->from\(\s*[\'"]([a-z0-9_]+)(?:\s+as\s+\w+)?[\'"]/i',
            'protected $table' => '/protected\s+\$table\s*=\s*[\'"]([a-z0-9_]+)[\'"]/i',
            'aturan exists' => '/[\'"]exists:([a-z0-9_]+),/i',
            'aturan unique' => '/[\'"]unique:([a-z0-9_]+),/i',
        ];

        $hilang = [];
        $diperiksa = 0;
        $diketahui = [];

        foreach ([[app_path(), '.php'], [database_path('seeders'), '.php'], [resource_path('views'), '.blade.php']] as [$akar, $ext]) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar)) as $berkas) {
                if (!$berkas->isFile() || !str_ends_with($berkas->getFilename(), $ext)) {
                    continue;
                }

                $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $berkas->getPathname());

                foreach (file($berkas->getPathname()) as $nomor => $baris) {
                    foreach ($pola as $label => $p) {
                        if (!preg_match_all($p, $baris, $cocok)) {
                            continue;
                        }

                        foreach ($cocok[1] as $tabel) {
                            $diperiksa++;

                            if (!array_key_exists($tabel, $diketahui)) {
                                $diketahui[$tabel] = \Illuminate\Support\Facades\Schema::hasTable($tabel);
                            }

                            if (!$diketahui[$tabel]) {
                                $hilang[] = "{$rel}:" . ($nomor + 1) . " [{$label}] tabel '{$tabel}' tidak ada di database";
                            }
                        }
                    }
                }
            }
        }

        $this->assertGreaterThan(150, $diperiksa, 'Pemindaian nama tabel tidak menjangkau cukup rujukan.');

        $this->assertSame(
            [],
            array_values(array_unique($hilang)),
            "Nama tabel yang ditulis sebagai string tidak ikut terbawa saat rename, dan tidak ada yang meneriakkannya "
                . "sampai baris kode itu benar-benar dijalankan:\n" . implode("\n", array_unique($hilang))
        );
    }


    public function test_every_route_points_at_a_controller_method_that_exists(): void
    {
        $rusak = [];
        $diperiksa = 0;

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $rute) {
            $aksi = $rute->getAction('uses');

            if (!is_string($aksi) || !str_contains($aksi, '@')) {
                continue;
            }

            [$kelas, $metode] = explode('@', $aksi, 2);
            $diperiksa++;
            $label = ($rute->getName() ?: $rute->uri()) . ' [' . implode('|', $rute->methods()) . ']';

            if (!class_exists($kelas)) {
                $rusak[] = "{$label} -> controller {$kelas} tidak ada";
                continue;
            }

            if (!method_exists($kelas, $metode)) {
                $rusak[] = "{$label} -> {$kelas}::{$metode}() tidak ada";
            }
        }

        $this->assertGreaterThan(200, $diperiksa, 'Pemindaian aksi route tidak menjangkau cukup route.');

        $this->assertSame(
            [],
            $rusak,
            "Route yang menunjuk method controller yang tidak ada hanya meledak saat URL-nya benar-benar dipanggil, "
                . "dan smoke test GET tidak menjangkau route mutasi:\n" . implode("\n", $rusak)
        );
    }
    private function rutePerNama(): array
    {
        $peta = [];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $rute) {
            if ($nama = $rute->getName()) {
                $peta[$nama] = implode('|', $rute->methods());
            }
        }

        return $peta;
    }

    private function bladeFiles(): array
    {
        $daftar = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $daftar[str_replace(DIRECTORY_SEPARATOR, '/', $rel)] = $file->getPathname();
            }
        }

        return $daftar;
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
