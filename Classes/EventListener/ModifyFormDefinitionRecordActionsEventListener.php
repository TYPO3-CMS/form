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

use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;

/**
 * Event listener to replace the standard edit/delete actions for form_definition
 * records in the record list with form-editor-specific actions.
 *
 * - The "edit" action is replaced with a link to the Form Editor module.
 * - The "delete" action is removed, as deletion should only happen through the Form Manager.
 *
 * @internal
 */
final readonly class ModifyFormDefinitionRecordActionsEventListener
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
        private UriBuilder $uriBuilder,
    ) {}

    #[AsEventListener('form-framework/modify-form-definition-record-list-actions')]
    public function __invoke(ModifyRecordListRecordActionsEvent $event): void
    {
        if ($event->getRecord()->getMainType() !== FormDefinitionRepository::TABLE_NAME) {
            return;
        }

        $uid = $event->getRecord()->getUid();
        $formPersistenceIdentifier = (string)$uid;

        // Replace the "edit" action with a link to the Form Editor
        if ($event->hasAction('edit', ActionGroup::primary)) {
            $returnUrl = (string)$event->getRecordList()->listURL();
            $editUrl = (string)$this->uriBuilder->buildUriFromRoute('form_editor', [
                'formPersistenceIdentifier' => $formPersistenceIdentifier,
                'returnUrl' => $returnUrl,
            ]);

            $editButton = $this->componentFactory->createLinkButton()
                ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
                ->setTitle($GLOBALS['LANG']->sL('core.mod_web_list:edit'))
                ->setHref($editUrl);

            $event->setAction($editButton, 'edit', ActionGroup::primary);
        }

        // Remove the "delete" action — deletion of form_definition records
        // should only be done through the Form Manager module for now.
        $event->removeAction('delete', ActionGroup::primary);
    }
}
