<?php
namespace SmoothPhp\LaravelAdapter\Console\Generators;

/**
 * Class MakeCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class MakeAggregate extends StubConvertingCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:make:aggregate {aggregate} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Aggregate';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/aggregate.stub';
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
        return '';
    }

    /**
     * @return mixed
     */
    protected function getClassName()
    {
        return $this->argument('name');
    }
}