<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 */
class Emshf_Plugin {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EMSHF_VERSION' ) ) {
			$this->version = EMSHF_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'template-share-for-elementor';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_hook_or_initialize();

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Include files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		include_once EMSHF_PATH . 'includes/class-key-manager.php';

		require_once EMSHF_PATH . 'includes/class-emsh-elementor-templates-assets.php';

		require_once EMSHF_PATH . 'includes/class-ajax-handler.php';

		require_once EMSHF_PATH . 'includes/class-rest-handler.php';
	}

	/**
	 * Defines hook or initializes any class.
	 *
	 * @return void
	 */
	public function define_hook_or_initialize() {

		//Admin enqueue script
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

		// custom post types
		add_action( 'init', array($this, 'register_cpt') );

		// queries modification for column sorting
		add_action( 'pre_get_posts', array($this, 'query_modification') );

		// custom column for licenses post type
		add_filter( 'manage_emsh-licenses_posts_columns', array( $this, 'licenses_custom_columns' ) );

		// column content for licenses post type
		add_action( 'manage_emsh-licenses_posts_custom_column', array($this, 'licenses_column_content'), 10, 2 );
		
		// make column sortable
		add_filter( 'manage_edit-emsh-licenses_sortable_columns', array( $this, 'licenses_sortable_columns' ) );

		// custom column for sites post type
		add_filter( 'manage_emsh-sites_posts_columns', array( $this, 'sites_custom_columns' ) );

		// column content for sites post type
		add_action( 'manage_emsh-sites_posts_custom_column', array($this, 'sites_column_content'), 10, 2 );

		// make column sortable
		add_filter( 'manage_edit-emsh-sites_sortable_columns', array( $this, 'sites_sortable_columns' ) );

		// meta boxes
		add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );

		add_action( 'emsh_metabox_pro_notice', array($this, 'metabox_pro_notice') );

		// disconnect when a "site" post type is trash or deleted
		add_action( 'wp_trash_post', array($this, 'disconnect_on_delete'), 10, 1 );

		// save post metas
		add_action( 'save_post', array( $this, 'save_postmeta' ) );

		// initialize elementor assets manager class
		Emsh_Elementor_Templates_Assets::get_instance();

		// rest handler class initialization
		Emsh_Rest_Handler::get_instance();

		// ajax handler class initialization
		Emsh_Ajax_Handler::get_instance();
	}


	/**
	 * Save custom meta fields
	 *
	 * @param [type] $post_id
	 * @return void
	 */
	public function save_postmeta($post_id) {

		if ( isset( $_POST['emsh_license_key'] ) ) {
			$key = sanitize_text_field( $_POST['emsh_license_key'] );
			update_post_meta( $post_id, 'emsh_license_key', $key );
		}

		if ( isset( $_POST['emsh_site_url'] ) ) {
			$key = sanitize_url( $_POST['emsh_site_url'] );
			update_post_meta( $post_id, 'emsh_site_url', $key );
		}

		if ( isset( $_POST['emsh_site_license_key'] ) ) {
			$key = sanitize_text_field( $_POST['emsh_site_license_key'] );
			update_post_meta( $post_id, 'emsh_site_license_key', $key );
		}

		if ( isset( $_POST['emsh_site_expiry_date'] ) ) {
			$key = sanitize_text_field( $_POST['emsh_site_expiry_date'] );
			update_post_meta( $post_id, 'emsh_site_expiry_date', $key );
		}

		if ( isset( $_POST['emsh_license_additional_info'] ) ) {
			$key = sanitize_text_field( $_POST['emsh_license_additional_info'] );
			update_post_meta( $post_id, 'emsh_license_additional_info', $key );
		}

		if ( isset( $_POST['emsh_connection_status'] ) ) {
			$key = sanitize_text_field( $_POST['emsh_connection_status'] );
			update_post_meta( $post_id, 'emsh_connection_status', $key );
		}

		if ( isset( $_POST['emsh_required_plugins']['names'] ) ) {
			$freshdata = array(
				'names' => array(),
				'urls' => array(),
			);
			foreach ($_POST['emsh_required_plugins']['names'] as $index => $plugin_name) {
				if ( $plugin_name ) {
					$freshdata['names'][$index] = sanitize_text_field( $plugin_name );
					$freshdata['urls'][$index] = sanitize_text_field( $_POST['emsh_required_plugins']['urls'][$index] );
				}
			}
			if ( is_array( $freshdata ) ) {
				update_post_meta( $post_id, 'emsh_required_plugins', sanitize_text_field( maybe_serialize( $freshdata ) ) );
			}
		}

		if ( isset( $_POST['emsh_preview_url'] ) ) {
			$key = sanitize_url( $_POST['emsh_preview_url'] );
			update_post_meta( $post_id, 'emsh_preview_url', $key );
		}


		do_action( 'emsh_save_postmeta', $post_id );
	}


	/**
	 * Disconnect on deleting a "site" post type
	 *
	 * @return void
	 */
	public function disconnect_on_delete($post_id) {
		// if there is a license key, a site url and the connection status is connected
		$key = get_post_meta( $post_id, 'emsh_site_license_key', true );
		$site = get_post_meta( $post_id, 'emsh_site_url', true );
		$status = get_post_meta( $post_id, 'emsh_connection_status', true );

		if ( $key && $site && $status === 'connected' ) {
			wp_remote_post( trailingslashit( $site ) . 'wp-json/template-share-server/api/v1/disconnect/', array(
					'method'      => 'POST',
					'timeout'     => 45,
					'sslverify' => false,
					'body'        => array(
						'license_key' => $key,
						'requesting_site' => trailingslashit(get_home_url()),
					),
				)
			);
		}
	}


	public function metabox_pro_notice() {
		echo esc_html__( 'Upgrade to Template Share Pro to use this feature', 'template-share-for-elementor' );
	}


	/**
	 * Define metaboxes across custom post types
	 *
	 * @return void
	 */
	public function metaboxes() {
		// metabox for licenses post type
		add_meta_box( 'license-information', esc_html__('License Information', 'template-share-for-elementor'), array($this, 'metabox_license_info'), array('emsh-licenses', 'emsh-generators') );

		// metabox for picking templates in licenses post type
		add_meta_box( 'license-templates', esc_html__('Templates', 'template-share-for-elementor'), array($this, 'metabox_license_templates'), array('emsh-licenses', 'emsh-generators') );

		// metabox for picking template groups in licenses post type
		add_meta_box( 'license-template-groups', esc_html__('Template Groups', 'template-share-for-elementor'), array($this, 'metabox_license_template_groups'), array('emsh-licenses', 'emsh-generators') );

		// another metabox for showing connected sites in the license post type
		add_meta_box( 'license-connected-sites', esc_html__('Connected Sites', 'template-share-for-elementor'), array($this, 'metabox_license_connected_sites'), 'emsh-licenses' );

		// another metabox for showing blocked sites in the license post type
		add_meta_box( 'license-blocked-sites', esc_html__('Blocked Sites', 'template-share-for-elementor'), array($this, 'metabox_license_blocked_sites'), 'emsh-licenses' );

		// metabox for sites post type
		add_meta_box( 'site-information', esc_html__('Site Information', 'template-share-for-elementor'), array( $this, 'metabox_site_info' ), 'emsh-sites' );

		// metabox for templates in the template group post type
		add_meta_box( 'template-group-templates', esc_html__('Templates', 'template-share-for-elementor'), array( $this, 'metabox_template_group_templates' ), 'emsh-template-groups' );

		// metabox for required plugins in elementor templates
		add_meta_box( 'emsh-required-plugins', esc_html__('Required Plugins', 'template-share-for-elementor'), array( $this, 'metabox_required_plugins' ), 'elementor_library' );

		// metabox for preview in elementor templates
		add_meta_box( 'emsh-preview-url', esc_html__('Preview URL', 'template-share-for-elementor'), array( $this, 'metabox_preview_url' ), 'elementor_library' );
	}


	/**
	 * Metabox html for license connected sites
	 *
	 * @return void
	 */
	public function metabox_license_connected_sites() {
		$connected_sites = get_post_meta( get_the_ID(  ), 'emsh_connected_sites', true );
		if ( $connected_sites && count(json_decode($connected_sites, true)) ) {
			$connected_sites = json_decode($connected_sites, true);
			?> 
			<div class="notice emsh-notice notice-error" style="display: none;"></div>
			<div class="notice emsh-notice notice-success" style="display: none;"></div>
			<ul>
				<?php 
				foreach ($connected_sites as $key => $value) {
					?> 
					<li class="single-connected-site">
						<a href="<?php echo esc_url( $value ); ?>"><?php echo esc_html( $value ); ?></a>
						<button class="button server-disconnect"><?php echo esc_html__( 'Disconnect', 'template-share-for-elementor' ); ?></button>
						<?php 
						ob_start();
						?>
						<button class="button server-block disabled"><?php echo esc_html__( 'Block (Pro)', 'template-share-for-elementor' ); ?></button>
						<?php 
						$content = apply_filters( 'emsh_metabox_html_connected_sites_block_button', ob_get_clean() );
						echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
			
		}
		else {
			?> 
			<p><?php echo esc_html__( 'This license is not connected to any site yet.', 'template-share-for-elementor' ); ?></p>
			<?php
		}
	}


	/**
	 * Metabox html for license info
	 *
	 * @return void
	 */
	public function metabox_license_info() {
		$key = get_post_meta( get_the_ID(  ), 'emsh_license_key', true );
		$date = get_post_meta( get_the_ID(  ), 'emsh_expiry_date', true );
		if ( $date ) {
			$date = date('Y-m-d', $date);
		}
		$max_site = get_post_meta( get_the_ID(  ), 'emsh_maximum_site', true );
		$additional_info = get_post_meta( get_the_ID(  ), 'emsh_license_additional_info', true );
		?> 
		<div class="emsh-admin">
			<?php 
			// Dont show the license key field in the generator post type
			if ( get_post_type(get_the_ID(  )) !== 'emsh-generators' ) {
				?> 
				<div class="form-group">
					<label for=""><?php echo esc_html__( 'License Key', 'template-share-for-elementor' ); ?></label>
					<div>
						<input type="text" name="emsh_license_key" id="license_key" value="<?php echo esc_attr( $key ); ?>">
						<button class="button cpy-btn">
							<i class="dashicons dashicons-admin-page"></i>
						</button>
						<button class="button license-generator"><?php echo esc_html__( 'Generate', 'template-share-for-elementor' ); ?></button>
					</div>
				</div>
				<?php
			}
			
			ob_start();
			?>
			<div class="form-group">
				<label for="expiry_date"><?php echo esc_html__( 'Expiry Date (Pro)', 'template-share-for-elementor' ); ?></label>
				<div>
					<input disabled type="date" name="" id="expiry_date" value="<?php echo esc_attr( $date ); ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="maximum_site"><?php echo esc_html__( 'Maximum Site (Pro)', 'template-share-for-elementor' ); ?></label>
				<div>
					<input disabled type="number" min="1" name="" id="maximum_site" value="<?php echo esc_attr( $max_site ); ?>">
				</div>
			</div>
			<?php 
			$content = apply_filters( 'emsh_metabox_html_license_info_pro_fields', ob_get_clean(), $date, $max_site );
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<div class="form-group">
				<label for="additional_info"><?php echo esc_html__( 'Additional Information', 'template-share-for-elementor' ); ?></label>
				<div>
					<input type="text" name="emsh_license_additional_info" id="additional_info" value="<?php echo esc_attr( $additional_info ); ?>">
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Metabox html for site info metabox
	 *
	 * @return void
	 */
	public function metabox_site_info() {
		$siteurl = get_post_meta( get_the_ID(  ), 'emsh_site_url', true );
		$site_license_key = get_post_meta( get_the_ID(  ), 'emsh_site_license_key', true );
		$status = get_post_meta( get_the_ID(  ), 'emsh_connection_status', true );
		$expiry_date = get_post_meta( get_the_ID(  ), 'emsh_site_expiry_date', true );
		// status can be connected, expired, notconnected
		$button_text = esc_html__('Connect', 'template-share-for-elementor');
		if ( $status === 'connected' ) {
			$button_text = esc_html__('Disconnect', 'template-share-for-elementor');
		}
		?> 
		<div class="emsh-admin">
			<input type="hidden" name="emsh_connection_status" value="<?php echo esc_attr( $status ); ?>">
			<input type="hidden" name="emsh_site_expiry_date" value="<?php echo esc_attr( $expiry_date ); ?>">
			<div class="notice emsh-notice notice-error" style="display: none;"></div>
			<div class="notice emsh-notice notice-success" style="display: none;"></div>
			<div class="form-group">
				<label for="emsh_site_url"><?php echo esc_html__( 'Site URL', 'template-share-for-elementor' ); ?></label>
				<div>
					<input type="text" name="emsh_site_url" id="emsh_site_url" value="<?php echo esc_attr( $siteurl ); ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="emsh_site_license_key"><?php echo esc_html__( 'License Key', 'template-share-for-elementor' ); ?></label>
				<div>
					<input type="text" min="1" name="emsh_site_license_key" id="emsh_site_license_key" value="<?php echo esc_attr( $site_license_key ); ?>">
				</div>
			</div>
			<button class="button connect-btn"
			data-connect="<?php echo esc_html__( 'Connect', 'template-share-for-elementor' ); ?>"
			data-disconnect="<?php echo esc_html__( 'Disconnect', 'template-share-for-elementor' ); ?>"
			data-connecting="<?php echo esc_html__( 'Connecting..', 'template-share-for-elementor' ); ?>"
			data-disconnecting="<?php echo esc_html__( 'Disonnecting..', 'template-share-for-elementor' ); ?>"
			data-status="<?php echo esc_attr( $status ); ?>"
			>
				<?php echo esc_html( $button_text ); ?>
			</button>
		</div>
		<?php
	}


	/**
	 * Metabox for picking templates in the template group post type
	 *
	 * @return void
	 */
	public function metabox_license_template_groups() {
		
		ob_start();

		?> 
		<div class="emsh-admin">
			<p><?php echo esc_html__( 'You can choose template groups for this license here.', 'template-share-for-elementor' ); ?></p>
			<?php do_action( 'emsh_metabox_pro_notice' ); ?>
		</div>
		<?php
		$content = apply_filters( 'emsh_metabox_html_license_template_groups', ob_get_clean() );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Metabox for picking templates in the template group post type
	 *
	 * @return void
	 */
	public function metabox_license_templates() {
		
		ob_start();

		?> 
		<div class="emsh-admin">
			<p><?php echo esc_html__( 'You can choose templates for this license here.', 'template-share-for-elementor' ); ?></p>
			<?php do_action( 'emsh_metabox_pro_notice' ); ?>
		</div>
		<?php
		$content = apply_filters( 'emsh_metabox_html_license_templates', ob_get_clean() );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Metabox for picking templates in the template group post type
	 *
	 * @return void
	 */
	public function metabox_license_blocked_sites() {
		
		ob_start();

		?> 
		<div class="emsh-admin">
			<p><?php echo esc_html__( 'Here you can view the list of blocked sites of this license and you can unblock them.', 'template-share-for-elementor' ); ?></p>
			<?php do_action( 'emsh_metabox_pro_notice' ); ?>
		</div>
		<?php
		$content = apply_filters( 'emsh_metabox_html_license_blocked_sites', ob_get_clean() );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Metabox for picking templates in the template group post type
	 *
	 * @return void
	 */
	public function metabox_template_group_templates() {
		
		ob_start();

		?> 
		<div class="emsh-admin">
			<p><?php echo esc_html__( 'Here you can choose the templates of this template group.', 'template-share-for-elementor' ); ?></p>
			<?php do_action( 'emsh_metabox_pro_notice' ); ?>
		</div>
		<?php
		$content = apply_filters( 'emsh_metabox_html_template_group_templates', ob_get_clean() );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Required plugins metabox in elementor templates post type
	 *
	 * @return void
	 */
	public function metabox_required_plugins() {
		?> 
		<div class="emsh-admin">
			<div class="emsh-sortable-wrap">
				<?php 
				$plugins = maybe_unserialize( get_post_meta( get_the_ID(  ), 'emsh_required_plugins', true ) );
				if ( !$plugins ) $plugins = array(
					"names" => array(),
					"urls" => array()
				);
				
				if ( is_array($plugins['names']) ) {
					if ( !count( $plugins['names'] ) ) {
						$plugins['names'] = array('');
					}
					if ( !count( $plugins['urls'] ) ) {
						$plugins['urls'] = array('');
					}
					$counter = 0;
					foreach ($plugins['names'] as $index => $plugin_name) {
						?> 
						<div class="emsh-single-sortable-item counter-<?php echo esc_attr( $counter ); ?>">
							<div class="form-group">
								<label><?php echo esc_html__( 'Plugin Name', 'template-share-for-elementor' ); ?></label>
								<div>
									<input type="text" name="emsh_required_plugins[names][]" value="<?php echo esc_attr( $plugin_name ); ?>">
								</div>
							</div>
							<div class="form-group">
								<label><?php echo esc_html__( 'Plugin URL', 'template-share-for-elementor' ); ?></label>
								<div>
									<input type="url" name="emsh_required_plugins[urls][]" value="<?php echo esc_attr( $plugins['urls'][$index] ); ?>">
								</div>
							</div>
							<div 
							class="button tg-delete-item"
							style="display: <?php echo esc_attr( $counter === 0 ? 'none': 'inline-block' ); ?>;"
							>
								<?php echo esc_html__( 'Delete', 'template-share-for-elementor' ); ?>
							</div>
							<div class="clear"></div>
						</div>
						<?php
						$counter++;
					}
				}
				?>
			</div>
			<button class="button addsortableitem"><?php echo esc_html__( 'Add New', 'template-share-for-elementor' ); ?></button>
		</div>
		<?php
	}


	/**
	 * Required plugins metabox in elementor templates post type
	 *
	 * @return void
	 */
	public function metabox_preview_url() {
		$preview_url = get_post_meta( get_the_ID(  ), 'emsh_preview_url', true );
		?> 
		<div class="emsh-admin">
			<div class="form-group">
				<label><?php echo esc_html__( 'URL', 'template-share-for-elementor' ); ?></label>
				<div>
					<input type="url" name="emsh_preview_url" value="<?php echo esc_url( $preview_url ); ?>">
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Define custom columns for sites post type
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function sites_custom_columns($columns) {
		$columns = array_merge($columns, ['site_url' => esc_html__('Site URL', 'template-share-for-elementor')]);
		$columns = array_merge($columns, ['license_key' => esc_html__('License Key', 'template-share-for-elementor')]);

		$columns = array_merge($columns, ['expiry_date' => esc_html__('Expiry Date', 'template-share-for-elementor')]);


		$columns = array_merge($columns, ['status' => esc_html__('Status', 'template-share-for-elementor')]);

		unset($columns['date']);
		return $columns;
	}


	/**
	 * Define sortable columns for sites post type
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function sites_sortable_columns($columns) {
		$columns['expiry_date'] = esc_html__('Expiry Date', 'template-share-for-elementor');
		return $columns;
	}


	/**
	 * Column content for sites post type
	 *
	 * @param [type] $column_key
	 * @param [type] $post_id
	 * @return void
	 */
	public function sites_column_content($column_key, $post_id) {

		if ( $column_key === 'license_key' ) {
			$key = get_post_meta( $post_id, 'emsh_site_license_key', true );
			echo esc_html( substr_replace($key, '**********************', 5, 22) );
		}

		if ( $column_key === 'site_url' ) {
			$maxsites = get_post_meta( $post_id, 'emsh_site_url', true );
			echo esc_html( $maxsites );
		}

		if ( $column_key === 'status' ) {
			$con_status = get_post_meta( $post_id, 'emsh_connection_status', true );
			$text = esc_html__('Not Connected', 'template-share-for-elementor');
			$class = 'emsh-notconnected';
			if ( $con_status ) {
				$class = 'emsh-' . $con_status;

				if ( $con_status === 'connected' ) {
					$text = esc_html__('Connected', 'template-share-for-elementor');
				}
				else if ( $con_status === 'expired' ) {
					$text = esc_html__('Expired', 'template-share-for-elementor');
				}
			}

			?> 
			<span class="<?php echo esc_attr( $class ); ?>">
				<?php echo esc_html( $text ); ?>
			</span>
			<?php
		}

		if ( $column_key === 'expiry_date' ) {
			$date = get_post_meta( $post_id, 'emsh_site_expiry_date', true );
			if ( $date ) {

				echo esc_html( date("F j, Y", (int)$date) );
			}
		}
	}


	/**
	 * Define sortable columns for licenses post type
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function licenses_sortable_columns($columns) {
		$columns['expiry_date'] = esc_html__('Expiry Date', 'template-share-for-elementor');
		$columns['max_sites'] = esc_html__('Maximum Sites', 'template-share-for-elementor');
		return $columns;
	}


	/**
	 * Column content for licenses post type
	 *
	 * @param [type] $column_key
	 * @param [type] $post_id
	 * @return void
	 */
	public function licenses_column_content($column_key, $post_id) {
		if ( $column_key === 'expiry_date' ) {
			$date = get_post_meta( $post_id, 'emsh_expiry_date', true );
			if ( $date ) {

				echo esc_html( date("F j, Y", (int)$date) );
			}
		}

		if ( $column_key === 'license_key' ) {
			$key = get_post_meta( $post_id, 'emsh_license_key', true );
			echo esc_html( substr_replace($key, '**********************', 5, 22) );
		}

		if ( $column_key === 'max_sites' ) {
			$maxsites = get_post_meta( $post_id, 'emsh_maximum_site', true );
			echo esc_html( $maxsites );
		}

		if ( $column_key === 'connected_sites' ) {
			$connected_sites = get_post_meta( get_the_ID(  ), 'emsh_connected_sites', true );
            if ( $connected_sites ) {
                $connected_sites = json_decode($connected_sites, true);
            }
            else {
                $connected_sites = array();
            }

			echo esc_html( count($connected_sites) );
		}
	}


	/**
	 * Define custom columns for licenses post type
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function licenses_custom_columns($columns) {
		$columns = array_merge($columns, ['license_key' => esc_html__('License Key', 'template-share-for-elementor')]);
		$columns = array_merge($columns, ['expiry_date' => esc_html__('Expiry Date', 'template-share-for-elementor')]);
		$columns = array_merge($columns, ['max_sites' => esc_html__('Maximum Sites', 'template-share-for-elementor')]);


		$columns = array_merge($columns, ['connected_sites' => esc_html__('Connected Sites', 'template-share-for-elementor')]);

		unset($columns['date']);
		return $columns;
	}


	/**
	 * For modifying query while sorting posts table column
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function query_modification($query) {
		$orderby = $query->get( 'orderby' );
		if ( 'Expiry Date' === $orderby ) {
			$query->set( 'orderby', 'meta_value_num' );
			if ( $query->get('post_type') === 'emsh-sites' ) {

				$query->set('meta_key', 'emsh_site_expiry_date');
			}
			else {
				$query->set('meta_key', 'emsh_expiry_date');
			}
		}

		if ( 'Maximum Sites' === $orderby ) {
			$query->set( 'orderby', 'meta_value_num' );
			$query->set('meta_key', 'emsh_maximum_site');
		}
	}


	/**
	 * Register custom post types
	 *
	 * @return void
	 */
	public function register_cpt() {

		// For license (share)
		$args = array(
			'labels' => array(
				'name'           => esc_html__('Licenses You Share', 'template-share-for-elementor'),
				'singular_name'  => esc_html__('License', 'template-share-for-elementor'),
				'add_new_item'   => esc_html__('Add new', 'template-share-for-elementor'),
				'add_new'   => esc_html__('Add new', 'template-share-for-elementor'),
				'edit_item'      => esc_html__('Edit License', 'template-share-for-elementor'),
				'item_updated'   => esc_html__('License updated', 'template-share-for-elementor'),
				'item_published' => esc_html__('License published', 'template-share-for-elementor'),
				'menu_name'      => esc_html__('Share', 'template-share-for-elementor'),
			),
			'hierarchical' => false,
			'public'       => true,
			'has_archive'  => false,
			'supports'     => array('title'),
			'show_in_menu' => 'emsh',
			'rewrite'      => array(
				'slug'     => 'emsh-licenses'
			)
		);
		register_post_type( 'emsh-licenses', $args );

		// For sites (receive)
		$args = array(
			'labels' => array(
				'name'           => esc_html__('Licenses to Connect', 'template-share-for-elementor'),
				'singular_name'  => esc_html__('License', 'template-share-for-elementor'),
				'add_new_item'   => esc_html__('Add new', 'template-share-for-elementor'),
				'add_new'   => esc_html__('Add new', 'template-share-for-elementor'),
				'edit_item'      => esc_html__('Edit License', 'template-share-for-elementor'),
				'item_updated'   => esc_html__('License updated', 'template-share-for-elementor'),
				'item_published' => esc_html__('License published', 'template-share-for-elementor'),
				'menu_name'      => esc_html__('Receive', 'template-share-for-elementor'),
			),
			'hierarchical' => false,
			'public'       => true,
			'has_archive'  => false,
			'supports'     => array('title'),
			'show_in_menu' => 'emsh',
			'rewrite'      => array(
				'slug'     => 'emsh-sites'
			)
		);
		register_post_type( 'emsh-sites', $args );


		// For template groups
		$args = array(
			'labels' => array(
				'name'           => esc_html__('Template Groups', 'template-share-for-elementor'),
				'singular_name'  => esc_html__('Template Group', 'template-share-for-elementor'),
				'add_new_item'   => esc_html__('Add new Template Group', 'template-share-for-elementor'),
				'edit_item'      => esc_html__('Edit Template Group', 'template-share-for-elementor'),
				'item_updated'   => esc_html__('Template Group updated', 'template-share-for-elementor'),
				'item_published' => esc_html__('Template Group published', 'template-share-for-elementor'),
			),
			'hierarchical' => false,
			'public'       => true,
			'has_archive'  => false,
			'supports'     => array('title', 'thumbnail'),
			'show_in_menu' => 'emsh',
			'rewrite'      => array(
				'slug'     => 'emsh-template-groups'
			)
		);
		register_post_type( 'emsh-template-groups', $args );

		// category taxonomy for template groups
		$labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'template-share-for-elementor' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'template-share-for-elementor' ),
			'search_items'      => esc_html__( 'Search Categories', 'template-share-for-elementor' ),
			'all_items'         => esc_html__( 'All Categories', 'template-share-for-elementor' ),
			'parent_item'       => esc_html__( 'Parent Category', 'template-share-for-elementor' ),
			'parent_item_colon' => esc_html__( 'Parent Category:', 'template-share-for-elementor' ),
			'edit_item'         => esc_html__( 'Edit Category', 'template-share-for-elementor' ),
			'update_item'       => esc_html__( 'Update Category', 'template-share-for-elementor' ),
			'add_new_item'      => esc_html__( 'Add New Category', 'template-share-for-elementor' ),
			'new_item_name'     => esc_html__( 'New Category Name', 'template-share-for-elementor' ),
			'menu_name'         => esc_html__( 'Category', 'template-share-for-elementor' ),
		);
	
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu' => 'emsh',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'emsh-template-group-category' ),
		);
	
		register_taxonomy( 'emsh-template-group-category', array( 'emsh-template-groups' ), $args );
	}


	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		$capability = class_exists('WooCommerce') ? 'manage_woocommerce' : 'manage_options';
		add_menu_page( esc_html__('Template Share', 'template-share-for-elementor'), esc_html__('Template Share', 'template-share-for-elementor'), $capability, 'emsh', '__return_null', 'dashicons-share', 3 );

		add_submenu_page( 'emsh', esc_html__('Template Group Categories', 'template-share-for-elementor'), esc_html__('Categories', 'template-share-for-elementor'), $capability, 'emsh-template-group-category', '__return_null' );
	}
	
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'template-share-for-elementor',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function admin_scripts() {
		global $typenow;

		if ( in_array( $typenow, array('emsh-template-groups', 'emsh-sites', 'emsh-licenses', 'emsh-generators') ) ) {
			wp_enqueue_script( 'crypto-js-core', EMSHF_ASSETS_URL . 'lib/js/crypto-js-core.js', array(), EMSHF_ASSETS_VERSION, true );
			wp_enqueue_script( 'md5', EMSHF_ASSETS_URL . 'lib/js/md5.js', array('crypto-js-core'), EMSHF_ASSETS_VERSION, true );
			
			wp_enqueue_script( 'emshselectwoo', EMSHF_ASSETS_URL . 'lib/js/selectWoo.full.min.js', array('jquery'), EMSHF_ASSETS_VERSION, true );

			wp_enqueue_style( 'emshselectwoo', EMSHF_ASSETS_URL . 'lib/css/selectWoo.min.css', array(), EMSHF_ASSETS_VERSION );
		}
		
		wp_enqueue_style( 'template-share-admin', EMSHF_ASSETS_URL . 'dist/css/admin.min.css', array(), EMSHF_ASSETS_VERSION );
		wp_enqueue_script( 'template-share-admin', EMSHF_ASSETS_URL . 'dist/js/admin.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), EMSHF_ASSETS_VERSION, true );

		wp_localize_script( 'template-share-admin', 'emsh_admin', array(
			'areyousure' => esc_html__('Are you sure?', 'template-share-for-elementor')
		) );
	}

}
