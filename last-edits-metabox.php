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

use LastEditsMetabox\Admin\Hooks;

require 'vendor/autoload.php';

define('LEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Initiate the Last Edits Metabox hooks
$hooks = new Hooks();
$hooks->init();

/**
 * Enqueue the styles for the metabox in the WordPress admin.
 * This function is hooked to the admin_enqueue_scripts action.
 * @param string $hook_suffix The current admin page hook.
 * @return void
 * @since 1.0.0
 */
function lem_admin_enqueue_scripts(  $hook_suffix ) {
    // Only enqueue the styles on the index.php admin page (dashboard)
    // This is to avoid loading the styles on other admin pages
    if ( $hook_suffix !== 'index.php' ) {
        return;
    }

    wp_enqueue_style(
        'lem-metabox',
        LEM_PLUGIN_URL . 'assets/styles/metabox.css',
        [],
        '1.0',
        'screen'
    );
}
add_action( 'admin_enqueue_scripts', 'lem_admin_enqueue_scripts' );
