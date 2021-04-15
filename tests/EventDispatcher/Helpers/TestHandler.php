<?php declare (strict_types=1);

namespace Tests\EventDispatcher\Helpers;

use SmoothPhp\Contracts\EventDispatcher\Subscriber;

/**
 * Class TestHandler
 * @package Tests\EventDispatcher\Helpers
 * @author Simon Bennett <simon@bennett.im>
 */
final class TestHandler implements Subscriber
{
    public $runCount = 0;
    /**
     * @param TestEvent $testEvent
     */
    public function whenTestEvent(TestEvent $testEvent)
    {
        $this->runCount++;
    }

    /**
     * ['eventName' => 'methodName']
     * ['eventName' => ['methodName', $priority]]
     * ['eventName' => [['methodName1', $priority], array['methodName2']]
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [TestEvent::class => ['whenTestEvent'],];
    }
}
