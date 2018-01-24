<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractSectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructThrowsExceptionWhenIdentifierIsEmpty()
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082501);

        $this->getAccessibleMockForAbstractClass(AbstractSection::class, ['', 'foobar']);
    }

    /**
     * @test
     */
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString()
    {
        $mock = $this->getAccessibleMockForAbstractClass(AbstractSection::class, ['foobar', 'foobar']);
        $this->assertInstanceOf(AbstractSection::class, $mock);
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionNotFoundAndSkipUnknownElementsIsFalse()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => false]);
        $rootForm
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn([]);

        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $mockAbstractSection
            ->expects($this->once())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1382364019);

        $mockAbstractSection->_call('createElement', '', '');
    }

    /**
     * @test
     */
    public function createElementReturnsUnknownElementsIfTypeDefinitionIsNotFoundAndSkipUnknownElementsIsTrue()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => true]);
        $rootForm
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn([]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturn(new UnknownFormElement('foo', 'bar'));

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);
        $result = $mockAbstractSection->createElement('foo', 'bar');
        GeneralUtility::removeSingletonInstance(ObjectManager::class, $objectManager);

        $this->assertInstanceOf(UnknownFormElement::class, $result);
        $this->assertSame('foo', $result->getIdentifier());
        $this->assertSame('bar', $result->getType());
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionIsNotSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => true]);
        $rootForm
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn(['foobar' => []]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $mockAbstractSection
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1325689855);

        $mockAbstractSection->createElement('id', 'foobar');
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionNotInstanceOfFormElementInterface()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn([]);
        $rootForm
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn(
                [
                    'foobar' => [
                        'implementationClassName' => self::class
                    ]
                ]
            );

        $mockAbstractSection
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('get')
            ->with(self::class)
            ->willReturn($this);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1327318156);
        $mockAbstractSection->createElement('id', 'foobar');

        GeneralUtility::removeSingletonInstance(ObjectManager::class, $objectManager);
    }

    /**
     * @test
     */
    public function createElementExpectedToAddAndInitializeElement()
    {
        $implementationMock = $this->getMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            ['setOptions', 'initializeFormElement']
        );

        $typeDefinition = [
            'foo' => 'bar',
            'implementationClassName' => get_class($implementationMock),
            'fizz' => 'buzz'
        ];

        $typeDefinitionWithoutImplementationClassName = $typeDefinition;
        unset($typeDefinitionWithoutImplementationClassName['implementationClassName']);

        $implementationMock
            ->expects($this->once())
            ->method('initializeFormElement');

        $implementationMock
            ->expects($this->once())
            ->method('setOptions')
            ->with($typeDefinitionWithoutImplementationClassName);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn([]);
        $rootForm
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn(['foobar' => $typeDefinition]);

        $mockAbstractSection
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('get')
            ->with(get_class($implementationMock))
            ->willReturn($implementationMock);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        $mockAbstractSection->createElement('id', 'foobar');

        GeneralUtility::removeSingletonInstance(ObjectManager::class, $objectManager);
    }
}
