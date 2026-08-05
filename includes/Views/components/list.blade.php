<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props(['items' => [], 'allowHtml' => false])

<x-info-list :items="$items" :allow-html="$allowHtml" />

