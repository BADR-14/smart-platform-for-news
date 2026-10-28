<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeServiceCommand extends GeneratorCommand
{
    protected $name = 'make:service';

    protected $description = 'Create a new Service class';

    protected $type = 'Service';

    protected function getStub()
    {
        if ($this->option('model')) {
            return resource_path('stubs/service.model.stub');
        }

        return resource_path('stubs/service.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Services';
    }

    protected function getOptions()
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'Generate service with model injection'],
        ];
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceModel($stub, $model)
    {
        $modelClass = $this->qualifyModel($model);

        $stub = str_replace('{{ modelNamespace }}', $modelClass, $stub);
        $stub = str_replace('{{ model }}', class_basename($modelClass), $stub);

        return $stub;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        if ($this->option('model')) {
            $stub = $this->replaceModel($stub, $this->option('model'));
        }

        return $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);
    }
}
//