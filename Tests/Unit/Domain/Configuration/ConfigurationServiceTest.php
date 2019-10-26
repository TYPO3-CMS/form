<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration;

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

use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConfigurationServiceTest extends UnitTestCase
{

    /**
     * @test
     */
    public function getPrototypeConfigurationReturnsPrototypeConfiguration()
    {
        $mockConfigurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'dummy',
            ],
            [],
            '',
            false
        );

        $mockConfigurationService->_set(
            'formSettings',
            [
                'prototypes' => [
                    'standard' => [
                        'key' => 'value',
                    ],
                ],
            ]
        );

        $expected = [
            'key' => 'value',
        ];

        $this->assertSame($expected, $mockConfigurationService->getPrototypeConfiguration('standard'));
    }

    /**
     * @test
     */
    public function getPrototypeConfigurationThrowsExceptionIfNoPrototypeFound()
    {
        $mockConfigurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'dummy',
            ],
            [],
            '',
            false
        );

        $this->expectException(PrototypeNotFoundException::class);
        $this->expectExceptionCode(1475924277);

        $mockConfigurationService->_set(
            'formSettings',
            [
                'prototypes' => [
                    'noStandard' => [],
                ],
            ]
        );

        $mockConfigurationService->getPrototypeConfiguration('standard');
    }

    /**
     * @test
     */
    public function getSelectablePrototypeNamesDefinedInFormEditorSetupReturnsPrototypes()
    {
        $configurationService = $this->getAccessibleMock(ConfigurationService::class, ['dummy'], [], '', false);

        $configurationService->_set(
            'formSettings',
            [
                'formManager' => [
                    'selectablePrototypesConfiguration' => [
                        0 => [
                            'identifier' => 'standard',
                        ],
                        1 => [
                            'identifier' => 'custom',
                        ],
                        'a' => [
                            'identifier' => 'custom-2',
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            'standard',
            'custom',
        ];

        $this->assertSame($expected, $configurationService->getSelectablePrototypeNamesDefinedInFormEditorSetup());
    }

    /**
     * @test
     * @dataProvider isFormElementPropertyDefinedInFormEditorSetupDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isFormElementPropertyDefinedInFormEditorSetup(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isFormElementPropertyDefinedInFormEditorSetup($validationDto)
        );
    }

    /**
     * @test
     * @dataProvider isPropertyCollectionPropertyDefinedInFormEditorSetupDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isPropertyCollectionPropertyDefinedInFormEditorSetup(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isPropertyCollectionPropertyDefinedInFormEditorSetup($validationDto)
        );
    }

    /**
     * @test
     * @dataProvider isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup($validationDto)
        );
    }

    /**
     * @test
     * @dataProvider isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup(
                $validationDto
            )
        );
    }

    /**
     * @test
     */
    public function getFormElementPredefinedDefaultValueFromFormEditorSetupThrowsExceptionIfNoPredefinedDefaultIsAvailable(
    ) {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528578401);

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn(false);
        $validationDto = new ValidationDto(null, 'Text', null, 'properties.foo.1');

        $configurationService->getFormElementPredefinedDefaultValueFromFormEditorSetup($validationDto);
    }

    /**
     * @test
     */
    public function getFormElementPredefinedDefaultValueFromFormEditorSetupReturnsDefaultValue()
    {
        $expected = 'foo';
        $configuration = ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => $expected]]]];

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'buildFormDefinitionValidationConfigurationFromFormEditorSetup',
                'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup',
            ],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);
        $configurationService->expects($this->any())->method(
            'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn(true);

        $validationDto = new ValidationDto('standard', 'Text', null, 'properties.foo.1');

        $this->assertSame(
            $expected,
            $configurationService->getFormElementPredefinedDefaultValueFromFormEditorSetup($validationDto)
        );
    }

    /**
     * @test
     */
    public function getPropertyCollectionPredefinedDefaultValueFromFormEditorSetupThrowsExceptionIfNoPredefinedDefaultIsAvailable(
    ) {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528578402);

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn(false);
        $validationDto = new ValidationDto(
            null,
            null,
            null,
            'properties.foo.1',
            'validators',
            'StringLength'
        );

        $configurationService->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($validationDto);
    }

    /**
     * @test
     */
    public function getPropertyCollectionPredefinedDefaultValueFromFormEditorSetupReturnsDefaultValue()
    {
        $expected = 'foo';
        $configuration = ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => $expected]]]]];

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'buildFormDefinitionValidationConfigurationFromFormEditorSetup',
                'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup',
            ],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);
        $configurationService->expects($this->any())->method(
            'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn(true);

        $validationDto = new ValidationDto(
            'standard',
            null,
            null,
            'properties.foo.1',
            'validators',
            'StringLength'
        );

        $this->assertSame(
            $expected,
            $configurationService->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($validationDto)
        );
    }

    /**
     * @test
     * @dataProvider isFormElementTypeCreatableByFormEditorDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isFormElementTypeCreatableByFormEditor(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isFormElementTypeCreatableByFormEditor($validationDto)
        );
    }

    /**
     * @test
     * @dataProvider isPropertyCollectionElementIdentifierCreatableByFormEditorDataProvider
     * @param array $configuration
     * @param ValidationDto $validationDto
     * @param bool $expectedReturn
     */
    public function isPropertyCollectionElementIdentifierCreatableByFormEditor(
        array $configuration,
        ValidationDto $validationDto,
        bool $expectedReturn
    ) {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method(
            'buildFormDefinitionValidationConfigurationFromFormEditorSetup'
        )->willReturn($configuration);

        $this->assertSame(
            $expectedReturn,
            $configurationService->isPropertyCollectionElementIdentifierCreatableByFormEditor($validationDto)
        );
    }

    /**
     * @test
     */
    public function isFormElementTypeDefinedInFormSetup()
    {
        $configuration = [
            'formElementsDefinition' => [
                'Text' => [],
            ],
        ];

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['getPrototypeConfiguration'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method('getPrototypeConfiguration')->willReturn($configuration);

        $validationDto = new ValidationDto('standard', 'Text');
        $this->assertTrue($configurationService->isFormElementTypeDefinedInFormSetup($validationDto));

        $validationDto = new ValidationDto('standard', 'Foo');
        $this->assertFalse($configurationService->isFormElementTypeDefinedInFormSetup($validationDto));
    }

    /**
     * @test
     */
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfHookResultIsNoFormDefinitionValidation()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528633966);

        $configurationService = $this->getAccessibleMock(ConfigurationService::class, ['dummy'], [], '', false);
        $input = ['dummy'];

        $configurationService->_call('addAdditionalPropertyPathsFromHook', '', '', $input, []);
    }

    /**
     * @test
     */
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfPrototypeDoesNotMatch()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528634966);

        $configurationService = $this->getAccessibleMock(ConfigurationService::class, ['dummy'], [], '', false);
        $validationDto = new ValidationDto('Bar', 'Foo');
        $input = [$validationDto];

        $configurationService->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    /**
     * @test
     */
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfFormElementTypeDoesNotMatch()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528633967);

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method('isFormElementTypeDefinedInFormSetup')->willReturn(false);
        $validationDto = new ValidationDto('standard', 'Text');
        $input = [$validationDto];

        $configurationService->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    /**
     * @test
     */
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfPropertyCollectionNameIsInvalid()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528636941);

        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method('isFormElementTypeDefinedInFormSetup')->willReturn(true);
        $validationDto = new ValidationDto('standard', 'Text', null, null, 'Bar', 'Baz');
        $input = [$validationDto];

        $configurationService->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    /**
     * @test
     */
    public function addAdditionalPropertyPathsFromHookAddPaths()
    {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $configurationService->expects($this->any())->method('isFormElementTypeDefinedInFormSetup')->willReturn(true);

        $input = [
            new ValidationDto('standard', 'Text', null, 'options.xxx', 'validators', 'Baz'),
            new ValidationDto('standard', 'Text', null, 'options.yyy', 'validators', 'Baz'),
            new ValidationDto('standard', 'Text', null, 'options.zzz', 'validators', 'Custom'),
            new ValidationDto('standard', 'Text', null, 'properties.xxx'),
            new ValidationDto('standard', 'Text', null, 'properties.yyy'),
            new ValidationDto('standard', 'Custom', null, 'properties.xxx'),
        ];
        $expected = [
            'formElements' => [
                'Text' => [
                    'collections' => [
                        'validators' => [
                            'Baz' => [
                                'additionalPropertyPaths' => [
                                    'options.xxx',
                                    'options.yyy',
                                ],
                            ],
                            'Custom' => [
                                'additionalPropertyPaths' => [
                                    'options.zzz',
                                ],
                            ],
                        ],
                    ],
                    'additionalPropertyPaths' => [
                        'properties.xxx',
                        'properties.yyy',
                    ],
                ],
                'Custom' => [
                    'additionalPropertyPaths' => [
                        'properties.xxx',
                    ],
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            $configurationService->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, [])
        );
    }

    /**
     * @test
     * @dataProvider buildFormDefinitionValidationConfigurationFromFormEditorSetupDataProvider
     * @param array $configuration
     * @param array $expected
     */
    public function buildFormDefinitionValidationConfigurationFromFormEditorSetup(array $configuration, array $expected)
    {
        $configurationService = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'getCacheEntry',
                'getPrototypeConfiguration',
                'getTranslationService',
                'executeBuildFormDefinitionValidationConfigurationHooks',
                'setCacheEntry',
            ],
            [],
            '',
            false
        );

        $translationService = $this->getAccessibleMock(
            TranslationService::class,
            ['translateValuesRecursive'],
            [],
            '',
            false
        );
        $translationService->expects($this->any())->method('translateValuesRecursive')->willReturnArgument(0);

        $configurationService->expects($this->any())->method('getCacheEntry')->willReturn(null);
        $configurationService->expects($this->any())->method('getPrototypeConfiguration')->willReturn($configuration);
        $configurationService->expects($this->any())->method('getTranslationService')->willReturn($translationService);
        $configurationService->expects($this->any())
            ->method('executeBuildFormDefinitionValidationConfigurationHooks')
            ->willReturnArgument(1);
        $configurationService->expects($this->any())->method('setCacheEntry')->willReturn(null);

        $this->assertSame(
            $expected,
            $configurationService->_call('buildFormDefinitionValidationConfigurationFromFormEditorSetup', 'standard')
        );
    }

    /**
     * @return array
     */
    public function isFormElementPropertyDefinedInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1.bar'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1.foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isPropertyCollectionPropertyDefinedInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.1',
                    'validators',
                    'StringLength'
                ),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.1',
                    'validators',
                    'StringLength'
                ),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.1.bar',
                    'validators',
                    'StringLength'
                ),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.1',
                    'validators',
                    'StringLength'
                ),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.2',
                    'validators',
                    'StringLength'
                ),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Text',
                    null,
                    'properties.foo.2',
                    'validators',
                    'StringLength'
                ),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto(
                    'standard',
                    'Foo',
                    null,
                    'properties.foo.1.bar',
                    'validators',
                    'StringLength'
                ),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar', 'validators', 'Foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.2'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.2', 'validators', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isFormElementTypeCreatableByFormEditorDataProvider(): array
    {
        return [
            [
                [],
                new ValidationDto('standard', 'Form'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['creatable' => true]]],
                new ValidationDto('standard', 'Text'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['creatable' => false]]],
                new ValidationDto('standard', 'Text'),
                false,
            ],
            [
                ['formElements' => ['Foo' => ['creatable' => true]]],
                new ValidationDto('standard', 'Text'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isPropertyCollectionElementIdentifierCreatableByFormEditorDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['creatable' => false]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Foo' => ['collections' => ['validators' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['foo' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['Foo' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function buildFormDefinitionValidationConfigurationFromFormEditorSetupDataProvider(): array
    {
        return [
            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Foo',
                                        'propertyPath' => 'properties.foo',
                                        'setup' => [
                                            'propertyPath' => 'properties.bar',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'propertyPaths' => [
                                'properties.foo',
                                'properties.bar',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-GridColumnViewPortConfigurationEditor',
                                        'propertyPath' => 'properties.{@viewPortIdentifier}.foo',
                                        'configurationOptions' => [
                                            'viewPorts' => [
                                                ['viewPortIdentifier' => 'viewFoo'],
                                                ['viewPortIdentifier' => 'viewBar'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'propertyPaths' => [
                                'properties.viewFoo.foo',
                                'properties.viewBar.foo',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'additionalElementPropertyPaths' => [
                                            'properties.foo',
                                            'properties.bar',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'additionalElementPropertyPaths' => [
                                'properties.foo',
                                'properties.bar',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-PropertyGridEditor',
                                        'propertyPath' => 'properties.foo.1',
                                    ],
                                    [
                                        'templateName' => 'Inspector-MultiSelectEditor',
                                        'propertyPath' => 'properties.foo.2',
                                    ],
                                    [
                                        'templateName' => 'Inspector-ValidationErrorMessageEditor',
                                        'propertyPath' => 'properties.foo.3',
                                    ],
                                    [
                                        'templateName' => 'Inspector-RequiredValidatorEditor',
                                        'propertyPath' => 'properties.fluidAdditionalAttributes.required',
                                        'configurationOptions' => [
                                            'validationErrorMessage' => [
                                                'propertyPath' => 'properties.validationErrorMessages',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'multiValueProperties' => [
                                'properties.foo.1',
                                'defaultValue',
                                'properties.foo.2',
                                'properties.foo.3',
                                'properties.validationErrorMessages',
                            ],
                            'propertyPaths' => [
                                'properties.foo.1',
                                'properties.foo.2',
                                'properties.foo.3',
                                'properties.fluidAdditionalAttributes.required',
                                'properties.validationErrorMessages',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'predefinedDefaults' => [
                                    'foo' => [
                                        'bar' => 'xxx',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'predefinedDefaults' => [
                                'foo.bar' => 'xxx',
                            ],
                            'untranslatedPredefinedDefaults' => [
                                'foo.bar' => 'xxx',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => true,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Foo' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Foo',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-FinishersEditor',
                                        'selectOptions' => [
                                            [
                                                'value' => 'FooFinisher',
                                            ],
                                            [
                                                'value' => 'BarFinisher',
                                            ],
                                        ],
                                    ],
                                    [
                                        'templateName' => 'Inspector-ValidatorsEditor',
                                        'selectOptions' => [
                                            [
                                                'value' => 'FooValidator',
                                            ],
                                            [
                                                'value' => 'BarValidator',
                                            ],
                                        ],
                                    ],
                                    [
                                        'templateName' => 'Inspector-RequiredValidatorEditor',
                                        'validatorIdentifier' => 'NotEmpty',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'finishers' => [
                                    'FooFinisher' => [
                                        'creatable' => true,
                                    ],
                                    'BarFinisher' => [
                                        'creatable' => true,
                                    ],
                                ],
                                'validators' => [
                                    'FooValidator' => [
                                        'creatable' => true,
                                    ],
                                    'BarValidator' => [
                                        'creatable' => true,
                                    ],
                                    'NotEmpty' => [
                                        'creatable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'propertyPath' => 'options.xxx',
                                                ],
                                                [
                                                    'propertyPath' => 'options.yyy',
                                                    'setup' => [
                                                        'propertyPath' => 'options.zzz',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'validators' => [
                                    'fooValidator' => [
                                        'propertyPaths' => [
                                            'options.xxx',
                                            'options.yyy',
                                            'options.zzz',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'additionalElementPropertyPaths' => [
                                                        'options.xxx',
                                                    ],
                                                ],
                                                [
                                                    'additionalElementPropertyPaths' => [
                                                        'options.yyy',
                                                        'options.zzz',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'additionalElementPropertyPaths' => [
                                'options.xxx',
                                'options.yyy',
                                'options.zzz',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'templateName' => 'Inspector-PropertyGridEditor',
                                                    'propertyPath' => 'options.xxx',
                                                ],
                                                [
                                                    'templateName' => 'Inspector-MultiSelectEditor',
                                                    'propertyPath' => 'options.yyy',
                                                ],
                                                [
                                                    'templateName' => 'Inspector-ValidationErrorMessageEditor',
                                                    'propertyPath' => 'options.zzz',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'validators' => [
                                    'fooValidator' => [
                                        'multiValueProperties' => [
                                            'options.xxx',
                                            'defaultValue',
                                            'options.yyy',
                                        ],
                                        'propertyPaths' => [
                                            'options.xxx',
                                            'options.yyy',
                                            'options.zzz',
                                        ],
                                    ],
                                ],
                            ],
                            'multiValueProperties' => [
                                'options.zzz',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'validatorsDefinition' => [
                        'someValidator' => [
                            'formEditor' => [
                                'predefinedDefaults' => [
                                    'some' => [
                                        'property' => 'value',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'collections' => [
                        'validators' => [
                            'someValidator' => [
                                'predefinedDefaults' => [
                                    'some.property' => 'value',
                                ],
                                'untranslatedPredefinedDefaults' => [
                                    'some.property' => 'value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
