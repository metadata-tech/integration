<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure VarDumper to display all content expanded by default
        \Symfony\Component\VarDumper\Dumper\AbstractDumper::$defaultOutput = false;

        \Symfony\Component\VarDumper\VarDumper::setHandler(function ($var) {
            $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
            $dumper->setDisplayOptions([
                'maxDepth' => 10,  // Increase max depth (default is 1)
                'maxStringLength' => 999,  // Increase max string length
                'fileLinkFormat' => true,
            ]);
            $dumper->dump((new \Symfony\Component\VarDumper\Cloner\VarCloner())->cloneVar($var));
        });
    }
}
