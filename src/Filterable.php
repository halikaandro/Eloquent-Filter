<?php namespace EloquentFilter;

trait Filterable
{
    protected $filter;

    /**
     * Array of input used to filter the query 
     *
     * @var array
     */
    protected $filtered = [];

    public function scopeFilter($query, array $input = [])
    {
        // Resolve the current Model's filter
        $filter = config('eloquentfilter.namespace') . class_basename($this) . 'Filter';

        $modelFilter = new $filter($query, $input);

        // Set the input that was used in the filter (this will exclude empty strings)
        $this->filtered = $modelFilter->input();

        // Return the filter query
        return $modelFilter->handle();
    }

    /**
     * Paginate the given query with url query params appended
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopePaginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $paginator = $query->paginate($perPage, $columns, $pageName, $page);

        foreach ($this->filtered as $key => $val)
        {
            $paginator->addQuery($key, $val);
        }

        return $paginator;
    }

    /**
     * Paginate the given query with url query params appended
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopeSimplePaginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page')
    {
        $paginator = $query->simplePaginate($perPage, $columns, $pageName);

        foreach ($this->filtered as $key => $val)
        {
            $paginator->addQuery($key, $val);
        }

        return $paginator;
    }


}