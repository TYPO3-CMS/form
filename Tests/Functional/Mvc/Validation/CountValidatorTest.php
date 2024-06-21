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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Validation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Mvc\Validation\CountValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CountValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function countValidatorReturnsFalseIfInputItemsCountIsEqualToMaximum(): void
    {
        $options = ['minimum' => 1, 'maximum' => 2];
        $validator = new CountValidator();
        $validator->setOptions($options);
        $input = [
            'klaus',
            'steve',
        ];
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function countValidatorReturnsFalseIfInputItemsCountIsEqualToMinimum(): void
    {
        $options = ['minimum' => 2, 'maximum' => 3];
        $validator = new CountValidator();
        $validator->setOptions($options);
        $input = [
            'klaus',
            'steve',
        ];
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function countValidatorReturnsFalseIfInputItemsCountIsEqualToMinimumAndMaximum(): void
    {
        $options = ['minimum' => 2, 'maximum' => 2];
        $validator = new CountValidator();
        $validator->setOptions($options);
        $input = [
            'klaus',
            'steve',
        ];
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function countValidatorReturnsTrueIfInputCountHasMoreItemsAsMaximumValue(): void
    {
        $options = ['minimum' => 1, 'maximum' => 2];
        $validator = new CountValidator();
        $validator->setOptions($options);
        $input = [
            'klaus',
            'steve',
            'francine',
        ];
        self::assertTrue($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function countValidatorReturnsTrueIfInputCountHasLessItemsAsMinimumValue(): void
    {
        $options = ['minimum' => 2, 'maximum' => 3];
        $validator = new CountValidator();
        $validator->setOptions($options);
        $input = [
            'klaus',
        ];
        self::assertTrue($validator->validate($input)->hasErrors());
    }
}
