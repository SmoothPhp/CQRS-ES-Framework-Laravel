<?php
return [
    /*
     |--------------------------------------------------------------------------
     | Event Store Settings
     |--------------------------------------------------------------------------
     |
     | You have the option to use a prepacked eventstore, build on laravel's query builder.
     | This helps getting setup fast, however you are free to provide your own
     | you just need to bind a implementation to SmoothPhp\Contracts\EventStore
     |
     */
    'laravel_eventstore_enabled' => true,

    /**
     * The database connection to use laravels database.php
     * We recommend not using the same database as the one you run migrations on
     * as you don't want to use your eventstore.
     */
    'eventstore_connection'      => 'eventstore',

    /**
     * Database Table use to store events in
     */
    'eventstore_table'           => 'eventstore',


    /*
     |--------------------------------------------------------------------------
     | Command Bus
     |--------------------------------------------------------------------------
     |
     | We ship with a command bus, You are free to turn it off, If you don't want it registered
     |
     */
    'command_bus_enabled'        => true,

    /**
     * The Chain of Middleware you wish the command bus to use. Can be left black for simple resolving.
     */
    'command_bus_middleware'     => [],

    /*
    |--------------------------------------------------------------------------
    | Serializer
    |--------------------------------------------------------------------------
    |
    | We ship with a serializer, again you are free to change it
    |
    */
    'serializer'                 => \SmoothPhp\Serialization\ObjectSelfSerializer::class,


    /*
   |--------------------------------------------------------------------------
   | Event Dispatcher
   |--------------------------------------------------------------------------
   |
   | We ship with a event dispatcher, use to get your domain events to your own
   | custom listeners. The default one has projections return detection, learn more
   | https://github.com/SmoothPhp/CQRS-ES-Framework/tree/master/src/EventDispatcher
   |
   */
    'event_dispatcher'           => \SmoothPhp\EventDispatcher\ProjectEnabledDispatcher::class,

    /*
    |--------------------------------------------------------------------------
    | EventBus
    |--------------------------------------------------------------------------
    |
    | We ship with a serializer, again you are free to change it
    |
    */
    'event_bus'                  => \SmoothPhp\EventBus\SimpleEventBus::class,

    'event_bus_listeners'   => [
        \SmoothPhp\LaravelAdapter\EventBus\EventBusLogger::class,
        \SmoothPhp\LaravelAdapter\EventBus\PushEventsThroughQueue::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Rebuild Command
    |--------------------------------------------------------------------------
    |
    | Set the commands you want to fire before and after replaying the events.
    | Here are some sensible command to start with
    |
    */
    'pre_rebuild_commands'  => [
        'down',
        'migrate:reset',
        'migrate',
    ],
    'post_rebuild_commands' => [
        'up'
    ],

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    |
    | Some variables to help generators to run
    |
    */
    'path'                  => 'src',
    'namespace'             => 'App\\'
];