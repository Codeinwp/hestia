<?php
/**
 * Class that manages WooCommerce appearance.
 *
 * @package Inc/Modules/Woo_Enhancements
 */

/**
 * Class Hestia_Woocommerce_Module
 */
class Hestia_Woocommerce_Manager extends Hestia_Abstract_Module {

	/**
	 * Check if this module should load.
	 *
	 * @return bool|void
	 */
	function should_load() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Run module.
	 */
	function run_module() {
		add_action( 'wp', array( $this, 'run' ) );
		add_action( 'elementor/widget/before_render_content', array( $this, 'fix_related_products' ) );
	}


	/**
	 * Fix related products in elementor preview.
	 *
	 * @return bool
	 */
	public function fix_related_products() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return false;
		}
		if ( ! is_product() ) {
			return false;
		}
		$this->manage_product_listing_layout();

		return true;
	}

	/**
	 * Manage shop elements
	 */
	public function run() {
		$this->manage_before_shop_loop_elements();
		$this->manage_sale_tag();
		$this->manage_product_listing_layout();
		$this->manage_related_products();
		$this->load_fa_on_account();
	}

	/**
	 * Load font awesome on account page.
	 *
	 * @return bool
	 */
	private function load_fa_on_account() {
		if ( ! $this->should_load() ) {
			return false;
		}
		if ( ! is_account_page() ) {
			return false;
		}
		hestia_load_fa();
		return true;
	}

	/**
	 * Manage related products.
	 */
	private function manage_related_products() {
		$hooks = array(
			'add'    => array(
				array( 'woocommerce_after_single_product', 'woocommerce_output_related_products', 20 ),
			),
			'remove' => array(
				array( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 ),
			),
		);

		$this->process_hooks( $hooks );

		add_action(
			'elementor/theme/before_do_single',
			function() use ( $hooks ) {
				$this->process_hooks( $hooks, true );
			}
		);
	}

	/**
	 * Manage product listing.
	 */
	private function manage_product_listing_layout() {
		$hooks = array(
			'add'    => array(
				array( 'woocommerce_before_shop_loop_item_title', 'hestia_woocommerce_template_loop_product_thumbnail', 10 ),
				array( 'woocommerce_before_shop_loop_item', 'hestia_woocommerce_before_shop_loop_item', 10 ),
				array( 'woocommerce_after_shop_loop_item', 'hestia_woocommerce_after_shop_loop_item', 20 ),
				array( 'woocommerce_shop_loop_item_title', 'hestia_woocommerce_template_loop_product_title', 10 ),
			),
			'remove' => array(
				array( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 ),
				array( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 ),
				array( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 ),
				array( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 ),
				array( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 ),
				array( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 ),
				array( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 ),
			),
		);

		$this->process_hooks( $hooks );
	}

	/**
	 * Process hooks.
	 *
	 * @param array $hooks Hooks.
	 * @param bool  $inverse Inverse flag.
	 */
	private function process_hooks( $hooks, $inverse = false ) {
		$hooks_to_add    = $inverse === true ? $hooks['remove'] : $hooks['add'];
		$hooks_to_remove = $inverse === true ? $hooks['add'] : $hooks['remove'];

		foreach ( $hooks_to_add as $hook ) {
			add_action( $hook[0], $hook[1], $hook[2] );
		}

		foreach ( $hooks_to_remove as $hook ) {
			remove_action( $hook[0], $hook[1], $hook[2] );
		}
	}

	/**
	 * Manage elements that are displayed before shop content.
	 */
	private function manage_before_shop_loop_elements() {

		/**
		 * Remove breadcrumbs, result count, catalog ordering and taxonomy archive description to reposition them.
		 */
		add_action( 'woocommerce_before_main_content', array( $this, 'hestia_woocommerce_remove_shop_elements' ) );
		add_action( 'hestia_woocommerce_custom_reposition_left_shop_elements', array( $this, 'hestia_woocommerce_reposition_left_shop_elements' ) );
		add_action( 'hestia_woocommerce_custom_reposition_right_shop_elements', array( $this, 'hestia_woocommerce_reposition_right_shop_elements' ) );
	}


	/**
	 * Reposition breadcrumb, sorting and results count - removing
	 */
	public function hestia_woocommerce_remove_shop_elements() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	}

	/**
	 * Reposition breadcrumb and results count - adding
	 */
	public function hestia_woocommerce_reposition_left_shop_elements() {
		woocommerce_breadcrumb();
		woocommerce_result_count();
	}

	/**
	 * Reposition ordering - adding
	 */
	public function hestia_woocommerce_reposition_right_shop_elements() {
		woocommerce_catalog_ordering();
	}

	/**
	 * Render the sidebar trigger button.
	 */
	private function render_sidebar_trigger() {
		if ( ! is_active_sidebar( 'sidebar-woocommerce' ) ) {
			return false;
		}
		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return false;
		}

		$sidebar_layout = hestia_get_shop_sidebar_layout();
		if ( $sidebar_layout === 'full-width' ) {
			return false;
		}
		echo '<div class="hestia-sidebar-toggle-container">';
		echo '<span class="hestia-sidebar-open btn btn-border"><i class="fas fa-filter" aria-hidden="true"></i></span>';
		echo '</div>';

		return true;
	}

	/**
	 * Manage WooCommerce sale tag on products.
	 */
	private function manage_sale_tag() {
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 20 );

		if ( is_product() ) {
			remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'hestia_wrap_product_image' ), 18 );
			add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 21 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'hestia_close_wrap' ), 22 );
		}
	}

	/**
	 * Wrap product image in a div.
	 */
	public function hestia_wrap_product_image() {
		echo '<div class="hestia-product-image-wrap">';
	}

	/**
	 * Close product image wrap.
	 */
	public function hestia_close_wrap() {
		echo '</div>';
	}
}
