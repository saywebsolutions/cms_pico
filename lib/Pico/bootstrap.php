<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * This file provides backward-compatible global class aliases for the
 * forked Pico CMS classes.
 *
 * @license GNU AGPL version 3 or any later version
 */

declare(strict_types=1);

// Only define aliases if the classes don't already exist (e.g., from vendor)
if (!class_exists('Pico', false)) {
    class_alias(\OCA\CMSPico\Pico\Core\Pico::class, 'Pico');
}

if (!interface_exists('PicoPluginInterface', false)) {
    class_alias(\OCA\CMSPico\Pico\Plugin\PicoPluginInterface::class, 'PicoPluginInterface');
}

if (!class_exists('AbstractPicoPlugin', false)) {
    class_alias(\OCA\CMSPico\Pico\Plugin\AbstractPicoPlugin::class, 'AbstractPicoPlugin');
}

if (!class_exists('PicoTwigExtension', false)) {
    class_alias(\OCA\CMSPico\Pico\Core\PicoTwigExtension::class, 'PicoTwigExtension');
}
