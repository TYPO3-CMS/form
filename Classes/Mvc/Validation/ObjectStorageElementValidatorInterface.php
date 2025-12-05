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

namespace TYPO3\CMS\Form\Mvc\Validation;

/**
 * Marker interface for validators that operate on individual elements
 * of a collection rather than on the collection itself.
 *
 * When ProcessingRule encounters an ObjectStorage value, validators are
 * by default called with the whole collection (preserving backwards
 * compatibility). Validators implementing this interface are called
 * once per element instead.
 *
 * Example: A MimeTypeValidator validates each file individually, while
 * a CountValidator checks the total number of items in the collection.
 */
interface ObjectStorageElementValidatorInterface {}
