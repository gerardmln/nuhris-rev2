<?php

namespace App;

use Composer\Script\Event;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

class ComposerScripts
{
    /**
     * Rebuild package discovery without spawning a PHP process.
     * Hostinger Git deploys disable proc_open, so @php artisan cannot run.
     */
    public static function discoverPackages(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $basePath = dirname($vendorDir);

        require_once $vendorDir.'/autoload.php';

        $manifest = new PackageManifest(
            new Filesystem,
            $basePath,
            $basePath.'/bootstrap/cache/packages.php'
        );

        $manifest->build();
    }
}
