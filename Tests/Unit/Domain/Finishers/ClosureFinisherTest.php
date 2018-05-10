<?php
declare(strict_types = 1);
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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Test case
 */
class ClosureFinisherTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function closureOptionForFinisherCanBeSetAndIsFunctional()
    {
        $closure = function (FinisherContext $finisherContext) {
            return 'foobar';
        };

        /** @var ClosureFinisher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $mockClosureFinisher */
        $mockClosureFinisher = $this->getAccessibleMock(ClosureFinisher::class, ['dummy'], [], '', false);

        $mockClosureFinisher->_set('options', [
            'closure' => $closure
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);
        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $finisherContextProphecy->getFormRuntime(Argument::cetera())->willReturn($formRuntimeProphecy->reveal());

        /** @var FinisherContext|ObjectProphecy $revealedFinisherContext */
        $revealedFinisherContext = $finisherContextProphecy->reveal();

        $mockClosureFinisher->_set('finisherContext', $revealedFinisherContext);
        $closure = $mockClosureFinisher->_call('parseOption', 'closure');

        $this->assertSame('foobar', $closure($revealedFinisherContext));
    }
}
