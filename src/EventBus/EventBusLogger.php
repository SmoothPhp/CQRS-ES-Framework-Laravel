<?php
namespace SmoothPhp\LaravelAdapter\EventBus;

use Illuminate\Contracts\Logging\Log;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventBus\EventListener;

/**
 * Class EventBusLogger
 * @package SmoothPhp\LaravelAdapter\EventBus
 * @author Simon Bennett <simon@bennett.im>
 */
final class EventBusLogger implements EventListener
{
    /**
     * @var Log
     */
    private $log;

    /**
     * @param Log $log
     */
    public function __construct(Log $log)
    {
        $this->log = $log;
    }
    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $name = explode('.',$domainMessage->getType());

        $name = preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', end($name));
        $this->log->debug(trim(ucwords($name)) . " ({$domainMessage->getType()})");
    }
}