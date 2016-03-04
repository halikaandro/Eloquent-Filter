<?php namespace EloquentFilter;

trait Filterable
{
    protected $filter;

    public function scopeFilter($query, array $input)
    {
        $filter = config('eloquentfilter.namespace').class_basename($this).'Filter';
        
        return with(new $filter($query,$input))->handle();
    }
}