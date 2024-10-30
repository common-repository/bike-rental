<?php
$order_id 		= bkng_get_order_id();

$order          = bws_bkng_get_order( $order_id );
$product        = bws_bkng_get_ordered_product( $order );

$billing_data 	= bws_bkng_get_billing_data();
$errors       	= bws_bkng_get_errors( 'checkout', true );

$session 		= BWS_BKNG_Session::get_instance( true );

$cart 			= BWS_BKNG_Cart::get_instance( true );

if ( isset( $_REQUEST['options'] ) ) {
	$cart->bkng_add_extras_to_cart();
	$session->add( 'order_options', array_map('sanitize_text_field', $_REQUEST['options'] ) );
	$order_options = array_map('sanitize_text_field', $_REQUEST['options'] );

} else {
	$order_options = $session->get( 'order_options' );
}

if ( isset( $_GET['clear_cart'] ) && 'true' == $_GET['clear_cart'] ) {
	$session->update( 'order_options', 0 );
	$session->update( 'order', 0 );
	wp_redirect( get_permalink() );
}
get_header();
?>
<div class="bwspattern-content-wrapper">
	<?php
	if ( $order && ! isset( $order['status'] ) && isset( $product['id'] ) ) {
		global $bws_bkng;

        $post_type = get_post_type( $product['id'] );
        if ( $post_type == 'bws_extra' ) {
            $taxonomy = 'extra_type';
        } else {
            $taxonomy = 'bike_type';
        }

        $term = get_the_terms( $product['id'], $taxonomy );
		$product_link = get_post_permalink( $product['id'] );
		$options = array(
			'from'  => date( 'F j', $product['rent_interval']['from'] ),
			'to'    => date( 'F j', $product['rent_interval']['till'] ),
			'type'  => $term[0]->name,
		);
		foreach ( $order_options as $key => $value ) {
			if ( $value ) {
				$options[ $key ] = $value;
			}
		}
        bws_bkng_errors( 'checkout' );

        do_action( 'bws_bkng_before_checkout_form', $billing_data, $errors );
		?>
		<div class="bwspattern-content-section">
			<div class="item-content">
				<div class="single-item">
					<div class="container item-container">
						<div class="single-item-picture col-md-3 col-12">
							<div class="edit-close-item">
								<div class="close-item">
									<a id="close-item" href="<?php echo esc_url( get_permalink() . '?clear_cart=true' ); ?>">
										<img src="<?php echo esc_url( get_template_directory_uri() . '/images/fa-close.png' ); ?>" alt="">
									</a>
								</div>
								<div class="edit-item">
									<a href="<?php echo esc_url( $product_link ); ?>">
										<img src="<?php echo esc_url( get_template_directory_uri() . '/images/edit-icon.png' ); ?>" alt="">
									</a>
								</div>
							</div>
							<?php if ( has_post_thumbnail( $product['id'] ) ) {
								echo get_the_post_thumbnail( $product['id'], 'large' );
							} ?>
						</div>

						<div class="single-item-content" >
							<div class="single-item-title-price-container">
								<div class="single-item-title">
									<span class="span-item-name"><?php _e( 'BIKE: ', 'rent-a-bike' ); ?></span>
									<span class="span-item-type"><?php echo esc_html( $product['title'] ); ?></span>
									<span class="single-item-price"><?php echo bws_bkng_price_format( $product['price'] ); ?></span>
								</div>
							</div>

							<div class="single-item-visual-options">

								<div class="item-visual-option-1">
									<img src="<?php echo esc_url( get_template_directory_uri() . '/images/bike-parameters-icon.png' ); ?>" alt="">
								</div>
								<div class="item-visual-option-2">
									<img src="<?php echo esc_url( get_template_directory_uri() . '/images/additional-accessories-icon.png' ); ?>" alt="">
								</div>

							</div>

							<div class="container-single-item-options d-sm-flex text-sm-left text-center flex-wrap">
								<?php foreach ( $options as $name => $option ) { ?>
									<div class="single-item-option">
										<span class="span-item-option-name"><?php echo esc_html( $name ); ?></span>
										<span class="span-item-option-type"><?php echo esc_html( $option ); ?></span>
									</div>
								<?php } ?>
							</div>

						</div>

					</div>
				</div>
			</div>

			<div class="total-price">
				<div class="container">
					<div class="total-price-container">
						<div class="total-prices">
							<span class="subtotal-title"><?php _e( 'SUBTOTAL', 'rent-a-bike' ); ?></span>


							<span class="width-price subtotal-price float-price"><?php echo bws_bkng_get_currency() . ' ' . esc_html( $product['subtotal'] ); ?></span>

							<span class="margin-price width-price subtotal-price float-price"><?php _e( 'day(s)', 'rent-a-bike' ); ?></span>

							<span class="width-price subtotal-price float-price"><?php echo esc_html( ( ( int )$product['rent_interval']['till'] - ( int )$product['rent_interval']['from'] ) / ( int )$product['rent_interval']['step'] ) . '&nbsp'; ?></span>
							<span class="width-price subtotal-price float-price"><?php echo '&#215;' ?></span>
							<span class="width-price subtotal-price float-price"><?php echo  bws_bkng_get_currency() . ' ' . esc_html( $product['price'] ); ?></span>
						</div>
						<div class="total-prices">
							<span class="subtotal-title"><?php _e( 'EXTRAS', 'rent-a-bike' ); ?>:</span>
						</div>
						<?php foreach ( $order['products'] as $position ) {
							if ( false !== $position['linked_to'] ) { ?>
								<div class="total-prices">
									</span><span class="bikes-title font-title"><?php echo esc_html( $position['title'] ); ?></span>
									<span class="width-price bikes-price float-price font-price"><?php echo bws_bkng_get_currency() . ' ' . esc_html( ( ( int )$position['price'] * ( int )$position['quantity'] ) ); ?></span>

									<span class="margin-price width-price bikes-price float-price font-price"><?php _e( 'pc(s).', 'rent-a-bike' ); ?></span>

									<span class="width-price bikes-price float-price font-price"><?php echo esc_html( $position['quantity'] ) . '&nbsp'; ?></span>
									<span class="width-price bikes-price float-price font-price"><?php echo '&#215;' ?></span>
									<span class="width-price bikes-price float-price font-price"><?php echo bws_bkng_get_currency() . ' ' . esc_html( $position['price'] ); ?></span>
								</div>
							<?php }
						} ?>
						<div class="total-prices">
							<span class="subtotal-title"><?php _e( 'EXTRAS TOTAL', 'rent-a-bike' ); ?></span>
							<?php $number_of_days =  ( ( ( int )$product['rent_interval']['till'] - ( int )$product['rent_interval']['from'] ) / ( int )$product['rent_interval']['step'] ) ?>



							<span class="width-price subtotal-price float-price"><?php echo bws_bkng_get_currency() . ' ' . esc_html( $order['extras_total'] ); ?></span>


							<span class="margin-price width-price subtotal-price float-price"><?php _e( 'day(s)', 'rent-a-bike' ); ?></span>

							<span class="width-price subtotal-price float-price"><?php echo esc_html( $number_of_days ) . '&nbsp'; ?></span>
							<span class="width-price subtotal-price float-price"><?php echo '&#215;' ?></span>
							<span class="width-price subtotal-price float-price"><?php echo bws_bkng_get_currency() . ' ' . ( esc_html( $order['extras_total'] / $number_of_days ) ); ?></span>


						</div>
						<div class="order-total">
							<span class="order-total-title"><?php _e( 'ORDER TOTAL', 'rent-a-bike' ); ?></span>
							<span class="width-price order-total-price float-price"><?php echo bws_bkng_get_currency() . ' ' . '<span class="bws_bkng_order_total">' . esc_html( $order['total'] ) . '</span>'; ?></span>
						</div>
					</div>
				</div>
			</div>

			<form class="bws_bkng_checkout_form" method="post" action="<?php echo get_page_link(); ?>">

				<div class="bwspattenr-userinfo-block">
					<div class="container">
						<h4 class="bwspattern-title"><?php _e( 'Name', 'rent-a-bike' ); ?></h4>
						<p class="bwspattern-text-info py-3"><?php _e( 'Please enter your name!', 'rent-a-bike' ); ?></p>
						<div class="bwspattern-validation-wrapper">
							<input type="text" name="bkng_billing_data[user_firstname]" placeholder="<?php _e( 'First name', 'rent-a-bike' ); ?>" id="name-first">
							<span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_firstname', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'This field is required!', 'rent-a-bike' ); ?></span>
						</div>
						<div class="bwspattern-validation-wrapper">
							<input type="text" name="bkng_billing_data[user_lastname]" placeholder="<?php _e( 'Last name', 'rent-a-bike' ); ?>" id="name-last">
							<span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_lastname', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'This field is required!', 'rent-a-bike' ); ?></span>
						</div>
					</div>
				</div><!-- .bwspattenr-userinfo-block -->

                <div class="bwspattenr-userinfo-block">
                    <div class="container">
                        <h4 class="bwspattern-title"><?php _e( 'Age', 'rent-a-bike' ); ?></h4>
                        <p class="bwspattern-text-info py-3"><?php _e( 'Please enter your age!', 'rent-a-bike' ); ?></p>
                        <div class="bwspattern-validation-wrapper">
                            <input type="number" name="bkng_billing_data[user_age]" min="18" value="18" placeholder="<?php _e( 'Age', 'rent-a-bike' ); ?>" id="age">
                            <span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_firstname', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'This field is required!', 'rent-a-bike' ); ?></span>
                        </div>
                    </div>
                </div><!-- .bwspattenr-userinfo-block -->

                <div class="bwspattenr-userinfo-block">
                    <div class="container">
                        <h4 class="bwspattern-title"><?php _e( 'Phone', 'rent-a-bike' ); ?></h4>
                        <p class="bwspattern-text-info py-3"><?php _e( 'Please enter your phone!', 'rent-a-bike' ); ?></p>
                        <div class="bwspattern-validation-wrapper">
                            <input type="tel" name="bkng_billing_data[user_phone]" placeholder="<?php _e( 'Phone', 'rent-a-bike' ); ?>" id="phone">
                            <span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_email', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'Invalid Phone Number', 'rent-a-bike' ); ?></span>
                        </div>
                    </div>
                </div><!-- .bwspattenr-userinfo-block -->

				<div class="bwspattenr-userinfo-block">
					<div class="container">
						<h4 class="bwspattern-title"><?php _e( 'Email address', 'rent-a-bike' ); ?></h4>
						<p class="bwspattern-text-info py-3"><?php _e( 'Please enter your email address!', 'rent-a-bike' ); ?></p>
						<div class="bwspattern-validation-wrapper">
							<input type="email" name="bkng_billing_data[user_email]" placeholder="<?php _e( 'Email Address', 'rent-a-bike' ); ?>" id="email-new">
							<span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_email', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'Invalid Email Address', 'rent-a-bike' ); ?></span>
						</div>
						<div class="bwspattern-validation-wrapper">
							<input type="email" name="bkng_billing_data[user_confirm_email]" placeholder="<?php _e( 'Confirm Email Address', 'rent-a-bike' ); ?>" id="email-confirm">
							<span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_confirm_email', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'Email Address does not match', 'rent-a-bike' ); ?></span>
						</div>
					</div>
				</div><!-- .bwspattenr-userinfo-block -->

				<?php if ( has_action( 'bws_bkng_checkout_form_after_contact_info' ) ) { ?>
				<div class="bwspattenr-userinfo-block">
					<div class="container">
						<?php do_action( 'bws_bkng_checkout_form_after_contact_info' ); ?>
					</div>
				</div><!-- .bwspattenr-userinfo-block -->
				<?php } ?>

				<?php if ( has_action( 'bws_bkng_checkout_bike-rental' ) ) { ?>
				<div class="bwspattenr-userinfo-block">
					<div class="container">
						<?php do_action( 'bws_bkng_checkout_bike-rental' ); ?>
					</div>
				</div><!-- .bwspattenr-userinfo-block -->
				<?php } ?>

				<div class="bwspattenr-userinfo-block">
					<div class="container">
						<h4 class="bwspattern-title"><?php _e( 'Additional requests or comments', 'rent-a-bike' ); ?></h4>
						<p class="bwspattern-text-info py-3"><?php _e( 'Please enter your additional requests or comments!', 'rent-a-bike' ); ?></p>
						<div class="bwspattern-validation-wrapper w-100">
							<textarea name="bkng_billing_data[user_message]" placeholder="<?php _e( 'Your Message', 'rent-a-bike' ); ?>" id="user-mess"></textarea>
							<span class="bwspattern-popup-invalid" style="<?php echo bws_bkng_is_error( 'user_message', $errors ) ? 'display: block;' : ''; ?>"><?php _e( 'This field is required!', 'rent-a-bike' ); ?></span>
						</div>
					</div>
				</div><!-- .bwspattenr-userinfo-block -->
                <?php bws_bkng_get_payment_methods();
				$terms_and_conds = bws_bkng_get_terms_and_conditions( get_post_type( $product['id'] ) );
				if ( $terms_and_conds ) { ?>

					<div class="bwspattenr-userinfo-block">
						<div class="container">
							<h4 class="bwspattern-title pb-4"><?php _e( 'Terms and Conditions', 'rent-a-bike' ); ?></h4>
							<p <?php echo bws_bkng_is_error( 'user_agree_with_terms', $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
								<label><input type="checkbox" value="1" name="bkng_billing_data[user_agree_with_terms]"<?php if ( $billing_data['user_agree_with_terms'] ) echo ' checked="checked"'; ?> />
									<?php _e( 'I agree with', 'rent-a-bike' ); ?>&nbsp;<a href="#bws_bkng_terms_and_conditions"><?php _e( 'terms and conditions', 'rent-a-bike' ); ?></a></label>
								</p>

								<div id="bws_bkng_terms_and_conditions" class="bkng_hidden"><?php echo esc_html( $terms_and_conds ); ?></div>
							</div>
						</div><!-- .bwspattenr-userinfo-block -->
					<?php } ?>

				<div class="container-confirm-checkout">
					<div class="container">
						<?php bws_bkng_errors( 'checkout' ); ?>
						<div class="bwspattern-submit">
							<input type="submit" name="bkng_place_order" class="button button-primary confirm-checkout" value="<?php _e( 'Confirm', 'rent-a-bike' ); ?>">
							<input type="hidden" name="bkng_nonce" value="<?php echo wp_create_nonce( 'bkng_place_order' ); ?>" />
						</div>
					</div>
				</div>
			</form>
		</div><!-- .bwspattern-content-section -->
	<?php } else { ?>
		<div class="text-center py-5 my-5">
			<?php _e( 'Your order is empty', 'rent-a-bike' ); ?>
		</div>
	<?php } ?>
</div><!-- .bwspattern-content-wrapper -->
<?php get_footer(); ?>
