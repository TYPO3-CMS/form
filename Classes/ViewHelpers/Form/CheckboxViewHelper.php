<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers\Form;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper as FluidCheckboxViewHelper;

/**
 * Fix a bug within the fluid checkbox viewhelper.
 * It is not possible to pass a bool value for the 'multiple' option
 *
 * Scope: frontend
 * @api
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
 */
class CheckboxViewHelper extends FluidCheckboxViewHelper
{

    /**
     * Renders the checkbox.
     *
     * @return string
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function render()
    {
        GeneralUtility::logDeprecatedFunction();
        return parent::render();
    }
}
