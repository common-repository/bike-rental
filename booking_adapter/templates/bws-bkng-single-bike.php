<?php
get_header();
the_post();
?>
	<div class="bwspattern-content-section bwspattern-search-results-section bwspattern-transparent-header">
		<div class="bwspattern-section-background"></div>
		<div class="container">
			<div <?php echo bws_bkng_get_product_classes(); ?>>
                <?php
				global $bws_booking_adapter, $bws_bkng, $post;

                $filter     	= BWS_BKNG_Content_Filter::get_instance();
                $content    	= get_the_content();
                $post_data		= new BWS_BKNG_Post_Data();
				$attribute_list	= $post_data->get_custom_attribute_list();
				$extras 		= $bws_booking_adapter->get_available_extra( $post->ID );

				$location = bws_bkng_get_product_address();
                $option = $bws_bkng->get_option();
				$location = $location ?? $option['google_map_default_address'];

				$order          = bws_bkng_get_order( false );
				if ( ! empty( $order ) ) {
					$product        = bws_bkng_get_ordered_product( $order );
					$session 		= BWS_BKNG_Session::get_instance( true );
					$order_options  = $session->get( 'order_options' );

					$options = array(
						'from'  => date( 'F j, Y', $product['rent_interval']['from'] ),
						'to'    => date( 'F j, Y', $product['rent_interval']['till'] ),
					);
					if ( ! empty( $order_options ) ) {
						foreach ( $order_options as $key => $value ) {
							if ( $value ) {
								$options[ $key ] = $value;
							}
						}
					}
				} ?>
				<div class="bws_bkng_product_content">
					<h2 class="bws_bkng_product_title"><span><?php _e( 'Bike', 'rent-a-bike' ); ?>:</span> <?php the_title(); ?></h2>
					<div class="row d-md-flex justify-content-between">
						<div class="col-lg-6 col-12">
							<div class="position-relative overflow-hidden">
								<?php the_post_thumbnail( 'catalog_image_size' );
                                bws_bkng_get_wishlist_btn(); ?>
								<div class="bwspattern-post-thumbnail-blur"></div>
								<div class="bwspattern-post-thumbnail-blur-overlay"></div>
							</div>
							<div class="bws_bkng_product_gallery">
                                <?php echo $filter->get_template_parts( 'gallery' ); ?>
                            </div>
						</div><!-- .col-6 -->
						<form id="bws_bkng_to_checkout_form" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink_by_post_type( 'checkout' , get_post_type( $post->ID ) ) ); ?>" class="col-lg-6 col-12">
                            <?php echo ( empty( $content ) ? '' : esc_html( $content ) ); ?>
                            <?php if ( isset( $attribute_list['features']['value'] ) && is_array( $attribute_list['features']['value'] ) ) { ?>
                                <div class="bwspattern-features-block d-flex flex-wrap mt-5">
	                            <?php foreach ( $attribute_list['features']['value'] as $feature ) { ?>
                                    <div class="col-sm-4 offset-sm-0 col-8 offset-4 bwspattern-feature">
	                                    <?php echo esc_html( $feature ); ?>
                                    </div>
                                <?php } ?>
                                </div>
								<?php unset( $attribute_list['features'] ); ?>
                            <?php } ?>
							<div class="bwspattern-options-form row d-flex flex-wrap py-5">
								<div class="col-sm-4 bwspattern-form-block bwspattern-required bws_bkng_filter_datetimepicker">
									<label><?php _e( 'From', 'rent-a-bike' ); ?></label>
									<div class="bwspattern-icon-holder bwspattern-from">
										<input id="bwspattern-from" class="bws_bkng_datepicker bwspattern-ghost-input pr-5" data-display-time="hide" name="bws_bkng_search[from]" value="<?php echo ! empty( $options ) ? esc_attr( $options['from'] ) : '-Date-'; ?>" maxlength="255" onfocus="blur();" required="required"/>
									</div>
								</div><!-- .bwspattern-form-block -->
								<div class="col-sm-4 bwspattern-form-block bwspattern-required bws_bkng_filter_datetimepicker">
									<label><?php _e( 'To', 'rent-a-bike' ); ?></label>
									<div class="bwspattern-icon-holder bwspattern-to">
										<input id="bwspattern-to" class="bws_bkng_datepicker bwspattern-ghost-input pr-5" data-display-time="hide" name="bws_bkng_search[till]" value="<?php echo ! empty( $options ) ? esc_attr( $options['to'] ) : '-Date-'; ?>" maxlength="255" onfocus="blur();" required="required"/>
									</div>
								</div><!-- .bwspattern-form-block -->
								<?php if ( isset( $location ) ) { ?>
									<div class="col-sm-4 bwspattern-form-block bwspattern-required">
										<label><?php _e( 'Take bike in', 'rent-a-bike' ); ?></label>
										<input type="text" readonly="readonly" class="bwspattern-ghost-input" name="options[location]" value="<?php echo esc_attr( $location ); ?>" maxlength="255" />
									</div><!-- .bwspattern-form-block -->
								<?php } ?>
								<?php if ( isset( $attribute_list['size']['value'] ) ) { ?>
									<div class="col-sm-4 bwspattern-form-block bwspattern-required">
										<label><?php _e( 'Bike size', 'rent-a-bike' ); ?></label>
										<?php if ( count( $attribute_list['size']['value'] ) > 1 ) { ?>
											<select name="options[<?php echo esc_attr( $attribute_list['size']['label'] ); ?>]" id="bwspattern-size" class="bwspattern-select-dark" required="required">
												<option value=""><?php _e( '-Size-', 'rent-a-bike' ); ?></option>
												<?php foreach ( $attribute_list['size']['value'] as $size ) { ?>
													<option value="<?php echo esc_attr( $size ); ?>" <?php if ( isset( $options['Size'] ) ) selected( $options['Size'], $size ); ?>><?php echo esc_html( $size ); ?></option>
												<?php } ?>
											</select>
										<?php } else { ?>
											<input type="text" readonly class="bwspattern-ghost-input" name="options[size]" value="<?php echo esc_attr( $attribute_list['size']['value'][0] ); ?>" maxlength="255" />
										<?php } ?>
									</div><!-- .bwspattern-form-block -->
									<?php unset( $attribute_list['size'] ); ?>
								<?php } ?>
								<?php if ( isset( $attribute_list['pedals_type']['value'] ) ) { ?>
									<div class="col-sm-4 bwspattern-form-block">
										<label><?php _e( 'Pedals type', 'rent-a-bike' ); ?></label>
										<select name="options[<?php echo esc_attr( $attribute_list['pedals_type']['label'] ); ?>]" id="bwspattern-pedals" class="bwspattern-select-dark">
											<option value=""><?php _e( '-Type-', 'rent-a-bike' ); ?></option>
			                                <?php foreach ( $attribute_list['pedals_type']['value'] as $pedal ) { ?>
			                                    <option value="<?php echo esc_attr( $pedal ); ?>" <?php if ( isset( $options['Pedals Type'] ) ) selected( $options['Pedals Type'], $pedal ); ?>><?php echo esc_html( $pedal ); ?></option>
			                                <?php } ?>
										</select>
									</div><!-- .bwspattern-form-block -->
									<?php unset( $attribute_list['pedals_type'] ); ?>
								<?php } ?>

								<?php if ( $attribute_list ) { ?>
									<?php foreach ( $attribute_list as $attr ) { ?>
										<div class="col-sm-4 bwspattern-form-block">
											<label><?php echo esc_html( $attr['label'] ); ?></label>
											<?php if ( count( $attr['value'] ) > 1 ) { ?>
												<select name="options[<?php echo esc_attr( $attr['label'] ); ?>]" class="bwspattern-select-dark">
													<option value=""><?php echo esc_html( $attr['label'] ); ?></option>
													<?php foreach ( $attr['value'] as $val ) { ?>
														<option value="<?php echo esc_attr( $val ); ?>" <?php if ( isset( $options[ $attr['label'] ] ) ) selected( $options[ $attr['label'] ], $val ); ?>><?php echo esc_html( $val ); ?></option>
													<?php } ?>
												</select>
											<?php } else { ?>
												<input type="text" readonly="readonly" class="bwspattern-ghost-input" name="options[<?php echo esc_attr( $attr['label'] ); ?>]" value="<?php echo esc_attr( $attr['value'][0] ); ?>" maxlength="255" />
											<?php } ?>
										</div><!-- .bwspattern-form-block -->
									<?php } ?>
								<?php } ?>
							</div><!-- .bwspattern-options-form -->
							<noscript>
								<style>
								input[type="checkbox"], input[type="radio"] {
									opacity: 100;
								}
								</style>
							</noscript>
                            <div class="bwspattern-extras">
                                <?php if ( ! empty( $extras ) ) { ?>
		                            <div id="bws_bkng_extras" class="bws_bkng_products_list" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'checkout' ) ); ?>" >
				                            <div class="bws_bkng_extras_list_wrap bws_bkng_extras_">
					                            <h4 class="mb-4"><?php _e( 'EXTRA PRODUCTS', 'rent-a-bike' ); ?></h4>
					                            <div class="bws_bkng_extras_list">
                                                    <?php foreach ( $extras as $post ) {
                                                        setup_postdata( $post );
                                                        $is_countable = $bws_booking_adapter->is_countable( $post->post_type, $post->ID );
                                                        $max_quantity = 0;
                                                        if ( $is_countable ) {
                                                            $max_quantity = $bws_booking_adapter->max_quantity( $post->post_type, $post->ID );
                                                            if ( $max_quantity < 1 )
                                                                continue;
                                                        } ?>
							                            <div class="row d-flex bws_bkng_extra" id="bws_bkng_extra_<?php echo esc_attr( $post->ID ); ?>">
								                            <div class="col-sm-4 col-12">
                                                                <?php the_post_thumbnail( 'catalog_image_size' ); ?>
								                            </div><!-- . -->
								                            <div class="col-sm-8 col-12">
									                            <h3 class="bws_bkng_product_title">
                                                                    <?php the_title(); ?>
									                            </h3>
                                                                <div class="d-flex justify-content-between align-items-center">
	                                                                <div>
                                                                        <?php echo bws_bkng_get_currency(); ?> <span class="bwspattern-price"><?php echo esc_html( bws_bkng_get_product_price() ); ?></span>
	                                                                </div>
	                                                                <div class="d-flex align-items-center">
                                                                        <?php if ( $max_quantity > 1 ) { ?>
			                                                                <input class="bwspattern-ghost-input" type="number" value="0" name="bkng_extras[<?php echo esc_attr( $post->ID ); ?>][quantity]" min="0" max="<?php echo esc_attr( $max_quantity ); ?>" step="1" form="bws_bkng_to_checkout_form" />
                                                                        <?php } ?>
		                                                                <label class="ml-4 mb-0 <?php if ( isset( $order['products'][ absint( $post->ID ) ] ) ) echo ' checked'; ?>">
			                                                                <input
				                                                                type="checkbox"
				                                                                value="1"
				                                                                name="bkng_extras[<?php echo esc_attr( $post->ID ); ?>][choose]"
				                                                                form="bws_bkng_to_checkout_form"<?php if ( isset( $order['products'][ absint( $post->ID ) ] ) ) echo ' checked="checked"'; ?>
				                                                                data-price="<?php echo esc_attr( bws_bkng_get_product_price() ); ?>"
				                                                                data-rent-step="<?php echo esc_attr( bws_bkng_get_rent_interval_step() ); ?>"
				                                                                data-show-price="<?php echo esc_attr( intval( bws_bkng_show_product_price() ) ); ?>"
																				<?php ! empty( $order ) ? checked( isset( $order['products'][ absint( $post->ID ) ] ), true ) : ''; ?>
			                                                                />
		                                                                </label>
	                                                                </div><!-- .-flex align-items-center -->
                                                                </div><!-- .d-flex justify-content-between align-items-center -->
								                            </div><!-- .col-sm-8 col-12 -->
							                            </div><!-- .bws_bkng_product -->
                                                    <?php wp_reset_postdata(); } ?>
					                            </div><!-- .bws_bkng_extras_list -->
				                            </div><!-- .bws_bkng_extras_list_wrap -->
		                            </div><!-- .bws_bkng_products_list -->
                                <?php } ?>
                            </div><!-- .bwspattern-extras -->
						</form>
						<div class="col-12 d-lg-flex text-center justify-content-between mt-5">
							<input type="submit" onclick="history.back(-1); return false;" value="<?php _e( 'Back', 'rent-a-bike' ); ?>">
							<div class="bkng_checkout_product_js">
								<input class="my-lg-0 mt-2" type="submit" <?php echo empty( $order ) ? 'disabled="disabled"' : ''; ?>  name="bkng_checkout_product" form="bws_bkng_to_checkout_form" value="<?php _e( 'Checkout', 'rent-a-bike' ); ?>">
							</div>
							<div class="bkng_checkout_product_no_js">
								<input class="my-lg-0 mt-2" type="submit" name="bkng_checkout_product" form="bws_bkng_to_checkout_form" value="<?php _e( 'Checkout', 'rent-a-bike' ); ?>">
							</div>
							<input type="hidden" name="bkng_product" form="bws_bkng_to_checkout_form" value="<?php echo esc_attr( absint( $post->ID ) ); ?>" />
							<input type="hidden" name="bkng_nonce" form="bws_bkng_to_checkout_form" value="<?php echo wp_create_nonce( "bkng_checkout_product" ); ?>" />
							<input type="hidden" name="bkng_product_rent_interval_step" form="bws_bkng_to_checkout_form" value="<?php echo esc_attr( bws_bkng_get_rent_interval_step() ); ?>" />
						</div><!-- .d-flex justify-content-between -->
					</div><!-- .row d-flex justify-content-between -->
				</div><!-- .bws_bkng_product_content -->
			</div><!--  bws_bkng_get_product_classes() -->
		</div>
	</div>
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>
