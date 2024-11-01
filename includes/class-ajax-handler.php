<?php 



class Emsh_Ajax_Handler {
		
	/**
	 * Instance of the class
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 */
	private static $instance = null;

		
	/**
	 * Sources.
	 *
	 * @access private
	 * @since  1.0.0
	 * @var array
	 */
	private $sources = array();

	
		
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

		// ajax handler for connecting
		add_action( 'wp_ajax_emsh_connect', array($this, 'connect') );

		// ajax handler for disconnecting
		add_action( 'wp_ajax_emsh_disconnect', array($this, 'disconnect') );

		// ajax handler for revoking a client site from server site
		add_action( 'wp_ajax_emsh_revoke_site', array($this, 'revoke') );

		// ajax handler for blocking a client site from server site
		add_action( 'wp_ajax_emsh_block_site', array($this, 'block') );

		// ajax handler for unblocking a client site from server site
		add_action( 'wp_ajax_emsh_unblock_site', array($this, 'unblock') );

		// ajax handlers for request came from elementor editor mode
		add_action( 'wp_ajax_emsh_elementor_get_templates', array($this, 'ajax_get_templates') );
		add_action( 'wp_ajax_emsh_elementor_get_subtemplates', array($this, 'ajax_get_templates') );

		add_action( 'wp_ajax_emsh_elementor_inner_template', array( $this, 'ajax_insert_inner_template' ) );
			
