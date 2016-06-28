<?php

namespace EloquentFilter\TestClass;

use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;

class Client extends Model
{
    use Filterable;

    public function agent()
    {
        return $this->belongsTo(User::class);
    }

    public function modelFilter()
    {
        return $this->provideFilter(ClientFilter::class);
    }
}
