<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        $compiler = app('blade.compiler');

        /*
        |--------------------------------------------------------------------------
        | Blade guardrails for PHP 8.5 / Laravel 13
        |--------------------------------------------------------------------------
        | These two callbacks run at the very start of every Blade compilation,
        | before Laravel's own preprocessors. They neutralise two upstream bugs
        | that surfaced on PHP 8.5:
        |
        |  (1) `$forElseCounter` is a singleton-level counter on BladeCompiler.
        |      Any leaked/corrupted state produces `$__empty_-N = true;`, which
        |      then breaks the exception renderer's markdown.blade.php template
        |      with a fatal ParseError. We reset it to 0 per compile.
        |
        |  (2) Laravel's `storePhpBlocks()` uses the regex
        |          /(?<!@)@php(.*?)@endphp/s
        |      which, when a template mixes the shorthand `@php(expression)`
        |      with a later `@php ... @endphp` block, greedily captures from
        |      the shorthand's `@php` up to the block's `@endphp`. Everything
        |      in between is consumed as raw PHP and the shorthand is left
        |      uncompiled, producing "Undefined variable" errors downstream
        |      (e.g. `$statusStyles` in hr/credentials.blade.php).
        |
        |      We pre-compile every `@php(expr)` occurrence into raw PHP so
        |      Laravel's regex only ever sees balanced `@php...@endphp`
        |      blocks.
        */
        Blade::prepareStringsForCompilationUsing(function (string $value) use ($compiler) {
            // (1) Reset the forElseCounter so each template compiles in a clean state.
            $reflection = new \ReflectionObject($compiler);
            if ($reflection->hasProperty('forElseCounter')) {
                $property = $reflection->getProperty('forElseCounter');
                $property->setAccessible(true);
                $property->setValue($compiler, 0);
            }

            // (2) Pre-compile the `@php(expr)` shorthand using a balanced-paren
            // recursive pattern so arguments like `@php($x = session('foo'))`
            // are captured correctly.
            return preg_replace_callback(
                '/(?<!@)@php\s*(\((?:[^()]|(?1))*\))/s',
                function (array $matches): string {
                    // Strip the outermost parentheses captured in $matches[1].
                    $expression = substr($matches[1], 1, -1);

                    return "<?php {$expression}; ?>";
                },
                $value
            );
        });
    }
}
