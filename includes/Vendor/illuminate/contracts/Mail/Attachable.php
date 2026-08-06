<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Attachable
{
    /**
     * Get an attachment instance for this entity.
     *
     * @return \Illuminate\Mail\Attachment
     */
    public function toMailAttachment();
}
