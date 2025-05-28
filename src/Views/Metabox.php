<?php

namespace LastEditsMetabox\Views;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use LastEditsMetabox\Models\Tools;

class Metabox
{
	/**
     * This is a callback function registered in add_meta_box().
     * It generates the HTML content for the Last Edits Metabox that will be displayed in the WordPress dashboard.
     * @return void
     * @since 1.0.0
     */
    public function generate_metabox_content()
    {
        $content = '<div id="last-edits-metabox">';
        $content .= $this->render_ui();
        $content .= '<p class="lem-settings-link-container">';
        $content .= '<a href="' . esc_url(admin_url('admin.php?page=lem-settings')) . '" aria-label="' . esc_attr__('Last Edits Metabox Settings', 'last-edits-metabox') . '">';
        $content .= '<span class="dashicons dashicons-admin-generic"></span>';
        $content .= '<span class="lem-settings-link-text">' . esc_html__('Last Edits Metabox Settings', 'last-edits-metabox') . '</span>';
        $content .= '</a>';
        $content .= '</p>';
        $content .= '</div>';

        echo $content;
    }

    /**
     * Generate the list of last edits for the post and / or page post types.
     * @return string
     * @since 1.0.0
     */
    public function render_ui()
    {
        $post_types = ['post', 'page'];// Default post types to display last edits for
        $lem_settings = get_option('lem_settings', []);

        $post_types = $lem_settings['post_types'] ?? $post_types;
        $default_limit = $lem_settings['limit_per_post_type'] ?? 2;
        $limit_per_post_type = is_numeric($default_limit) && $default_limit > 0 ? (int)$default_limit : 2;

        // Get the last edits
        $tools = new Tools();
        $last_edits = $tools->get_last_edits($post_types, $limit_per_post_type);

        $html = '';

        // Return early if $last_edits is not an array or is empty
        if ( ! is_array( $last_edits ) || empty( $last_edits ) ) {
            $error_message = __( 'We are having a problem retrieving last edits data.', 'last-edits-metabox' );
            $html .= '<p>' . apply_filters( 'lem_error_message', $error_message ) . '</p>';

            return $html;
        }
        
        foreach ($last_edits as $post_type => $edits) {
			$html .= $this->render_last_edits_list_per_post_type( $post_type, $edits );
        }

        return $html;
    }

    public function render_last_edits_list_per_post_type( $post_type, $edits )
    {
        $html = '<div class="lem-list-container">';
        $html .= '  <div class="lem-list-header">';
        $html .= '    <a href="' . esc_url(admin_url('edit.php?post_type=' . $post_type)) . '" class="lem-list-header-link">';
        $html .= '      <span class="dashicons dashicons-admin-' . $post_type . '"></span>';
        $html .= '      <h3>' . esc_html(ucfirst($post_type)) . '</h3>';
        $html .= '    </a>';
        $html .= '  </div>';
        $html .= '  <ul class="lem-list ' . $post_type . '-list">';
        if (empty($edits)) {
            $no_last_edits_message = $this->generate_no_last_edits_message($post_type);
            $html .= '<li class="lem-list-item no-last-edits">' . $no_last_edits_message . '</li>';
        } else {
            foreach ($edits as $edit) {
                $html .= $this->render_last_edits_items_per_post_type( $post_type, $edit );
            }
        }
        $html .= '  </ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate the no last edits message for the specified post type.
     * @param string $post_type The post type to generate the no last edits message for.
     * @return string The no last edits message for the specified post type.
     * @since 1.0.0
     */
    private function generate_no_last_edits_message( $post_type )
    {
        $add_new_link = esc_url( admin_url( 'post-new.php?post_type=' . $post_type ) );
        $add_new_text = esc_html__( 'create', 'last-edits-metabox' );
        $add_new_text_link = '<a href="' . $add_new_link . '" class="create-new-link">' . $add_new_text . '</a>';
        $message = sprintf(
            esc_html__( 'You do not have any last edit %s. Let\'s start %s one from here!', 'last-edits-metabox' ),
            $post_type, $add_new_text_link
        );
        $message = apply_filters('lem_no_last_edits_message', $message, $post_type);
        return $message;
    }

    private function render_last_edits_items_per_post_type( $post_type, $edit )
    {
        $href = $this->generate_edit_link($edit->ID);
        $html = '<li class="lem-list-item">';
        $html .= '<a href="' . $href . '" class="lem-item-link">' . esc_html($edit->post_title) . '</a>';
        $html .= ' <span class="lem-item-status ' . esc_attr($edit->post_status) . '">( ' . esc_html(ucfirst($edit->post_status)) . ' )</span>';
        $html .= ' - ' . esc_html(date_i18n(get_option('date_format'), strtotime($edit->post_modified)));
        $html .= $this->generate_options_toolbar($edit->ID);
        $html .= '</li>';

        return $html;
    }

    private function generate_edit_link( $post_id )
    {
        // Check if the post was edited using Elementor
        $is_elementor = get_post_meta( $post_id, '_elementor_edit_mode', true );
        if ( $is_elementor ) {
            // If the post was edited using Elementor, generate the edit link for Elementor
            return esc_url( admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) );
        }
        // If the post was not edited using Elementor, generate the edit link for the WordPress editor
		return esc_url( get_edit_post_link( $post_id ) );
	}

