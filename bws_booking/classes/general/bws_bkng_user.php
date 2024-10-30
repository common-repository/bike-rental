<?php
/**
 * Provides information about user
 * @version  0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_User' ) )
	return;

class BWS_BKNG_User {
    /**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
    private static $instance;

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
    private $user_settings;

    /**
	 * Contains the id of current user
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	public $user_id;

	/**
	 * Contains the id of current user
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	public $gallery_id;

    /**
	 * Contains the meta key for wishlist
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $wishlist_key = 'bws_bkng_wishlist';

    /**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
            self::$instance = new self();
        }

		return self::$instance;
	}

    /**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng, $wpdb;

        $this->user_id = $this->get_user_id();
		$this->gallery_id = $this->get_gallery_id();

		for ( $m = 1; $m <= 12; ++$m ) {
			$months[] = date( 'F', mktime( 0, 0, 0, $m, 1 ) );
		}

		$payment_country = array(
			'Albania',
			'Algeria',
			'Andorra',
			'Angola',
			'Anguilla',
			'Antigua & Barbuda',
			'Argentina',
			'Armenia',
			'Aruba',
			'Australia',
			'Austria',
			'Azerbaijan',
			'Bahamas',
			'Bahrain',
			'Barbados',
			'Belarus',
			'Belgium',
			'Belize',
			'Benin',
			'Bermuda',
			'Bhutan',
			'Bolivia',
			'Bosnia & Herzegovina',
			'Botswana',
			'Brazil',
			'British Virgin Islands',
			'Brunei',
			'Bulgaria',
			'Burkina Faso',
			'Burundi',
			'Cambodia',
			'Cameroon',
			'Canada',
			'Cape Verde',
			'Cayman Islands',
			'Chad',
			'Chile',
			'China',
			'Colombia',
			'Comoros',
			'Congo - Brazzaville',
			'Congo - Kinshasa',
			'Cook Islands',
			'Costa Rica',
			'Côte D’ivoire',
			'Croatia',
			'Cyprus',
			'Czech Republic',
			'Denmark',
			'Djibouti',
			'Dominica',
			'Dominican Republic',
			'Ecuador',
			'Egypt',
			'El Salvador',
			'Eritrea',
			'Estonia',
			'Falkland Islands',
			'Faroe Islands',
			'Fiji',
			'Finland',
			'France',
			'French Guiana',
			'French Polynesia',
			'Gabon',
			'Gambia',
			'Georgia',
			'Germany',
			'Gibraltar',
			'Greece',
			'Greenland',
			'Grenada',
			'Guadeloupe',
			'Guatemala',
			'Guinea',
			'Guinea-Bissau',
			'Guyana',
			'Honduras',
			'Hong Kong Sar China',
			'Hungary',
			'Iceland',
			'India',
			'Indonesia',
			'Ireland',
			'Israel',
			'Italy',
			'Jamaica',
			'Japan',
			'Jordan',
			'Kazakhstan',
			'Kenya',
			'Kiribati',
			'Kuwait',
			'Kyrgyzstan',
			'Laos',
			'Latvia',
			'Lesotho',
			'Liechtenstein',
			'Lithuania',
			'Luxembourg',
			'Macedonia',
			'Madagascar',
			'Malawi',
			'Malaysia',
			'Maldives',
			'Mali',
			'Malta',
			'Marshall Islands',
			'Martinique',
			'Mauritania',
			'Mauritius',
			'Mayotte',
			'Mexico',
			'Micronesia',
			'Moldova',
			'Monaco',
			'Mongolia',
			'Montenegro',
			'Montserrat',
			'Morocco',
			'Mozambique',
			'Namibia',
			'Nauru',
			'Nepal',
			'Netherlands',
			'New Caledonia',
			'New Zealand',
			'Nicaragua',
			'Niger',
			'Niue',
			'Norfolk Island',
			'Norway',
			'Oman',
			'Palau',
			'Panama',
			'Papua New Guinea',
			'Paraguay',
			'Peru',
			'Philippines',
			'Pitcairn Islands',
			'Poland',
			'Portugal',
			'Qatar',
			'Réunion',
			'Romania',
			'Russia',
			'Rwanda',
			'Samoa',
			'San Marino',
			'São tomé & príncipe',
			'Saudi Arabia',
			'Senegal',
			'Serbia',
			'Seychelles',
			'Sierra Leone',
			'Singapore',
			'Slovakia',
			'Slovenia',
			'Solomon Islands',
			'Somalia',
			'South Africa',
			'South Korea',
			'Spain',
			'Sri Lanka',
			'St. Helena',
			'St. Kitts & Nevis',
			'St. Lucia',
			'St. Pierre & Miquelon',
			'St. Vincent & Grenadines',
			'Suriname',
			'Svalbard & Jan Mayen',
			'Swaziland',
			'Sweden',
			'Switzerland',
			'Taiwan',
			'Tajikistan',
			'Tanzania',
			'Thailand',
			'Togo',
			'Tonga',
			'Trinidad & Tobago',
			'Tunisia',
			'Turkmenistan',
			'Turks & Caicos Islands',
			'Tuvalu',
			'Uganda',
			'Ukraine',
			'United Arab Emirates',
			'United Kingdom',
			'United States',
			'Uruguay',
			'Vanuatu',
			'Vatican City',
			'Venezuela',
			'Vietnam',
			'Wallis & Futuna',
			'Yemen',
			'Zambia',
			'Zimbabwe'
		);


		$locations = $wpdb->get_results( "SELECT `location_id`, `location_name` FROM `" . BWS_BKNG_DB_PREFIX . "locations`", ARRAY_A );
		$locations = array_column( $locations, 'location_name', 'location_id' );

		$this->user_settings = array(
			'gallery' => array(
				array(
					'id' 			=> 'bws_bkng_gallery_cols',
					'label' 		=> __( 'Columns', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> array(
						3 => '3 columns',
						4 => '4 columns',
						6 => '6 columns',
					),
					'helper'		=> __( 'The amount of columns that will be displayed in gallery on Your profile page', BWS_BKNG_TEXT_DOMAIN )
				),
			),
			'wp_settings' => array(
				array(
					'id' 			=> 'first_name',
					'label' 		=> __( 'First Name', BWS_BKNG_TEXT_DOMAIN ),
				),
				array(
					'id' 			=> 'last_name',
					'label' 		=> __( 'Last Name', BWS_BKNG_TEXT_DOMAIN ),
				),
				array(
					'id' 			=> 'nickname',
					'label' 		=> __( 'Nickname', BWS_BKNG_TEXT_DOMAIN ),
					'required' 		=> true,
				),
				array(
					'id' 			=> 'user_email',
					'type' 			=> 'email',
					'label' 		=> __( 'Email Address', BWS_BKNG_TEXT_DOMAIN ),
					'required' 		=> true,
				),
				array(
					'id' 			=> 'description',
					'label' 		=> __( 'About Me', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'textarea',
				),
			),
			'password_form' => array(
				array(
					'id' 			=> 'curr_pass',
					'label' 		=> __( 'Current Password', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'password',
					'name_group' 	=> 'bkng_user_password',
				),
				array(
					'id' 			=> 'new_pass',
					'label' 		=> __( 'New Password', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'password',
					'name_group' 	=> 'bkng_user_password',
				),
				array(
					'id' 			=> 'new_pass_conf',
					'label' 		=> __( 'Confirm New Password', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'password',
					'name_group' 	=> 'bkng_user_password',
				),
			),
			'nicname_form' => array(
				array(
					'id' 			=> 'nickname',
					'label' 		=> __( 'Nickname', BWS_BKNG_TEXT_DOMAIN ),
					'required' 		=> true,
				),
			),
			'bkng_birthday' => array(
				array(
					'id' 			=> 'bws_bkng_dob_month',
					'label' 		=> __( 'Month', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> $months
				),
				array(
					'id' 			=> 'bws_bkng_dob_day',
					'label' 		=> __( 'Day', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> range( 1, 31 )
				),
				array(
					'id' 			=> 'bws_bkng_dob_year',
					'label' 		=> __( 'Year', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> range( date( 'Y' ), 1900, -1 )
				),
				array(
					'id' 			=> 'bws_bkng_dob_consent',
					'label' 		=> __( 'Visibility', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'checkbox',
					'helper' 		=> __( 'Do not show the date of birth to other users.', BWS_BKNG_TEXT_DOMAIN ),
					'default'		=> '0',
				),
			),
			'billing_data' => array(
				array(
					'id' 			=> 'bws_bkng_location',
					'label' 		=> __( 'Location', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> $locations
				),
				array(
					'id' 			=> 'bws_bkng_postal',
					'label' 		=> __( 'Postal Code', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'number',
				),
				array(
					'id' 			=> 'bws_bkng_address_1',
					'label' 		=> __( 'Billing Address', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'text',
				),
				array(
					'id' 			=> 'bws_bkng_address_2',
					'label' 		=> __( 'Billing Address 2', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'text',
				),
			),
			'payment_info' => array(
				array(
					'id' 			=> 'bws_bkng_numb_card',
					'label' 		=> __( 'Card Number', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'number',
					'helper'		=> __( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore', BWS_BKNG_TEXT_DOMAIN ),
					'payment_image'			=> array(
						'id' 			=> array( 'bws_bkng_visa', 'bws_bkng_master', 'bws_bkng_express' ),
						'type' 		=> 'payment_image',
						'url'		  => array( 'images/visa.png', 'images/master.png', 'images/express.png' )
					)
				),
				array(
					'id' 				=> 'bws_bkng_country',
					'label' 		=> __( 'Country', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options'		=> $payment_country,
				),
				array(
					'id' 			=> 'bws_bkng_month',
					'label' 		=> __( 'Month', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> $months,
				),
				array(
					'id' 			=> 'bws_bkng_year',
					'label' 		=> __( 'Year', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'select',
					'options' 		=> range( date( 'Y' ), 1900, -1 ),
				),
				array(
					'id' 			=> 'bws_bkng_ccv',
					'label' 		=> __( 'CCV', BWS_BKNG_TEXT_DOMAIN ),
					'type' 			=> 'number',
				),
			),
		);
    }

	public function get_user_settings_fields( $what = '', $just_fields = false ) {
		if ( ! $what ) {
			return $just_fields ? call_user_func_array( 'array_merge', $this->user_settings ) : $this->user_settings;
		}

		if ( isset( $this->user_settings[ $what ] ) ) {
			return $this->user_settings[ $what ];
		} else {
			$fields = call_user_func_array( 'array_merge', $this->user_settings );
			$key = array_search( $what, array_column( $fields, 'id' ) );

			return isset( $fields[ $key ] ) ? $fields[ $key ] : false;
		}

		return false;
	}

	public function get_user_settings( $what ) {
		$option = $this->get_user_settings_fields( $what, true );
		$value = get_user_option( $option['id'], $this->user_id );

		return isset( $option['options'] ) ? $option['options'][ $value ] : $value;
	}

    public function enqueue( $what = array() ) {
		if ( empty( $what ) ) {
			return;
		}

		if ( ! is_array( $what ) ) {
			$what = array( $what );
		}

		if ( in_array( 'avatar', $what ) ) {
			wp_enqueue_media();

			wp_enqueue_script( 'customize-base', site_url( 'wp-includes/js/customize-base.js' ), array( 'jquery', 'json2', 'underscore' ) );
			wp_enqueue_script( 'customize-model', site_url( 'wp-includes/js/customize-models.js' ), array( 'underscore', 'backbone' ) );

			wp_enqueue_script( 'bkng-user-avatar', BWS_BKNG_URL . 'js/avatar_handle.js', array( 'jquery', 'imgareaselect', 'customize-base', 'customize-model' ) );

			wp_localize_script( 'bkng-user-avatar', 'bws_bkng', array(
				'default_url' => $this->get_avatar_default_ur(),
			) );
		}
	}

    public function get_avatar_id() {
        return get_user_meta( $this->user_id, 'bkng_user_avatar', true );
	}

	public function get_avatar_default_ur() {
        return get_avatar_url( $this->user_id, array( 'bkng' => true, 'size' => 512 ) );
	}

    public function get_avatar_url() {
        $attachment_id = $this->get_avatar_id();
		$default_url = get_avatar_url( $this->user_id, array( 'bkng' => true, 'size' => 512 ) );

		if ( ! $attachment_id ) {
			return $default_url;
		}

		$avatar_url = wp_get_attachment_url( $attachment_id );

		if ( empty( $avatar_url ) ) {
			return $default_url;
		}

		return esc_url( $avatar_url );
	}

	public function get_avatar_upload_btn( $echo = true ) {
		global $bws_bkng;

		$content = '<button id="bws_bkng_avatar_upload_btn"';
		$content .= 'class="button button-primary ' . $bws_bkng::$hide_if_no_js . '"';
		$content .= 'data-title="' . __( 'Select image', BWS_BKNG_TEXT_DOMAIN ) . '"';
		$content .= 'data-button-title="' . __( 'Select and Crop', BWS_BKNG_TEXT_DOMAIN ) . '">';
		$content .= __( 'Set Image', BWS_BKNG_TEXT_DOMAIN );
        $content .= '</button>';
		$content .= '<input type="hidden" class="bws-bkng-att-id" name="bkng_user_avatar" />';

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

	public function get_avatar_delete_btn( $echo = true ) {
		global $bws_bkng;

		$content = '<button id="bws_bkng_avatar_delete_btn"';
		$content .= 'class="button button-secondary ' . $bws_bkng::$hide_if_no_js . '">';
		$content .= __( 'Remove Image', BWS_BKNG_TEXT_DOMAIN );
        $content .= '</button>';

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

    public function get_avatar_img_field( $echo = true ) {
		$url = $this->get_avatar_url();

		$content = '<div class="bkng_avatar">';
        $content .= '<img class="bws-bkng-gravatar-img" src="' . $url . '" />';
		$content .= '</div>';

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

    public function change_user_password( $data ) {
		$defaults = array(
			'curr_pass' 	=> false,
			'new_pass'		=> false,
			'new_pass_conf' => false
		);
		$data = wp_parse_args( $data, $defaults );

		if ( ! ( $data['curr_pass'] || $data['new_pass'] || $data['new_pass_conf'] ) ) {
			return 'empty_pass';
		}

		if ( strlen( $data['new_pass'] ) < 3 ) {
			return 'short_new_pass';
		}

		$hash = get_user_option( 'user_pass', $this->user_id );
		if ( wp_check_password( $data['curr_pass'], $hash, $this->user_id ) ) {
			if ( $data['new_pass'] === $data['new_pass_conf'] ) {
				wp_set_password( $data['new_pass'], $this->user_id );
			} else {
				return 'wrong_pass_conf';
			}
		} else {
			return 'wrong_pass';
		}

		return 'pass_changed';
	}

    public function get_user_wishlist( $single = true ) {
        return get_user_meta( $this->user_id, $this->wishlist_key, $single );
    }

    private function update_user_wishlist( $data ) {
        return update_user_meta( $this->user_id, $this->wishlist_key, $data );
	}

    public function is_in_wishlist( $id ) {
        $id = absint( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$wishlist = $this->get_user_wishlist();

		return is_array( $wishlist ) ? in_array( $id, $wishlist ) : $wishlist == $id;
    }

	public function add_to_user_wishlist( $id ) {
		$id = absint( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$wishlist = $this->get_user_wishlist();

		if ( is_array( $wishlist ) ) {
			if ( ! in_array( $id, $wishlist ) ) {
				array_push( $wishlist, $id );
			} else {
				return false;
			}
		} else {
			$wishlist = array( $id );
		}

		$this->update_user_wishlist( $wishlist );
	}

	public function remove_from_user_wishlist( $ids ) {
		$wishlist = $this->get_user_wishlist();

		if ( empty( $wishlist ) ) {
			return false;
		}

		$wishlist = array_diff( $wishlist, (array)$ids );

		$this->update_user_wishlist( $wishlist );
    }

	public function get_user_orders( $post_type = '' ) {
		global $wpdb;

		if ( '' !== $post_type ) {
			$post_type .= '_';
		}

		$order_table = BWS_BKNG_DB_PREFIX . $post_type . 'orders';

		$results = $wpdb->get_col(
			"SELECT `id`
			FROM `" . $order_table . "`
			WHERE `user_id` = '" . $this->user_id . "'"
		);

		return $results;
	}

	public function get_user_ordered_products( $post_type = '' ) {
		global $wpdb;

		$orders = $this->get_user_orders( $post_type );

		if ( empty( $orders ) ) {
			return;
		}
		/* sorting kinda by date */
		rsort( $orders );

		$order_class = BWS_BKNG_Order::get_instance();

		foreach ( $orders as $order_id ) {
			$results[] = $order_class->get( $order_id );
		}

		return $results;
    }

    /**
	 * Fetch user gallery id
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return int
	 */
	private function get_gallery_id() {
		global $wpdb;

        $post_id = $wpdb->get_var(
			"SELECT `ID`
			FROM `" . $wpdb->posts . "`
			WHERE `post_type` = 'bws-bkng-gallery'
				AND `post_author` = '" . $this->user_id . "'"
		);

		if ( empty( $post_id ) ) {
			$args = array(
				'post_type' => 'bws-bkng-gallery'
			);

			$post_id = wp_insert_post( $args );
		}

		return absint( $post_id );
	}
    /**
	 * Set user id
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return int
	 */
	private function get_user_id() {
		$author_name = get_query_var( 'author_name' );

		if ( ! empty( $author_name ) ) {
			$user = get_user_by( 'slug', $author_name );
			$user_id = $user->ID;
		} else {
			$user_id = get_current_user_id();
		}

		return absint( $user_id );
	}

    /**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()     {}
    private function __sleep()     {}
    private function __wakeup()    {}
}
