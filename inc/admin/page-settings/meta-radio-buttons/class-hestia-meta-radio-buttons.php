<?php
/**
 * Metabox radio button control.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Meta_Radio_Buttons
 */
class Hestia_Meta_Radio_Buttons {

	/**
	 * Control id.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Control settings.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Hestia_Meta_Radio_Buttons constructor.
	 *
	 * @param string $id Control id.
	 * @param array  $settings Control settings.
	 */
	public function __construct( $id, $settings ) {
		$this->id       = $id;
		$this->settings = $settings;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}


	/**
	 * Determine if a control should be visible or not.
	 *
	 * @return bool
	 */
	public function should_add_control() {

		if ( ! array_key_exists( 'active_callback', $this->settings ) ) {
			return true;
		}

		$object = $this->settings['active_callback'][0];
		$method = $this->settings['active_callback'][1];
		if ( method_exists( $object, $method ) ) {
			return $object->$method();
		}

		return true;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue() {
		wp_enqueue_script( 'hestia-meta-radio-buttons-script', get_template_directory_uri() . '/inc/admin/page-settings/meta-radio-buttons/script.js', array( 'jquery', 'jquery-ui-button' ), HESTIA_VERSION, true );
		wp_enqueue_style( 'hestia-meta-radio-buttons-style', get_template_directory_uri() . '/inc/admin/page-settings/meta-radio-buttons/style.css', array(), HESTIA_VERSION );
	}

	/**
	 * Render controls content
	 *
	 * @return void
	 */
	public function render_content() {

		if ( empty( $this->id ) ) {
			return;
		}
		if ( empty( $this->settings ) ) {
			return;
		}
		if ( ! array_key_exists( 'choices', $this->settings ) ) {
			return;
		}

		global $post;
		wp_nonce_field( 'hestia_individual_layout_nonce', 'individual_layout_nonce' );
		echo $this->get_control_content( $post->ID );
	}

	/**
	 * Render control label.
	 *
	 * @return string
	 */
	private function render_label() {
		$control_label = '';
		$label         = array_key_exists( 'label', $this->settings ) ? $this->settings['label'] : '';
		if ( ! empty( $label ) ) {
			$control_label .= '<p class="post-attributes-label-wrapper">';
			$control_label .= '<span class="post-attributes-label">' . esc_html( $label ) . '</span>';
			$control_label .= '</p>';
		}
		return $control_label;
	}

	/**
	 * Render default button.
	 *
	 * @return string
	 */
	private function render_default_button( $pid ) {
		$default_button = '';

		$class_to_add = 'button button-secondary reset-data';
		$value        = $this->get_control_value( $pid );
		if ( empty( $value ) || $value === 'default' ) {
			$class_to_add .= ' disabled';
		}

		$default_button .= '<div class="reset-data-wrapper">';
		$default_button .= '<div class="' . esc_attr( $class_to_add ) . '" data-default="' . ( array_key_exists( 'default', $this->settings ) ? esc_attr( $this->settings['default'] ) : '' ) . '" data-id="' . esc_attr( $this->id ) . '" data-pid="' . esc_attr( $pid ) . '">';
		$default_button .= '<span class="dashicons dashicons-image-rotate"></span>';
		$default_button .= '</div>';
		$default_button .= '</div>';
		return $default_button;
	}

	/**
	 * Render control content.
	 *
	 * @return string
	 */
	private function get_control_content( $pid ) {

		$should_render_control = $this->should_add_control();
		if ( $should_render_control === false ) {
			return '';
		}

		$control_content  = '';
		$choices          = $this->settings['choices'];
		$selected         = $this->get_control_value( $pid );
		$control_content .= '<div id="control-' . esc_attr( $this->id ) . '">';

		$control_content .= $this->render_label();

		$control_content .= '<div class="buttonset">';
		foreach ( $choices as $choice => $choice_setting ) {
			if ( empty( $choice_setting['url'] ) ) {
				continue;
			}

			$control_content .= '<input type="radio" name="' . esc_attr( $this->id ) . '" value="' . esc_attr( $choice ) . '" id="' . esc_attr( $this->id ) . '-' . esc_attr( $choice ) . '" ' . checked( $selected, $choice, false ) . '/>';
			$control_content .= '<label for="' . esc_attr( $this->id ) . '-' . esc_attr( $choice ) . '">';

			if ( ! empty( $choice_setting['label'] ) ) {
				$control_content .= '<span class="screen-reader-text">';
				$control_content .= esc_html( $choice_setting['label'] );
				$control_content .= '</span>';
			}
			$control_content .= '<img src="' . $choice_setting['url'] . '" alt="' . ( array_key_exists( 'label', $choice_setting ) ? esc_attr( $choice_setting['label'] ) : esc_attr( $choice ) ) . '" />';
			$control_content .= '</label>';
		}
		$control_content .= $this->render_default_button( $pid );
		$control_content .= '</div>';
		$control_content .= '</div>';

		return $control_content;
	}

	/**
	 * Get control value
	 *
	 * @param string $pid Post id.
	 *
	 * @return string
	 */
	private function get_control_value( $pid ) {
		$values = get_post_meta( $pid );
		return isset( $values[ $this->id ] ) ? esc_attr( $values[ $this->id ][0] ) : '';
	}

	/**
	 * Save metabox data.
	 *
	 * @param string $post_id Post id.
	 *
	 * @since 1.1.58
	 * @return void
	 */
	public function save( $post_id ) {
		// Bail if we're doing an auto save
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// if our nonce isn't there, or we can't verify it, bail
		if ( ! isset( $_POST['individual_layout_nonce'] ) || ! wp_verify_nonce( $_POST['individual_layout_nonce'], 'hestia_individual_layout_nonce' ) ) {
			return;
		}
		// if our current user can't edit this post, bail
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( isset( $_POST[ $this->id ] ) ) {

			$valid = array(
				'full-width',
				'sidebar-left',
				'sidebar-right',
				'default',
				'no-content',
				'classic-blog',
			);

			$value = wp_unslash( $_POST[ $this->id ] );
			update_post_meta( $post_id, $this->id, in_array( $value, $valid ) ? $value : '' );
			return;
		}
		delete_post_meta( $post_id, $this->id );

	}
}
