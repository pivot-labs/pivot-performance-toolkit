<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Validation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Illuminate\Validation\Validator;

interface ValidatorAwareRule
{
    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator);
}
