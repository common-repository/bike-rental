<?php
/**
 * Displays the products search filter widget
 *
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Search_Filter_Widget' ) )
	return;

class BWS_BKNG_Search_Filter_Widget extends WP_Widget {

	/**
	 * Constructor of class
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;
		$info   = $bws_bkng->get_plugin_info();
		$prefix = str_replace( 'by BestWebSoft', '', $info['Name'] );
		parent::__construct(
			strtolower( __CLASS__ ),
			$prefix . __( 'Search Filter', BWS_BKNG_TEXT_DOMAIN ),
			array( 'description' => '' )
		);
	}

	/**
	 * Function to displaying widget in front end
	 * @since  0.1
	 * @access public
	 * @param  array   $args       Sidebar settings
	 * @param  array   $instance   Wdget settings
	 * @return void
	 */
	public function widget( $args, $instance ) {
		global $bws_bkng;

		if ( 'products' != $bws_bkng->is_booking_page() || ! bws_bkng_get_search_fields() )
			return;

		$widget_title = ( ! empty( $instance['widget_title'] ) ) ? apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base ) : '';
		if ( ! empty( $widget_title ) ) {
			echo "{$args['before_widget']}{$args['before_title']}{$widget_title}{$args['after_title']}";
		}

		bws_bkng_get_template_part( 'forms/search-filter' );

		echo $args['after_widget'];

	}

	/**
	 * Function to displaying widget settings in back end
	 * @since  0.1
	 * @access public
	 * @param  array     $instance   Widget settings
	 * @return void
	 */
	public function form( $instance ) {
		$widget_title = isset( $instance['widget_title'] ) ? stripslashes( esc_html( $instance['widget_title'] ) ) : null; ?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>">
				<?php _e( 'Title', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</label>
		</p>

	<?php }

	/**
	 * Function to save widget settings
	 * @since  0.1
	 * @access public
	 * @param  array    $new_instance     New widget settings
	 * @param  array    $old_instance     Old widget settings
	 * @return array    $instance         Sanitized, merged and updated settings
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['widget_title'] = ( ! empty( $new_instance['widget_title'] ) ) ? strip_tags( $new_instance['widget_title'] ) : null;
		return $instance;
	}

}