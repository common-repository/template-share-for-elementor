<?php 

/**
 * Class responsible for handling all keys.
 */
class Emsh_Key_Manager {

    public $license_key;
    public $expiry_date;
    public $maximum_site_allowed;
    public $connected_sites_no = 0;
    public $post_id;
    public $connected_sites;
    public $requesting_site;
    public $blocked_sites;
    public $single_template_type;

    public function __construct($license_key, $requesting_site = null) {
        // Hooks
        $this->$license_key = $license_key;
        $this->requesting_site = $requesting_site;

        // do wp query to get all of the data of this license key and save in instances
        global $wp_query;
        $wp_query = new WP_Query(array(
            'post_type' => 'emsh-licenses',
            'posts_per_page' => -1,
            'meta_key' => 'emsh_license_key',
            'meta_value' => $license_key
        ));

        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();

            $this->expiry_date = get_post_meta( get_the_ID(  ), 'emsh_expiry_date', true );
            $this->maximum_site_allowed = get_post_meta( get_the_ID(  ), 'emsh_maximum_site', true );
            

            $this->connected_sites = get_post_meta( get_the_ID(  ), 'emsh_connected_sites', true );
            if ( $this->connected_sites ) {
                $this->connected_sites = json_decode($this->connected_sites, true);
            }
            else {
                $this->connected_sites = array();
            }

            $this->connected_sites_no = count( $this->connected_sites );


            $this->blocked_sites = get_post_meta( get_the_ID(  ), 'emsh_blocked_sites', true );
            if ( $this->blocked_sites ) {
                $this->blocked_sites = json_decode($this->blocked_sites, true);
            }
            else {
                $this->blocked_sites = array();
            }



            $this->post_id = get_the_ID(  );
        }

        wp_reset_query(  );
    }


    public function get_expiry_date() {
        return apply_filters( 'emsh_key_manager_get_expiry_date', '', $this );
    }

    /**
     * If a given key exists.
     *
     * @return boolean
     */
    public function does_key_exist() {
        if ( $this->post_id ) {
            return true;
        }
        return false;
    }


    public function is_connected() {
        return in_array($this->requesting_site, $this->connected_sites);
    }


    public function perform_primary_checks() {

        if ( !$this->does_key_exist() ) {
            return array(
                'success' => false,
                'message' => esc_html__( 'Invalid License Key.', 'template-share-for-elementor' )
            );
        }

        if ( !$this->has_date_validity() ) {
            return array(
                'success' => false,
                'message' => esc_html__( 'Your License Has Been Expired.', 'template-share-for-elementor' )
            );
        }

        if ( $this->is_in_blocklist() ) {
            return array(
                'success' => false,
                'message' => esc_html__( 'You have been blocked by the site administrator.', 'template-share-for-elementor' )
            );
        }

        if ( !$this->is_connected() ) {
            if ( !$this->has_site_limit_to_connect() ) {
                return array(
                    'success' => false,
                    'message' => esc_html__( 'Site limit reached.', 'template-share-for-elementor' )
                );
            }
            else {
                $this->increment_site_count();
            }
        }

        return array(
            'success' => true
        );
    }

    /**
     * If the requesting site is in blocklist
     *
     * @return boolean
     */
    public function is_in_blocklist() {
        return apply_filters( 'emsh_key_manager_is_in_blocklist', false, $this );
    }

    /**
     * If a the key has expiry date
     *
     * @return boolean
     */
    public function has_date_validity() {
        if ( !$this->get_expiry_date() ) {
            return true;
        }
        return apply_filters( 'emsh_key_manager_has_date_validity', true, $this );
    }

    /**
     * Check if a key has enough site limit to connect more.
     *
     * @param string $key
     * @return boolean
     */
    public function has_site_limit_to_connect() {
        if ( !$this->maximum_site_allowed ) {
            return true;
        }

        // if the requesting site already exists in connected sites, let it connect and send success message
        // useful when any client site deleted a connected license site then it lets create a new "site" post type and connect again
        if ( $this->requesting_site && in_array( $this->requesting_site, $this->connected_sites ) ) {
            return true;
        }

        return apply_filters( 'emsh_key_manager_has_site_limit_to_connect', true, $this );
    }

    /**
     * Increment the site usage count for a key.
     *
     * @param string $key
     * @return void
     */
    public function increment_site_count() {
        // only increment and add if not exist already
        if ( !in_array( $this->requesting_site, $this->connected_sites ) ) {

            $this->connected_sites[] = $this->requesting_site;
            $this->connected_sites_no = count( $this->connected_sites );
            update_post_meta( $this->post_id, 'emsh_connected_sites', json_encode($this->connected_sites) );
        }
    }

    /**
     * Decrement the site usage count for a key.
     *
     * @param string $key
     * @return void
     */
    public function decrement_site_count() {
        // only decrement and remove if exist
        if (($key = array_search($this->requesting_site, $this->connected_sites)) !== false) {

            unset($this->connected_sites[$key]);
            $this->connected_sites_no = count( $this->connected_sites );
            update_post_meta( $this->post_id, 'emsh_connected_sites', json_encode($this->connected_sites) );
        }
    }

    /**
     * Add the requesting site in blocklist
     *
     * @return void
     */
    public function block_site() {
        // only add if not exist already
        if ( !in_array( $this->requesting_site, $this->blocked_sites ) ) {

            $this->blocked_sites[] = $this->requesting_site;
            update_post_meta( $this->post_id, 'emsh_blocked_sites', json_encode($this->blocked_sites) );

            // remove from connected sites as well
            $this->decrement_site_count();
        }
    }

    /**
     * Remove the requesting site from blocklist
     *
     * @return void
     */
    public function unblock_site() {
        // only remove if exist
        if (($key = array_search($this->requesting_site, $this->blocked_sites)) !== false) {

            unset($this->blocked_sites[$key]);
            update_post_meta( $this->post_id, 'emsh_blocked_sites', json_encode($this->blocked_sites) );
        }
    }

    /**
     * Sanitize a one dimensional array.
     *
     * @param array $array
     * @return array
     */
    public function sanitize_array( $array ) {
        foreach ($array as $key => $value) {
            $array[$key] = sanitize_text_field( $value );
        }
        return $array;
    }

    /**
     * Get elementor templates as array of post objects.
     *
     * @return array
     */
    public function get_elementor_template_posts() {
        // Get posts
		$args = array(
			'post_type' => 'elementor_library',
			'posts_per_page' => -1
		);
		$query = new \WP_Query( $args );
		$posts = $query->get_posts();
        if ( is_array( $posts ) ) {
            return $posts;
        }
        return array();
    }

    /**
     * Get elementor template categories as array.
     *
     * @return array
     */
    public function get_elementor_template_categories() {
        $args = array(
            'taxonomy' => 'elementor_library_category',
            'orderby' => 'name',
            'order'   => 'DESC'
        );

        $cats = get_categories($args);
        return $cats;
    }
}


?>