    private function generate_options_toolbar( $post_id )
    {
        $lem_settings = get_option( 'lem_settings' );
        $show_toolbar = !$lem_settings ? 1 : $lem_settings['show_metabox_toolbar'] ?? 0;
        if ( ! $show_toolbar ) {
            return '';
        }

        // Generate the edit and delete links for the specified post ID
        $edit_link = $this->generate_edit_link( $post_id );
        $delete_link = esc_url( wp_nonce_url( admin_url( 'post.php?action=trash&post=' . $post_id ), 'trash-post_' . $post_id ) );

        return '<div class="lem-options-toolbar">
            <a href="' . $edit_link . '" class="lem-edit-link">Edit</a>
            <a href="' . $delete_link . '" class="lem-trash-link">Trash</a>
        </div>';
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

    public function render_settings_section()
    {
        echo '<p>' . __('Configure the settings for the Last Edits Metabox.', 'last-edits-metabox') . '</p>';
    }

    /**
     * Wrapper function to create a settings field.
     * @param mixed $type
     * @param mixed $id
     * @param mixed $label
     * @param mixed $description
     * @param mixed $callback
     * @return void
     */
    public function create_field($type, $id, $label, $description = '', $callback = null)
    {
        $args = [
            'type' => $type,
            'id' => $id,
            'label_for' => $id,
        ];

        if ($description) {
            $args['description'] = $description;
        }

        add_settings_field(
            $id,
            $label,
            $callback ?? [$this, 'render_field_template'],
            'last_edits_metabox',
            'lem_settings_section',
            $args
        );
    }

    public function render_field_template($args)
    {
        $lem_settings = get_option('lem_settings', []);
        $type = $args['type'];

        switch ($type) {
            case 'checkboxes':
                $value = $lem_settings[$args['id']] ?? [];
                $this->default_checkboxes_field($args, $value);
                break;
            case 'number':
                // Make sure the value is numeric and bigger than 0
                $value = isset($lem_settings[$args['id']]) && is_numeric($lem_settings[$args['id']]) && $lem_settings[$args['id']] > 0 ? $lem_settings[$args['id']] : 2; // Default to 2
                echo '<input type="number" id="' . esc_attr($args['id']) . '" name="lem_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" />';
                break;
            default:// Default input type is checkbox for now
                // Set value to 1 if $lem_settings is an empty array
                $value = empty($lem_settings) ? 1 : ($lem_settings[$args['id']] ?? 0);
                // Generate the checked attribute based on the value
                $checked = checked($value, 1, false);
                echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($args['id']) . '" name="lem_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" ' . $checked . ' />';
                break;
        }

        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function default_checkboxes_field($args, $value)
    {
        $post_types = ['post', 'page'];
        $selected_post_types = is_array($value) && !empty($value) ? $value : $post_types; // Default to all post types if not set

        foreach ($post_types as $post_type) {
            $checked = in_array($post_type, $selected_post_types) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="lem_settings[' . esc_attr($args['id']) . '][]" value="' . esc_attr($post_type) . '" ' . $checked . '>';
            echo ucfirst($post_type);
            echo '</label><br>';
        }
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