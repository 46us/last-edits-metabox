<?php

namespace LastEditsMetabox\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use LastEditsMetabox\Traits\Utilities;

class Metabox
{
    use Utilities;
    
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
        $last_edits = $this->get_last_edits($post_types, $limit_per_post_type);

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

    public function render_last_edits_items_per_post_type( $post_type, $edit )
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

    /**
     * Generate options toolbar for each item in the list.
     * @param int $post_id The post ID to generate the options toolbar for.
     * @return string The options toolbar for the specified post ID.
     * @since 1.0.0
     */
    public function generate_options_toolbar( $post_id )
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

    /**
     * Generate the no last edits message for the specified post type.
     * @param string $post_type The post type to generate the no last edits message for.
     * @return string The no last edits message for the specified post type.
     * @since 1.0.0
     */
    function generate_no_last_edits_message( $post_type )
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
}