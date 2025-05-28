<?php
/**
 * Plugin Name: Last Edits Metabox
 * Description: A plugin to add a last edits metabox to the WordPress admin dashboard.
 * Version: 1.0
 * Author: Devagus
 * Author URI: https://github.com/46us
 * License: GPLv2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use LastEditsMetabox\Controllers\LemControllers;

if (! defined( 'LEM_PLUGIN_DIR_URL' )) {
	define( 'LEM_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) );
}

// Initiate the Last Edits Metabox hooks
if ( class_exists( '\LastEditsMetabox\Controllers\LemControllers' ) ) {
    LemControllers::get_instance();
} else {
	wp_die( 'class does not exist' );
	exit;
}
