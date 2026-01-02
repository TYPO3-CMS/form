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

namespace TYPO3\CMS\Form\Storage\Permission;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;

/**
 * Permission checker for database-stored form definitions
 *
 * Encapsulates all backend user permission checks for the database storage adapter:
 * - TCA table existence checks
 * - Table read/write access
 * - Page-level access (web mounts, page permissions)
 *
 * @internal
 */
final readonly class DatabasePermissionChecker
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Check if the current backend user has read permissions for the given page
     */
    public function hasReadPermission(int $pageId): bool
    {
        if (!$this->hasBackendUser()) {
            return false;
        }
        return $this->tcaSchemaFactory->has(FormDefinitionRepository::TABLE_NAME)
            && $this->hasTableReadAccess()
            && $this->hasPageAccess($pageId);
    }

    /**
     * Check if the current backend user has write permissions for the given page
     */
    public function hasWritePermission(int $pageId): bool
    {
        if (!$this->hasBackendUser()) {
            return false;
        }
        return $this->tcaSchemaFactory->has(FormDefinitionRepository::TABLE_NAME)
            && $this->hasTableWriteAccess()
            && $this->hasPageAccess($pageId);
    }

    /**
     * Assert that the current backend user has write permissions for the page
     * a given form record is stored on.
     *
     * @throws PersistenceManagerException
     */
    public function assertWriteAccessForRecord(int $uid, ?array $record): void
    {
        $pid = (int)($record['pid'] ?? throw new PersistenceManagerException(
            sprintf('The form with uid "%d" has no valid pid.', $uid),
            1767199436
        ));

        if (!$this->hasWritePermission($pid)) {
            throw new PersistenceManagerException(
                sprintf('Access denied: You do not have permission to access forms on page "%d".', $pid),
                1767199442
            );
        }
    }

    private function hasPageAccess(int $pageId): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($pageId <= 0) {
            return true;
        }

        $pageRow = BackendUtility::getRecord('pages', $pageId);
        if ($pageRow === null) {
            return false;
        }

        // For all other pages, check web mount and page permissions
        if ($backendUser->isInWebMount($pageId) === null) {
            return false;
        }
        return $backendUser->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW);
    }

    private function hasTableReadAccess(): bool
    {
        return $this->getBackendUser()->check('tables_select', FormDefinitionRepository::TABLE_NAME);
    }

    private function hasTableWriteAccess(): bool
    {
        return $this->getBackendUser()->check('tables_modify', FormDefinitionRepository::TABLE_NAME);
    }

    private function hasBackendUser(): bool
    {
        return ($GLOBALS['BE_USER'] ?? null) instanceof BackendUserAuthentication;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
