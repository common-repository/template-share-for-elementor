<?php 



class Emsh_Rest_Handler {
		
	/**
	 * Instance of the class
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 */
	private static $instance = null;

	
		
	/**
	 * Get instance.
	 *
	 * Creates and returns an instance of the class.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}


    public function __construct() {

        // define hooks
        

		// create rest api endpoints
		add_action( 'rest_api_init', array($this, 'create_endpoints') );
    }


	/**
	 * Create rest api endpoints
	 *
	 * @return void
	 */
	public function create_endpoints() {
		// endpoint for connecting
		register_rest_route( 'template-share-server/api/v1', 'connect/', array(
			'methods'  => 'POST',
			'callback' => array($this, 'connect_to_client_using_key'),
			'permission_callback' => '__return_true',
		));

		// endpoint for disconnecting
		register_rest_route( 'template-share-server/api/v1', 'disconnect/', array(
			'methods'  => 'POST',
			'callback' => array($this, 'disconnect_client_using_key'),
			'permission_callback' => '__return_true',
		));

		// endpoint for providing elementor templates
		register_rest_route( 'template-share-server/api/v1', 'templates/emsh_single_templates', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_elementor_templates'),
			'permission_callback' => '__return_true',
		));

		// endpoint for providing emsh template groups
		register_rest_route( 'template-share-server/api/v1', 'templates/emsh_template_groups', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_emsh_template_groups'),
			'permission_callback' => '__return_true',
		));

		// endpoint for providing elementor template categories
		register_rest_route( 'template-share-server/api/v1', 'template-categories/emsh_single_templates', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_elementor_template_categories'),
			'permission_callback' => '__return_true',
		));

		// endpoint for providing emsh template group categories
		register_rest_route( 'template-share-server/api/v1', 'template-categories/emsh_template_groups', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_emsh_template_group_categories'),
			'permission_callback' => '__return_true',
		));

		// endpoint for providing subtemplates of template group
		register_rest_route( 'template-share-server/api/v1', 'subtemplates/emsh_template_groups', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_emsh_subtemplates'),
			'permission_callback' => '__return_true',
		));

        // Single template info
		register_rest_route( 'template-share-server/api/v1', 'template/(?P<id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_single_template_info'),
			'permission_callback' => '__return_true',
		));

        // Single template info
		register_rest_route( 'template-share-server/api/v1', 'template-json/(?P<template_id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array($this, 'provide_template_json'),
			'permission_callback' => '__return_true',
		));
	}

	/**
	 * Provides template groups.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_emsh_template_groups( $request ) {


		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$response = apply_filters( 'emsh_rest_template_groups', array(
				'success' => true,
				'templates' => array()
			), $key, $site );
			
			return $response;
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('License key is required', 'template-share-for-elementor')
			);
		}
    }


	/**
	 * Handle connection request came from client site
	 *
	 * @param [type] $request
	 * @return void
	 */
	public function connect_to_client_using_key( $request ) {
		$body = $request->get_body_params();
		if ( isset( $body['license_key'] ) && $body['license_key'] && $body['requesting_site'] && isset( $body['requesting_site'] ) ) {

			$key = sanitize_text_field($body['license_key']);
			$site = sanitize_text_field($body['requesting_site']);
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));
			if ( !$key_manager->does_key_exist() ) {
				return array(
					'success' => false,
					'message' => esc_html__('Invalid license key.', 'template-share-for-elementor')
				);
			}

			$pro_plugin_response = apply_filters( 'emsh_rest_connect_to_client_using_key', array('success' => true), $key_manager );

			if ( !$pro_plugin_response['success'] ) {
				return $pro_plugin_response;
			}
			else {
				// Connect and increment site count.
				$key_manager->increment_site_count();
		
				return array(
					'success' => true,
					'message' => esc_html__('Connection Successful', 'template-share-for-elementor'),
					'expiry_date' => $key_manager->get_expiry_date()
				);
			}
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('Please provide a license key and a requesting site', 'template-share-for-elementor')
			);
		}
	}

	/**
	 * Handle disconnection request came from client site
	 *
	 * @param [type] $request
	 * @return void
	 */
	public function disconnect_client_using_key( $request ) {
		$body = $request->get_body_params();
		if ( isset( $body['license_key'] ) && $body['license_key'] && $body['requesting_site'] && isset( $body['requesting_site'] ) ) {

			$key = sanitize_text_field($body['license_key']);
			$site = sanitize_text_field($body['requesting_site']);
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));
			if ( !$key_manager->does_key_exist() ) {
				return array(
					'success' => false,
					'message' => esc_html__('Invalid license key.', 'template-share-for-elementor')
				);
			}
	
			// Connect and increment site count.
			$key_manager->decrement_site_count();
	
			return array(
				'success' => true,
				'message' => esc_html__('Successfully Disconnected', 'template-share-for-elementor'),
				'expiry_date' => $key_manager->get_expiry_date()
			);
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('Please provide a license key and a requesting site', 'template-share-for-elementor')
			);
		}
	}

	/**
	 * Provides template categories.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_emsh_template_group_categories( $request ) {


		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			if ( !$key_manager->does_key_exist() ) {
				status_header( 401, esc_html__( 'Invalid License key', 'template-share-for-elementor' ) );
				exit;
			}
			else {
				$template_select_by = get_post_meta( $key_manager->post_id, 'emsh_license_template_group_type', true );
				$selected_cats = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_template_group_categories', true ) );
				$selected_templates = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_template_groups', true ) );
        
				$terms = null;

				if ( $template_select_by === 'specific_cats' ) {
					$args = array(
						'post_type'      => 'emsh-template-groups',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'tax_query'      => array(
							array(
								'taxonomy' => 'emsh-template-group-category',
								'field'    => 'term_id',
								'terms'    => $selected_cats,
								'operator' => 'IN',
							),
						),
					);
				}
				else if ( $template_select_by === 'specific_templates' ) {
					$args = array(
						'post_type'      => 'emsh-template-groups',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'post__in'       => $selected_templates,
					);
				}
				else {
					$args = array(
						'post_type'      => 'emsh-template-groups',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
					);

					// Get terms in regular way because all templates are selected.
					$terms = get_terms([
						'taxonomy' => 'emsh-template-group-category',
						'hide_empty' => false,
					]);
				}


				$terms_array = array();
				// If terms already exists, that means all templates are selected,
				// So no need to get terms for each post again.
				if ( $terms !== null ) {
					if( is_array($terms) && count($terms) > 0 ) {
						foreach ($terms as $term) {
							$slug = $term->slug;
							$title = $term->name;
							$terms_array[] = array(
								'slug' => $slug,
								'title' => $title,
							);
						}
					}
				}
				else {
					// Get posts
					$query = new \WP_Query( $args );
					$posts = $query->get_posts();
					
					if( is_array($posts) && count($posts) > 0 ) {
						foreach ($posts as $post) {
							$terms = get_the_terms($post->ID, 'emsh-template-group-category');
							if( is_array($terms) && count($terms) > 0 ) {
								foreach ($terms as $term) {
									$slug = $term->slug;
									$title = $term->name;
									$terms_array[] = array(
										'slug' => $slug,
										'title' => $title,
									);
								}
							}
						}
					}
				}
				
				
				// make the terms array unique
				if ( is_array($terms_array) && count( $terms_array ) ) {
					$terms_array = array_unique($terms_array, SORT_REGULAR);
				}

				$big_array = array(
					"success" => true,
					"terms" => $terms_array
				);
				return $big_array;
			}
			
		}
		else {
			status_header( 401, esc_html__( 'License Key is Required', 'template-share-for-elementor' ) );
			exit;
		}
    }

	/**
	 * Provides template categories.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_elementor_template_categories( $request ) {


		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			if ( !$key_manager->does_key_exist() ) {
				status_header( 401, esc_html__( 'Invalid License key', 'template-share-for-elementor' ) );
				exit;
			}
			else {
				$template_select_by = get_post_meta( $key_manager->post_id, 'emsh_license_template_type', true );
				$selected_cats = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_template_categories', true ) );
				$selected_templates = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_templates', true ) );
        
				$terms = null;

				if ( $template_select_by === 'specific_cats' ) {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'tax_query'      => array(
							array(
								'taxonomy' => 'elementor_library_category',
								'field'    => 'term_id',
								'terms'    => $selected_cats,
								'operator' => 'IN',
							),
						),
					);
				}
				else if ( $template_select_by === 'specific_templates' ) {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'post__in'       => $selected_templates,
					);
				}
				else {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
					);

					// Get terms in regular way because all templates are selected.
					$terms = get_terms([
						'taxonomy' => 'elementor_library_category',
						'hide_empty' => false,
					]);
				}


				$terms_array = array();
				// If terms already exists, that means all templates are selected,
				// So no need to get terms for each post again.
				if ( $terms !== null ) {
					if( is_array($terms) && count($terms) > 0 ) {
						foreach ($terms as $term) {
							$slug = $term->slug;
							$title = $term->name;
							$terms_array[] = array(
								'slug' => $slug,
								'title' => $title,
							);
						}
					}
				}
				else {
					// Get posts
					$query = new \WP_Query( $args );
					$posts = $query->get_posts();
					
					if( is_array($posts) && count($posts) > 0 ) {
						foreach ($posts as $post) {
							$terms = get_the_terms($post->ID, 'elementor_library_category');
							if( is_array($terms) && count($terms) > 0 ) {
								foreach ($terms as $term) {
									$slug = $term->slug;
									$title = $term->name;
									$terms_array[] = array(
										'slug' => $slug,
										'title' => $title,
									);
								}
							}
						}
					}
				}
				
				// make the terms array unique
				if ( is_array($terms_array) && count( $terms_array ) ) {
					$terms_array = array_unique($terms_array, SORT_REGULAR);
				}

				$big_array = array(
					"success" => true,
					"terms" => $terms_array
				);
				return $big_array;
			}
			
		}
		else {
			status_header( 401, esc_html__( 'License Key is Required', 'template-share-for-elementor' ) );
			exit;
		}
    }

	/**
	 * Provides templates.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_elementor_templates( $request ) {


		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			// using key manager, check if key valid (exist or not), has expiry date, site limit reached or not
			// and if everything okay but not connected yet, call key manager to connect
			$check_result = $key_manager->perform_primary_checks();
			if ( !$check_result['success'] ) {
				return $check_result;
			}
			else {
				$template_select_by = get_post_meta( $key_manager->post_id, 'emsh_license_template_type', true );
				$selected_cats = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_template_categories', true ) );
				$selected_templates = maybe_unserialize( get_post_meta( $key_manager->post_id, 'emsh_license_templates', true ) );
        
				if ( $template_select_by === 'specific_cats' ) {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'tax_query'      => array(
							array(
								'taxonomy' => 'elementor_library_category',
								'field'    => 'term_id',
								'terms'    => $selected_cats,
								'operator' => 'IN',
							),
						),
					);
				}
				else if ( $template_select_by === 'specific_templates' ) {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'post__in'       => $selected_templates,
					);
				}
				else {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'DESC',
					);
				}
				

				// Get posts
				$query = new \WP_Query( $args );
				$posts = $query->get_posts();

				$templates_array = array();
				if( is_array($posts) && count($posts) > 0 ) {
					foreach ($posts as $post) {
						// Find image url
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
						$image_url = '';
						if ( $image && is_array($image) ) {

							$image_url = $image[0];
						}
						if ( !strlen( $image_url ) ) {
							$image_url = EMSHF_ASSETS_URL . 'images/placeholder.png';
						}

						// get required plugins
						$required_plugins = maybe_unserialize( get_post_meta( $post->ID, 'emsh_required_plugins', true ) );
						if ( !is_array($required_plugins) ) {
							$required_plugins = array();
						}

						// get page settings
						$page_settings = get_post_meta( $post->ID, '_elementor_page_settings', true );

						// template type page or section
						$template_type = get_post_meta( $post->ID, '_elementor_template_type', true );

						// Construct categories array
						$categories = array();
						
						if ( $template_type == 'section' || $template_type == 'page') {
							$categories[] = 'emsh-template-type-' . $template_type;
						}
						$post_categories = wp_get_post_terms( $post->ID, 'elementor_library_category' );
						foreach($post_categories as $c){
							$cat = get_category( $c );
							$categories[] = $cat->slug;
						}

						$single_post = array(
							"template_id"  => $post->ID,
							"title"        => $post->post_title,
							"type"         => "emsh_single_templates",
                            "source"       => 'emsh-elementor-templates-api',
							"preview"      => $image_url,
							"thumbnail"    => $image_url,
							'hasPageSettings' => ! empty( $page_settings ),
							"template_type" => $template_type,
							"categories"   => $categories,
							"required_plugins" => json_encode($required_plugins),
							'is_pro' => false,
							'preview_url' => get_post_meta( $post->ID, 'emsh_preview_url', true ),
						);
						$templates_array[] = $single_post;
					}
				}

				$big_array = array(
					"success" => true,
					"templates" => $templates_array
				);
				return $big_array;
			}
			
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('License key is required', 'template-share-for-elementor')
			);
		}
    }

	/**
	 * Provides templates.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_emsh_subtemplates( $request ) {


		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');
		$template_group_id = $request->get_param('template_group_id');

		if ( $key && $site && $template_group_id ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			// using key manager, check if key valid (exist or not), has expiry date, site limit reached or not
			// and if everything okay but not connected yet, call key manager to connect
			$check_result = $key_manager->perform_primary_checks();
			if ( !$check_result['success'] ) {
				return $check_result;
			}
			else {
				// find subtemplate ids first
				$subtemplates = maybe_unserialize( get_post_meta( $template_group_id, 'emsh_template_group_templates', true ) );
				if ( !is_array($subtemplates) ) {
					return array(
						'success' => false,
						'message' => esc_html__( 'No Templates Found', 'template-share-for-elementor' )
					);
				}
				else {
					$args = array(
						'post_type'      => 'elementor_library',
						'posts_per_page' => -1,
						'orderby'        => 'post__in',
						'post__in'       => $subtemplates,
					);
					
	
					// Get posts
					$query = new \WP_Query( $args );
					$posts = $query->get_posts();
	
					$templates_array = array();
					if( is_array($posts) && count($posts) > 0 ) {
						foreach ($posts as $post) {
							// Find image url
							$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
							$image_url = '';
							if ( $image && is_array($image) ) {

								$image_url = $image[0];
							}

							// get required plugins
							$required_plugins = maybe_unserialize( get_post_meta( $post->ID, 'emsh_required_plugins', true ) );
							if ( !is_array($required_plugins) ) {
								$required_plugins = array();
							}

							// get page settings
							$page_settings = get_post_meta( $post->ID, '_elementor_page_settings', true );

							// template type page or section
							$template_type = get_post_meta( $post->ID, '_elementor_template_type', true );
							
							if ( !strlen( $image_url ) ) {
								$image_url = EMSHF_ASSETS_URL . 'images/placeholder.png';
							}
	
							// Construct categories array
							$categories = array();
							$post_categories = wp_get_post_terms( $post->ID, 'elementor_library_category' );
							foreach($post_categories as $c){
								$cat = get_category( $c );
								$categories[] = $cat->slug;
							}
	
							$single_post = array(
								"template_id"  => $post->ID,
								"title"        => $post->post_title,
								"type"         => "emsh_single_templates",
								"preview"      => $image_url,
								"thumbnail"    => $image_url,
								"categories"   => $categories,
								'hasPageSettings' => ! empty( $page_settings ),
								"required_plugins" => json_encode($required_plugins),
								"template_type" => $template_type,
								"source"       => 'emsh-elementor-templates-api',
								'is_pro' => false,
								'preview_url' => get_post_meta( $post->ID, 'emsh_preview_url', true ),
							);
							$templates_array[] = $single_post;
						}
					}
	
					$big_array = array(
						"success" => true,
						"templates" => $templates_array
					);
					return $big_array;
				}
				
			}
			
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('License key is required', 'template-share-for-elementor')
			);
		}
    }

	

	/**
	 * Provides a single template info.
	 *
	 * @param object $request
	 * @return array
	 */
    public function provide_single_template_info( $request ) {

		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			// using key manager, check if key valid (exist or not), has expiry date, site limit reached or not
			// and if everything okay but not connected yet, call key manager to connect
			$check_result = $key_manager->perform_primary_checks();
			if ( !$check_result['success'] ) {
				return $check_result;
			}
			else {
				$template_id   = $request['id'];
				$json_file_url = add_query_arg( array(
					'license_key' => $key,
					'requesting_site' => $site,
				), get_rest_url( null, 'template-share-server/api/v1/template-json/' . $template_id ) );
				$request       = wp_remote_get($json_file_url);
				$response      = wp_remote_retrieve_body( $request );
				$response      = json_decode($response);

				$big_array = array(
					"success" => true,
				);
				return array_merge($big_array, (array)$response);
			}
			
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('License key is required', 'template-share-for-elementor')
			);
		}
    }

    /**
     * Directly export a template into json.
     *
     * @param object $request
     * @return array
     */
    public function provide_template_json( $request ) {
        if ( !isset( $request['template_id'] ) ) {
            return;
        }

		$key = $request->get_param('license_key');
		$site = $request->get_param('requesting_site');

		if ( $key && $site ) {
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $site ));

			// using key manager, check if key valid (exist or not), has expiry date, site limit reached or not
			// and if everything okay but not connected yet, call key manager to connect
			$check_result = $key_manager->perform_primary_checks();
			if ( !$check_result['success'] ) {
				return $check_result;
			}
			else {
				if ( !class_exists('\Elementor\TemplateLibrary\Source_Local') ) {
					require_once ELEMENTOR_PATH . 'includes/template-library/sources/base.php';
					require_once ELEMENTOR_PATH . 'includes/template-library/sources/local.php';
				}
		
				$local_template_manager = new \Elementor\TemplateLibrary\Source_Local();
				$local_template_manager->export_template($request['template_id']);
			}
			
		}
		else {
			return array(
				'success' => false,
				'message' => esc_html__('License key is required', 'template-share-for-elementor')
			);
		}

        
    }
}



?>