<?php

namespace Whitecube\SearchBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whitecube\SearchBuilder\HasSearchBuilder;

class SoftDeleteModel extends Model
{
    use HasSearchBuilder;
    use SoftDeletes;
}
