<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props(['title' => '', 'id' => ''])

<section {{ $attributes->merge(array('id' => $id ?? '', 'class' => 'pivot-performance-toolkit-card')) }}>
	<h2 class="pivot-performance-toolkit-card-title">{{ $title }}</h2>
	{{ $slot }}
</section>

