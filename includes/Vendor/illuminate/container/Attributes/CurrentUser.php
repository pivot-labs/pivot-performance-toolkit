<?php

namespace PerformanceToolkit\Vendor\Illuminate\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CurrentUser extends Authenticated
{
    //
}
