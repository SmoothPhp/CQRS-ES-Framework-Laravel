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
    | We ship with a event bus, this is needed to push events from domain to projection handlers async
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
    ],
    'post_rebuild_commands' => [
        'up'
    ],

    /*
   |--------------------------------------------------------------------------
   | Projections
   |--------------------------------------------------------------------------
   |
   | Register the ProjectionServiceProviders must be key=>value pair's with the key been the name
   | eg ['members' => ACME/MembersProjectionServiceProvider::class]
   | Must implement SmoothPhp\Contracts\Projections\ProjectionServiceProvider
   |
   */
    'projections_service_providers' => [
    ],

    /**
     * The Projection Service Providers you want registered when rebuilding, Key names from projections_service_providers
     * e.g ['members']
     */
    'rebuild_projections' => [
    ],

    /**
     * The number of events to chunk when rebuilding.
     * The more in a chunk the generally faster you can rebuild, but the more memory you will use.
     */
    'rebuild_transaction_size' => 10000,
    /**
     * Queue Name
     *
     * default will leave it on laravel default queue system but you are free to have a separated queues,
     * Good when you require projections to be handled faster than other queue jobs like file processing and emails.
     */
    'queue_name' => 'default',

    /**
     * If using the queue event dispatcher system that sperates each handler in to a different queue,
     * you can set the queue that works on here
     */
    'queue_name_handler' => 'default',

    /**
     * Set which event dispatcher you wish to use on a rebuild. Normally when you rebuild you don't want any async process,
     * so just use the default provided
     */
    'rebuild_event_dispatcher' => \SmoothPhp\EventDispatcher\ProjectEnabledDispatcher::class,
];