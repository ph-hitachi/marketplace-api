<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateOverview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overview:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the OVERVIEW.md file dynamically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating OVERVIEW.md...');
        $readmePath = base_path('../OVERVIEW.md');

        $content = "# Marketplace API — Project Overview\n\n";
        $content .= "This document provides a complete and detailed overview of the application's directory structure, the full routes outline from `routes/api.php`, and the latest automated test execution results.\n\n---\n\n";

        // Section 1: Complete Folder Structure
        $content .= "## 1. Complete Folder Structure\n\n```\n";
        $content .= "src/\n";
        $content .= $this->generateTree(base_path(), '', ['vendor', 'node_modules', 'storage', 'bootstrap', 'public', 'config']);
        $content .= "```\n\n---\n\n";

        // Section 2: API Routes Outline
        $content .= "## 2. API Routes Outline (`routes/api.php`)\n\n";
        $content .= "Below is the routing layout configured in the application:\n\n```php\n";
        $content .= File::get(base_path('routes/api.php'));
        $content .= "\n```\n\n---\n\n";

        // Section 3: Test Results
        $content .= "## 3. Test Results\n\n";
        $content .= "*Note: As per project constraints for a rapid 2-hour implementation, comprehensive automated testing has been disabled for this MVP iteration.*\n\n```bash\n";
        $content .= "   Tests Skipped\n   The automated test suite has been disabled to accelerate MVP delivery.\n```\n";

        File::put($readmePath, $content);

        $this->info("OVERVIEW.md successfully generated at: {$readmePath}");
    }

    private function generateTree($dir, $prefix = '', $exclude = [])
    {
        $result = '';
        $files = array_diff(scandir($dir), ['.', '..']);
        
        // Filter excludes and some dot files
        $files = array_filter($files, function($file) use ($exclude) {
            return !in_array($file, $exclude) && substr($file, 0, 1) !== '.' && $file !== 'artisan' && $file !== 'phpunit.xml' && $file !== 'composer.json' && $file !== 'composer.lock' && $file !== 'package.json' && $file !== 'vite.config.js' && $file !== 'package-lock.json';
        });

        // Specific filtering based on the original structure (we only want app, database, docs, routes, tests)
        if ($prefix === '') {
            $files = array_filter($files, function($file) {
                return in_array($file, ['app', 'database', 'docs', 'routes', 'tests']);
            });
        }

        $files = array_values($files);
        $count = count($files);

        foreach ($files as $index => $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $isLast = ($index === $count - 1);
            $connector = $isLast ? '└── ' : '├── ';

            if (is_dir($path)) {
                $result .= $prefix . $connector . $file . "/\n";
                $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
                $result .= $this->generateTree($path, $newPrefix, []);
            } else {
                $result .= $prefix . $connector . $file . "\n";
            }
        }
        return $result;
    }
}
