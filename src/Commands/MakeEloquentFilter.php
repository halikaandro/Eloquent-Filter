<?php

namespace EloquentFilter\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeEloquentFilter extends Command
{
    use AppNamespaceDetectorTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:filter {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create A New Eloquent Model Filter';

    /**
     * Class to create.
     *
     * @var array|string
     */
    protected $class;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * MakeEloquentFilter constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->makeClassName()->compileStub();
        $this->info(class_basename($this->class).' Created Successfully!');
    }

    public function compileStub()
    {
        if ($this->files->exists($path = $this->getPath())) {
            $this->error("\n\n\t".$path.' Already Exists!'."\n");
            die;
        }
        $tmp = $this->applyValuesToStub($this->files->get(__DIR__.'/../stubs/modelfilter.stub'));
        $this->files->put($path, $tmp);
    }

    public function applyValuesToStub($stub)
    {
        $className = class_basename($this->class);
        $search = ['{{class}}', '{{namespace}}'];
        $replace = [$className, str_replace('\\'.$className, '', $this->class)];

        return str_replace($search, $replace, $stub);
    }

    public function getPath()
    {
        return app_path(str_replace([$this->getAppNamespace(), '\\'], ['', '/'], $this->class.'.php'));
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Create Filter Class Name.
     *
     * @return $this
     */
    public function makeClassName()
    {
        $parts = explode('\\', $this->argument('name'));
        $className = array_pop($parts);
        $ns = count($parts) > 0 ? implode('\\', $parts).'\\' : '';

        $this->class = config('eloquentfilter.namespace').$ns.studly_case($className);

        if (substr($this->class, -6, 6) !== 'Filter') {
            $this->class = (substr($this->class, -6, 6) === 'filter' ? str_replace('filter', '', $this->class) : $this->class).'Filter';
        }

        if (class_exists($this->class)) {
            $this->error("\n\n\t".$this->class.' Already Exists!'."\n");
            die;
        }

        return $this;
    }
}
