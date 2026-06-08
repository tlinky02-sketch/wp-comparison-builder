<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Custom Database Table operations for items.
 * Table: wp_wpc_items
 */
class WPC_Database {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpc_items';
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
            
            -- Core Data
            public_name varchar(255) NOT NULL,
            short_description text,
            price varchar(50),
            period varchar(50),
            rating float(2,1) DEFAULT 0.0,
            clicks int(10) UNSIGNED DEFAULT 0,
            
            -- Links & Buttons
            details_link text,
            direct_link text,
            button_text varchar(255),
            footer_button_text varchar(255),
            
            -- Visuals
            logo_url text,
            dashboard_image text,
            hero_subtitle varchar(255),
            analysis_label varchar(255),
            badge_text varchar(255),
            badge_color varchar(50),
            
            -- Product/Schema Details
            condition_status varchar(50), 
            availability varchar(50),
            mfg_date date,
            exp_date date,
            service_type varchar(100),
            area_served varchar(255),
            duration varchar(50),
            brand varchar(255),
            sku varchar(100),
            gtin varchar(100),
            product_category varchar(100),
            
            -- Pricing Plan Settings
            coupon_code varchar(50),
            show_coupon tinyint(1) DEFAULT 0,
            hide_plan_features tinyint(1) DEFAULT 0,
            show_plan_links tinyint(1) DEFAULT 0,
            show_plan_links_popup tinyint(1) DEFAULT 0,
            show_plan_buttons tinyint(1) DEFAULT 1,
            table_btn_pos varchar(50),
            popup_btn_pos varchar(50),
            
            -- Design Overrides (JSON)
            design_overrides longtext,
            
            -- Pros/Cons Colors (JSON)
            pros_cons_colors longtext,
            
            -- Feature Table Options (JSON)
            feature_table_options longtext,

            -- Text Labels Overrides (JSON)
            text_labels longtext,

            -- Complex Data (JSON)
            pros longtext,
            cons longtext,
            pricing_plans longtext,
            plan_features longtext,
            use_cases longtext,
            competitors longtext,
            selected_tools longtext,
            
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Add option to track DB version for future updates
        add_option( 'wpc_db_version', '1.0.0' );
    }

    /**
     * Insert or Update Item
     * 
     * @param int $post_id
     * @param array $data Assumes keys match column names
     * @return int|false Row ID on success, false on failure
     */
    public function update_item( $post_id, $data ) {
        global $wpdb;

        // Ensure post_id is set
        $data['post_id'] = $post_id;

        // Check if exists
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE post_id = %d", $post_id ) );

        // Sanitize & Format JSON fields
        $json_fields = [
            'design_overrides', 'pros_cons_colors', 'feature_table_options', 'text_labels',
            'pros', 'cons', 'pricing_plans', 'plan_features', 'use_cases', 'competitors', 'selected_tools'
        ];

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
     * Get Item by Post ID
     * 
     * @param int $post_id
     * @return object|null Row object or null
     */
    public function get_item( $post_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE post_id = %d", $post_id ) );
        
        if ( $row ) {
            // Decode JSON fields automatically
            $json_fields = [
                'design_overrides', 'pros_cons_colors', 'feature_table_options', 'text_labels',
                'pros', 'cons', 'pricing_plans', 'plan_features', 'use_cases', 'competitors', 'selected_tools'
            ];

            foreach ( $json_fields as $field ) {
                if ( ! empty( $row->$field ) ) {
                    $row->$field = json_decode( $row->$field, true );
                } else {
                     $row->$field = []; // return empty array if null
                }
            }
        }

        return $row;
    }

    /**
     * Delete Item
     */
    public function delete_item( $post_id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'post_id' => $post_id ) );
    }

    /**
     * Get All Items (Optimized for API)
     * 
     * @param array $post_ids Optional array of post IDs to filter by
     */
    public function get_items( $post_ids = [] ) {
        global $wpdb;
        
        if ( ! empty( $post_ids ) ) {
            $ids_placeholder = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
            $sql = "SELECT * FROM {$this->table_name} WHERE post_id IN ($ids_placeholder)";
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $post_ids ) );
        } else {
            $results = $wpdb->get_results( "SELECT * FROM {$this->table_name}" );
        }

        // Decode JSON for all results
         if ( $results ) {
            $json_fields = [
                'design_overrides', 'pros_cons_colors', 'feature_table_options', 'text_labels',
                'pros', 'cons', 'pricing_plans', 'plan_features', 'use_cases', 'competitors', 'selected_tools'
            ];

            foreach ( $results as $row ) {
                foreach ( $json_fields as $field ) {
                    if ( ! empty( $row->$field ) ) {
                        $row->$field = json_decode( $row->$field, true );
                    } else {
                        $row->$field = [];
                    }
                }
            }
        }
        
        return $results;
    }
}
