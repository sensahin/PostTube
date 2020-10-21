<?php

/**
 * Runs on Uninstall of PostTube
 * 
 * 
 */


// Check that we should be doing this
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}


$option_name = 'wporg_option';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
 
// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tube_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tube_character");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tube_config");

?>