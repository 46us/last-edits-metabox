<?php

namespace LastEditsMetabox\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Tools
{
    /**
     * Get the plugin version.
     * @return string The plugin version.
     * @since 1.0.0
     */
    public static function get_plugin_version()
    {
        return '1.0.0';
    }

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
}