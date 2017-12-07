<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

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

use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Test case
 */
class SaveToDatabaseFinisherTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function throwExceptionOnInconsistentConfigurationThrowsExceptionOnInconsistentConfiguration()
    {
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1480469086);

        $mockSaveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, [
            'dummy'
        ], [], '', false);

        $mockSaveToDatabaseFinisher->_set('options', [
            'mode' => 'update',
            'whereClause' => '',
        ]);

        $mockSaveToDatabaseFinisher->_call('throwExceptionOnInconsistentConfiguration');
    }

    /**
     * @test
     */
    public function prepareDataConvertsArrayValuesToCsv()
    {
        $elementsConfiguration = [
            'foo' => [
                'mapOnDatabaseColumn' => 'bar'
            ]
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'foo' => [
                'one',
                'two'
            ]
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->prophesize(FormElementInterface::class)->reveal());
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        self::assertSame('one,two', $databaseData['bar']);
    }

    /**
     * @test
     */
    public function executeInternalProcessesSingleTable()
    {
        $saveToDatabaseFinisher = $this->getMockBuilder(SaveToDatabaseFinisher::class)
            ->setMethods(['process'])
            ->getMock();
        $this->inject($saveToDatabaseFinisher, 'options', [
            'table' => 'tx_foo',
            'databaseColumnMappings' => [
                'foo' => 1,
            ],
        ]);

        $saveToDatabaseFinisher->expects($this->once())->method('process')->with(0);

        $saveToDatabaseFinisher->execute($this->prophesize(FinisherContext::class)->reveal());
    }

    /**
     * @return array
     */
    public function skipIfValueIsEmptyDataProvider()
    {
        return [
            'null value' => [
                'value' => null,
                'expectedEmpty' => true,
            ],
            'empty string' => [
                'value' => '',
                'expectedEmpty' => true,
            ],
            'false value' => [
                'value' => false,
                'expectedEmpty' => false,
            ],
            'space character' => [
                'value' => ' ',
                'expectedEmpty' => false,
            ],
            'zero' => [
                'value' => 0,
                'expectedEmpty' => false,
            ],
            'zero float' => [
                'value' => 0.0,
                'expectedEmpty' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider skipIfValueIsEmptyDataProvider
     * @param mixed $value
     * @param bool $expectedEmpty
     */
    public function skipIfValueIsEmptyDetectsEmptyValues($value, bool $expectedEmpty)
    {
        $elementsConfiguration = [
            'foo' => [
                'mapOnDatabaseColumn' => 'bar',
                'skipIfValueIsEmpty' => true,
            ]
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'foo' => $value
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->prophesize(FormElementInterface::class)->reveal());
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        self:self::assertSame($expectedEmpty, empty($databaseData));
    }

    /**
     * @test
     */
    public function executeInternalProcessesMultipleTables()
    {
        $saveToDatabaseFinisher = $this->getMockBuilder(SaveToDatabaseFinisher::class)
            ->setMethods(['process'])
            ->getMock();
        $this->inject($saveToDatabaseFinisher, 'options', [
            [
                'table' => 'tx_foo',
                'databaseColumnMappings' => [
                    'foo' => 1,
                ],
            ],
            [
                'table' => 'tx_bar',
                'databaseColumnMappings' => [
                    'bar' => 1,
                ],
            ],
        ]);

        $saveToDatabaseFinisher->expects($this->exactly(2))->method('process')->withConsecutive([0], [1]);

        $saveToDatabaseFinisher->execute($this->prophesize(FinisherContext::class)->reveal());
    }
}
