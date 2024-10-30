<?php
/**
 * Handle the content of "Checkout" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
    die();

if ( class_exists( 'BWS_BKNG_Settings_Checkout' ) )
    return;

class BWS_BKNG_Settings_Checkout extends BWS_BKNG_Settings_Tabs {

    /**
     * Class constructor
     * @since    0.1
     * @access   public
     * @param    void
     * @return   void
     */
    public function __construct() {
        $this->tabs = array(
            'general' => array( 'label' => __( 'General', BWS_BKNG_TEXT_DOMAIN ) )
        );

        parent::__construct();
    }

    /**
     * Prepares the plugin options before further saving to database
     * @since    0.1
     * @access   public
     * @param    void
     * @return   void
     */
    public function prepare_options( $options = NULL ) {
        global $wpdb;
        $post_type = '';
        if( isset( $_GET['post_type'] ) ){
            $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
            $post_type .= '_';
        }
        $notices_table         = BWS_BKNG_DB_PREFIX . $post_type . 'notifications';
        $checkout_registration = array( 'yes', 'no', 'user' );


        if ( NULL !== $options ) {
			$this->options = $options;
		}

        /* numbers */
        $this->options['checkout_page']  = absint( $_POST['bkng_checkout_page'] );
        $this->options['thank_you_page'] = absint( $_POST['bkng_thank_you_page'] );

        /* variations */
        $this->options['checkout_registration'] = in_array( $_POST['bkng_checkout_registration'], $checkout_registration ) ? sanitize_text_field( stripslashes( $_POST['bkng_checkout_registration'] ) ) : $this->options['checkout_registration'];

        $terms_and_conditions = sanitize_text_field( stripslashes( $_POST['bkng_notice']['terms_and_conditions']['body'] ) );

        /*paypal*/
        $this->options['paypal'] = ! empty( $_POST['bkng_paypal'] );
        if ( $_POST['bkng_paypal_secret'] && $_POST['bkng_paypal_clientid'] ) {
            $paypal = new BWS_BKNG_Paypal(
                sanitize_text_field( stripslashes( $_POST['bkng_paypal_clientid'] ) ),
                sanitize_text_field( stripslashes( $_POST['bkng_paypal_secret'] ) )
            );
            $token = $paypal->pay_pal_accessToken();
            if ( ! isset($token['error'] ) && isset( $_POST['bkng_paypal'] ) ) {
                $this->options['paypal_clientid'] = sanitize_text_field( stripslashes( $_POST['bkng_paypal_clientid'] ) );
                $this->options['paypal_secret']  =  sanitize_text_field( stripslashes( $_POST['bkng_paypal_secret'] ) );
            } else {
                $this->options['paypal_clientid'] = "";
                $this->options['paypal_secret']  = "" ;
            }
        } else {
            $this->options['paypal_clientid'] = "";
            $this->options['paypal_secret']  = "";
        }

        $wpdb->update(
            $notices_table,
            array( 'body' => $terms_and_conditions ),
            array( 'type' => 'terms_and_conditions' )
        );

        if ( NULL !== $options ) {
            return $this->options;
        }
    }

    /**
     * Displays the tab content
     * @since    0.1
     * @access   public
     * @param    void
     * @return   void
     */
    public function tab_general() {
        global $bws_bkng; ?>
        <table class="form-table">
            <?php /**
             * Checkout page select
             */
            $content = $bws_bkng->get_list_pages( 'checkout_page' );
            $bws_bkng->display_table_row( __( 'Checkout Page', BWS_BKNG_TEXT_DOMAIN ), $content );

            /**
             * Redirect after placing order
             */
            $thank_you_page_list = $bws_bkng->get_list_pages( 'thank_you_page' ) . __( 'page', BWS_BKNG_TEXT_DOMAIN );
            $bws_bkng->display_table_row( __( 'Redirect after Placing an Order to', BWS_BKNG_TEXT_DOMAIN ), $thank_you_page_list );

            /**
             * Registration options
             */
            $name       = 'bkng_checkout_registration';
            $option     = $this->options["checkout_registration"];
            $radioboxes = array(
                'yes'   => array( __( 'Only for registered users', BWS_BKNG_TEXT_DOMAIN ) ),
                'no'    => array( __( 'Available for anyone', BWS_BKNG_TEXT_DOMAIN ) ),
                'user'  => array( __( 'The customer can be registered during placing the order', BWS_BKNG_TEXT_DOMAIN ), __( 'The registration will be enabled but not required. Also, please make sure that users registration is enabled on "Dashboard" -> "Settings" -> "General" page', BWS_BKNG_TEXT_DOMAIN ) ),
            );

            foreach( $radioboxes as $value => $labels ) {
                $attr   = $value == $option ? 'checked="checked"' : '';
                $info   = empty( $labels[1] ) ? '' : $bws_bkng->get_tooltip( $labels[1] . '.' );
                $$value = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) ) . $labels[0] . $info;
            }

            $content = "<p>{$yes}</p><p>{$no}</p><p>{$user}</p>";

            $bws_bkng->display_table_row( __( 'Placing Orders', BWS_BKNG_TEXT_DOMAIN ), $content );

            /**
             * Terms And Conditions order
             */
            $content  = $bws_bkng->get_notice_editor( 'terms_and_conditions' );
            $content .= $bws_bkng->get_info( __( 'To avoid the displaying of the "Agree..." checkbox, just leave this field empty', BWS_BKNG_TEXT_DOMAIN ) . '.' );
            $bws_bkng->display_table_row( __( 'Terms and Conditions', BWS_BKNG_TEXT_DOMAIN ), $content );
            /**
             * PayPal order
             */

            if ( ! isset( $this->options['paypal'] ) ) {
                $bws_bkng->update_option( 0, 'paypal' );
                $this->options['paypal'] = 0;
            }
            if ( ! isset( $this->options['paypal_clientid'] ) ) {
                $bws_bkng->update_option( "", 'paypal_clientid' );
                $this->options['paypal_clientid'] = "";
            }
            if ( ! isset( $this->options['paypal_secret'] ) ) {
                $bws_bkng->update_option( "", 'paypal_secret' );
                $this->options['paypal_secret'] = "";
            }

            $paypal_currencies = array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'INR', 'ILS', 'JPY', 'MYR',
                'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
            $option = $this->options["paypal"];

            if ( ! $bws_bkng->is_pro ) { ?>
                </table>
                <?php if ( ! $this->hide_pro_tabs ) { ?>
                    <div class="bws_pro_version_bloc">
                        <div class="bws_pro_version_table_bloc">
                            <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'captcha-bws' ); ?>"></button>
                            <div class="bws_table_bg"></div>
                            <table class="form-table bws_pro_version">
                                <?php

                                $attr   .= 'disabled';

                                if ( ! in_array( $this->options["currency_code"], $paypal_currencies ) ) {
                                    $before_message = __( 'PayPal does not support this currency.', BWS_BKNG_TEXT_DOMAIN );
                                    echo "<th colspan=\"2\" style='color: red'>" . esc_html( $before_message ) . "</th>";
                                }
                                $descript = __( "Enable to add the PayPal payment feature.", BWS_BKNG_TEXT_DOMAIN ) . '<div class="bws_info">' . __( 'Get the necessary API keys and enter them below. ', BWS_BKNG_TEXT_DOMAIN ) . '<a target="_blank" href="https://support.bestwebsoft.com/hc/en-us/articles/360028912051">' . __( 'Get the API Keys', BWS_BKNG_TEXT_DOMAIN ) . '</a></div>';

                                $args = array(
                                    'id'    => 'bws_bkng_paypal',
                                    'attr'  => $attr,
                                    'value' => '1',
                                    'after' => $descript
                                );
                                $content = $bws_bkng->get_checkbox( $args );
                                $bws_bkng->display_table_row( __( 'PayPal ', BWS_BKNG_TEXT_DOMAIN ), $content );
                                $descript = '<div class="bws_info">' . __( "Client ID of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                                $args = array(
                                    'id'    => 'bws_bkng_paypal_clientid',
                                    'value' => $this->options["paypal_clientid"],
                                    'after' => $descript,
                                    'attr' => 'disabled="disabled"',
                                );
                                $content = $bws_bkng->get_text_input( $args );
                                $bws_bkng->display_table_row( __( 'PayPal Client ID', BWS_BKNG_TEXT_DOMAIN ), $content );
                                $descript = '<div class="bws_info">' . __( "Secret of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                                $args = array(
                                    'id'    => 'bws_bkng_paypal_secret',
                                    'value' => $this->options["paypal_secret"],
                                    'after' => $descript,
                                    'attr' => 'disabled',
                                );
                                $content = $bws_bkng->get_text_input( $args );
                                $bws_bkng->display_table_row( __( 'PayPal Secret', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
                            </table>
                            <?php $this->bws_pro_block_links(); ?>
                        </div>
                    </div>
                <?php }
            } else {
                    $attr   = 1 == $option ? 'checked="checked"' : '';
                    $attr   .= in_array( $this->options["currency_code"], $paypal_currencies) ? "" : 'disabled';
                    if ( ! in_array( $this->options["currency_code"], $paypal_currencies ) ) {
                        $before_message = __( 'PayPal does not support this currency.', BWS_BKNG_TEXT_DOMAIN );
                        echo "<th colspan=\"2\" style='color: red'>" . esc_html( $before_message ) . "</th>";
                    }
                    $descript = __( "Enable to add the PayPal payment feature.", BWS_BKNG_TEXT_DOMAIN ) . '<div class="bws_info">' . __( 'Get the necessary API keys and enter them below. ', BWS_BKNG_TEXT_DOMAIN ) . '<a target="_blank" href="https://support.bestwebsoft.com/hc/en-us/articles/360028912051">' . __( 'Get the API Keys', BWS_BKNG_TEXT_DOMAIN ) . '</a></div>';
                    $args = array(
                        'id'    => 'bws_bkng_paypal',
                        'name'  => 'bkng_paypal',
                        'attr'  => $attr,
                        'value' => '1',
                        'after' => $descript
                    );
                    $content = $bws_bkng->get_checkbox( $args );
                    $bws_bkng->display_table_row( __( 'PayPal ', BWS_BKNG_TEXT_DOMAIN ), $content );
                    $descript = '<div class="bws_info">' . __( "Client ID of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                    $args = array(
                        'id'    => 'bws_bkng_paypal_clientid',
                        'name'  => 'bkng_paypal_clientid',
                        'value' => $this->options["paypal_clientid"],
                        'after' => $descript
                    );
                    $content = $bws_bkng->get_text_input( $args );
                    $bws_bkng->display_table_row( __( 'PayPal Client ID', BWS_BKNG_TEXT_DOMAIN ), $content );
                    $descript = '<div class="bws_info">' . __( "Secret of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                    $args = array(
                        'id'    => 'bws_bkng_paypal_secret',
                        'name'  => 'bkng_paypal_secret',
                        'value' => $this->options["paypal_secret"],
                        'after' => $descript
                    );
                    $content = $bws_bkng->get_text_input( $args );
                    $bws_bkng->display_table_row( __( 'PayPal Secret', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
                </table>
            <?php }
        }

        /**
         * Displays the tab content
         * @since    0.1
         * @access   public
         * @param    void
         * @return   void
         */
        public function display_tab_content() {
            global $bws_bkng;
            $this->tab_title( __( 'Checkout', BWS_BKNG_TEXT_DOMAIN ) ); ?>
            <table class="form-table">
                <?php /**
                 * Checkout page select
                 */
                $content = $bws_bkng->get_list_pages( 'checkout_page' );
                $bws_bkng->display_table_row( __( 'Checkout Page', BWS_BKNG_TEXT_DOMAIN ), $content );

                /**
                 * Redirect after placing order
                 */
                $thank_you_page_list = $bws_bkng->get_list_pages( 'thank_you_page' ) . __( 'page', BWS_BKNG_TEXT_DOMAIN );
                $bws_bkng->display_table_row( __( 'Redirect after Placing an Order to', BWS_BKNG_TEXT_DOMAIN ), $thank_you_page_list );

                /**
                 * Registration options
                 */
                $name       = 'bkng_checkout_registration';
                $option     = $this->options["checkout_registration"];
                $radioboxes = array(
                    'yes'   => array( __( 'Only for registered users', BWS_BKNG_TEXT_DOMAIN ) ),
                    'no'    => array( __( 'Available for anyone', BWS_BKNG_TEXT_DOMAIN ) ),
                    'user'  => array( __( 'The customer can be registered during placing the order', BWS_BKNG_TEXT_DOMAIN ), __( 'The registration will be enabled but not required. Also, please make sure that users registration is enabled on "Dashboard" -> "Settings" -> "General" page', BWS_BKNG_TEXT_DOMAIN ) ),
                );
                $yes = $no = $user = '';
                foreach( $radioboxes as $value => $labels ) {
                    $attr   = $value == $option ? 'checked="checked"' : '';
                    $info   = empty( $labels[1] ) ? '' : $bws_bkng->get_tooltip( $labels[1] . '.' );
                    $$value = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) ) . $labels[0] . $info;
                }

                $content = "<p>{$yes}</p><p>{$no}</p><p>{$user}</p>";

                $bws_bkng->display_table_row( __( 'Placing Orders', BWS_BKNG_TEXT_DOMAIN ), $content );

                /**
                 * Terms And Conditions order
                 */
                $content  = $bws_bkng->get_notice_editor( 'terms_and_conditions' );
                $content .= $bws_bkng->get_info( __( 'To avoid the displaying of the "Agree..." checkbox, just leave this field empty', BWS_BKNG_TEXT_DOMAIN ) . '.' );
                $bws_bkng->display_table_row( __( 'Terms and Conditions', BWS_BKNG_TEXT_DOMAIN ), $content );
                /**
                 * PayPal order
                 */

                if ( ! isset( $this->options['paypal'] ) ) {
                    $bws_bkng->update_option( 0, 'paypal' );
                    $this->options['paypal'] = 0;
                }
                if ( ! isset( $this->options['paypal_clientid'] ) ) {
                    $bws_bkng->update_option( "", 'paypal_clientid' );
                    $this->options['paypal_clientid'] = "";
                }
                if ( ! isset( $this->options['paypal_secret'] ) ) {
                    $bws_bkng->update_option( "", 'paypal_secret' );
                    $this->options['paypal_secret'] = "";
                }

                $paypal_currencies = array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'INR', 'ILS', 'JPY', 'MYR',
                    'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
                $option = $this->options["paypal"];

                if ( ! $bws_bkng->is_pro ) { ?>
                    </table>
                    <?php if ( ! $this->hide_pro_tabs ) { ?>
                        <div class="bws_pro_version_bloc">
                            <div class="bws_pro_version_table_bloc">
                                <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'captcha-bws' ); ?>"></button>
                                <div class="bws_table_bg"></div>

                                <table class="form-table bws_pro_version">
                                    <?php

                                    $attr   = 1 == $option ? '' : '';
                                    $attr   .= in_array( $this->options["currency_code"], $paypal_currencies) ? 'disabled' : 'disabled';

                                    if ( ! in_array( $this->options["currency_code"], $paypal_currencies ) ){
                                        $before_message = __( 'PayPal does not support this currency.', BWS_BKNG_TEXT_DOMAIN );
                                        echo "<th colspan=\"2\" style='color: red'>" . esc_html( $before_message ) . "</th>";
                                    }
                                    $descript = __( "Enable to add the PayPal payment feature.", BWS_BKNG_TEXT_DOMAIN ) . '<div class="bws_info">' . __( 'Get the necessary API keys and enter them below. ', BWS_BKNG_TEXT_DOMAIN ) . '<a target="_blank" href="https://support.bestwebsoft.com/hc/en-us/articles/360028912051">' . __( 'Get the API Keys', BWS_BKNG_TEXT_DOMAIN ) . '</a></div>';

                                    $args = array(
                                        'id'    => 'bws_bkng_paypal',
                                        'attr'  => $attr,
                                        'value' => '1',
                                        'after' => $descript
                                    );

                                    $content = $bws_bkng->get_checkbox( $args );
                                    $bws_bkng->display_table_row( __( 'PayPal ', BWS_BKNG_TEXT_DOMAIN ), $content );

                                    $descript = '<div class="bws_info">' . __( "Client ID of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                                    if ( $bws_bkng->is_pro ) {
                                        $args = array(
                                            'id'    => 'bws_bkng_paypal_clientid',
                                            'name'  => 'bkng_paypal_clientid',
                                            'value' => $this->options["paypal_clientid"],
                                            'after' => $descript
                                        );
                                    } else {
                                        $args = array(
                                            'id'    => 'bws_bkng_paypal_clientid',
                                            'value' => $this->options["paypal_clientid"],
                                            'after' => $descript,
                                            'attr' => 'disabled="disabled"',
                                        );
                                    }

                                    $content = $bws_bkng->get_text_input( $args );
                                    $bws_bkng->display_table_row( __( 'PayPal Client ID', BWS_BKNG_TEXT_DOMAIN ), $content );

                                    $descript = '<div class="bws_info">' . __( "Secret of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';
                                    if ( $bws_bkng->is_pro ) {
                                        $args = array(
                                            'id'    => 'bws_bkng_paypal_secret',
                                            'name'  => 'bkng_paypal_secret',
                                            'value' => $this->options["paypal_secret"],
                                            'after' => $descript
                                        );
                                    } else {
                                        $args = array(
                                            'id'    => 'bws_bkng_paypal_secret',
                                            'value' => $this->options["paypal_secret"],
                                            'after' => $descript,
                                            'attr' => 'disabled',
                                        );
                                    }
                                    $content = $bws_bkng->get_text_input( $args );
                                    $bws_bkng->display_table_row( __( 'PayPal Secret', BWS_BKNG_TEXT_DOMAIN ), $content );
                                    ?>
                                </table>

                            </div>
                            <?php $this->bws_pro_block_links(); ?>
                        </div>
                    <?php }
                } else {
                    $attr   = 1 == $option ? 'checked="checked"' : '';
                    $attr   .= in_array( $this->options["currency_code"], $paypal_currencies) ? "" : 'disabled';

                    if ( ! in_array( $this->options["currency_code"], $paypal_currencies ) ){
                        $before_message = __( 'PayPal does not support this currency.', BWS_BKNG_TEXT_DOMAIN );
                        echo "<th colspan=\"2\" style='color: red'>" . esc_html( $before_message ) . "</th>";
                    }

                    $descript = __( "Enable to add the PayPal payment feature.", BWS_BKNG_TEXT_DOMAIN ) . '<div class="bws_info">' . __( 'Get the necessary API keys and enter them below. ', BWS_BKNG_TEXT_DOMAIN ) . '<a target="_blank" href="https://support.bestwebsoft.com/hc/en-us/articles/360028912051">' . __( 'Get the API Keys', BWS_BKNG_TEXT_DOMAIN ) . '</a></div>';

                    $args = array(
                        'id'    => 'bws_bkng_paypal',
                        'name'  => 'bkng_paypal',
                        'attr'  => $attr,
                        'value' => '1',
                        'after' => $descript
                    );

                    $content = $bws_bkng->get_checkbox( $args );
                    $bws_bkng->display_table_row( __( 'PayPal ', BWS_BKNG_TEXT_DOMAIN ), $content );

                    $descript = '<div class="bws_info">' . __( "Client ID of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';

                    $args = array(
                        'id'    => 'bws_bkng_paypal_clientid',
                        'name'  => 'bkng_paypal_clientid',
                        'value' => $this->options["paypal_clientid"],
                        'after' => $descript
                    );

                    $content = $bws_bkng->get_text_input( $args );
                    $bws_bkng->display_table_row( __( 'PayPal Client ID', BWS_BKNG_TEXT_DOMAIN ), $content );

                    $descript = '<div class="bws_info">' . __( "Secret of your PayPal App.", BWS_BKNG_TEXT_DOMAIN ) . '</div>';

                    $args = array(
                        'id'    => 'bws_bkng_paypal_secret',
                        'name'  => 'bkng_paypal_secret',
                        'value' => $this->options["paypal_secret"],
                        'after' => $descript
                    );

                    $content = $bws_bkng->get_text_input( $args );
                    $bws_bkng->display_table_row( __( 'PayPal Secret', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>

                    </table>
                <?php }
            }
}
