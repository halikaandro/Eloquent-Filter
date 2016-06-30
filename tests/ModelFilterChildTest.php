<?php

use PHPUnit\Framework\TestCase;
<<<<<<< HEAD
use TestClasses\TestModel;
use Mockery as m;


=======
use EloquentFilter\TestClass\User;
use EloquentFilter\TestClass\Client;
use EloquentFilter\TestClass\UserFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery as m;

>>>>>>> master
class ModelFilterChildTest extends TestCase
{
    protected $model;

    public function setUp()
    {
<<<<<<< HEAD
        $this->model = new TestModel;
=======
        $this->model = new User;
    }

    public function tearDown()
    {
        m::close();
    }

    public function testGetRelatedModel()
    {
        $userMock = m::mock('User');
        $userQueryMock = m::mock('Builder');
        $hasManyMock = m::mock('HasMany');

        $userQueryMock->shouldReceive('getModel')->once()->andReturn($userMock);

        $userMock->shouldReceive('clients')->once()->andReturn($hasManyMock);

        $hasManyMock->shouldReceive('getRelated')->once()->andReturn(new Client);

        $client = (new UserFilter($userQueryMock))->getRelatedModel('clients');

        $this->assertEquals($client, new Client);
>>>>>>> master
    }

    public function testProvideFilter()
    {
<<<<<<< HEAD
        // Empty provide filter
        $this->assertEquals($this->model->provideFilter(), App\ModelFilters\TestModelFilter::class);
=======
        // Empty provide filter App\ModelFilters is the default namespace when empty
        $this->assertEquals($this->model->provideFilter(), App\ModelFilters\UserFilter::class);
>>>>>>> master
        // Filter Value
        $this->assertEquals($this->model->provideFilter(App\ModelFilters\DynamicFilter\TestModelFilter::class), App\ModelFilters\DynamicFilter\TestModelFilter::class);
    }

    public function testGetModelFilterClass()
    {
<<<<<<< HEAD
        $this->assertEquals($this->model->getModelFilterClass(), FILTER_CLASS);
=======
        $this->assertEquals($this->model->getModelFilterClass(), EloquentFilter\TestClass\UserFilter::class);

>>>>>>> master
    }
}
