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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class YamlSourceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function loadThrowsExceptionIfFileToLoadNotExists(): void
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionCode(1480195405);
        $mockYamlSource = $this->getAccessibleMock(
            YamlSource::class,
            null,
            [new FilePersistenceSlot(new HashService()), new YamlFileLoader($this->createMock(LoggerInterface::class))]
        );
        $input = [
            'EXT:form/Resources/Forms/_example.yaml',
        ];
        $mockYamlSource->_call('load', $input);
    }

    #[Test]
    public function loadThrowsExceptionIfFileToLoadIsNotValidYamlUseSymfonyParser(): void
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionCode(1480195405);
        $mockYamlSource = $this->getAccessibleMock(
            YamlSource::class,
            null,
            [new FilePersistenceSlot(new HashService()), new YamlFileLoader($this->createMock(LoggerInterface::class))]
        );
        $input = [
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Invalid.yaml',
        ];
        $mockYamlSource->_call('load', $input);
    }

    #[Test]
    public function getHeaderFromFileReturnsHeaderPart(): void
    {
        $mockYamlSource = $this->getAccessibleMock(
            YamlSource::class,
            null,
            [new FilePersistenceSlot(new HashService()), new YamlFileLoader($this->createMock(LoggerInterface::class))],
        );
        $input = GeneralUtility::getFileAbsFileName('EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Header.yaml');
        $expected =
'# Header 1
# Header 2
';
        self::assertSame($expected, $mockYamlSource->_call('getHeaderFromFile', $input));
    }

    #[Test]
    public function loadOverruleNonArrayValuesOverArrayValues(): void
    {
        $mockYamlSource = $this->getAccessibleMock(
            YamlSource::class,
            null,
            [new FilePersistenceSlot(new HashService()), new YamlFileLoader($this->createMock(LoggerInterface::class))],
        );
        $input = [
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/OverruleNonArrayValuesOverArrayValues1.yaml',
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/OverruleNonArrayValuesOverArrayValues2.yaml',
        ];
        $expected = [
            'Form' => [
                'klaus01' => null,
                'key03' => 'value2',
            ],
        ];
        self::assertSame($expected, $mockYamlSource->_call('load', $input));
    }

    #[Test]
    public function loadRemovesVendorNamespacePrefixFromConfiguration(): void
    {
        $mockYamlSource = $this->getAccessibleMock(
            YamlSource::class,
            null,
            [new FilePersistenceSlot(new HashService()), new YamlFileLoader($this->createMock(LoggerInterface::class))],
        );
        $input = [
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/ConfigurationWithVendorNamespacePrefix1.yaml',
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/ConfigurationWithoutVendorNamespacePrefix1.yaml',
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/ConfigurationWithVendorNamespacePrefix2.yaml',
        ];
        $expected = [
            'klaus01' => [
                'key01' => 'key01_value',
                'key02' => 'key02_value_override',
                'key06' => 'key06_value_override',
                'key07' => 'key07_value_override',
            ],
            'key03' => 'key03_value',
            'key04' => 'key04_value',
            'key05' => 'key05_value_override',
            'key08' => 'key08_value_override',
        ];
        self::assertSame($expected, $mockYamlSource->_call('load', $input));
    }
}
