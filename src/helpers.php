<?php
if (!function_exists('wait')) {
    /**
     * Block execution until a command has finished been projected/dispatched
     * @param \SmoothPhp\Contracts\CommandBus\Command[]|\SmoothPhp\Contracts\CommandBus\Command $commands
     * @param int $timeout Timeout 8 seconds
     */
    function wait($commands, $timeout = 8)
    {
        if (!is_array($commands)) {
            wait([$commands], $timeout);
        }
        $i = 0;
        if (config('app.env') == 'testing') {
            return;
        }

        foreach ($commands as $command) {
            while (!Cache::has((string)$command)) {
                usleep(1000);

                if ($i > 8000) {
                    return;
                }
            }
        }
    }
}
if (!function_exists('uuid')) {
    /**
     * Generate a UUID string
     * @return string
     */
    function uuid()
    {
        return (string)\Ramsey\Uuid\Uuid::uuid4();
    }
}
