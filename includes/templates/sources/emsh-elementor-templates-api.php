<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Emsh_Elementor_Templates_Api extends Emsh_Elementor_Templates_Source_Base {
	
	private $_object_cache = array();
	
	/**
	 * Return source slug.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string
	 */
	public function get_slug() {
		return 'emsh-elementor-templates-api';
	}
	
	/**
	 * Return cached items list.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_items( $tab = null ) {
		
		if ( ! $tab ) {
			
			return array();
		}
		
		
		$result = $this->remote_get_templates( $tab );
		
		return $result;
		
	}
	
	/**
	 * Prepare items tab.
	 *
	 * @since  1.0.0
	 *
	 * @param string $tab tab slug
	 *
	 * @return object $result templates data
	 */
	public function prepare_items_tab( $tab = '' ) {
		
		if ( ! empty( $this->_object_cache[ $tab ] ) ) {
			return $this->_object_cache[ $tab ];
		}
		
		$result = array(
			'templates'  => array(),
			'categories' => array(),
		);
		$template_result = $this->remote_get_templates( $tab );
		$result['templates'] = $template_result['success'] ? $template_result['templates'] : array();
		$result['categories'] = $this->remote_get_categories( $tab );
		
		return $result;
	}
	
	/**
	 * Get templates from remote server.
	 * @since  1.0.0
	 *
	 * @param string $tab tab slug.
	 *
	 * @return array|bool
	 */
	public function remote_get_templates( $tab ) {
		
		
		$host_url = trailingslashit( sanitize_text_field( $_GET['site_url'] ) );
		$license_key = sanitize_text_field( $_GET['license_key'] );
		
		// send remote request to get templates
		$formdata = array(
			'license_key' => $license_key,
			'requesting_site' => trailingslashit(get_home_url()),
		);

		$endpoint = 'templates/';
		if ( isset( $_GET['template_group_id'] ) && $_GET['template_group_id'] ) {
			$endpoint = 'subtemplates/';
			$formdata['template_group_id'] = sanitize_text_field( $_GET['template_group_id'] );
		}

		$response = wp_remote_get( $host_url . "wp-json/template-share-server/api/v1/$endpoint" . $tab, array(
			'timeout'   => 60,
			'sslverify' => false,
			'body'        => $formdata,
		) );
		
		$body = wp_remote_retrieve_body( $response );
		
		// Bail out, if not set.
		if ( ! $body ) {
			return array(
				'success' => false,
				'message' => esc_html__('Unknown response from server', 'template-share-for-elementor')
			);
		}
		else {
			$body = json_decode( $body, true );

			if ( !isset( $body['success'] ) ) {
				return array(
					'success' => false,
					'message' => esc_html__('There was a problem establishing the connection', 'template-share-for-elementor')
				);
			}
			else {

				if ( !$body['success'] ) {
					return $body;
				}
				else {
					return array(
						'success' => true,
						'templates' => $body['templates']
					);
				}
			}
		
		}
		
	}
	
	/**
	 * Get categories from remote server.
	 * @since  1.0.0
	 *
	 * @param string $tab tab slug.
	 *
	 * @return array|bool
	 */
	public function remote_get_categories( $tab ) {

		$host_url = trailingslashit( sanitize_text_field( $_GET['site_url'] ) );
		$license_key = sanitize_text_field( $_GET['license_key'] );
		
		$response = wp_remote_get( $host_url . 'wp-json/template-share-server/api/v1/template-categories/' . $tab, array(
			'timeout'   => 60,
			'sslverify' => false,
			'body'        => array(
				'license_key' => $license_key,
				'requesting_site' => trailingslashit(get_home_url()),
			),
		) );
		
		$body = wp_remote_retrieve_body( $response );
		
		// Bail out, if not set.
		if ( ! $body ) {
			return false;
		}
		
		$body = json_decode( $body, true );
		
		// Bail out, if not success.
		if ( ! isset( $body['success'] ) || true !== $body['success'] ) {
			return false;
		}
		
		// Bail out, if not set categories.
		if ( empty( $body['terms'] ) ) {
			return false;
		}
		
		return $body['terms'];
		
	}
	
	/**
	 * Return source item list.
	 * @since  1.0.0
	 *
	 * @param string $tab
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_categories( $tab = null ) {
		
		if ( ! $tab ) {
			return array();
		}
		
		$categories = $this->remote_get_categories( $tab );
		
		if ( ! $categories ) {
			return array();
		}

		return $categories;
	}
	
	/**
	 * Return single item.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param bool $tab
	 * @param int  $template_id
	 *
	 * @return array
	 */
	public function get_item( $template_id, $tab = false, $host_url = '', $license_key = '' ) {
		
		
		$endpoint = 'template/';
		$response = wp_remote_get( trailingslashit( $host_url ) . "wp-json/template-share-server/api/v1/$endpoint" . $template_id, array(
			'timeout'   => 60,
			'sslverify' => false,
			'body'        => array(
				'license_key' => $license_key,
				'requesting_site' => trailingslashit( get_home_url(  ) )
			),
		) );
		
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );
		
		if ( ! isset( $body['success'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Internal Error', 'template-share-for-elementor' ),
				'body' => $body
			) );
		}
		
		$content = isset( $body['content'] ) ? $body['content'] : '';
		$type    = isset( $body['type'] ) ? $body['type'] : '';
		
		if ( ! empty( $content ) ) {
			$content = $this->replace_elements_ids( $content );
			$content = $this->process_export_import_content( $content, 'on_import' );
		}
		
		return array(
			'page_settings' => isset( $body['page_settings'] ) ? $body['page_settings'] : array(),
			'type'          => $type,
			'content'       => $content
		);
		
	}
	
	/**
	 * Return transient lifetime.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string
	 */
	public function transient_lifetime() {
		return DAY_IN_SECONDS;
	}
}
