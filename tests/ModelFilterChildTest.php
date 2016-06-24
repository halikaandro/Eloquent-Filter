<?php

use PHPUnit\Framework\TestCase;
use TestClasses\TestModel;
use Mockery as m;


class ModelFilterChildTest extends TestCase
{
    protected $model;

    public function setUp()
    {
        $this->model = new TestModel;
    }

    public function testProvideFilter()
    {
        // Empty provide filter
        $this->assertEquals($this->model->provideFilter(), App\ModelFilters\TestModelFilter::class);
        // Filter Value
        $this->assertEquals($this->model->provideFilter(App\ModelFilters\DynamicFilter\TestModelFilter::class), App\ModelFilters\DynamicFilter\TestModelFilter::class);
    }

    public function testGetModelFilterClass()
    {
        $this->assertEquals($this->model->getModelFilterClass(), FILTER_CLASS);
    }
}
