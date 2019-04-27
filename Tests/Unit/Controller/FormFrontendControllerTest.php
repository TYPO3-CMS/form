<?php
namespace TYPO3\CMS\Form\Tests\Unit\Controller;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormFrontendControllerTest extends UnitTestCase
{
    public function setUp()
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('cache_runtime')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
    }

    public function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsNoOverriddenConfigurationIfFlexformOverridesDisabled()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(ConfigurationService::class)
            ->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools;
        $contentObject = new \stdClass();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => ['vDEF' => 'Mesage Subject overridden'],
                            'settings.finishers.EmailToReceiver.recipientAddress' => ['vDEF' => 'your.company@example.com overridden'],
                            'settings.finishers.EmailToReceiver.format' => ['vDEF' => 'html overridden'],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->expects($this->any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->_set('objectManager', $objectManagerMock);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [],
                            'recipientAddress' => [],
                            'format' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 0,
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject',
                        'recipientAddress' => 'your.company@example.com',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject',
                        'recipientAddress' => 'your.company@example.com',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsOverriddenConfigurationIfFlexformOverridesEnabled()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(ConfigurationService::class)
            ->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools;
        $contentObject = new \stdClass();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => ['vDEF' => 'Mesage Subject overridden'],
                            'settings.finishers.EmailToReceiver.recipientAddress' => ['vDEF' => 'your.company@example.com overridden'],
                            'settings.finishers.EmailToReceiver.format' => ['vDEF' => 'html overridden'],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->expects($this->any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->_set('objectManager', $objectManagerMock);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => ['config' => ['type' => 'input']],
                            'recipientAddress' => ['config' => ['type' => 'input']],
                            'format' => ['config' => ['type' => 'input']],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 1,
        ]);

        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject',
                        'recipientAddress' => 'your.company@example.com',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject overridden',
                        'recipientAddress' => 'your.company@example.com overridden',
                        'format' => 'html overridden',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsNotOverriddenConfigurationKeyIfFlexformOverridesAreNotRepresentedInFormEngineConfiguration()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(ConfigurationService::class)
            ->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools;
        $contentObject = new \stdClass();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => ['vDEF' => 'Mesage Subject overridden'],
                            'settings.finishers.EmailToReceiver.recipientAddress' => ['vDEF' => 'your.company@example.com overridden'],
                            'settings.finishers.EmailToReceiver.format' => ['vDEF' => 'html overridden'],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->expects($this->any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->_set('objectManager', $objectManagerMock);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => ['config' => ['type' => 'input']],
                            'recipientAddress' => ['config' => ['type' => 'input']],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 1,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Mesage Subject overridden',
                    'recipientAddress' => 'your.company@example.com overridden',
                    'format' => 'html overridden',
                ],
            ],
        ]);

        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject',
                        'recipientAddress' => 'your.company@example.com',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject overridden',
                        'recipientAddress' => 'your.company@example.com overridden',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsOverriddenConfigurationWhileMultipleSheetsExists()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(ConfigurationService::class)
            ->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $anotherSheetIdentifier = md5(
            implode('', [
                '1:/foobar',
                'standard',
                'another-ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools;
        $contentObject = new \stdClass();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => ['vDEF' => 'Mesage Subject overridden 1'],
                            'settings.finishers.EmailToReceiver.recipientAddress' => ['vDEF' => 'your.company@example.com overridden 1'],
                            'settings.finishers.EmailToReceiver.format' => ['vDEF' => 'html overridden 1'],
                        ],
                    ],
                    $anotherSheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => ['vDEF' => 'Mesage Subject overridden 2'],
                            'settings.finishers.EmailToReceiver.recipientAddress' => ['vDEF' => 'your.company@example.com overridden 2'],
                            'settings.finishers.EmailToReceiver.format' => ['vDEF' => 'html overridden 2'],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->expects($this->any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->_set('objectManager', $objectManagerMock);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => ['config' => ['type' => 'input']],
                            'recipientAddress' => ['config' => ['type' => 'input']],
                            'format' => ['config' => ['type' => 'input']],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 1,
        ]);

        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject',
                        'recipientAddress' => 'your.company@example.com',
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Mesage Subject overridden 1',
                        'recipientAddress' => 'your.company@example.com overridden 1',
                        'format' => 'html overridden 1',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByTypoScriptSettingsReturnsNotOverriddenConfigurationIfNoTypoScriptOverridesExists()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(TypoScriptService::class)
            ->willReturn($typoScriptServiceProphecy->reveal());

        $mockController->_set('objectManager', $objectManagerMock);

        $typoScriptServiceProphecy
            ->resolvePossibleTypoScriptConfiguration(Argument::cetera())
            ->willReturnArgument(0);

        $mockController->_set('settings', [
            'formDefinitionOverrides' => [
            ],
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByTypoScriptSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByTypoScriptSettingsReturnsOverriddenConfigurationIfTypoScriptOverridesExists()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(TypoScriptService::class)
            ->willReturn($typoScriptServiceProphecy->reveal());

        $mockController->_set('objectManager', $objectManagerMock);

        $typoScriptServiceProphecy
            ->resolvePossibleTypoScriptConfiguration(Argument::cetera())
            ->willReturnArgument(0);

        $mockController->_set('settings', [
            'formDefinitionOverrides' => [
                'ext-form-identifier' => [
                    'label' => 'Label override',
                    'renderables' => [
                        0 => [
                            'renderables' => [
                                0 => [
                                    'label' => 'Label override',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label override',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label override',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('overrideByTypoScriptSettings', $input));
    }
}
