<?php declare (strict_types=1);

namespace Tests\EventDispatcher\Helpers;

use SmoothPhp\Contracts\EventSourcing\Event;
use SmoothPhp\Contracts\Serialization\Serializable;

/**
 * Class TestEvent
 * @package Tests\EventDispatcher\Helpers
 * @author Simon Bennett <simon@bennett.im>
 */
final class TestEvent implements Event, Serializable
{
    /** @var string */
    private $id;

    /**
     * TestEvent constructor.
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return ['id' => $this->id];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function deserialize(array $data)
    {
        return new static($data['id']);
    }
}
