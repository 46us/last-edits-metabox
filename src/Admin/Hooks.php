<?php

namespace LastEditsMetabox\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use LastEditsMetabox\Traits\Utilities;
use LastEditsMetabox\Admin\Metabox;

class Hooks
{
    use Utilities;

    private $metabox;

    public function init()
    {
        $this->metabox = new Metabox();

        add_action('wp_dashboard_setup', [$this, 'dashboard_metabox']);
        add_action('admin_menu', [$this, 'create_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
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
    public function create_settings_page() {
        add_submenu_page(
            null,
            __('Last Edits Metabox Settings', 'last-edits-metabox'),
            'Lem Settings',
            'manage_options',
            'lem-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        // Check if the user is allowed to access the settings page
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'last-edits-metabox' ) );
        }
    
        // Render the settings page content
        echo '<div class="wrap">';
        $this->generate_settings_form();
        echo '</div>';
    }

    public function generate_settings_form()
    {
        echo '<form method="post" action="options.php">';
        settings_fields('last_edits_metabox_settings_group');
        do_settings_sections('last_edits_metabox');
        submit_button(__('Save Settings', 'last-edits-metabox'));
        echo '</form>';
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
            [$this, 'render_settings_section'],
            'last_edits_metabox'
        );

		$this->create_field(
            'checkboxes',
            'post_types',
            __('Post Types', 'last-edits-metabox'),
            __('Select the post types to display in the Last Edits Metabox.', 'last-edits-metabox'),
            [$this, 'render_checkboxes_field']
        );

        $this->create_field(
            'number',
            'limit_per_post_type',
            __('Limit Per Post Type', 'last-edits-metabox'),
            __('Set the number of last edits to display per post type.', 'last-edits-metabox'),
            [$this, 'render_number_field']
        );

        $this->create_field(
            'checkbox',
            'show_metabox_toolbar',
            __('Show Metabox Toolbar', 'last-edits-metabox'),
            __('Enable to show the toolbar in the Last Edits Metabox.', 'last-edits-metabox'),
            [$this, 'render_checkbox_field']
        );
    }

    public function render_settings_section()
    {
        echo '<p>' . __('Configure the settings for the Last Edits Metabox.', 'last-edits-metabox') . '</p>';
    }

    public function render_checkboxes_field($args)
    {
		$post_types = [
			'post',
			'page'
		];
		$lem_settings = get_option( 'lem_settings', [] );
        $selected_post_types = $lem_settings[$args['id']] ?? $post_types; // Default to all post types if not set

        foreach ($post_types as $post_type) {
            $checked = in_array($post_type, $selected_post_types) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="lem_settings[' . esc_attr($args['id']) . '][]" value="' . esc_attr($post_type) . '" ' . $checked . '>';
            echo ucfirst($post_type);
            echo '</label><br>';
        }

        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function render_number_field($args)
    {
        $lem_settings = get_option( 'lem_settings', [] );
        $value = $lem_settings[$args['id']] ?? 2; // Default value is 2
        if (!is_numeric($value) || $value < 1) {
            $value = 2; // Reset to default if invalid
        }

        echo '<input type="number" name="lem_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" min="1" step="1">';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function render_checkbox_field($args)
    {
        $lem_settings = get_option('lem_settings');
        $value = !$lem_settings ? 1 : $lem_settings[$args['id']] ?? 0; // Default value is 1
        // Generate the checked attribute based on the value
        $checked = checked($value, 1, false);

        echo '<input type="checkbox" id="' . esc_attr($args['id']) . '" name="lem_settings[' . esc_attr($args['id']) . ']" value="1" ' . $checked . '>';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
}