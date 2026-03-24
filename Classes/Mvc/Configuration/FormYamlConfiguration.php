<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Mvc\Configuration;

/**
 * Represents a single auto-discovered form YAML configuration file.
 *
 * Carries the EXT: path, the priority used for merge ordering, and the
 * declared set name from config.yaml. A lower priority value is loaded first
 * (acts as base); higher priority values are merged on top and can override
 * earlier values.
 *
 * The {@see $setName} field mirrors the "name" key from config.yaml
 * (e.g. "my-vendor/my-set") and is used both for the disable mechanism
 * ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']) and
 * for diagnostic / logging output.
 *
 * @internal
 */
final readonly class FormYamlConfiguration
{
    /**
     * @param string $path     EXT: path to the YAML file, e.g. 'EXT:my_extension/Configuration/Form/MySet/config.yaml'
     * @param int    $priority Load order: lower = earlier = acts as base (default: 100)
     * @param string $setName  Declared "name" from config.yaml, e.g. "my-vendor/my-set". Empty string if omitted.
     */
    public function __construct(
        public string $path,
        public int $priority,
        public string $setName = '',
    ) {}
}
