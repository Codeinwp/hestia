<?php
/**
 * Page settings metabox.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Individual_Page_Settings
 */
class Hestia_Page_Settings extends Hestia_Abstract_Metabox {

	/**
	 * Controls of metabox.
	 *
	 * @var array
	 */
	public $controls = array();

	/**
	 * Init function
	 */
	public function init() {
		parent::init();
		$this->register_controls();
	}

	/**
	 * Populate controls array by registering metabox controls.
	 */
	protected function register_controls() {

		$control_settings = array(
			'label'           => esc_html__( 'Sidebar', 'hestia' ),
			'choices'         => array(
				'full-width'    => array(
					'url'   => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAABqAQMAAABknzrDAAAABlBMVEX////V1dXUdjOkAAAAPUlEQVRIx2NgGAUkAcb////Y/+d/+P8AdcQoc8vhH/X/5P+j2kG+GA3CCgrwi43aMWrHqB2jdowEO4YpAACyKSE0IzIuBgAAAABJRU5ErkJggg==',
					'label' => esc_html__( 'Full Width', 'hestia' ),
				),
				'sidebar-left'  => array(
					'url'   => apply_filters( 'hestia_layout_control_image_left', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAABqAgMAAAAjP0ATAAAACVBMVEX///8+yP/V1dXG9YqxAAAAWElEQVR42mNgGAXDE4RCQMDAKONaBQINWqtWrWBatQDIaxg8ygYqQIAOYwC6bwHUmYNH2eBPSMhgBQXKRr0w6oVRL4x6YdQLo14Y9cKoF0a9QCO3jYLhBADvmFlNY69qsQAAAABJRU5ErkJggg==' ),
					'label' => esc_html__( 'Left Sidebar', 'hestia' ),
				),
				'sidebar-right' => array(
					'url'   => apply_filters( 'hestia_layout_control_image_right', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAABqAgMAAAAjP0ATAAAACVBMVEX///8+yP/V1dXG9YqxAAAAWUlEQVR42mNgGAUjB4iGgkEIzZStAoEVTECiQWsVkLdiECkboAABOmwBF9BtUGcOImUDEiCkJCQU0ECBslEvjHph1AujXhj1wqgXRr0w6oVRLwyEF0bBUAUAz/FTNXm+R/MAAAAASUVORK5CYII=' ),
					'label' => esc_html__( 'Right Sidebar', 'hestia' ),
				),
			),
			'active_callback' => array( $this, 'sidebar_meta_callback' ),
			'default'         => $this->get_default_value(),
		);

		array_push(
			$this->controls,
			$this->create_control(
				'radio-buttons',
				'hestia_layout_select',
				$control_settings
			)
		);

	}

	/**
	 * Get default value.
	 */
	private function get_default_value() {
		if ( empty( $_GET['post'] ) ) {
			return '';
		}

		$default           = hestia_get_blog_layout_default();
		$post_type         = get_post_type( $_GET['post'] );
		$page_for_posts_id = get_option( 'page_for_posts' );

		if ( (int) $_GET['post'] === (int) $page_for_posts_id ) {
			return get_theme_mod( 'hestia_blog_sidebar_layout', $default );
		}
		if ( 'page' === $post_type ) {
			return get_theme_mod( 'hestia_page_sidebar_layout', 'full-width' );
		}

		return get_theme_mod( 'hestia_blog_sidebar_layout', $default );
	}

	/**
	 * Function that decide if sidebar metabox should be shown.
	 *
	 * @return bool
	 */
	public function sidebar_meta_callback() {

		global $post;

		if ( empty( $post ) ) {
			return false;
		}

		$post_type = get_post_type( $post->ID );
		if ( 'jetpack-portfolio' === $post_type ) {
			return false;
		}

		/**
		 * Check if current page is a restricted page.
		 */
		$restricted_pages_id = array();
		array_push( $restricted_pages_id, get_option( 'woocommerce_myaccount_page_id' ) );
		array_push( $restricted_pages_id, get_option( 'woocommerce_checkout_page_id' ) );
		array_push( $restricted_pages_id, get_option( 'woocommerce_cart_page_id' ) );
		if ( $this->is_restricted_page( $post->ID, $restricted_pages_id ) ) {
			return false;
		}

		$blog_page = get_option( 'page_for_posts' );
		if ( (int) $post->ID === (int) $blog_page ) {
			return true;
		}

		/**
		 * Check if is a template that have sidebar.
		 */
		$allowed_templates = array(
			'default',
			'page-templates/template-page-sidebar.php',
		);

		return $this->is_allowed_template( $post->ID, $allowed_templates );
	}

	/**
	 * Register meta box to control layout on pages and posts.
	 *
	 * @since 1.1.58
	 */
	public function add() {

		$should_add_meta = apply_filters( 'hestia_display_page_settings', $this->should_add_meta() );

		if ( false === $should_add_meta ) {
			return false;
		}

		$current_theme = wp_get_theme();
		$metabox_label = $current_theme->get( 'Name' ) . ' ' . esc_html__( 'General Settings', 'hestia' );

		add_meta_box(
			'hestia-page-settings',
			$metabox_label,
			array( $this, 'html' ),
			array( 'post', 'page', 'jetpack-portfolio' ),
			'side',
			'low'
		);

		return true;
	}

	/**
	 * Save metabox data.
	 *
	 * @param string $post_id Post id.
	 *
	 * @since 1.1.58
	 */
	public function save( $post_id ) {
		foreach ( $this->controls as $control ) {
			$control->save( $post_id );
		}
	}

	/**
	 * The metabox content.
	 *
	 * @since 1.1.58
	 */
	public function html() {
		foreach ( $this->controls as $control ) {
			$control->render_content();
		}
	}

	/**
	 * Decide if the metabox should be visible.
	 * This settings apply for every control. If you need to exclude a particular control,
	 * add "except" parameter when declaring new control.
	 *
	 * @return bool
	 */
	public function should_add_meta() {

		global $post;

		if ( empty( $post ) ) {
			return false;
		}

		/**
		 * Check if current page is a restricted page.
		 */
		$restricted_pages_id = array();
		array_push( $restricted_pages_id, get_option( 'woocommerce_pay_page_id' ) );
		array_push( $restricted_pages_id, get_option( 'woocommerce_view_order_page_id' ) );
		array_push( $restricted_pages_id, get_option( 'woocommerce_terms_page_id' ) );
		if ( $this->is_restricted_page( $post->ID, $restricted_pages_id ) ) {
			return false;
		}

		/**
		 * Don't display metabox on frontpage.
		 */
		if ( $this->is_default_frontpage( $post->ID ) ) {
			return false;
		}

		/**
		 * Don't display meta if all controls are disabled.
		 */
		if ( $this->all_controls_are_disabled() ) {
			return false;
		}

		return true;
	}

	/**
	 * Detect if current frontpage is our frontpage with sections.
	 *
	 * @param string $post_id Post id.
	 *
	 * @return bool
	 */
	private function is_default_frontpage( $post_id ) {

		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return false;
		}

		$frontpage_id = get_option( 'page_on_front' );
		if ( empty( $frontpage_id ) ) {
			return false;
		}

		if ( (int) $post_id !== (int) $frontpage_id ) {
			return false;
		}

		$shop_id = get_option( 'woocommerce_shop_page_id' );
		if ( ! empty( $shop_id ) && $shop_id === $post_id ) {
			return false;
		}

		$page_template = get_post_meta( $frontpage_id, '_wp_page_template', true );
		if ( ! empty( $page_template ) && 'default' !== $page_template ) {
			return false;
		}

		$disabled_frontpage = get_theme_mod( 'disable_frontpage_sections', false );
		if ( true === (bool) $disabled_frontpage ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a page id is in restricted pages array
	 *
	 * @param string $post_id Post id.
	 * @param array  $restricted_pages_id Array of restricted pages.
	 *
	 * @return bool
	 */
	private function is_restricted_page( $post_id, $restricted_pages_id ) {
		return in_array( $post_id, $restricted_pages_id );
	}

	/**
	 * Check if all controls in mata are disabled.
	 */
	private function all_controls_are_disabled() {

		if ( empty( $this->controls ) ) {
			return true;
		}

		/**
		 * Check if even one control should display
		 */
		foreach ( $this->controls as $meta_control ) {
			if ( $meta_control->should_add_control() === true ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Detect if is a page with sidebar template
	 *
	 * @param string $post_id Post id.
	 * @param array  $templates Allowed templates.
	 *
	 * @return bool
	 */
	protected function is_allowed_template( $post_id, $templates ) {

		$page_template = get_post_meta( $post_id, '_wp_page_template', true );
		if ( empty( $page_template ) ) {
			return true;
		}

		return in_array( $page_template, $templates );
	}
}
