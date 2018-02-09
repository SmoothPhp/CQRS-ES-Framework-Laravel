<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\EventBus;

use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventBus\EventListener;

/**
 * Class EventBusLogger
 * @package SmoothPhp\LaravelAdapter\EventBus
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class EventBusLogger implements EventListener
{
    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $name = explode('.', $domainMessage->getType());

        $name = preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', end($name));
        logger()->debug(trim(ucwords($name)) . " ({$domainMessage->getType()})");
    }
}