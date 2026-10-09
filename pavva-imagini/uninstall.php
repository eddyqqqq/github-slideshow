<?php
/**
 * Pavva Imagini – la ștergerea pluginului: variantele AVIF / WebP, starea lor și setările dispar.
 * Imaginile originale rămân neatinse.
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

require_once __DIR__ . '/includes/class-pvi-settings.php';
require_once __DIR__ . '/includes/class-pvi-converter.php';
require_once __DIR__ . '/includes/class-pvi-delivery.php';

global $wpdb;
@set_time_limit( 300 ); // phpcs:ignore
$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", PVI_Converter::META ) ); // phpcs:ignore
foreach ( $ids as $id ) {
	PVI_Converter::delete_variants( (int) $id );
}
PVI_Delivery::remove_rules();
delete_option( PVI_Settings::OPTION );
delete_transient( 'pvi_support' );
wp_unschedule_hook( PVI_Converter::CRON );
