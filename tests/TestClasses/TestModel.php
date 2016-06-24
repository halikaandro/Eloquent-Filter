<?php

namespace TestClasses;

use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;

class TestModel extends Model
{
    use Filterable;

    public function modelFilter()
    {
        return $this->provideFilter(FILTER_CLASS);
    }
}
