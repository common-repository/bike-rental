<?php

if (!defined('ABSPATH'))
    die();

class BWS_BKNG_Paypal
{

    private $client_id;
    private $secret;
    public $name_db;

    public function __construct($client_id, $secret, $hook_form = 'bws_bkng_checkout_form_before_submit_button', $hook_before_submit = 'bws_bkng_after_order_placed')
    {
        $this->client_id = $client_id;
        $this->secret = $secret;
        $this->name_db = 'bws_bkng_orders';
        add_action('wp_head', array($this, 'head_paypal_meta'));
        add_action('wp_ajax_bws_pay_pal_orderid', array($this, 'pay_pal_orderid'));
        add_action($hook_form, array($this, 'render_pay_pal_buttons'));
        add_filter($hook_before_submit, array($this, 'save_pay_status'));
        add_action('wp_enqueue_scripts', array($this, 'register_paypal_script'));
    }

    public function register_paypal_script()
    {
        wp_register_script('bkng_paypal_script', BWS_BKNG_URL . 'js/paypal_script.js');
    }

    public function head_paypal_meta()
    {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="X-UA-Compatible" content="IE=edge" />';
    }

    public function render_pay_pal_buttons() {

        if ( ! isset($_POST['bkng_product'] ) ) {
            return;
        }
        $option = get_option('bkrntl_options');
        $data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'currency_code' => $option['currency_code'],
            'message' => __('Payment successfully completed', BWS_BKNG_TEXT_DOMAIN),
            'message_input' => __('You have not entered the required data', BWS_BKNG_TEXT_DOMAIN),
        );
        wp_enqueue_script('bkng_paypal_script', BWS_BKNG_URL . "js/paypal_script.js");
        wp_localize_script('bkng_paypal_script', 'bws_bkng_paypal', $data);

        $echo = '
			<script src="https://www.paypal.com/sdk/js?client-id=' . esc_attr( $this->client_id ) . '&currency=' . esc_attr( $option["currency_code"] ) . '"></script>
			<div class="message_paypal"></div>
			<div id="paypal-button-container"></div>
			<input type="hidden" id="bkng_payment_id" name="bkng_payment_id" value="">
			<input type="hidden" id="bkng_payment_status" name="bkng_payment_status" value="">
			<input type="hidden" id="bkng_payment_data" name="bkng_payment_data" value="">';
        echo $echo;
    }

    public function pay_pal_accessToken()
    {
        $auth = base64_encode( $this->client_id . ':' . $this->secret );
        $response = wp_remote_post(
            'https://api-m.sandbox.paypal.com/v1/oauth2/token',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                    'Authorization' => "Basic $auth",
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
                'sslverify' => true,
            ]
        );

        $json = json_decode( $response['body'] );

        return $json->access_token ?? array( 'error' => $json->error_description );
    }

    public function pay_pal_status_pay($orderid)
    {
        $accessToken = $this->pay_pal_accessToken();
        if (isset($accessToken['error'])) {
            return $accessToken;
        }

        $response = wp_remote_get(
            "https://api.sandbox.paypal.com/v2/checkout/orders/$orderid",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken",
                ],
                'sslverify' => false,
            ]
        );

        return $response['body'];
    }

    public function pay_pal_orderid()
    {
        $result = $this->pay_pal_status_pay( absint( $_POST['orderid']) );
        if (isset($result['error'])) {
            echo json_encode($result);
            die();
        }
        $json = json_decode($result);
        if (!isset($json->status) || !isset($json->update_time) || !isset($json->id)) {
            $data = array(
                'error' => $json->message,
            );
        } else {
            $data = array(
                'status' => $json->status,
                'time' => $json->update_time,
                'id' => $json->id
            );
        }
        $data = json_encode($data);
        echo $data;
        die();
    }


    public function save_pay_status($order_id)
    {
        global $wpdb;

        $wpdb->update($wpdb->prefix . $this->name_db, array(
            'payment_id' => !empty($_POST['bkng_payment_id']) ? absint( $_POST['bkng_payment_id'] ) : null,
            'payment_status' => !empty($_POST['bkng_payment_status']) ? sanitize_text_field( stripslashes( $_POST['bkng_payment_status'] ) ) : __('Not paid', BWS_BKNG_TEXT_DOMAIN),
            'payment_date' => !empty($_POST['bkng_payment_data']) ? array_map( 'sanitize_text_field', $_POST['bkng_payment_data'] ) : null
        ), array(
            'id' => $order_id
        ));
    }
}

function bws_bkng_new_class_paypal()
{

    if (strripos($_SERVER['REQUEST_URI'], "checkout") != false || (isset($_REQUEST['action']) && 'bws_pay_pal_orderid' == $_REQUEST['action'])
    ) {

        $option = get_option('bkrntl_options');
        if (!empty($option['paypal_clientid']) &&
            !empty($option['paypal_secret']) &&
            $option['paypal']
        ) {
            new BWS_BKNG_Paypal($option['paypal_clientid'], $option['paypal_secret']);
        }
    }
}

function bws_bkng_paypal_currencies_notice()
{
    $option = get_option('bkrntl_options');
    if ( ! empty( $option ) ) {
        $paypal_currencies = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'INR', 'ILS', 'JPY', 'MYR',
            'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
        if ( ! in_array( $option["currency_code"], $paypal_currencies ) && ( isset( $option['paypal'] ) && $option['paypal'] )  ) {
            $before_message = __('PayPal does not support this currency.', BWS_BKNG_TEXT_DOMAIN);
            echo "<div class=\"error notice-success\"><p>" . esc_html( $before_message ) . "</p></div>";
        }
        if ( ( isset( $option['paypal'] ) && $option['paypal'] ) && ( empty ($option['paypal_clientid']) || empty($option['paypal_secret']))) {
            $before_message = __('For the payment system to work correctly, you need to introduce correct Client ID and Secret.', BWS_BKNG_TEXT_DOMAIN);
            echo "<div class=\"error notice-success\"><p>" . esc_html( $before_message ) . "</p></div>";
        }
    }
}

add_action('init', 'bws_bkng_new_class_paypal');
add_action('admin_notices', 'bws_bkng_paypal_currencies_notice');





