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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;

/**
 * Event listener to replace the title link for form_definition records
 * in the record list, so clicking the record title opens the Form Editor
 * instead of the standard record editing form.
 *
 * @internal
 */
final readonly class ModifyFormDefinitionRecordListRowEventListener
{
    public function __construct(
        private UriBuilder $uriBuilder,
    ) {}

    #[AsEventListener('form-framework/modify-form-definition-record-list-row')]
    public function __invoke(AfterRecordListRowPreparedEvent $event): void
    {
        if ($event->getTable() !== FormDefinitionRepository::TABLE_NAME) {
            return;
        }

        $data = $event->getData();

        if (!isset($data['__label'])) {
            return;
        }

        $uid = $event->getRecord()->getUid();
        $formPersistenceIdentifier = (string)$uid;

        $returnUrl = (string)$event->getRecordList()->listURL();
        $editUrl = (string)$this->uriBuilder->buildUriFromRoute('form_editor', [
            'formPersistenceIdentifier' => $formPersistenceIdentifier,
            'returnUrl' => $returnUrl,
        ]);

        $data['__label'] = preg_replace(
            '/href="[^"]*"/',
            'href="' . htmlspecialchars($editUrl) . '"',
            $data['__label']
        );

        $event->setData($data);
    }
}
