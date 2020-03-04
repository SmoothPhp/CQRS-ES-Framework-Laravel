<?php
if (!function_exists('wait')) {
    /**
     * Block execution until a command has finished been projected/dispatched
     * @param \SmoothPhp\Contracts\CommandBus\Command[]|\SmoothPhp\Contracts\CommandBus\Command $commands
     * @param int $timeout Timeout 8 seconds
     */
    function wait($commands, $timeout = 8)
    {
        if (config('app.env') == 'testing') {
            return;
        }

        if (!is_array($commands)) {
            return wait([$commands], $timeout);
        }

        $limit = time() + $timeout;
        foreach ($commands as $command) {
            while (!Cache::has((string)$command)) {
                usleep(1000);

                if (time() > $limit) {
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
     * @throws Exception
     */
    function uuid()
    {
        return (string)\Ramsey\Uuid\Uuid::uuid4();
    }
}
