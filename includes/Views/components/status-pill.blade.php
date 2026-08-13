<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props(['status' => '-', 'label' => null])

@php
	$rawStatus = trim((string) $status);
	$normalizedStatus = strtolower($rawStatus);
	$map = array(
		'excellent' => array('label' => 'Excellent', 'classes' => 'bg-emerald-100 text-emerald-800 border-emerald-200'),
		'good' => array('label' => 'Good', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'),
		'okay' => array('label' => 'Okay', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'),
		'needs improvement' => array('label' => 'Needs improvement', 'classes' => 'bg-amber-100 text-amber-800 border-amber-200'),
		'slow' => array('label' => 'Slow', 'classes' => 'bg-amber-100 text-amber-800 border-amber-200'),
		'moderate' => array('label' => 'Moderate', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'),
		'poor' => array('label' => 'Poor', 'classes' => 'bg-red-100 text-red-800 border-red-200'),
		'heavy' => array('label' => 'Heavy', 'classes' => 'bg-red-100 text-red-800 border-red-200'),
		'high' => array('label' => 'High', 'classes' => 'bg-red-100 text-red-800 border-red-200'),
		'-' => array('label' => '-', 'classes' => 'bg-gray-100 text-gray-500 border-gray-200'),
	);

	$pill = $map[ $normalizedStatus ] ?? array(
		'label' => '' !== $rawStatus ? $rawStatus : '-',
		'classes' => 'bg-gray-100 text-gray-500 border-gray-200',
	);

	$displayLabel = ( null !== $label && '' !== (string) $label ) ? (string) $label : $pill['label'];
@endphp

<span {{ $attributes->merge(array('class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold leading-none whitespace-nowrap ' . $pill['classes'])) }}>
	{{ $displayLabel }}
</span>




