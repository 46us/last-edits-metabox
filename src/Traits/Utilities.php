<?php

namespace LastEditsMetabox\Traits;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

trait Utilities
{
	/**
     * Get the last edits for the specified post types.
     * @param array $post_types The post types to get the last edits for.
     * @param int $limit_per_postype The number of last edits to get per post type.
     * @return array The last edits for the specified post types, group by post type.
     * @since 1.0.0
     */
    public function get_last_edits( $post_types, $limit_per_postype )
    {
        // Get the last edits for the specified post types
        $last_edits = [];
        foreach ( $post_types as $post_type ) {
            $edits = $this->get_last_edits_for_post_type( $post_type, $limit_per_postype );
            if ( ! empty( $edits ) ) {
                // Group the last edits by post type
                $last_edits[ $post_type ] = $edits;
            }
        }

        return $last_edits;
    }

    /**
     * Get the last edits for a specific post type.
     * @param string $post_type The post type to get the last edits for.
     * @param int $limit_per_postype The number of last edits to get.
     * @return array Array of objects of the last edits for the specified post type.
     * @since 1.0.0
     */
    public function get_last_edits_for_post_type( $post_type, $limit_per_postype )
    {
        global $wpdb;

        // Get the last edits for the specified post type and current user capabilities
        if ( current_user_can( 'edit_others_posts' ) ) {
            $query = $wpdb->prepare(
                "SELECT ID, post_title, post_status, post_modified, post_type FROM $wpdb->posts WHERE post_type = %s AND post_status IN ('publish', 'draft', 'pending') ORDER BY post_modified DESC LIMIT %d",
                $post_type, $limit_per_postype
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT ID, post_title, post_status, post_modified, post_type FROM $wpdb->posts WHERE post_type = %s AND post_status IN ('publish', 'draft', 'pending') AND post_author = %d ORDER BY post_modified DESC LIMIT %d",
                $post_type, get_current_user_id(), $limit_per_postype
            );
        }

        return $wpdb->get_results( $query );
    }

    /**
     * Generate the edit link for the specified post ID.
     * @param int $post_id The post ID to generate the edit link for.
     * @return string The edit link for the specified post ID.
     * @since 1.0.0
     */
	public function generate_edit_link( $post_id )
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
}
