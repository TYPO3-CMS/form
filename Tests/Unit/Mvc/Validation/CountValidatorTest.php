<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation;

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

use TYPO3\CMS\Form\Mvc\Validation\CountValidator;

/**
 * Test case
 */
class CountValidatorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function CountValidatorReturnsFalseIfInputItemsCountIsEqualToMaximum()
    {
        $options = ['minimum' => 1, 'maximum' => 2];
        $validator = $this->getMockBuilder(CountValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $input = [
            'klaus',
            'steve'
        ];

        $this->assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function CountValidatorReturnsFalseIfInputItemsCountIsEqualToMinimum()
    {
        $options = ['minimum' => 2, 'maximum' => 3];
        $validator = $this->getMockBuilder(CountValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $input = [
            'klaus',
            'steve'
        ];

        $this->assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function CountValidatorReturnsFalseIfInputItemsCountIsEqualToMinimumAndMaximum()
    {
        $options = ['minimum' => 2, 'maximum' => 2];
        $validator = $this->getMockBuilder(CountValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $input = [
            'klaus',
            'steve'
        ];

        $this->assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function CountValidatorReturnsTrueIfInputCountHasMoreItemsAsMaximumValue()
    {
        $options = ['minimum' => 1, 'maximum' => 2];
        $validator = $this->getMockBuilder(CountValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $input = [
            'klaus',
            'steve',
            'francine'
        ];

        $this->assertTrue($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function CountValidatorReturnsTrueIfInputCountHasLessItemsAsMinimumValue()
    {
        $options = ['minimum' => 2, 'maximum' => 3];
        $validator = $this->getMockBuilder(CountValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $input = [
            'klaus',
        ];

        $this->assertTrue($validator->validate($input)->hasErrors());
    }
}
