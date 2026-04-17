<?php
/**
 * This file is part of Pico and has been forked for CMS Pico for Nextcloud.
 *
 * Original source: https://github.com/picocms/Pico/blob/master/lib/AbstractPicoPlugin.php
 *
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\CMSPico\Pico\Plugin;

use BadMethodCallException;
use OCA\CMSPico\Pico\Core\Pico;
use RuntimeException;

/**
 * Abstract class to extend from when implementing a Pico plugin
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.1
 */
abstract class AbstractPicoPlugin implements PicoPluginInterface
{
    /**
     * Current instance of Pico
     *
     * @var Pico
     */
    protected Pico $pico;

    /**
     * Boolean indicating if this plugin is enabled (TRUE) or disabled (FALSE)
     *
     * @var bool|null
     */
    protected ?bool $enabled = null;

    /**
     * Boolean indicating if this plugin was ever enabled/disabled manually
     *
     * @var bool
     */
    protected bool $statusChanged = false;

    /**
     * Boolean indicating whether this plugin matches Pico's API version
     *
     * @var bool|null
     */
    protected ?bool $nativePlugin = null;

    /**
     * List of plugins which this plugin depends on
     *
     * @var string[]
     */
    protected array $dependsOn = [];

    /**
     * List of plugin which depend on this plugin
     *
     * @var object[]|null
     */
    protected ?array $dependants = null;

    /**
     * Constructs a new instance of a Pico plugin
     *
     * @param Pico $pico current instance of Pico
     */
    public function __construct(Pico $pico)
    {
        $this->pico = $pico;
    }

    /**
     * {@inheritDoc}
     */
    public function handleEvent(string $eventName, array $params): void
    {
        // plugins can be enabled/disabled using the config
        if ($eventName === 'onConfigLoaded') {
            $this->configEnabled();
        }

        if ($this->isEnabled() || ($eventName === 'onPluginsLoaded')) {
            if (method_exists($this, $eventName)) {
                call_user_func_array([$this, $eventName], $params);
            }
        }
    }

