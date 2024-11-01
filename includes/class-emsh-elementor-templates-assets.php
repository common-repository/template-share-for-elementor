<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // No access of directly access.

if ( ! class_exists( 'Emsh_Elementor_Templates_Assets' ) ) {
	
	/**
	 * EMSH Elementor Sections Templates Assets.
	 *
	 * EMSH Elementor Sections Templates Assets class is responsible for enqueuing all required assets for integration templates on the editor page.
	 *
	 * @since 1.0.0
	 */
	class Emsh_Elementor_Templates_Assets {
		
		/**
		 * Instance of the class.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private static $instance = null;
		
		/**
		 * Emsh_Elementor_Templates_Assets constructor.
		 *
		 * Triggers the required hooks to enqueue CSS/JS files.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function __construct() {
			
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_styles' ) );
			
			add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'editor_scripts' ), 0 );
			
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
			
			add_action( 'elementor/editor/footer', array( $this, 'load_footer_scripts' ) );
			
		}
		
		/**
		 * Preview Styles.
		 *
		 * Enqueue required templates CSS file.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_preview_styles() {
			
			$is_rtl = is_rtl() ? '-rtl' : '';
			
			wp_enqueue_style(
				'emsh-elementor-sections-editor-style',
				EMSHF_ASSETS_URL . 'dist/css/preview' . $is_rtl . '.min.css',
				array(),
				EMSHF_ASSETS_VERSION,
				'all'
			);
			
		}
		
		/**
		 * Editor Styles
		 *
		 * Enqueue required editor CSS files.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function editor_styles() {
			
			$is_rtl = is_rtl() ? '-rtl' : '';
			
			wp_enqueue_style(
				'emsh-elementor-sections-editor-style',
				EMSHF_ASSETS_URL . 'dist/css/editor' . $is_rtl . '.min.css',
				array(),
				EMSHF_ASSETS_VERSION,
				'all'
			);
			
		}
		
		/**
		 * Editor Scripts.
		 *
		 * Enqueue required editor JS files, localize JS with required data.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function editor_scripts() {
			wp_enqueue_script( 'sweetalert', EMSHF_ASSETS_URL . 'lib/js/sweetalert2.all.min.js', array(), null, true );
			wp_enqueue_script(
				'emsh-elementor-sections-temps-editor',
				EMSHF_ASSETS_URL . 'dist/js/editor.min.js',
				array(
					'jquery',
					'underscore',
					'backbone-marionette',
					'sweetalert',
				),
				EMSHF_ASSETS_VERSION,
				true
			);

			wp_enqueue_style( 'sweetalert', EMSHF_ASSETS_URL . 'lib/css/sweetalert2.min.css', array(), EMSHF_ASSETS_VERSION );

			wp_localize_script(
				'emsh-elementor-sections-temps-editor', 'Emsh_ElementorSectionsData',
				apply_filters( 'emsh-elementor-sections-templates-core/assets/editor/localize',
					array(
						'modalRegions'      => $this->get_modal_region(),
						'cantcommunicate' => esc_html__( 'Could not communicate with the server site!', 'template-share-for-elementor' ),
						'requiredplugins' => esc_html__( 'Required Plugins', 'template-share-for-elementor' ),
						'confirm' => esc_html__( 'Confirm', 'template-share-for-elementor' ),
						'pleaseconfirm' => esc_html__( 'Please confirm that you have the following required plugins installed', 'template-share-for-elementor' ),
						'cancel' => esc_html__( 'Cancel', 'template-share-for-elementor' ),
						'apply' => esc_html__( 'Apply', 'template-share-for-elementor' ),
						'thiswilloverride' => esc_html__( "This may override the design, layout, and other settings of the page you’re working on.", 'template-share-for-elementor' ),
						'applypagesettings' => esc_html__( 'Apply the page settings too?', 'template-share-for-elementor' ),
						'dontapply' => esc_html__( 'Don\'t Apply', 'template-share-for-elementor' ),
						'unknownresponse' => esc_html__( 'Unknown response from the server site!', 'template-share-for-elementor' ),
						'Elementor_Version' => ELEMENTOR_VERSION,
						'icon'              => EMSHF_ASSETS_URL . 'images/template-share.png',
						'tabs' => array(
							'emsh_single_templates' => array(
								'title'    => esc_html__( 'Templates', 'template-share-for-elementor' ),
								'data'     => array(),
								'settings' => array(
									'show_title' => true,
								),
							),
							'emsh_template_groups' => array(
								'title'    => esc_html__( 'Template Groups', 'template-share-for-elementor' ),
								'data'     => array(),
								'settings' => array(
									'show_title' => true,
								),
							)
						),
						'defaultTab' => 'emsh_single_templates',
					)
				)
			);

		}
		
		/**
		 * Get Modal Region.
		 *
		 * Get modal region in the editor.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function get_modal_region() {
			
			return array(
				'modalHeader'  => '.dialog-header',
				'modalContent' => '.dialog-message',
			);
			
		}
		
		/**
		 * Add Templates Scripts.
		 *
		 * Load required templates for the templates library.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function load_footer_scripts() {
			
			$scripts = glob( EMSHF_PATH . 'includes/templates/scripts/*.php' );
			array_map( function ( $file ) {
				$name = basename( $file, '.php' );
				ob_start();
				include $file;
				printf( '<script type="text/html" id="tmpl-emshelementor-%1$s">%2$s</script>', $name, ob_get_clean() );
				
			}, $scripts );
			
		}
		
		/**
		 * Get Instance.
		 *
		 * Creates and returns an instance of the class.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return object
		 */
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				
				self::$instance = new self;
				
			}
			
			return self::$instance;
			
		}
		
	}
	
}