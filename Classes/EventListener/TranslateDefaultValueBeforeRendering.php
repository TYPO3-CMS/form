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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Translates the defaultValue property of form elements before rendering.
 */
final readonly class TranslateDefaultValueBeforeRendering
{
    public function __construct(
        private TranslationService $translationService,
    ) {}

    #[AsEventListener('form-framework/translate-default-value-before-rendered')]
    public function __invoke(BeforeRenderableIsRenderedEvent $event): void
    {
        $renderable = $event->renderable;
        if (!$renderable instanceof FormElementInterface) {
            return;
        }
        $originalDefaultValue = $renderable->getDefaultValue();
        if ($originalDefaultValue === null) {
            return;
        }
        // Array defaultValues (e.g. MultiCheckbox pre-selections) contain option keys, not
        // human-readable labels. Translating them would break the value-to-option mapping.
        // The option labels themselves are translated via properties.options.[*] instead.
        if (is_array($originalDefaultValue)) {
            return;
        }
        try {
            $translatedDefaultValue = $this->translationService->translateFormElementValue(
                $renderable,
                ['defaultValue'],
                $event->formRuntime,
            );
        } catch (\Throwable) {
            // Translation may fail if site/language configuration is not available in the request.
            return;
        }
        if ($translatedDefaultValue !== null && $translatedDefaultValue !== $originalDefaultValue) {
            $renderable->setDefaultValue($translatedDefaultValue);
        }
    }
}
