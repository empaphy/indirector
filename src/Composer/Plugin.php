<?php

/**
 * @noinspection ReturnTypeCanBeDeclaredInspection
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector\Composer;

use Composer\Autoload\AutoloadGenerator;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{
    /**
     * @var AutoloadGenerator|null
     */
    private $originalAutoloadGenerator = null;

    /**
     * Apply plugin modifications to Composer
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->originalAutoloadGenerator = $composer->getAutoloadGenerator();
        $composer->setAutoloadGenerator(new Autoload\AutoloadGenerator($composer->getEventDispatcher(), $io));
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $composer->setAutoloadGenerator(
            $this->originalAutoloadGenerator ?? new AutoloadGenerator($composer->getEventDispatcher(), $io)
        );
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
    }
}
