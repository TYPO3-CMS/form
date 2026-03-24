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

namespace TYPO3\CMS\Form\DependencyInjection;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlCollector;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlConfiguration;

/**
 * Symfony service configurator for {@see FormYamlCollector}.
 *
 * Iterates over all active TYPO3 packages and registers every form YAML set
 * found under {@code Configuration/Form/<SetName>/} with the collector.
 *
 * Each set directory must contain a {@code config.yaml}
 * with the actual form configuration (loaded in both frontend and backend).
 *
 * Sets whose declared {@code name} in {@code config.yaml} appears in
 * {@code $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']}
 * are skipped.
 *
 * @internal
 */
final readonly class FormYamlCollectorConfigurator
{
    public function __construct(
        private PackageManager $packageManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Populates the given {@see FormYamlCollector} with all auto-discovered
     * form YAML configurations across all active extensions.
     *
     * Note: {@see $GLOBALS['TYPO3_CONF_VARS']} is read at service-instantiation
     * time (not at DI-compile time), so ext_localconf.php values are available.
     */
    public function configure(FormYamlCollector $collector): void
    {
        // Sets listed here (by their config.yaml "name" field) are excluded from loading.
        // Example in ext_localconf.php:
        //   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'][] = 'vendor/set-name';
        $disabledSets = (array)($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'] ?? []);

        foreach ($this->packageManager->getActivePackages() as $package) {
            $formConfigPath = $package->getPackagePath() . 'Configuration/Form';

            if (!is_dir($formConfigPath)) {
                continue;
            }

            $extensionKey = $package->getPackageKey();

            try {
                $finder = Finder::create()
                    ->files()
                    ->depth(1)
                    ->sortByName()
                    ->name('config.yaml')
                    ->in($formConfigPath);
            } catch (\InvalidArgumentException) {
                // Directory exists but is not traversable
                continue;
            }

            foreach ($finder as $fileInfo) {
                $setDirectory = dirname($fileInfo->getPathname());
                $setDirectoryName = basename($setDirectory);

                try {
                    $config = Yaml::parseFile($fileInfo->getPathname()) ?? [];
                } catch (ParseException $e) {
                    $this->logger->warning(
                        'EXT:form skipped form set: could not parse config.yaml.',
                        [
                            'file' => $fileInfo->getPathname(),
                            'error' => $e->getMessage(),
                        ]
                    );
                    continue;
                }

                if (!is_array($config)) {
                    $this->logger->warning(
                        'EXT:form skipped form set: config.yaml did not return an array.',
                        ['file' => $fileInfo->getPathname()]
                    );
                    continue;
                }

                // Skip disabled sets. Matching is done against the declared "name" in config.yaml
                // (e.g. "my-vendor/my-set"), NOT against the directory name, so that renaming
                // a set directory does not break the disable list.
                $declaredName = (string)($config['name'] ?? '');
                if ($declaredName !== '' && in_array($declaredName, $disabledSets, true)) {
                    continue;
                }

                $priority = (int)($config['priority'] ?? 100);
                $virtualBase = 'EXT:' . $extensionKey . '/Configuration/Form/' . $setDirectoryName . '/';

                $collector->add(new FormYamlConfiguration(
                    path: $virtualBase . 'config.yaml',
                    priority: $priority,
                    setName: $declaredName,
                ));
            }
        }
    }
}
