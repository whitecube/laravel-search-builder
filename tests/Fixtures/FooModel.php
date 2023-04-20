<?php

namespace Whitecube\SearchBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Whitecube\SearchBuilder\HasSearchBuilder;

class FooModel extends Model
{
    use HasSearchBuilder;
}
