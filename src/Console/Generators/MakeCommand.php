<?php
namespace SmoothPhp\LaravelAdapter\Console\Generators;

/**
 * Class MakeCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class MakeCommand extends StubConvertingCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:make:command {aggregate} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Command';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/command.stub';
    }

    /**
     * @return array
     */
    protected function replacementVariables()
    {
        return [
            'className' => $this->argument('name'),
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
        return $this->argument('name');
    }
}