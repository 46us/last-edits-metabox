<?php

namespace LastEditsMetabox\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use LastEditsMetabox\Views\Metabox;
use LastEditsMetabox\Models\Tools;

class LemControllers
{
    private static $instance = null;

    private $metabox;

	private $tools;

	public function __construct()
    {
		$this->metabox = new Metabox();
        $this->tools = new Tools();
        $this->run_hooks();
	}

	public static function get_instance()
    {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
	}

    public function run_hooks()
    {
        add_action( 'wp_dashboard_setup', [$this, 'dashboard_metabox'] );
        add_action( 'admin_menu', [$this, 'create_settings_page'] );
        add_action( 'admin_init', [$this, 'register_settings'] );
        add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'] );
    }

    /**
     * Register the metabox to be displayed in the WordPress dashboard.
     * This function is hooked to the wp_dashboard_setup action.
     * @return void
     * @since 1.0.0
     */
    public function dashboard_metabox()
    {
        add_meta_box(
            'last_edits_dashboard_metabox',
            'Last Edits',
            [ $this->metabox, 'generate_metabox_content' ],
            'dashboard',
            'side',
            'high'
        );
    }

    /**
     * This is a callback function of admin_menu hook action.
     * It adds an invisible submenu with no parent menu in the WordPress admin.
     * @return void
     * @since 1.0.0
     */
    public function create_settings_page()
    {
        add_submenu_page(
            null,
            __('Last Edits Metabox Settings', 'last-edits-metabox'),
            'Lem Settings',
            'manage_options',
            'lem-settings',
            [$this->metabox, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting(
            'last_edits_metabox_settings_group',
            'lem_settings'
        );
    
        add_settings_section(
            'lem_settings_section',
            __('Last Edits Metabox Settings', 'last-edits-metabox'),
            [$this->metabox, 'render_settings_section'],
            'last_edits_metabox'
        );

		$this->metabox->create_field(
            'checkboxes',
            'post_types',
            __('Post Types', 'last-edits-metabox'),
            __('Select the post types to display in the Last Edits Metabox.', 'last-edits-metabox'),
            [$this->metabox, 'render_checkboxes_field']
        );

        $this->metabox->create_field(
            'number',
            'limit_per_post_type',
            __('Limit Per Post Type', 'last-edits-metabox'),
            __('Set the number of last edits to display per post type.', 'last-edits-metabox'),
            [$this->metabox, 'render_number_field']
        );

        $this->metabox->create_field(
            'checkbox',
            'show_metabox_toolbar',
            __('Show Metabox Toolbar', 'last-edits-metabox'),
            __('Enable to show the toolbar in the Last Edits Metabox.', 'last-edits-metabox'),
            [$this->metabox, 'render_checkbox_field']
        );
    }

    public function admin_enqueue_scripts(  $hook_suffix )
    {
        // Only enqueue the styles on the index.php admin page (dashboard)
        // This is to avoid loading the styles on other admin pages
        if ( $hook_suffix !== 'index.php' ) {
            return;
        }
    
        wp_enqueue_style(
            'lem-metabox',
            LEM_PLUGIN_DIR_URL . 'assets/styles/metabox.css',
            [],
            '1.0',
            'screen'
        );
    }
}