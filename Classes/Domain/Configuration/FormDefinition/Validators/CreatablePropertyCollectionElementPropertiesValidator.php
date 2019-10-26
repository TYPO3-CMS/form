<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;

/**
 * @internal
 */
class CreatablePropertyCollectionElementPropertiesValidator extends CollectionBasedValidator
{

    /**
     * Checks if the property collection element property is defined
     * within the form editor setup or if the property is definied within
     * the "predefinedDefaults" in the form editor setup
     * and the property value matches the predefined value
     * or if there is a valid hmac hash for the value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value)
    {
        $dto = $this->validationDto->withPropertyPath($key);

        if (!$this->getConfigurationService()->isPropertyCollectionPropertyDefinedInFormEditorSetup($dto)) {
            if (
                $this->getConfigurationService()->isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup($dto)
                && !ArrayUtility::isValidPath($this->currentElement, $this->buildHmacDataPath($dto->getPropertyPath()), '.')
            ) {
                $this->validatePropertyCollectionElementPredefinedDefaultValue($value, $dto);
            } else {
                $this->validatePropertyCollectionElementPropertyValueByHmacData(
                    $this->currentElement,
                    $value,
                    $this->sessionToken,
                    $dto
                );
            }
        }
    }

    /**
     * Throws an exception if the value from a property collection property
     * does not match the default value from the form editor setup.
     *
     * @param mixed $value
     * @param ValidationDto $dto
     * @throws PropertyException
     */
    protected function validatePropertyCollectionElementPredefinedDefaultValue(
        $value,
        ValidationDto $dto
    ): void {
        // If the property collection element is newely created, we have to compare the $value (form definition) with $predefinedDefaultValue (form setup)
        // to check the integrity (at this time we don't have a hmac on the value to check the integrity)
        $predefinedDefaultValue = $this->getConfigurationService()->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($dto);
        if ($value !== $predefinedDefaultValue) {
            $throwException = true;

            if (is_string($predefinedDefaultValue)) {
                // Last chance:
                // Get all translations (from all backend languages) for the untranslated! $predefinedDefaultValue and
                // compare the (already translated) $value (from the form definition) against the possible
                // translations from $predefinedDefaultValue.
                $untranslatedPredefinedDefaultValue = $this->getConfigurationService()->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($dto, false);
                $translations = $this->getConfigurationService()->getAllBackendTranslationsForTranslationKey(
                    $untranslatedPredefinedDefaultValue,
                    $dto->getPrototypeName()
                );

                if (in_array($value, $translations, true)) {
                    $throwException = false;
                }
            }

            if ($throwException) {
                $message = 'The value "%s" of property "%s" (form element "%s" / "%s.%s") is not equal to the default value "%s" #1528591502';
                throw new PropertyException(
                    sprintf(
                        $message,
                        $value,
                        $dto->getPropertyPath(),
                        $dto->getFormElementIdentifier(),
                        $dto->getPropertyCollectionName(),
                        $dto->getPropertyCollectionElementIdentifier(),
                        $predefinedDefaultValue
                    ),
                    1528591502
                );
            }
        }
    }
}
