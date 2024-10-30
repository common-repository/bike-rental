<?php
/**
 * Displays the checkout form
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$billing_data = bws_bkng_get_billing_data();
$errors       = bws_bkng_get_errors( 'checkout', true );

if ( ! bws_bkng_get_order() || ! $billing_data )
	return;

bws_bkng_errors( 'checkout' );

do_action( 'bws_bkng_before_checkout_form', $billing_data, $errors ); ?>

<form class="bws_bkng_checkout_form" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'checkout' ) ); ?>">

	<?php do_action( 'bws_bkng_checkout_form_start', $billing_data, $errors ); ?>

	<p<?php echo bws_bkng_is_error( 'user_firstname', $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
		<label><?php _e( 'First Name', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<input type="text" name="bkng_billing_data[user_firstname]" value="<?php echo esc_attr( $billing_data['user_firstname'] ); ?>" required="required" />
	</p>

	<p<?php echo bws_bkng_is_error( 'user_lastname', $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
		<label><?php _e( 'Last Name', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<input type="text" name="bkng_billing_data[user_lastname]" value="<?php echo esc_attr( $billing_data['user_lastname'] ); ?>" required="required" />
	</p>

	<?php do_action( 'bws_bkng_checkout_form_after_personal_info', $billing_data, $errors ); ?>

	<p<?php echo bws_bkng_is_error( array( 'user_phone', 'wrong_user_phone' ), $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
		<label><?php _e( 'Phone', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<input type="text" name="bkng_billing_data[user_phone]" value="<?php echo esc_attr( $billing_data['user_phone'] ); ?>" required="required" />
	</p>

	<p<?php echo bws_bkng_is_error( array( 'wrong_user_email', 'user_email' ), $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
		<label><?php _e( 'Email', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<input type="text" name="bkng_billing_data[user_email]" value="<?php echo esc_attr( $billing_data['user_email'] ); ?>" required="required" />
	</p>

	<p<?php echo bws_bkng_is_error( array( 'user_confirm_email', 'missmatch_user_email' ), $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
		<label><?php _e( 'Confirm Email', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<input type="text" name="bkng_billing_data[user_confirm_email]" value="<?php echo empty( $billing_data['user_confirm_email'] ) ? '' : esc_attr( $billing_data['user_confirm_email'] ); ?>" required="required" />
	</p>

	<?php do_action( 'bws_bkng_checkout_form_after_contact_info' ); ?>

	<p>
		<label><?php _e( 'Enter your message', BWS_BKNG_TEXT_DOMAIN ); ?><span class="bws_bkng_required">*</span></label>
		<textarea name="bkng_billing_data[user_message]"><?php echo esc_textarea( $billing_data['user_message'] ); ?></textarea>
	</p>

	<?php do_action( 'bws_bkng_checkout_form_after_additional_info', $billing_data, $errors );

	$terms_and_conds = bws_bkng_get_terms_and_conditions();

	if ( $terms_and_conds ) { ?>

        <p<?php echo bws_bkng_is_error( 'user_agree_with_terms', $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
            <label><input type="checkbox" value="1" name="bkng_billing_data[user_agree_with_terms]"<?php if ( $billing_data['user_agree_with_terms'] ) echo ' checked="checked"'; ?> />
				<?php _e( 'I agree with', BWS_BKNG_TEXT_DOMAIN ); ?>&nbsp;<a href="#bws_bkng_terms_and_conditions"><?php _e( 'terms and conditions', BWS_BKNG_TEXT_DOMAIN ); ?></a></label>
        </p>

		<div id="bws_bkng_terms_and_conditions" class="bkng_hidden"><?php echo esc_html( $terms_and_conds ); ?></div>

	<?php }

	if ( ! is_user_logged_in() && bws_bkng_show_register_checkbox() ) { ?>
        <p>
            <label><input type="checkbox" value="1" name="bkng_billing_data[register_user]" />
				<?php _e( 'Register me', BWS_BKNG_TEXT_DOMAIN ); ?></label>
        </p>

	<?php }

	do_action( 'bws_bkng_checkout_form_before_submit_button', $billing_data, $errors ); ?>

	<p class="submit">
		<input type="submit" name="bkng_place_order" class="button button-primary" value="<?php _e( 'Place Order', BWS_BKNG_TEXT_DOMAIN ); ?>" />
		<input type="hidden" name="bkng_nonce" value="<?php echo wp_create_nonce( "bkng_place_order" ); ?>" />
	</p>
</form><!-- .bws_bkng_checkout_form -->

<?php do_action( 'bws_bkng_after_checkout_form', $billing_data, $errors );