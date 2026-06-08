<?php
/**
 * Profile-based scoring thresholds.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Utils;

final class PerformanceProfileBands {

	/**
	 * Resource-count bands keyed by website profile.
	 *
	 * Keep this as the single source of truth so thresholds are easy to modify later.
	 *
	 * @return array<string, array<int, array{label:string,max:int|null}>>
	 */
	public static function resourceCount(): array {
		return array(
			'standard' => array(
				array( 'label' => 'Excellent', 'max' => 49 ),
				array( 'label' => 'Good', 'max' => 90 ),
				array( 'label' => 'Moderate', 'max' => 130 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'woocommerce' => array(
				array( 'label' => 'Excellent', 'max' => 69 ),
				array( 'label' => 'Good', 'max' => 130 ),
				array( 'label' => 'Moderate', 'max' => 200 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'membership-lms' => array(
				array( 'label' => 'Excellent', 'max' => 69 ),
				array( 'label' => 'Good', 'max' => 140 ),
				array( 'label' => 'Moderate', 'max' => 220 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'page-builder-heavy' => array(
				array( 'label' => 'Excellent', 'max' => 79 ),
				array( 'label' => 'Good', 'max' => 160 ),
				array( 'label' => 'Moderate', 'max' => 240 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
		);
	}


	/**
	 * CSS size bands keyed by website profile.
	 *
	 * @return array<string, array<int, array{label:string,max:int|null}>>
	 */
	public static function cssSize(): array {
		return array(
			'standard' => array(
				array( 'label' => 'Excellent', 'max' => 99 * 1024 ),
				array( 'label' => 'Good', 'max' => 300 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 600 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'woocommerce' => array(
				array( 'label' => 'Excellent', 'max' => 149 * 1024 ),
				array( 'label' => 'Good', 'max' => 400 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 800 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'membership-lms' => array(
				array( 'label' => 'Excellent', 'max' => 149 * 1024 ),
				array( 'label' => 'Good', 'max' => 450 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 900 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'page-builder-heavy' => array(
				array( 'label' => 'Excellent', 'max' => 199 * 1024 ),
				array( 'label' => 'Good', 'max' => 600 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
		);
	}

	/**
	 * JS size bands keyed by website profile.
	 *
	 * @return array<string, array<int, array{label:string,max:int|null}>>
	 */
	public static function jsSize(): array {
		return array(
			'standard' => array(
				array( 'label' => 'Excellent', 'max' => ( 500 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => 1024 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 2048 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'woocommerce' => array(
				array( 'label' => 'Excellent', 'max' => ( 800 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => (int) ( 1.5 * 1024 * 1024 ) ),
				array( 'label' => 'Moderate', 'max' => 3 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'membership-lms' => array(
				array( 'label' => 'Excellent', 'max' => ( 800 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => (int) round( 1.8 * 1024 * 1024 ) ),
				array( 'label' => 'Moderate', 'max' => (int) ( 3.5 * 1024 * 1024 ) ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'page-builder-heavy' => array(
				array( 'label' => 'Excellent', 'max' => ( 900 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => 2 * 1024 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 4 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
		);
	}

	/**
	 * Image size bands keyed by website profile.
	 *
	 * @return array<string, array<int, array{label:string,max:int|null}>>
	 */
	public static function imageSize(): array {
		return array(
			'standard' => array(
				array( 'label' => 'Excellent', 'max' => ( 1024 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => 2 * 1024 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 4 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'woocommerce' => array(
				array( 'label' => 'Excellent', 'max' => (int) ( 1.5 * 1024 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => 3 * 1024 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 6 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'membership-lms' => array(
				array( 'label' => 'Excellent', 'max' => (int) ( 1.5 * 1024 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => 3 * 1024 * 1024 ),
				array( 'label' => 'Moderate', 'max' => 6 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
			'page-builder-heavy' => array(
				array( 'label' => 'Excellent', 'max' => (int) ( 1.5 * 1024 * 1024 ) - 1 ),
				array( 'label' => 'Good', 'max' => (int) ( 3.5 * 1024 * 1024 ) ),
				array( 'label' => 'Moderate', 'max' => 7 * 1024 * 1024 ),
				array( 'label' => 'Heavy', 'max' => null ),
			),
		);
	}
}








