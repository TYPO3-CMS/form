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

namespace TYPO3\CMS\Form\Service;

/**
 * Result of a single form transfer operation
 *
 * @internal
 */
final readonly class FormTransferResult
{
    public function __construct(
        public string $sourceIdentifier,
        public string $targetIdentifier,
        public string $formIdentifier,
        public string $formName,
        public bool $sourceDeleted = false,
        public ?string $deletionError = null,
    ) {}

    public function isFullySuccessful(): bool
    {
        return $this->deletionError === null;
    }
}
