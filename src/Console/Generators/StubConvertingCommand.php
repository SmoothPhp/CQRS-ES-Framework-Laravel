<?php
namespace SmoothPhp\LaravelAdapter\Console\Generators;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

/**
 * Class StubConvertingCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
abstract class StubConvertingCommand extends Command
{
    /** @var Repository */
    private $config;

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    abstract protected function getStub();

    /**
     * @return array
     */
    abstract protected function replacementVariables();

    /**
     * @return string
     */
    abstract protected function getFolder();

    /**
     * @return mixed
     */
    abstract protected function getClassName();

    /**
     * StubConvertingCommand constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function handle()
    {

        $path = base_path() . '/' . $this->config->get('cqrses.path') . '/' . $this->argument('aggregate') . '/' . $this->getFolder() . '/';

        if (!file_exists($path)) {
            $this->makeDirectory($path);
        }

        $fullPath = $path . $this->getClassName() . '.php';

        if (file_exists($fullPath)) {
            $this->error($fullPath . ' Already Exists');

            return;
        }
        file_put_contents($fullPath, $this->compileStub());

        $this->line('Success');
    }


    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
    }


    /**
     * Compile the migration stub.
     *
     * @return string
     */
    protected function compileStub()
    {
        $stub = file_get_contents($this->getStub());

        $namespace = rtrim($this->config->get('cqrses.namespace') . $this->argument('aggregate') . '\\' . $this->getFolder(),
                           '\\');

        $stub = $this->replaceStubVariable('namespace',
                                           $namespace,
                                           $stub);

        foreach ($this->replacementVariables() as $variableName => $variableValue) {
            $stub = $this->replaceStubVariable($variableName, $variableValue, $stub);
        }

        return $stub;
    }

    /**
     * @param $variableName
     * @param $stub
     * @return mixed
     */
    protected function replaceStubVariable($variableName, $variableValue, $stub)
    {
        $stub = str_replace('{{' . $variableName . '}}', $variableValue, $stub);

        return $stub;
    }
}