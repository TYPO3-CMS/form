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
 * Collects all auto-discovered form YAML configuration files across
 * all active TYPO3 extensions.
 *
 * Each extension can provide YAML files under
 *   EXT:my_extension/Configuration/Form/<SetName>/
 * with an accompanying config.yaml that declares name, label and priority.
 * The collector is populated by {@see \TYPO3\CMS\Form\DependencyInjection\FormYamlCollectorConfigurator}
 * and provides a priority-sorted list of file paths to {@see ConfigurationManager}.
 *
 * @internal
 */
final class FormYamlCollector
{
    /** @var list<FormYamlConfiguration> */
    private array $configurations = [];

    public function add(FormYamlConfiguration $configuration): void
    {
        $this->configurations[] = $configuration;
    }

    /**
     * Returns all registered file paths sorted by ascending priority
     * (lower = loaded first = acts as base, higher = override).
     *
     * @return list<string>
     */
    public function getPaths(): array
    {
        $sorted = $this->configurations;
        usort($sorted, static fn(FormYamlConfiguration $a, FormYamlConfiguration $b): int => $a->priority <=> $b->priority);

        return array_values(array_map(
            static fn(FormYamlConfiguration $c): string => $c->path,
            $sorted
        ));
    }

    /**
     * Returns all registered configurations, regardless of priority.
     * Useful for diagnostic/debug purposes.
     *
     * @return list<FormYamlConfiguration>
     */
    public function getAllConfigurations(): array
    {
        return array_values($this->configurations);
    }
}