    /**
     * Enables or disables this plugin depending on Pico's config
     */
    protected function configEnabled(): void
    {
        $pluginEnabled = $this->getPico()->getConfig(get_called_class() . '.enabled');
        if ($pluginEnabled !== null) {
            $this->setEnabled((bool) $pluginEnabled);
        } else {
            $pluginEnabled = $this->getPluginConfig('enabled');
            if ($pluginEnabled !== null) {
                $this->setEnabled((bool) $pluginEnabled);
            } elseif ($this->enabled) {
                $this->setEnabled(true, true, true);
            } elseif ($this->enabled === null) {
                try {
                    $this->setEnabled(true, false, true);
                } catch (RuntimeException $e) {
                    $this->enabled = false;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setEnabled(bool $enabled, bool $recursive = true, bool $auto = false): void
    {
        $this->statusChanged = (!$this->statusChanged) ? !$auto : true;
        $this->enabled = $enabled;

        if ($enabled) {
            $this->checkCompatibility();
            $this->checkDependencies($recursive);
        } else {
            $this->checkDependants($recursive);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function isStatusChanged(): bool
    {
        return $this->statusChanged;
    }

    /**
     * {@inheritDoc}
     */
    public function getPico(): object
    {
        return $this->pico;
    }

    /**
     * Returns either the value of the specified plugin config variable or the config array
     *
     * @param string|null $configName optional name of a config variable
     * @param mixed       $default    optional default value
     *
     * @return mixed
     */
    public function getPluginConfig(?string $configName = null, mixed $default = null): mixed
    {
        $pluginConfig = $this->getPico()->getConfig(get_called_class(), []);

        if ($configName === null) {
            return $pluginConfig;
        }

        return $pluginConfig[$configName] ?? $default;
    }

    /**
     * Passes all not satisfiable method calls to Pico
     *
     * @deprecated 2.1.0
     *
     * @param string $methodName name of the method to call
     * @param array  $params     parameters to pass
     *
     * @return mixed return value of the called method
     */
    public function __call(string $methodName, array $params): mixed
    {
        if (method_exists($this->getPico(), $methodName)) {
            return call_user_func_array([$this->getPico(), $methodName], $params);
        }

        throw new BadMethodCallException(
            'Call to undefined method ' . get_class($this->getPico()) . '::' . $methodName . '() '
            . 'through ' . get_called_class() . '::__call()'
        );
    }

    /**
     * Enables all plugins which this plugin depends on
     *
     * @param bool $recursive enable required plugins automatically
     *
     * @throws RuntimeException thrown when a dependency fails
     */
    protected function checkDependencies(bool $recursive): void
    {
        foreach ($this->getDependencies() as $pluginName) {
            try {
                $plugin = $this->getPico()->getPlugin($pluginName);
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    "Unable to enable plugin '" . get_called_class() . "': "
                    . "Required plugin '" . $pluginName . "' not found"
                );
            }

            if (($plugin instanceof PicoPluginInterface) && !$plugin->isEnabled()) {
                if ($recursive) {
                    if (!$plugin->isStatusChanged()) {
                        $plugin->setEnabled(true, true, true);
                    } else {
                        throw new RuntimeException(
                            "Unable to enable plugin '" . get_called_class() . "': "
                            . "Required plugin '" . $pluginName . "' was disabled manually"
                        );
                    }
                } else {
                    throw new RuntimeException(
                        "Unable to enable plugin '" . get_called_class() . "': "
                        . "Required plugin '" . $pluginName . "' is disabled"
                    );
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return $this->dependsOn;
    }

    /**
     * Disables all plugins which depend on this plugin
     *
     * @param bool $recursive disabled dependant plugins automatically
     *
     * @throws RuntimeException thrown when a dependency fails
     */
    protected function checkDependants(bool $recursive): void
    {
        $dependants = $this->getDependants();
        if ($dependants) {
            if ($recursive) {
                foreach ($this->getDependants() as $pluginName => $plugin) {
                    if ($plugin->isEnabled()) {
                        if (!$plugin->isStatusChanged()) {
                            $plugin->setEnabled(false, true, true);
                        } else {
                            throw new RuntimeException(
                                "Unable to disable plugin '" . get_called_class() . "': "
                                . "Required by manually enabled plugin '" . $pluginName . "'"
                            );
                        }
                    }
                }
            } else {
                $dependantsList = 'plugin' . ((count($dependants) > 1) ? 's' : '') . ' '
                    . "'" . implode("', '", array_keys($dependants)) . "'";
                throw new RuntimeException(
                    "Unable to disable plugin '" . get_called_class() . "': "
                    . "Required by " . $dependantsList
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDependants(): array
    {
        if ($this->dependants === null) {
            $this->dependants = [];
            foreach ($this->getPico()->getPlugins() as $pluginName => $plugin) {
                if ($plugin instanceof PicoPluginInterface) {
                    $dependencies = $plugin->getDependencies();
                    if (in_array(get_called_class(), $dependencies, true)) {
                        $this->dependants[$pluginName] = $plugin;
                    }
                }
            }
        }

        return $this->dependants;
    }

    /**
     * Checks compatibility with Pico's API version
     *
     * @throws RuntimeException thrown when the plugin's and Pico's API aren't compatible
     */
    protected function checkCompatibility(): void
    {
        if ($this->nativePlugin === null) {
            $picoClassName = get_class($this->pico);
            $picoApiVersion = defined($picoClassName . '::API_VERSION') ? $picoClassName::API_VERSION : 1;
            $pluginApiVersion = defined('static::API_VERSION') ? static::API_VERSION : 1;

            $this->nativePlugin = ($pluginApiVersion === $picoApiVersion);

            if (!$this->nativePlugin && ($pluginApiVersion > $picoApiVersion)) {
                throw new RuntimeException(
                    "Unable to enable plugin '" . get_called_class() . "': The plugin's API (version "
                    . $pluginApiVersion . ") isn't compatible with Pico's API (version " . $picoApiVersion . ")"
                );
            }
        }
    }
}
