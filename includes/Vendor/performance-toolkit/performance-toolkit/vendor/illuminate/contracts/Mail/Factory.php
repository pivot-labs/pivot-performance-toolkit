<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Mail;

interface Factory
{
    /**
     * Get a mailer instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Mail\Mailer
     */
    public function mailer($name = null);
}
