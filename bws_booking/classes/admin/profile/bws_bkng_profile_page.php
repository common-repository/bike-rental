<?php
/**
 * Contains the functionality that handles the displaying of the user profile page
 * @since	0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Profile_Page' ) )
	return;

class BWS_BKNG_Profile_Page {

    /**
	 * The list of page tabs and its settings
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $tabs = array();

	/**
	 * Contains the slug of the current tab
	 * @since  0.1
	 * @access private
	 * @var string
	 */
	private $current_tab = '';

    /**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() { 
		$this->tabs = array(
			'settings' => array(
				'label' => __( 'Settings', BWS_BKNG_TEXT_DOMAIN )
			),
			'history' => array(
				'label' => __( 'History', BWS_BKNG_TEXT_DOMAIN )
			),
			'wishlist' => array(
				'label' => __( 'Wishlist', BWS_BKNG_TEXT_DOMAIN )
			),
		);

		$this->current_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ) ) ? sanitize_text_field( stripslashes( $_GET['tab'] ) ) : 'settings';
	}

	/**
	 * Displays plugin profile page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display() {
		global $bws_bkng, $title;
		$class = 'BWS_BKNG_Profile_' . ucfirst( $this->current_tab );
		$page  = new $class();
		$count_tabs = $page->get_tabs() ? count( $page->get_tabs() ) : 0;
		?>
		<div class="wrap bkng_profile_page<?php echo 1 == $count_tabs ? ' bkng_single_tab_page' : ''; ?>">
			<h1><?php echo esc_html( $title ); ?></h1>

			<h2 class="nav-tab-wrapper"><?php $this->display_tabs(); ?></h2>
			<?php
			$page->display_content();
			?>
		</div><!-- .wrap .bkng_user_profile_page -->
		<?php
	}

	/**
	 * Displays the plugin horizontal tabs
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function display_tabs() {
		global $bws_bkng, $plugin_page;
		$link = '<a class="nav-tab %1$s" href="%4$s&page=%5$s&amp;tab=%2$s">%3$s</a>';
		$tabs = '';
		foreach( $this->tabs as $slug => $data ) {
			$class = $this->current_tab == $slug ? 'nav-tab-active' : '';
			$tabs .= sprintf( $link, $class, $slug, $data['label'], get_admin_page_parent(), $plugin_page );
		}
		echo $tabs;
	}
}