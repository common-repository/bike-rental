<?php
/**
 * Displays the list of extras
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( 'available' != bws_bkng_get_product_status() )
	return;
global $post;
$data = bws_bkng_query_linked_products();

do_action( 'bws_bkng_before_extras_list' ); ?>

<form id="bws_bkng_to_checkout_form" method="post" action="<?php echo esc_attr( bws_bkng_get_page_permalink( 'checkout' ) ); ?>">

	<h3><?php _e( 'Rent Interval', BWS_BKNG_TEXT_DOMAIN ); ?></h3>
	<?php bws_bkng_datetimepicker_form(); ?>

	<?php if ( bws_bkng_is_countable() && bws_bkng_get_max_quantity() > 1 ) { ?>
		<h3><?php _e( 'Quantity', BWS_BKNG_TEXT_DOMAIN ); ?></h3>
		<div><input type="number" value="1" name="bkng_quantity" min="1" max="<?php echo esc_attr( bws_bkng_get_max_quantity() ); ?>" step="1" /></div>
	<?php } ?>

	<?php if ( ! empty( $data ) ) { ?>
		<h3><?php _e( 'Extras', BWS_BKNG_TEXT_DOMAIN ); ?></h3>

		<div id="bws_bkng_extras" class="bws_bkng_products_list" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'checkout' ) ); ?>" >

			<?php foreach ( $data as $key => $value ) { ?>

				<div class="bws_bkng_extras_list_wrap bws_bkng_extras_<?php echo esc_attr( $value['cat_data']->slug ); ?>">

					<h4><?php echo esc_html( $value['cat_data']->name ); ?></h4>

					<div class="bws_bkng_extras_list">

						<?php foreach ( $value['products'] as $post ) {

							setup_postdata( $post );
							$is_countable = bws_bkng_is_countable();
							$max_quantity = 0;

							if ( $is_countable ) {
								$max_quantity = absint( get_post_meta( $post->ID , 'bkng_in_stock', true ) );
								if ( $max_quantity < 1 )
									continue;
							} ?>

							<div class="bws_bkng_extra" id="bws_bkng_extra_<?php echo esc_attr( $post->ID ); ?>">
								<div class="bws_bkng_column bws_bkng_thumbnail_column">
									<?php the_post_thumbnail( 'bkng_catalog_' . $post->post_type . '_image' ); ?>
								</div><!-- .bws_bkng_thumbnail_column -->

								<div class="bws_bkng_column bws_bkng_content_column">

									<h3 class="bws_bkng_product_title">
										<?php the_title(); ?>
									</h3>

									<?php bws_bkng_get_template_part( 'price' ); ?>

									<div class="bws_bkng_column bws_bkng_quantity_column bws_bkng_align_middle">
										<?php if ( $max_quantity > 1 ) { ?>
											<label><input type="number" value="0" name="bkng_extras[<?php echo esc_attr( $post->ID ); ?>][quantity]" min="0" max="<?php echo esc_attr( $max_quantity ); ?>" step="1" form="bws_bkng_to_checkout_form" /></label>
										<?php } ?>
									</div><!-- .bws_bkng_quantity_column -->

									<div class="bws_bkng_column bws_bkng_choice_column bws_bkng_align_middle"> <?php echo esc_html( bws_bkng_product_is_in_cart( $post->ID ) ); ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                value="1"
                                                name="bkng_extras[<?php echo esc_attr( $post->ID ); ?>][choose]"
                                                form="bws_bkng_to_checkout_form"<?php if ( bws_bkng_product_is_in_cart( $post->ID ) ) echo ' checked="checked"'; ?>
                                                data-price="<?php echo esc_attr( bws_bkng_get_product_price() ); ?>"
                                                data-rent-step="<?php echo esc_attr( bws_bkng_get_rent_interval_step() ); ?>"
                                                data-show-price="<?php echo esc_attr( bws_bkng_show_product_price() ); ?>"
                                            />
                                        </label>
									</div><!-- .bws_bkng_choice_column -->
								</div><!-- .bws_bkng_content_column -->

							</div><!-- .bws_bkng_product -->

						<?php } ?>

					</div><!-- .bws_bkng_extras_list -->

				</div><!-- .bws_bkng_extras_list_wrap -->

			<?php }

			wp_reset_postdata(); ?>

		</div><!-- .bws_bkng_products_list -->
	<?php } ?>

	<p class="submit">
		<input type="submit" name="bkng_checkout_product" class="button button-primary" value="<?php _e( 'Checkout', BWS_BKNG_TEXT_DOMAIN ); ?>" />
		<?php bws_bkng_back_to_search_link(); ?>
		<input type="hidden" name="bkng_product" value="<?php echo esc_attr( $post->ID ); ?>" />
		<input type="hidden" name="bkng_nonce" value="<?php echo wp_create_nonce( "bkng_checkout_product" ); ?>" />
		<input type="hidden" name="bkng_product_rent_interval_step" value="<?php echo esc_attr( bws_bkng_get_rent_interval_step() ); ?>" />
	</p>

</form><!-- #bws_bkng_to_checkout_form -->

<?php do_action( 'bws_bkng_after_extras_list' ); ?>

