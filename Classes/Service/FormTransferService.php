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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\DTO\StorageContext;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Storage\StorageAdapterFactory;
use TYPO3\CMS\Form\Storage\StorageAdapterInterface;

/**
 * Service for transferring form definitions between storage backends
 *
 * Used by the CLI command `form:formdefinition:transfer` and the
 * upgrade wizard for migrating file-based forms to database storage.
 * Handles reading from a source storage adapter, ensuring identifier
 * uniqueness, and writing to a target storage adapter.
 *
 * @internal
 */
final readonly class FormTransferService
{
    public function __construct(
        private StorageAdapterFactory $storageAdapterFactory,
        private FormPersistenceManagerInterface $formPersistenceManager,
        private ConnectionPool $connectionPool,
        private LoggerInterface $logger,
    ) {}

    /**
     * List all forms in the given source storage
     *
     * @return list<FormMetadata>
     */
    public function listSourceForms(string $sourceType, ?string $formIdentifier = null): array
    {
        $sourceAdapter = $this->storageAdapterFactory->getAdapterByType($sourceType);
        $forms = $sourceAdapter->findAll(new SearchCriteria());

        if ($formIdentifier !== null) {
            $forms = array_values(array_filter(
                $forms,
                static fn(FormMetadata $form) => $form->identifier === $formIdentifier,
            ));
        }

        return $forms;
    }

    /**
     * Read a form definition from a source storage
     */
    public function readForm(string $sourceType, string $persistenceIdentifier): FormData
    {
        return $this->storageAdapterFactory->getAdapterByType($sourceType)->read(FormIdentifier::fromString($persistenceIdentifier));
    }

    /**
     * Delete a form from a storage
     */
    public function deleteForm(string $storageType, string $persistenceIdentifier): void
    {
        $adapter = $this->storageAdapterFactory->getAdapterByType($storageType);
        $adapter->delete(FormIdentifier::fromString($persistenceIdentifier));
    }

    /**
     * Transfer a single form from source to target storage
     *
     * @return FormTransferResult
     */
    public function transferForm(
        FormMetadata $sourceForm,
        string $sourceType,
        string $targetType,
        string $targetLocation,
        bool $deleteSource = false,
    ): FormTransferResult {
        $sourceAdapter = $this->storageAdapterFactory->getAdapterByType($sourceType);
        $targetAdapter = $this->storageAdapterFactory->getAdapterByType($targetType);

        // Read from source
        $sourcePersistenceIdentifier = $sourceForm->persistenceIdentifier ?? $sourceForm->identifier;
        $formData = $sourceAdapter->read(FormIdentifier::fromString($sourcePersistenceIdentifier));

        // Ensure unique identifier in target.
        // For a move operation the source form will be deleted afterwards, so it must not be
        // counted as a duplicate. Therefore only the target adapter is checked for conflicts.
        // For a copy operation all adapters are checked to avoid the same logical identifier
        // existing in multiple storages simultaneously.
        if ($deleteSource) {
            $uniqueIdentifier = $this->getUniqueIdentifierInAdapter($targetAdapter, $formData->identifier);
        } else {
            $uniqueIdentifier = $this->formPersistenceManager->getUniqueIdentifier($formData->identifier);
        }

        // Build FormData with potentially updated identifier
        $targetFormData = $uniqueIdentifier !== $formData->identifier
            ? FormData::fromArray(array_merge($formData->toArray(), ['identifier' => $uniqueIdentifier]))
            : $formData;

        // Get unique persistence identifier in target storage
        $targetPersistenceIdentifier = $targetAdapter->getUniquePersistenceIdentifier(
            $uniqueIdentifier,
            $targetLocation,
        );

        // Write to target
        $context = $this->buildStorageContext($targetLocation);
        $savedIdentifier = $targetAdapter->write(
            FormIdentifier::fromString($targetPersistenceIdentifier),
            $targetFormData,
            $context,
        );

        // Optionally delete from source
        $sourceDeleted = false;
        $deletionError = null;
        if ($deleteSource) {
            try {
                $sourceAdapter->delete(FormIdentifier::fromString($sourcePersistenceIdentifier));
                $sourceDeleted = true;
            } catch (\Exception $e) {
                $deletionError = $e->getMessage();
            }
        }

        return new FormTransferResult(
            sourceIdentifier: $sourcePersistenceIdentifier,
            targetIdentifier: $savedIdentifier->identifier,
            formIdentifier: $uniqueIdentifier,
            formName: $sourceForm->name,
            sourceDeleted: $sourceDeleted,
            deletionError: $deletionError,
        );
    }

    /**
     * Get all registered storage type identifiers
     *
     * @return list<string>
     */
    public function getAvailableStorageTypes(): array
    {
        return $this->storageAdapterFactory->getRegisteredTypeIdentifiers();
    }

    /**
     * Check if a storage type exists
     */
    public function hasStorageType(string $typeIdentifier): bool
    {
        return $this->storageAdapterFactory->hasAdapterType($typeIdentifier);
    }

    /**
     * Get adapter for a storage type (for validation purposes)
     */
    public function getAdapter(string $typeIdentifier): StorageAdapterInterface
    {
        return $this->storageAdapterFactory->getAdapterByType($typeIdentifier);
    }

    /**
     * Get a unique form identifier by checking only the given storage adapter for conflicts.
     *
     * Used for move operations: since the source form is deleted after transfer, only the
     * target storage needs to be free of the identifier — not all storages globally.
     *
     * @throws \RuntimeException if no unique identifier can be found
     */
    private function getUniqueIdentifierInAdapter(StorageAdapterInterface $adapter, string $identifier): string
    {
        $originalIdentifier = $identifier;

        if (!$adapter->existsByFormIdentifier($identifier)) {
            return $identifier;
        }

        for ($attempts = 1; $attempts < 100; $attempts++) {
            $identifier = sprintf('%s_%d', $originalIdentifier, $attempts);
            if (!$adapter->existsByFormIdentifier($identifier)) {
                return $identifier;
            }
        }

        $identifier = $originalIdentifier . '_' . time();
        if (!$adapter->existsByFormIdentifier($identifier)) {
            return $identifier;
        }

        throw new \RuntimeException(
            sprintf('Could not find a unique identifier for form identifier "%s" after %d attempts', $originalIdentifier, $attempts),
            1742477400
        );
    }

    private function buildStorageContext(string $targetLocation): ?StorageContext
    {
        if (ctype_digit($targetLocation)) {
            return StorageContext::create((int)$targetLocation);
        }
        return null;
    }

    /**
     * Update tt_content FlexForm references from old persistence identifiers
     * to new ones.
     *
     * Uses DOM/XPath parsing to precisely target only the
     * `settings.persistenceIdentifier` field in FlexForm XML, avoiding
     * false replacements in other fields.
     *
     * @param array<string, string> $migrationMap Old persistenceIdentifier => new persistenceIdentifier
     * @return int Number of updated content element references
     */
    public function updateContentElementReferences(array $migrationMap): int
    {
        $updatedCount = 0;
        $connection = $this->connectionPool->getConnectionForTable('tt_content');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class)
        );

        $rows = $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('form_formframework')
                ),
                $queryBuilder->expr()->isNotNull('pi_flexform'),
                $queryBuilder->expr()->neq(
                    'pi_flexform',
                    $queryBuilder->createNamedParameter('')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $flexForm = $row['pi_flexform'];
            $newValue = $this->replacePersistenceIdentifierInFlexForm($flexForm, $migrationMap);

            if ($newValue !== null && $newValue !== $flexForm) {
                $connection->update(
                    'tt_content',
                    ['pi_flexform' => $newValue],
                    ['uid' => (int)$row['uid']]
                );
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Replace the persistenceIdentifier value in FlexForm XML using DOM parsing.
     *
     * Specifically targets only the <field index="settings.persistenceIdentifier">
     * element to avoid replacing values in other FlexForm fields.
     *
     * @param string $flexFormXml The raw FlexForm XML string
     * @param array<string, string> $migrationMap Old persistenceIdentifier => new persistenceIdentifier
     * @return string|null The modified XML string, or null if parsing failed or no changes were made
     */
    private function replacePersistenceIdentifierInFlexForm(string $flexFormXml, array $migrationMap): ?string
    {
        $document = new \DOMDocument();
        $previousErrorHandling = libxml_use_internal_errors(true);

        if (!$document->loadXML($flexFormXml)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
            $this->logger->warning('Could not parse FlexForm XML.');
            return null;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//field[@index="settings.persistenceIdentifier"]/value[@index="vDEF"]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $modified = false;
        foreach ($nodes as $node) {
            $currentValue = $node->nodeValue;
            if (isset($migrationMap[$currentValue])) {
                $node->nodeValue = (string)$migrationMap[$currentValue];
                $modified = true;
            }
        }

        if (!$modified) {
            return null;
        }

        return $document->saveXML();
    }
}
