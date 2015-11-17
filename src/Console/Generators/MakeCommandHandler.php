<?php
namespace SmoothPhp\LaravelAdapter\Console\Generators;

/**
 * Class MakeCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class MakeCommandHandler extends StubConvertingCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:make:commandhandler {aggregate} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Command Handler';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/commandHandler.stub';
    }

    /**
     * @return array
     */
    protected function replacementVariables()
    {
        return [
            'className'   => $this->argument('name') . 'Handler',
            'commandName' => $this->argument('name'),
        ];
    }

    /**
     * @return string
     */
    protected function getFolder()
    {
        return 'Commands';
    }

    /**
     * @return mixed
     */
    protected function getClassName()
    {
        return $this->argument('name') . 'Handler';
    }
}