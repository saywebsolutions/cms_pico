<?php
/**
 * This file is part of Pico and has been forked for CMS Pico for Nextcloud.
 *
 * Original source: https://github.com/picocms/Pico/blob/master/lib/PicoPluginInterface.php
 *
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\CMSPico\Pico\Plugin;

use RuntimeException;

/**
 * Common interface for Pico plugins
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.1
 */
interface PicoPluginInterface
{
    /**
     * Handles a event that was triggered by Pico
     *
     * @param string $eventName name of the triggered event
     * @param array  $params    passed parameters
     */
    public function handleEvent(string $eventName, array $params): void;

    /**
     * Enables or disables this plugin
     *
     * @param bool $enabled   enable (TRUE) or disable (FALSE) this plugin
     * @param bool $recursive when TRUE, enable or disable recursively
     * @param bool $auto      enable or disable to fulfill a dependency
     *
     * @throws RuntimeException thrown when a dependency fails
     */
    public function setEnabled(bool $enabled, bool $recursive = true, bool $auto = false): void;

    /**
     * Returns a boolean indicating whether this plugin is enabled or not
     *
     * @return bool|null plugin is enabled (TRUE) or disabled (FALSE)
     */
    public function isEnabled(): ?bool;

    /**
     * Returns TRUE if the plugin was ever enabled/disabled manually
     *
     * @return bool plugin is in its default state (TRUE), FALSE otherwise
     */
    public function isStatusChanged(): bool;

    /**
     * Returns a list of names of plugins required by this plugin
     *
     * @return string[] required plugins
     */
    public function getDependencies(): array;

    /**
     * Returns a list of plugins which depend on this plugin
     *
     * @return object[] dependant plugins
     */
    public function getDependants(): array;

    /**
     * Returns the plugin's instance of Pico
     *
     * @return object the plugin's instance of Pico
     */
    public function getPico(): object;
}
