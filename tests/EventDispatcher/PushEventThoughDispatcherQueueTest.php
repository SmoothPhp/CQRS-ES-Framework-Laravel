<?php declare (strict_types=1);

namespace Tests\EventDispatcher;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Support\Testing\Fakes\EventFake;
use SmoothPhp\LaravelAdapter\QueuedEventDispatcher\QueuedEventDispatcher;
use SmoothPhp\LaravelAdapter\QueuedEventDispatcher\QueuedEventHandler;
use SmoothPhp\Serialization\ObjectSelfSerializer;
use Tests\EventDispatcher\Helpers\TestEvent;
use Tests\EventDispatcher\Helpers\TestHandler;

/**
 * Class PushEventThoughDispatcherQueueTest
 * @package Tests\EventDispatcher
 * @author Simon Bennett <simon@bennett.im>
 */
final class PushEventThoughDispatcherQueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function test_pushing_event_to_queue()
    {
        $container = new Container();
       // $container->bind(\Illuminate\Contracts\Container\Container::class, $container);
        $queue = new Queue($container);


        $queue->addConnection(
            [
                'driver' => 'sync',
            ]
        );


        $config = $this->getMockBuilder(Repository::class)->getMock();
        $config->method('set')->with(
            $this->returnValueMap(
                [
                    // Config set values
                ]
            )
        );
        $config->method('get')->will(
            $this->returnValueMap(
                [
                    // Config get values
                ]
            )
        );
        $eventDispatcher = new QueuedEventDispatcher($queue->getConnection(), new ObjectSelfSerializer(), $config);

        $handler = new TestHandler();

        $eventDispatcher->addSubscriber($handler);
        $eventDispatcher->addSubscriber($handler);

        $event = new TestEvent(uuid());

        //$this->assertEquals(2,$handler->runCount);

        $eventDispatcher->dispatch(strtr(get_class($event), '\\', '.'), [$event]);


    }
}
