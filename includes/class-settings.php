<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings class.
 *
 * @class Post_Views_Counter_Settings
 */
class Post_Views_Counter_Settings {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'admin_init', [ $this, 'update_counter_mode' ], 12 );
		add_action( 'pvc_settings_sidebar', [ $this, 'settings_sidebar' ], 12 );
		add_action( 'pvc_settings_form', [ $this, 'settings_form' ], 10, 4 );

		// filters
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_data' ] );
		add_filter( 'post_views_counter_settings_pages', [ $this, 'settings_page' ] );
		add_filter( 'post_views_counter_settings_page_class', [ $this, 'settings_page_class' ] );
	}

	/**
	 * Add hidden inputs to redirect to valid page after changing menu position.
	 *
	 * @param string $setting
	 * @param string $page_type
	 * @param string $url_page
	 * @param string $tab_key
	 * @return void
	 */
	public function settings_form( $setting, $page_type, $url_page, $tab_key ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// topmenu referer
		$topmenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="topmenu" value="' .esc_url( admin_url( 'admin.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		// submenu referer
		$submenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="submenu" value="' .esc_url( admin_url( 'options-general.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		if ( $pvc->options['other']['menu_position'] === 'sub' )
			echo $topmenu . $submenu;
		else
			echo $submenu . $topmenu;
	}

	/**
	 * Display settings sidebar.
	 *
	 * @return void
	 */
	public function settings_sidebar() {
		// get main instance
		$pvc = Post_Views_Counter();
	}

	/**
	 * Update counter mode.
	 *
	 * @return void
	 */
	public function update_counter_mode() {
		// get main instance
		$pvc = Post_Views_Counter();

		// get settings
		$settings = $pvc->settings_api->get_settings();

		// fast ajax as active but not available counter mode?
		if ( $pvc->options['general']['counter_mode'] === 'ajax' && in_array( 'ajax', $settings['post-views-counter']['fields']['counter_mode']['disabled'], true ) ) {
			// set standard javascript ajax calls
			$pvc->options['general']['counter_mode'] = 'js';

			// update database options
			update_option( 'post_views_counter_settings_general', $pvc->options['general'] );
		}
	}

	/**
	 * Get available counter modes.
	 *
	 * @return array
	 */
	public function get_counter_modes() {
		// counter modes
		$modes = [
			'php'		=> __( 'PHP', 'post-views-counter' ),
			'js'		=> __( 'JavaScript', 'post-views-counter' ),
			'rest_api'	=> __( 'REST API', 'post-views-counter' ),
// 			'ajax'		=> __( 'Fast AJAX', 'post-views-counter' )
		];

		return apply_filters( 'pvc_get_counter_modes', $modes );
	}

	/**
	 * Add settings data.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function settings_data( $settings ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// time types
		$time_types = [
			'minutes'	=> __( 'minutes', 'post-views-counter' ),
			'hours'		=> __( 'hours', 'post-views-counter' ),
			'days'		=> __( 'days', 'post-views-counter' ),
			'weeks'		=> __( 'weeks', 'post-views-counter' ),
			'months'	=> __( 'months', 'post-views-counter' ),
			'years'		=> __( 'years', 'post-views-counter' )
		];

		// user groups
		$groups = [
			'robots'	=> __( 'robots', 'post-views-counter' ),
			'users'		=> __( 'logged in users', 'post-views-counter' ),
			'guests'	=> __( 'guests', 'post-views-counter' ),
			'roles'		=> __( 'selected user roles', 'post-views-counter' )
		];

		// get user roles
		$user_roles = $pvc->functions->get_user_roles();

		// get post types
		$post_types = $pvc->functions->get_post_types();

		// add settings
		$settings['post-views-counter'] = [
			'label' => __( 'Post Views Counter Settings', 'post-views-counter' ),
			'form' => [
				'reports'	=> [
					'buttons'	=> false
				]
			],
			'option_name' => [
				'general'	=> 'post_views_counter_settings_general',
				'display'	=> 'post_views_counter_settings_display',
				'other'		=> 'post_views_counter_settings_other'
			],
			'validate' => [ $this, 'validate_settings' ],
			'sections' => [
				'post_views_counter_general_settings'	=> [
					'tab'	=> 'general'
				],
				'post_views_counter_display_settings'	=> [
					'tab'	=> 'display'
				],
				'post_views_counter_other_settings'		=> [
					'tab'	=> 'other'
				]
			],
			'fields' => [
				'post_types_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Post Types Count', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types for which post views will be counted.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'counter_mode' => [
					'tab'			=> 'general',
					'title'			=> __( 'Counter Mode', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'radio',
					'description'	=> __( 'Select the method of collecting post views data. If you are using any of the caching plugins select JavaScript, REST API or Fast AJAX (up to <code>10+</code> times faster!).', 'post-views-counter' ),
					'options'		=> $this->get_counter_modes(),
				],
				'post_views_column' => [
					'tab'			=> 'general',
					'title'			=> __( 'Admin Column', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to display post views count admin column for each counted content type.', 'post-views-counter' )
				],
				'restrict_edit_views' => [
					'tab'			=> 'general',
					'title'			=> __( 'Admin Edit', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to restrict post views editing to admins only.', 'post-views-counter' )
				],
				'data_storage' => [
					'tab'			=> 'general',
					'title'			=> __( 'Data Storage', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'radio',
					'description'	=> __( "Choose how to store the content views data in the user's browser - with or without cookies.", 'post-views-counter' ),
					'options'		=> [
						'cookies'		=> __( 'Cookies', 'post-views-counter' ),
						'cookieless'	=> __( 'Cookieless', 'post-views-counter' )
					],
					'value'			=> 'cookies'
				],
				'time_between_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Count Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_time_between_counts' ],
					'validate'		=> [ $this, 'validate_time_between_counts' ]
				],
				'reset_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Cleanup Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> sprintf( __( 'Delete single day post views data older than specified above. Enter %s if you want to preserve your daily views data regardless of its age.', 'post-views-counter' ), '<code>0</code>' ),
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_reset_counts' ],
					'validate'		=> [ $this, 'validate_reset_counts' ]
				],
				'object_cache' => [
					'tab'			=> 'general',
					'title'			=> __( 'Object Cache Support', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'value'			=> false,
					'label'			=> sprintf( __( 'Enable to use object cache optimization.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>' ),
					'description'	=> sprintf( __( 'This feature requires a persistent object cache like %s or %s to be installed and activated.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>' )
				],
				'exclude' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude Visitors', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'callback'		=> [ $this, 'setting_exclude' ],
					'validate'		=> [ $this, 'validate_exclude' ]
				],
				'exclude_ips' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude IPs', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'callback'		=> [ $this, 'setting_exclude_ips' ],
					'validate'		=> [ $this, 'validate_exclude_ips' ]
				],
				'label' => [
					'tab'			=> 'display',
					'title'			=> __( 'Views Label', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'input',
					'description'	=> __( 'Enter the label for the post views counter field.', 'post-views-counter' ),
					'subclass'		=> 'regular-text',
					'validate'		=> [ $this, 'validate_label' ],
					'reset'			=> [ $this, 'reset_label' ]
				],
				'display_period' => [
					'tab'			=> 'display',
					'title'			=> __( 'Views Period', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'select',
					'description'	=> __( 'Select the time period to be included when displaying the number of views. The default display is the total number of views of the post.', 'post-views-counter' ),
					'options'		=> 	[
						'total'		=> __( 'Total Views', 'post-views-counter' )
					]
				],
				'display_style' => [
					'tab'			=> 'display',
					'title'			=> __( 'Display Style', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'custom',
					'description'	=> __( 'Choose how to display the post views counter.', 'post-views-counter' ),
					'callback'		=> [ $this, 'setting_display_style' ],
					'validate'		=> [ $this, 'validate_display_style' ],
					'options'		=> [
						'icon'	=> __( 'icon', 'post-views-counter' ),
						'text'	=> __( 'label', 'post-views-counter' )
					]
				],
				'icon_class' => [
					'tab'			=> 'display',
					'title'			=> __( 'Icon Class', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'class',
					'default'		=> '',
					'description'	=> sprintf( __( 'Enter the post views icon class. Any of the <a href="%s" target="_blank">Dashicons</a> classes are available.', 'post-views-counter' ), 'https://developer.wordpress.org/resource/dashicons/' ),
					'subclass'		=> 'regular-text'
				],
				'position' => [
					'tab'			=> 'display',
					'title'			=> __( 'Position', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'select',
					'description'	=> sprintf( __( 'Select where would you like to display the post views counter. Use %s shortcode for manual display.', 'post-views-counter' ), '<code>[post-views]</code>' ),
					'options'		=> [
						'before'	=> __( 'before the content', 'post-views-counter' ),
						'after'		=> __( 'after the content', 'post-views-counter' ),
						'manual'	=> __( 'manual', 'post-views-counter' )
					]
				],
				'use_format' => [
					'tab'			=> 'display',
					'title'			=> __( 'Format Number', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'boolean',
					'label'			=> __( 'Enable to display the views number formatted based on the locale (using the WP number_format_i18n function).', 'post-views-counter' )
				],
				'post_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Post Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types for which the views count will be displayed.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'page_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Page Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select page types where the views count will be displayed.', 'post-views-counter' ),
					'options'		=> apply_filters(
						'pvc_page_types_display_options',
						[
							'home'		=> __( 'Home', 'post-views-counter' ),
							'archive'	=> __( 'Archives', 'post-views-counter' ),
							'singular'	=> __( 'Single pages', 'post-views-counter' ),
							'search'	=> __( 'Search results', 'post-views-counter' ),
						]
					)
				],
				'restrict_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'User Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'callback'		=> [ $this, 'setting_restrict_display' ],
					'validate'		=> [ $this, 'validate_restrict_display' ]
				],
				'toolbar_statistics' => [
					'tab'			=> 'display',
					'title'			=> __( 'Toolbar Chart', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'boolean',
					'description'	=> __( 'The post views chart will be displayed for the post types that are being counted.', 'post-views-counter' ),
					'label'			=> __( 'Enable to display the post views chart at the toolbar.', 'post-views-counter' )
				],
				'menu_position' => [
					'tab'			=> 'other',
					'title'			=> __( 'Menu Position', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_settings',
					'type'			=> 'radio',
					'options'		=> [
						'top'	=> __( 'Top menu', 'post-views-counter' ),
						'sub'	=> __( 'Settings submenu', 'post-views-counter' )
					],
					'description'	=> __( "Choose where to display the plugin's menu.", 'post-views-counter' ),
				],
				'import_views' => [
					'tab'			=> 'other',
					'title'			=> __( 'Import Views', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_import_views' ]
				],
				'delete_views' => [
					'tab'			=> 'other',
					'title'			=> __( 'Delete Views', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_delete_views' ]
				],
				'deactivation_delete' => [
					'tab'			=> 'other',
					'title'			=> __( 'Deactivation', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_settings',
					'type'			=> 'boolean',
					'description'	=> __( 'If you deactivate the plugin with this option enabled all plugin data will be deleted along with the number of post views.', 'post-views-counter' ),
					'label'			=> __( 'Enable to delete all plugin data on deactivation.', 'post-views-counter' )
				]
			]
		];

		return $settings;
	}

	/**
	 * Add settings page.
	 *
	 * @param array $pages
	 * @return array
	 */
	public function settings_page( $pages ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// default page
		$pages['post-views-counter'] = [
			'menu_slug'		=> 'post-views-counter',
			'page_title'	=> __( 'Post Views Counter Settings', 'post-views-counter' ),
			'menu_title'	=> $pvc->options['other']['menu_position'] === 'sub' ? __( 'Post Views Counter', 'post-views-counter' ) : __( 'Post Views', 'post-views-counter' ),
			'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
			'callback'		=> null,
			'tabs'			=> [
				'general'	 => [
					'label'			=> __( 'Counting', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_general'
				],
				'display'	 => [
					'label'			=> __( 'Display', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_display'
				],
				'other'		=> [
					'label'			=> __( 'Other', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_other'
				]
			]
		];

		// submenu?
		if ( $pvc->options['other']['menu_position'] === 'sub' ) {
			$pages['post-views-counter']['type'] = 'settings_page';
		// topmenu?
		} else {
			// highlight submenus
			add_filter( 'submenu_file', [ $this, 'submenu_file' ], 10, 2 );

			// add parameters
			$pages['post-views-counter']['type'] = 'page';
			$pages['post-views-counter']['icon'] = 'dashicons-chart-bar';
			$pages['post-views-counter']['position'] = '99.301';

			// add subpages
			$pages['post-views-counter-general'] = [
				'menu_slug'		=> 'post-views-counter',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Counting', 'post-views-counter' ),
				'menu_title'	=> __( 'Counting', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-display'] = [
				'menu_slug'		=> 'post-views-counter&tab=display',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Display', 'post-views-counter' ),
				'menu_title'	=> __( 'Display', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-reports'] = [
				'menu_slug'		=> 'post-views-counter&tab=reports',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Reports', 'post-views-counter' ),
				'menu_title'	=> __( 'Reports', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-other'] = [
				'menu_slug'		=> 'post-views-counter&tab=other',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Other', 'post-views-counter' ),
				'menu_title'	=> __( 'Other', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];
		}

		return $pages;
	}

	/**
	 * Settings page CSS class(es).
	 *
	 * @param array $class
	 * @return array
	 */
	public function settings_page_class( $class ) {
		$is_pro = class_exists( 'Post_Views_Counter_Pro' );

		if ( ! $is_pro )
			$class[] = 'has-sidebar';

		return $class;
	}

	/**
	 * Highlight submenu items.
	 *
	 * @param string|null $submenu_file
	 * @param string $parent_file
	 * @return string|null
	 */
	public function submenu_file( $submenu_file, $parent_file ) {
		if ( $parent_file === 'post-views-counter' ) {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

			if ( $tab !== 'general' )
				return 'post-views-counter&tab=' . $tab;
		}

		return $submenu_file;
	}

	/**
	 * Validate options.
	 *
	 * @global object $wpdb
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate_settings( $input ) {
		// check capability
		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		global $wpdb;

		// get main instance
		$pvc = Post_Views_Counter();

		// use internal settings api to validate settings first
		$input = $pvc->settings_api->validate_settings( $input );

		// update meta key on save changes
		if ( isset( $_POST['post_views_counter_import_meta_key'] ) && isset( $_POST['save_post_views_counter_settings_other'] ) )
			$input['import_meta_key'] = sanitize_key( $_POST['post_views_counter_import_meta_key'] );

		// import post views data from another plugin
		if ( isset( $_POST['post_views_counter_import_wp_postviews'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['other'];

			// get views key
			$meta_key = sanitize_key( apply_filters( 'pvc_import_meta_key', ( isset( $_POST['post_views_counter_import_meta_key'] ) ? $_POST['post_views_counter_import_meta_key'] : $pvc->options['other']['import_meta_key'] ) ) );

			// set meta_key option
			$input['import_meta_key'] = $meta_key;

			// get views
			$views = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = %s", $meta_key ), ARRAY_A, 0 );

			// any views?
			if ( ! empty( $views ) ) {
				$sql = [];

				foreach ( $views as $view ) {
					$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", (int) $view['post_id'], (int) $view['meta_value'] );
				}

				$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE count = " . ( isset( $_POST['post_views_counter_import_wp_postviews_override'] ) ? '' : 'count + ' ) . "VALUES(count)" );

				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'Post views data imported successfully.', 'post-views-counter' ), 'updated' );
			} else
				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'There was no post views data to import.', 'post-views-counter' ), 'updated' );
		// delete all post views data
		} elseif ( isset( $_POST['post_views_counter_reset_views'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['other'];

			if ( $wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'post_views' ) )
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'All existing data deleted successfully.', 'post-views-counter' ), 'updated' );
			else
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'Error occurred. All existing data were not deleted.', 'post-views-counter' ), 'error' );
		// save general settings
		} elseif ( isset( $_POST['save_post_views_counter_settings_general'] ) ) {
			$input['update_version'] = $pvc->options['general']['update_version'];
			$input['update_notice'] = $pvc->options['general']['update_notice'];
			$input['update_delay_date'] = $pvc->options['general']['update_delay_date'];
		// reset general settings
		} elseif ( isset( $_POST['reset_post_views_counter_settings_general'] ) ) {
			$input['update_version'] = $pvc->options['general']['update_version'];
			$input['update_notice'] = $pvc->options['general']['update_notice'];
			$input['update_delay_date'] = $pvc->options['general']['update_delay_date'];
		}

		return $input;
	}

	/**
	 * Validate label.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_label( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		if ( ! isset( $input ) )
			$input = $pvc->defaults['display']['label'];

		// use internal settings API to validate settings first
		$input = $pvc->settings_api->validate_field( $input, 'input', $field );

		if ( function_exists( 'icl_register_string' ) )
			icl_register_string( 'Post Views Counter', 'Post Views Label', $input );

		return $input;
	}

	/**
	 * Restore post views label to default value.
	 *
	 * @param array $default
	 * @param array $field
	 * @return array
	 */
	public function reset_label( $default, $field ) {
		if ( function_exists( 'icl_register_string' ) )
			icl_register_string( 'Post Views Counter', 'Post Views Label', $default );

		return $default;
	}

	/**
	 * Setting: display style.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_display_style( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input type="hidden" name="post_views_counter_settings_display[display_style]" value="empty" />';

		foreach ( $field['options'] as $key => $label ) {
			$html .= '
			<label><input id="post_views_counter_display_display_style_' . esc_attr( $key ) . '" type="checkbox" name="post_views_counter_settings_display[display_style][]" value="' . esc_attr( $key ) . '" ' . checked( ! empty( $pvc->options['display']['display_style'][$key] ), true, false ) . ' />' . esc_html( $label ) . '</label> ';
		}

		return $html;
	}

	/**
	 * Validate display style.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_display_style( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$data = [];

		foreach ( $field['options'] as $value => $label ) {
			$data[$value] = false;
		}

		// any data?
		if ( ! empty( $input['display_style'] && $input['display_style'] !== 'empty' && is_array( $input['display_style'] ) ) ) {
			foreach ( $input['display_style'] as $value ) {
				if ( array_key_exists( $value, $field['options'] ) )
					$data[$value] = true;
			}
		}

		$input['display_style'] = $data;

		return $input;
	}

	/**
	 * Setting: count interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_time_between_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( $pvc->options['general']['time_between_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $field['options'] as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['time_between_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . sprintf( __( 'Enter the time between single user visit count. Enter %s if you want to count every page view.', 'post-views-counter' ), '<code>0</code>' ) . '</p>';

		return $html;
	}

	/**
	 * Validate count interval.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_time_between_counts( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// number
		$input['time_between_counts']['number'] = isset( $input['time_between_counts']['number'] ) ? (int) $input['time_between_counts']['number'] : $pvc->defaults['general']['time_between_counts']['number'];

		if ( $input['time_between_counts']['number'] < $field['min'] || $input['time_between_counts']['number'] > $field['max'] )
			$input['time_between_counts']['number'] = $pvc->defaults['general']['time_between_counts']['number'];

		// type
		$input['time_between_counts']['type'] = isset( $input['time_between_counts']['type'], $field['options'][$input['time_between_counts']['type']] ) ? $input['time_between_counts']['type'] : $pvc->defaults['general']['time_between_counts']['type'];

		return $input;
	}

	/**
	 * Setting: reset data interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_reset_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( $pvc->options['general']['reset_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( array_slice( $field['options'], 2, null, true ) as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['reset_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>';

		return $html;
	}

	/**
	 * Validate reset data interval.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_reset_counts( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// number
		$input['reset_counts']['number'] = isset( $input['reset_counts']['number'] ) ? (int) $input['reset_counts']['number'] : $pvc->defaults['general']['reset_counts']['number'];

		if ( $input['reset_counts']['number'] < $field['min'] || $input['reset_counts']['number'] > $field['max'] )
			$input['reset_counts']['number'] = $pvc->defaults['general']['reset_counts']['number'];

		// type
		$input['reset_counts']['type'] = isset( $input['reset_counts']['type'], $field['options'][$input['reset_counts']['type']] ) ? $input['reset_counts']['type'] : $pvc->defaults['general']['reset_counts']['type'];

		// run cron on next visit?
		$input['cron_run'] = ( $input['reset_counts']['number'] > 0 );

		// cron update?
		$input['cron_update'] = ( $input['cron_run'] && ( $pvc->options['general']['reset_counts']['number'] !== $input['reset_counts']['number'] || $pvc->options['general']['reset_counts']['type'] !== $input['reset_counts']['type'] ) );

		return $input;
	}

	/**
	 * Setting: object cache.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_object_cache( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// check object cache
		$wp_using_ext_object_cache = wp_using_ext_object_cache();

		$html = '
		<input size="4" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="" value="0" disabled /> <span>' . __( 'minutes', 'post-views-counter' ) . '</span>
		<p class="">' . __( 'Persistent Object Cache', 'post-views-counter' ) . ': <span class="' . ( $wp_using_ext_object_cache ? '' : 'un' ) . 'available">' . ( $wp_using_ext_object_cache ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span></p>
		<p class="description">' . sprintf( __( 'How often to flush cached view counts from the object cache into the database. This feature is used only if a persistent object cache like %s or %s is detected and the interval is greater than %s. When used, view counts will be collected and stored in the object cache instead of the database and will then be asynchronously flushed to the database according to the specified interval. The maximum value is %s which means 24 hours.%sNotice:%s Potential data loss may occur if the object cache is cleared/unavailable for the duration of the interval.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>', '<code>0</code>', '<code>1440</code>', '<br /><strong> ', '</strong>' ) . '</p>';

		return $html;
	}

	/**
	 * Setting: exclude visitors.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_exclude( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			$html .= '
			<label><input id="' . esc_attr( 'pvc_exclude-' . $type ) . '" type="checkbox" name="post_views_counter_settings_general[exclude][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['general']['exclude']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Use it exclude specific user groups from post views count.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles pvc_subfield"' . ( in_array( 'roles', $pvc->options['general']['exclude']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_general[exclude][roles][' . $role . ']" value="1" ' . checked( in_array( $role, $pvc->options['general']['exclude']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Use it to exclude specific user roles from post views count.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate exclude visitors.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_exclude( $input, $field ) {
		// any groups?
		if ( isset( $input['exclude']['groups'] ) ) {
			$groups = [];

			foreach ( $input['exclude']['groups'] as $group => $set ) {
				if ( isset( $field['options']['groups'][$group] ) )
					$groups[] = $group;
			}

			$input['exclude']['groups'] = array_unique( $groups );
		} else
			$input['exclude']['groups'] = [];

		// any roles?
		if ( in_array( 'roles', $input['exclude']['groups'], true ) && isset( $input['exclude']['roles'] ) ) {
			$roles = [];

			foreach ( $input['exclude']['roles'] as $role => $set ) {
				if ( isset( $field['options']['roles'][$role] ) )
					$roles[] = $role;
			}

			$input['exclude']['roles'] = array_unique( $roles );
		} else
			$input['exclude']['roles'] = [];

		return $input;
	}

	/**
	 * Setting: exclude IP addresses.
	 *
	 * @return string
	 */
	public function setting_exclude_ips() {
		// get ip addresses
		$ips = Post_Views_Counter()->options['general']['exclude_ips'];

		$html = '';

		// any ip addresses?
		if ( ! empty( $ips ) ) {
			foreach ( $ips as $key => $ip ) {
				$html .= '
			<div class="ip-box">
				<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="' . esc_attr( $ip ) . '" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '">' . esc_html__( 'Remove', 'post-views-counter' ) . '</a>
			</div>';
			}
		} else {
			$html .= '
			<div class="ip-box">
				<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '" style="display: none">' . esc_html__( 'Remove', 'post-views-counter' ) . '</a>
			</div>';
		}

		$html .= '
			<p><input type="button" class="button button-secondary add-exclude-ip" value="' . esc_attr__( 'Add new', 'post-views-counter' ) . '" /> <input type="button" class="button button-secondary add-current-ip" value="' . esc_attr__( 'Add my current IP', 'post-views-counter' ) . '" data-rel="' . esc_attr( $_SERVER['REMOTE_ADDR'] ) . '" /></p>
			<p class="description">' . esc_html__( 'Enter the IP addresses to be excluded from post views count.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Validate exclude IP addresses.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_exclude_ips( $input, $field ) {
		// any ip addresses?
		if ( isset( $input['exclude_ips'] ) ) {
			$ips = [];

			foreach ( $input['exclude_ips'] as $ip ) {
				if ( strpos( $ip, '*' ) !== false ) {
					$new_ip = str_replace( '*', '0', $ip );

					if ( filter_var( $new_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
						$ips[] = $ip;
				} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
					$ips[] = $ip;
			}

			$input['exclude_ips'] = array_unique( $ips );
		}

		return $input;
	}

	/**
	 * Setting: tools.
	 *
	 * @return string
	 */
	public function setting_import_views() {
		$html = '
		<div>
			<input type="text" class="regular-text" name="post_views_counter_import_meta_key" value="' . esc_attr( Post_Views_Counter()->options['other']['import_meta_key'] ) . '"/>
			<p class="description">' . esc_html__( 'Enter the meta key from which the views data is to be retrieved during import.', 'post-views-counter' ) . '</p>
		</div>
		<div class="pvc-subfield">
			<input type="submit" class="button button-secondary" name="post_views_counter_import_wp_postviews" value="' . esc_attr__( 'Import Views', 'post-views-counter' ) . '"/> <label><input id="pvc-wp-postviews" type="checkbox" name="post_views_counter_import_wp_postviews_override" value="1" />' . esc_html__( 'Override existing views data during import.', 'post-views-counter' ) . '</label>
			<p class="description">' . esc_html__( 'Click Import Views to start importing the views data.', 'post-views-counter' ) . '</p>
		</div>';

		return $html;
	}

	/**
	 * Setting: delete views.
	 *
	 * @return string
	 */
	public function setting_delete_views() {
		$html = '
		<input type="submit" class="button button-secondary" name="post_views_counter_reset_views" value="' . esc_attr__( 'Delete Views', 'post-views-counter' ) . '" />
		<p class="description">' . esc_html__( 'Delete ALL the existing post views data. Note that this is an irreversible process!', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Setting: user type.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_restrict_display( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			if ( $type === 'robots' )
				continue;

			$html .= '
			<label><input id="pvc_restrict_display-' . esc_attr( $type ) . '" type="checkbox" name="post_views_counter_settings_display[restrict_display][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['display']['restrict_display']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Use it to hide the post views counter from selected type of visitors.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles pvc-subfield"' . ( in_array( 'roles', $pvc->options['display']['restrict_display']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_display[restrict_display][roles][' . esc_attr( $role ) . ']" value="1" ' . checked( in_array( $role, $pvc->options['display']['restrict_display']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Use it to hide the post views counter from selected user roles.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate user type.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_restrict_display( $input, $field ) {
		// any groups?
		if ( isset( $input['restrict_display']['groups'] ) ) {
			$groups = [];

			foreach ( $input['restrict_display']['groups'] as $group => $set ) {
				if ( $group === 'robots' )
					continue;

				if ( isset( $field['options']['groups'][$group] ) )
					$groups[] = $group;
			}

			$input['restrict_display']['groups'] = array_unique( $groups );
		} else
			$input['restrict_display']['groups'] = [];

		// any roles?
		if ( in_array( 'roles', $input['restrict_display']['groups'], true ) && isset( $input['restrict_display']['roles'] ) ) {
			$roles = [];

			foreach ( $input['restrict_display']['roles'] as $role => $set ) {
				if ( isset( $field['options']['roles'][$role] ) )
					$roles[] = $role;
			}

			$input['restrict_display']['roles'] = array_unique( $roles );
		} else
			$input['restrict_display']['roles'] = [];

		return $input;
	}

	/**
	 * Validate license.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_license( $input, $field ) {
		// save value from database
		return $field['value'];
	}
}
