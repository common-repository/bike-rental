<?php
/**
 * Displays the products widget
 *
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

function bkrntl_widget_init(){
	register_widget( 'BWS_BKNG_Bike_Widget' );
}

if ( class_exists( 'BWS_BKNG_Bike_Widget' ) ) {
	add_action( 'widgets_init', 'bkrntl_widget_init' );
	return;
}

class BWS_BKNG_Bike_Widget extends WP_Widget {
	
	private $widget_product_count_display;
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
			$prefix . __( 'Bikes', BWS_BKNG_TEXT_DOMAIN ),
			array( 'description' => '' )
		);
		$this->widget_product_count_display = 4;
	}

	/**
	 * Function to displaying widget in front end
	 * @since  0.1
	 * @access public
	 * @param  array   $args       Sidebar settings
	 * @param  array   $instance   Widget settings
	 * @return void
	 */
	public function widget( $args, $instance ) {
		global $wp_query;

		$widget_title = ( ! empty( $instance['widget_title'] ) ) ? apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base ) : '';
		$widget_product_button_text = ( ! empty( $instance['widget_product_button_text'] ) ) ? apply_filters( 'widget_product_button_text', $instance['widget_product_button_text'], $instance, $this->id_base ) : '';
		$widget_product_button_link = ( ! empty( $instance['widget_product_button_link'] ) ) ? apply_filters( 'widget_product_button_link', $instance['widget_product_button_link'], $instance, $this->id_base ) : '';
		$widget_product_count_display = ( ! empty( $instance['widget_product_count_display'] ) ) ? apply_filters( 'widget_product_count_display', $instance['widget_product_count_display'], $instance, $this->id_base ) : '4';
		$this->widget_product_count_display = $widget_product_count_display;
		$title_wrapper = '<div class="d-flex justify-content-between align-items-center">';
		$title_wrapper_end = '</div>';

		if ( ! empty( $widget_title ) ) {
			echo $args['before_widget'] . $title_wrapper . $args['before_title'] . $widget_title . $args['after_title'];
		} else {
			echo $args['before_widget'];
		}

		if ( ! empty( $widget_product_button_text ) ) {
			echo '<a class="button bkng-widget-button" href="' . esc_url( get_permalink( $widget_product_button_link ) ) . '">' . esc_html( $widget_product_button_text ) . '</a>';
		}		
		
		if ( ! empty( $widget_title ) ) {
			echo $title_wrapper_end;
		}

		$old_query = $wp_query;

		add_filter( 'bws_bkng_products_list_class', array( $this, 'products_row_class' ), 10 );
		add_filter( 'bws_bkng_product_class', array( $this, 'products_col_class' ), 10 );
		add_action( 'pre_get_posts', array( $this, 'products_per_widget' ) );

		get_template_part( 'bws-templates/products' );

		remove_action( 'pre_get_posts', array( $this, 'products_per_widget' ) );

		echo $args['after_widget'];

		$wp_query = $old_query;
	}

	/**
	 * Function to displaying widget settings in back end
	 * @since  0.1
	 * @access public
	 * @param  array     $instance   Widget settings
	 * @return void
	 */
	public function form( $instance ) {
		global $bkrntl_options;
		if( empty( $bkrntl_options ) ){
			$bkrntl_options = get_option( 'bkrntl_options' );
		}
		$widget_title = isset( $instance['widget_title'] ) ? stripslashes( esc_html( $instance['widget_title'] ) ) : null;
		$widget_product_button_text = isset( $instance['widget_product_button_text'] ) ? stripslashes( esc_html( $instance['widget_product_button_text'] ) ) : __( 'More Bikes', BWS_BKNG_TEXT_DOMAIN );
		$widget_product_button_link = isset( $instance['widget_product_button_link'] ) ? stripslashes( esc_html( $instance['widget_product_button_link'] ) ) : ( isset( $bkrntl_options['products_page'] ) ? $bkrntl_options['products_page'] : 0 );
		$widget_product_count_display = isset( $instance['widget_product_count_display'] ) ? stripslashes( esc_html( $instance['widget_product_count_display'] ) ) : 4; ?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>">
				<?php _e( 'Title', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_product_button_text' ) ); ?>">
				<?php _e( 'Product Button Text', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_product_button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_product_button_text' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_product_button_text ); ?>"/>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_product_button_link' ) ); ?>">
				<?php _e( 'Product Button Link', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<?php $args = array(
					'depth'                 => 0,
					'selected'              => $widget_product_button_link,
					'echo'                  => 1,
					'name'                  => $this->get_field_name( 'widget_product_button_link' ),
					'class'                 => 'widefat'
				);
				wp_dropdown_pages( $args ) ?>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_product_count_display' ) ); ?>">
				<?php _e( 'Product Count Display', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_product_count_display' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_product_count_display' ) ); ?>" >
					<option <?php selected( $widget_product_count_display, 2 ); ?>>2</option>
					<option <?php selected( $widget_product_count_display, 3 ); ?>>3</option>
					<option <?php selected( $widget_product_count_display, 4 ); ?>>4</option>
					<option <?php selected( $widget_product_count_display, 6 ); ?>>6</option>
				</select>
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
		$instance['widget_product_button_text'] = isset( $new_instance['widget_product_button_text'] ) ? stripslashes( esc_html( $new_instance['widget_product_button_text'] ) ) : null;
		$instance['widget_product_button_link'] = isset( $new_instance['widget_product_button_link'] ) ? stripslashes( esc_html( $new_instance['widget_product_button_link'] ) ) : null;
		$instance['widget_product_count_display'] = isset( $new_instance['widget_product_count_display'] ) ? stripslashes( esc_html( $new_instance['widget_product_count_display'] ) ) : 4;

		return $instance;
	}

	public function products_row_class( $classes ){
		$classes[] = 'row';
		return $classes;
	}

	public function products_col_class( $classes ){
		switch( $this->widget_product_count_display ){
			case 2:
				$classes[] = 'col col-12 col-sm-6';
				break;
			case 3:
				$classes[] = 'col col-12 col-sm-6 col-lg-4';
				break;
			case 4:
				$classes[] = 'col col-12 col-sm-6 col-lg-3';
				break;
			case 6:
				$classes[] = 'col col-12 col-sm-6 col-lg-2';
				break;
		}		
		return $classes;
	}

	public function products_per_widget( $query ){
		$query->set( 'posts_per_page', $this->widget_product_count_display  );
	}
}