		if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '2.2.8', '>' ) ) {
			add_action( 'elementor/ajax/register_actions', array( $this, 'register_ajax_actions' ), 20 );
		} else {
			add_action( 'wp_ajax_elementor_get_template_data', array( $this, 'get_template_data' ), - 1 );
		}

		$this->register_sources();
    }


	/**
	 * Block a site from server site
	 *
	 * @return void
	 */
	public function unblock() {
		if ( isset( $_POST['site_url'] ) && isset( $_POST['license_key'] ) ) {
			$url = sanitize_url( $_POST['site_url'] );
			$key = sanitize_text_field( $_POST['license_key'] );
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $url ));
			if ( $key_manager->does_key_exist() ) {

				$key_manager->unblock_site();

				wp_send_json( array(
					'success' => true,
					'message' => esc_html__('Successfully blocked the site', 'template-share-for-elementor')
				) );
			}
			else {
				wp_send_json( array(
					'success' => false,
					'message' => esc_html__('The license key is invalid!', 'template-share-for-elementor')
				) );
			}
		}
	}


	/**
	 * Block a site from server site
	 *
	 * @return void
	 */
	public function block() {
		if ( isset( $_POST['site_url'] ) && isset( $_POST['license_key'] ) ) {
			$url = sanitize_url( $_POST['site_url'] );
			$key = sanitize_text_field( $_POST['license_key'] );
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $url ));
			if ( $key_manager->does_key_exist() ) {

				$key_manager->block_site();

				wp_send_json( array(
					'success' => true,
					'message' => esc_html__('Successfully blocked the site', 'template-share-for-elementor')
				) );
			}
			else {
				wp_send_json( array(
					'success' => false,
					'message' => esc_html__('The license key is invalid!', 'template-share-for-elementor')
				) );
			}
		}
	}


	/**
	 * Revoke a site from server site
	 *
	 * @return void
	 */
	public function revoke() {
		if ( isset( $_POST['site_url'] ) && isset( $_POST['license_key'] ) ) {
			$url = sanitize_url( $_POST['site_url'] );
			$key = sanitize_text_field( $_POST['license_key'] );
			$key_manager = new Emsh_Key_Manager($key, trailingslashit( $url ));
			if ( $key_manager->does_key_exist() ) {

				$key_manager->decrement_site_count();

				wp_send_json( array(
					'success' => true,
					'message' => esc_html__('Successfully revoked the site', 'template-share-for-elementor')
				) );
			}
			else {
				wp_send_json( array(
					'success' => false,
					'message' => esc_html__('The license key is invalid!', 'template-share-for-elementor')
				) );
			}
		}
	}


	/**
	 * Send connection request to server site.
	 *
	 * @return void
	 */
	public function connect() {
		if ( isset( $_POST['license_key'] ) && isset( $_POST['site_url'] ) ) {
			$license_key = sanitize_text_field( $_POST['license_key'] );
			$site_url = sanitize_text_field( $_POST['site_url'] );
			$data_to_send = array();

			// send request to server site using this license key
			$response = wp_remote_post( trailingslashit( $site_url ) . 'wp-json/template-share-server/api/v1/connect/', array(
					'method'      => 'POST',
					'timeout'     => 45,
					'sslverify' => false,
					'body'        => array(
						'license_key' => $license_key,
						'requesting_site' => trailingslashit(get_home_url()),
					),
				)
			);

			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$data_to_send = array(
					'success' => false,
					'message' => $error_message
				);
			} else {
				$body = wp_remote_retrieve_body( $response );
			
				// Bail out, if not set.
				if ( ! $body ) {
					$data_to_send = array(
						'success' => false,
						'message' => esc_html__('Unable to communicate with the server site', 'template-share-for-elementor')
					);
				}
				else {
					$firstStringCharacter = substr($body, 0, 1);
					if ( $firstStringCharacter === '{' ) {
						$data_to_send = json_decode($body, true);
					}
					else {
						$data_to_send = array(
							'success' => false,
							'message' => esc_html__('There is some issue with connection response', 'template-share-for-elementor')
						);
					}
				}
			}

			wp_send_json( $data_to_send );
		}
	}


	/**
	 * Send disconnection request to server site.
	 *
	 * @return void
	 */
	public function disconnect() {
		if ( isset( $_POST['license_key'] ) && isset( $_POST['site_url'] ) ) {
			$license_key = sanitize_text_field( $_POST['license_key'] );
			$site_url = sanitize_text_field( $_POST['site_url'] );
			$data_to_send = array();

			// send request to server site using this license key
			$response = wp_remote_post( trailingslashit( $site_url ) . 'wp-json/template-share-server/api/v1/disconnect/', array(
					'method'      => 'POST',
					'timeout'     => 45,
					'sslverify' => false,
					'body'        => array(
						'license_key' => $license_key,
						'requesting_site' => trailingslashit(get_home_url()),
					),
				)
			);

			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$data_to_send = array(
					'success' => false,
					'message' => $error_message
				);
			} else {
				$body = wp_remote_retrieve_body( $response );
			
				// Bail out, if not set.
				if ( ! $body ) {
					$data_to_send = array(
						'success' => false,
						'message' => esc_html__('Unable to communicate with the server site', 'template-share-for-elementor')
					);
				}
				else {
					$firstStringCharacter = substr($body, 0, 1);
					if ( $firstStringCharacter === '{' ) {
						$data_to_send = json_decode($body, true);
					}
					else {
						$data_to_send = array(
							'success' => false,
							'message' => esc_html__('There is some issue with connection response', 'template-share-for-elementor')
						);
					}
				}
			}

			wp_send_json( $data_to_send );
		}
	}
		
	/**
	 * Get template tabs.
	 *
	 * Get tabs for the library.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $key   source key
	 * @param string $class source class
	 */
	public function add_source( $key, $class ) {
		$this->sources[ $key ] = new $class();
	}
		
	/**
	 * Register sources.
	 *
	 * Register templates sources.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_sources() {
		
		require EMSHF_PATH . 'includes/templates/sources/base.php';
		
		$sources = array(
			'emsh-elementor-templates-api' => 'Emsh_Elementor_Templates_Api',
		);
		
		foreach ( $sources as $key => $class ) {
			
			require EMSHF_PATH . 'includes/templates/sources/' . $key . '.php';
			
			$this->add_source( $key, $class );
		}
		
	}
		
	/**
	 * Register AJAX actions.
	 *
	 * Add new actions to handle data after an AJAX requests returned.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param object $ajax_manager
	 *
	 */
	public function register_ajax_actions( $ajax_manager ) {
		
		if ( ! isset( $_POST['actions'] ) ) {
			return;
		}
		
		$actions = json_decode( stripslashes( $_REQUEST['actions'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data    = false;
		
		foreach ( $actions as $id => $action_data ) {
			if ( ! isset( $action_data['get_template_data'] ) ) {
				$data = $action_data;
			}
		}
		
		if ( ! $data ) {
			return;
		}
		
		if ( ! isset( $data['data'] ) ) {
			return;
		}
		
		if ( ! isset( $data['data']['source'] ) ) {
			return;
		}
		
		$source = $data['data']['source'];
		
		if ( ! isset( $this->sources[ $source ] ) ) {
			return;
		}
		
		$ajax_manager->register_ajax_action( 'get_template_data', function ( $data ) {
			return $this->get_template_data_array( $data );
		} );
		
	}
		
	/**
	 * Get template data array.
	 *
	 * triggered to get an array for a single template data.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $data
	 *
	 * @return bool|array
	 */
	public function get_template_data_array( $data ) {
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		
		if ( empty( $data['template_id'] ) ) {
			return false;
		}
		
		$source_name = isset( $data['source'] ) ? esc_attr( $data['source'] ) : '';
		
		if ( ! $source_name ) {
			return false;
		}
		
		$source = isset( $this->sources[ $source_name ] ) ? $this->sources[ $source_name ] : false;
		
		if ( ! $source ) {
			return false;
		}
		
		if ( empty( $data['tab'] ) || empty($data['license_key']) || empty($data['site_url']) ) {
			return false;
		}
		
		return $source->get_item( $data['template_id'], $data['tab'], $data['site_url'], $data['license_key'] );
		
	}

	
		
	/**
	 * Insert inner template.
	 *
	 * Insert an inner template before insert the parent one.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function ajax_insert_inner_template() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		// PHPCS - only reading the value from $_REQUEST['template'].
		$template = isset( $_REQUEST['template'] ) ? $_REQUEST['template'] : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		if ( ! $template ) {
			wp_send_json_error();
		}
		
		$template_id = isset( $template['template_id'] ) ? esc_attr( $template['template_id'] ) : false;
		$source_name = isset( $template['source'] ) ? esc_attr( $template['source'] ) : false;
		$source      = isset( $this->sources[ $source_name ] ) ? $this->sources[ $source_name ] : false;
		
		if ( ! $source || ! $template_id ) {
			wp_send_json_error();
		}
		$host_url = trailingslashit( sanitize_text_field( $_REQUEST['site_url'] ) );
		$license_key = sanitize_text_field( $_REQUEST['license_key'] );
		$template_data = $source->get_item( $template_id, false, $host_url, $license_key );


		if ( ! empty( $template_data['content'] ) ) {
            $content = $template_data['content'];
            // TODO: add featured image, categories and custom post meta as well.

            $meta_input = array(
                '_elementor_data'          => $content,
                '_elementor_edit_mode'     => 'builder',
                '_elementor_template_type' => $template_data['type'],
            );

            if ( isset( $template_data['page_settings'] ) && is_array($template_data['page_settings']) && count($template_data['page_settings']) ) {
                $meta_input['_elementor_page_settings'] = $template_data['page_settings'];
            }

			wp_insert_post( array(
				'post_type'   => 'elementor_library',
				'post_title'  => $template['title'],
				'post_status' => 'publish',
				'meta_input'  => $meta_input,
			) );
		}
		
		wp_send_json_success();
	}

    
		
		
	/**
	 * Get template.
	 *
	 * Get templates grid data.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function ajax_get_templates() {
		
		$tab = sanitize_text_field( $_GET['tab'] );
		$source = $this->sources['emsh-elementor-templates-api'];

		if ( $source ) {
			$template_result = $source->get_items($tab);
			if ( !$template_result['success'] ) {
				wp_send_json( $template_result );
			}
			else {
				$templates = $template_result['templates'];

				// dont send request for categories if this request is for subtemplates
				if ( isset( $_GET['template_group_id'] ) && $_GET['template_group_id'] ) {
					wp_send_json(array(
						'success' => true,
						'data' => array(
							'templates' => $templates,
							'categories' => array()
						)
					));
				}
				else {
					$catgs = $source->get_categories($tab);
					$all_cats = array(
						array(
							'slug'  => '',
							'title' => esc_html__( 'All', 'template-share-for-elementor' ),
						),
					);

					if ( $tab === 'emsh_single_templates' ) {
						$all_cats = array_merge($all_cats, array(
							array(
								'slug'  => 'emsh-template-type-section',
								'title' => esc_html__( 'Section', 'template-share-for-elementor' ),
							),
							array(
								'slug'  => 'emsh-template-type-page',
								'title' => esc_html__( 'Page', 'template-share-for-elementor' ),
							),
						));
					}
					
					if ( ! empty( $catgs ) ) {
						$catgs = array_merge( $all_cats, $catgs );
					}
					wp_send_json(array(
						'success' => true,
						'data' => array(
							'templates' => $templates,
							'categories' => $catgs
						)
					));
				}
			}
		}
		
	}
}



?>