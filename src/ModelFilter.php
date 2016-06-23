<?php

namespace EloquentFilter;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * @method  QueryBuilder where(string $column, string $operator = null, mixed $value = null, string $boolean = 'and')
 * @method  QueryBuilder has( string $relation, string $operator = '>=', int $count = 1, string $boolean = 'and', \Closure $callback = null)
 * @method  QueryBuilder doesntHave( string $relation, string $boolean = 'and', \Closure $callback = null)
 * @method  QueryBuilder whereHas( string $relation, \Closure $callback, string $operator = '>=', int $count = 1)
 * @method  QueryBuilder whereDoesntHave( string $relation, \Closure $callback = null)
 * @method  QueryBuilder selectRaw( string $expression, array $bindings = array())
 * @method  QueryBuilder selectSub( \Closure|QueryBuilder|string $query, string $as)
 * @method  QueryBuilder whereRaw( string $sql, mixed $bindings = array(), string $boolean = 'and')
 * @method  QueryBuilder whereBetween( string $column, array $values, string $boolean = 'and', bool $not = false)
 * @method  QueryBuilder whereNotBetween( string $column, array $values, string $boolean = 'and')
 * @method  QueryBuilder whereNested( \Closure $callback, string $boolean = 'and')
 * @method  QueryBuilder whereExists( \Closure $callback, string $boolean = 'and', bool $not = false)
 * @method  QueryBuilder whereIn( string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method  QueryBuilder whereNotIn( string $column, mixed $values, string $boolean = 'and')
 * @method  QueryBuilder whereNotNull( string $column, string $boolean = 'and')
 * @method  QueryBuilder whereDate( string $column, string $operator, int $value, string $boolean = 'and')
 * @method  QueryBuilder whereDay( string $column, string $operator, int $value, string $boolean = 'and')
 * @method  QueryBuilder whereMonth( string $column, string $operator, int $value, string $boolean = 'and')
 * @method  QueryBuilder whereYear( string $column, string $operator, int $value, string $boolean = 'and')
 * @method  QueryBuilder orderBy( string $column, string $direction = 'asc')
 * @method  QueryBuilder limit( int $value)
 * @method  QueryBuilder withTrashed()
 */
class ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relatedModel => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    /**
     * Array of input to filter.
     *
     * @var array
     */
    protected $input;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Drop `_id` from the end of input keys when referencing methods.
     *
     * @var bool
     */
    protected $drop_id = true;

    /**
     * Tables already joined in the query to filter by the joined column instead of using
     *  ->whereHas to save a little bit of resources.
     *
     * @var null
     */
    private $_joinedTables = null;

    /**
     * This is to be able to bypass relations if we are filtering a joined table.
     *
     * @var bool
     */
    protected $relationsEnabled;

    /**
     * ModelFilter constructor.
     *
     * @param $query
     * @param array $input
     * @param bool $relationsEnabled
     */
    public function __construct($query, array $input, $relationsEnabled = true)
    {
        $this->query = $query;
        $this->input = $this->removeEmptyInput($input);
        $this->relationsEnabled = $relationsEnabled;
    }

    /**
     * Handle calling methods on the query object.
     *
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        $class = method_exists($this, $method) ? $this : $this->query;

        return call_user_func_array([$class, $method], $args);
    }

    /**
     * Remove empty strings from the input array.
     *
     * @param $input
     * @return array
     */
    public function removeEmptyInput($input)
    {
        $filterableInput = [];

        foreach ($input as $key => $val) {
            if ($val !== '') {
                $filterableInput[$key] = $val;
            }
        }

        return $filterableInput;
    }

    /**
     * Handle all filters.
     *
     * @return QueryBuilder
     */
    public function handle()
    {
        // Filter global methods
        if (method_exists($this, 'setup')) {
            $this->setup();
        }

        // Run input filters
        $this->filterInput();
        // Set up all the whereHas and joins constraints
        $this->filterRelations();

        return $this->query;
    }

    /**
     * Filter with input array.
     */
    public function filterInput()
    {
        foreach ($this->input as $key => $val) {
            // Call all local methods on filter
            $method = camel_case($this->drop_id ? preg_replace('/^(.*)_id$/', '$1', $key) : $key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $val);
            }
        }
    }

    /**
     * Filter relationships defined in $this->relations array.
     * @return $this
     */
    public function filterRelations()
    {
        // No need to filer if we dont have any relations
        if (! $this->relationsEnabled || count($this->relations) === 0) {
            return $this;
        }

        foreach ($this->relations as $related => $fields) {
            if (count($filterableInput = array_only($this->input, $fields)) > 0) {
                if ($this->relationIsJoined($related)) {
                    $this->filterJoinedRelation($related, $filterableInput);
                } else {
                    $this->filterUnjoinedRelation($related, $filterableInput);
                }
            }
        }

        return $this;
    }

    /**
     * Run the filter on models that already have their tables joined.
     *
     * @param $related
     * @param $filterableInput
     */
    public function filterJoinedRelation($related, $filterableInput)
    {
        $relatedModel = $this->query->getModel()->{$related}()->getRelated();

        $filterClass = $relatedModel->getModelFilterClass();

        // Disable querying a joined tables relations
        with(new $filterClass($this->query, $filterableInput, false))->handle();
    }

    /**
     * Gets all the joined tables.
     *
     * @return array
     */
    public function getJoinedTables()
    {
        $joins = [];

        if (is_array($queryJoins = $this->query->getQuery()->joins)) {
            $joins = array_map(function ($join) {
                return $join->table;
            }, $queryJoins);
        }

        return $joins;
    }

    /**
     * Checks if the relation to filter's table is already joined.
     *
     * @param $relation
     * @return bool
     */
    public function relationIsJoined($relation)
    {
        if (is_null($this->_joinedTables)) {
            $this->_joinedTables = $this->getJoinedTables();
        }

        return in_array($this->getRelatedTable($relation), $this->_joinedTables);
    }

    /**
     * Get the table name from a relationship.
     *
     * @param $relation
     * @return string
     */
    public function getRelatedTable($relation)
    {
        return $this->query->getModel()->{$relation}()->getRelated()->getTable();
    }

    /**
     * Filters by a relationship that isnt joined by using that relation's ModelFilter.
     *
     * @param $related
     * @param $filterableInput
     */
    public function filterUnjoinedRelation($related, $filterableInput)
    {
        $this->query->whereHas($related, function ($q) use ($filterableInput) {
            return $q->filter($filterableInput);
        });
    }

    /**
     * Retrieve input by key or all input as array.
     *
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function input($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->input;
        }

        return isset($this->input[$key]) ? $this->input[$key] : $default;
    }

    /**
     * Disable querying relations (Mainly for joined tables as the related model isn't queried).
     *
     * @return $this
     */
    public function disableRelations()
    {
        $this->relationsEnabled = false;

        return $this;
    }

    /**
     * Enable querying relations.
     *
     * @return $this
     */
    public function enableRelations()
    {
        $this->relationsEnabled = true;

        return $this;
    }

    /**
     * Add values to filter by if called in setup().
     * Will ONLY filter relations if called on additional method.
     *
     * @param $key
     * @param null $value
     */
    public function push($key, $value = null)
    {
        if (is_array($key)) {
            $this->input = array_merge($this->input, $key);
        } else {
            $this->input[$key] = $value;
        }
    }
}
