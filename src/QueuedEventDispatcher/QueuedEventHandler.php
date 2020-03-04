<?php declare(strict_types=1);


namespace SmoothPhp\LaravelAdapter\QueuedEventDispatcher;

use Illuminate\Container\Container;


/**
 * Class QueuedEventHandler
 * @package SmoothPhp\LaravelAdapter\QueuedEventDispatcher
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class QueuedEventHandler
{
    /** @var Container */
    private $container;

    /**
     * QueuedEventHandler constructor.
     * @param Container $container
     * @internal param $Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $job
     * @param $data
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function fire($job, $data)
    {
        $event = call_user_func(
            [
                str_replace('.', '\\', $data['event']['class']),
                'deserialize'
            ],
            $data['event']['payload']
        );

        $this->container->make($data['listener_class'])->{$data['listener_method']}($event);

        $job->delete();
    }
}
