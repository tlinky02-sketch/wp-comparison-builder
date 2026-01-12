<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Custom Database Table operations for TOOLS.
 * Table: wp_wpc_tools
 */
class WPC_Tools_Database {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpc_tools';
    }

    /**
     * Create or Update the table schema
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            
            badge_text varchar(255),
            link text,
            button_text varchar(255),
            short_description text,
            rating float(2,1) DEFAULT 0.0,
            
            -- JSON Arrays
            features longtext,
            pricing_plans longtext,
            
            clicks int(10) UNSIGNED DEFAULT 0,
            
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        add_option( 'wpc_tools_db_version', '1.0.0' );
    }

    /**
     * Insert or Update Tool
     */
    public function update_tool( $post_id, $data ) {
        global $wpdb;

        // Ensure post_id is set
        $data['post_id'] = $post_id;

        // Check if exists
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE post_id = %d", $post_id ) );

        // Sanitize & Format JSON fields
        $json_fields = [ 'features', 'pricing_plans' ];

        foreach ( $json_fields as $field ) {
            if ( isset( $data[$field] ) && is_array( $data[$field] ) ) {
                $data[$field] = wp_json_encode( $data[$field] );
            }
        }

        if ( $existing ) {
            $wpdb->update( $this->table_name, $data, array( 'post_id' => $post_id ) );
            return $existing;
        } else {
            $wpdb->insert( $this->table_name, $data );
            return $wpdb->insert_id;
        }
    }

    /**
     * Get Tools by Post IDs (Bulk)
     */
    public function get_tools( $post_ids ) {
        global $wpdb;
        
        if ( empty( $post_ids ) ) {
            return array();
        }
        
        $post_ids = array_map( 'absint', $post_ids );
        $ids_sql = implode( ',', $post_ids );
        
        $rows = $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE post_id IN ($ids_sql)" );
        
        if ( $rows ) {
            $json_fields = [ 'features', 'pricing_plans' ];
            foreach ( $rows as $row ) {
                foreach ( $json_fields as $field ) {
                    if ( ! empty( $row->$field ) ) {
                        $row->$field = json_decode( $row->$field, true );
                    } else {
                         $row->$field = [];
                    }
                }
            }
        }
        
        return $rows ?: array();
    }

    /**
     * Get Tool by Post ID
     */
    public function get_tool( $post_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE post_id = %d", $post_id ) );
        
        if ( $row ) {
            $json_fields = [ 'features', 'pricing_plans' ];
            foreach ( $json_fields as $field ) {
                if ( ! empty( $row->$field ) ) {
                    $row->$field = json_decode( $row->$field, true );
                } else {
                     $row->$field = [];
                }
            }
        }

        return $row;
    }

    /**
     * Delete Tool
     */
    public function delete_tool( $post_id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'post_id' => $post_id ) );
    }
}
