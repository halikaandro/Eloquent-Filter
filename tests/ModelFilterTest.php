<?php

use PHPUnit\Framework\TestCase;
use EloquentFilter\ModelFilter;
use Mockery as m;

class ModelFilterTest extends TestCase
{
    protected $filter;

    protected $builder;

    protected $testInput;

    protected $config;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $this->builder = m::mock('builder');
        $this->filter = new ModelFilter($this->builder);
        $this->config = require __DIR__.'/config.php';
        $this->testInput = $this->config['test_input'];
    }

    public function testRemoveEmptyInput()
    {
        $filteredInput = $this->filter->removeEmptyInput($this->testInput);
        // Remove empty strings from the input
        foreach ($filteredInput as $val) {
            $this->assertNotEquals($val, '');
        }
    }

    public function testPush()
    {
        // Test key/value pair
        $this->filter->push('name', 'er');
        $this->assertEquals($this->filter->input(), ['name' => 'er']);

        // Test with inserting array
        $this->filter->push([
            'company_id' => '2',
            'roles'      => ['1', '4', '7'],
            ]);

        $this->assertEquals($this->filter->input(), [
            'name'       => 'er',
            'company_id' => '2',
            'roles'      => ['1', '4', '7'],
        ]);
    }

    public function testDisableRelations()
    {
        // Default is true
        $this->assertEquals($this->filter->relationsEnabled(), true);

        // Set to false
        $this->filter->disableRelations();
        $this->assertEquals($this->filter->relationsEnabled(), false);

        // Set to true
        $this->filter->enableRelations();
        $this->assertEquals($this->filter->relationsEnabled(), true);
    }

    /**
     * @depends testPush
     * @depends testRemoveEmptyInput
     */
    public function testInputMethod()
    {
        $filteredInput = $this->filter->removeEmptyInput($this->testInput);

        // Push has already been tested
        $this->filter->push($filteredInput);

        // All keys are in tact
        foreach ($this->testInput as $key => $val) {
            $this->assertEquals($this->filter->input($key), $this->testInput[$key]);
        }

        // All input is in tact after filter
        $this->assertEquals($this->filter->input(), $filteredInput);

        // Passing a key that doesnt exist returns null
        $this->assertNull($this->filter->input('missing_key'));

        // Test default parameter
        $this->assertEquals($this->filter->input('missing_key', 'my_default'), 'my_default');
    }

    public function testGetFilterMethod()
    {
        $input = [
            'name'               => 'name',
            'first_name'         => 'firstName',
            'first_or_last_name' => 'firstOrLastName',
        ];

        foreach ($input as $key => $method) {
            $this->assertEquals($method, $this->filter->getFilterMethod($key));
        }
    }

    public function testGetFilterMethodWithIds()
    {
        $key = 'user_name_id';

        $this->filter->dropIdSuffix(true);
        $this->assertEquals('userName', $this->filter->getFilterMethod($key));

        $this->filter->dropIdSuffix(false);
        $this->assertEquals('userNameId', $this->filter->getFilterMethod($key));
    }
}
