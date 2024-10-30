<?php
/**
 * The template for displaying search results pages
 *
 * @package WordPress
 * @subpackage Rent_a_Bike
 * @since 1.0.0
 */
get_header(); ?>
<div class="bwspattern-content-wrapper bwspattern-transparent-header">
    <div class="bwspattern-content-section bwspattern-search-results-section">
        <div class="bwspattern-section-background"></div>
        <div class="container">
            <div class="row">
                <div class="col-12 bwspattern-main-content">
                    <?php
                    global $wp_query;
                    $big = 999999999;
                    $args = array(
                        'base'						=> str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'					=> '?paged=%#%',
                        'total'						=> $wp_query->max_num_pages,
                        'current'					=> max( 1, $wp_query->query_vars['paged'] ),
                        'show_all'					=> false,
                        'end_size'					=> 1,
                        'mid_size'					=> 1,
                        'prev_next'					=> true,
                        'prev_text'					=> '<i class="bws-i-arrow-left-circle"></i>',
                        'next_text'					=> '<i class="bws-i-arrow-right-circle"></i>',
                        'type'						=> 'plain',
                        'add_args'					=> false,
                        'add_fragment'				=> '',
                        'before_page_number'	    => '',
                        'after_page_number'		    => '',
                    );
                    if ( class_exists( 'BWS_BKNG_List_Categories' ) ) {
                        $categories_class = BWS_BKNG_List_Categories::get_instance();
                        $categories = $categories_class->get_all_categories();
                    }
                    ?>
                    <div class="d-lg-flex justify-content-lg-end mb-4">
                       <?php if ( ! empty( $categories ) ) { ?>
                            <div class="col-lg-9 col-12 p-0 text-lg-left text-center bwspattern-categories" search-keyword="<?php echo esc_attr( wp_unslash( $_GET['s'] ) ); ?>" paged="<?php echo esc_attr( $wp_query->query_vars['paged'] ); ?>">
                                <button type="button" class="bwspattern-category-button bwspattern-category-active">All</button>
                                <?php
                                foreach ( $categories as $category ) { ?>
                                    <button type="button" class="bwspattern-category-button"><?php echo esc_html( $category->name ); ?></button>
                                <?php } ?>
                            </div>
                       <?php } ?>
                        <div class="col-lg-3 col-12 p-0 my-lg-0 mt-3 text-lg-right text-center bwspattern-paginate-wrapper">
                            <div class="nav-links bwspattern-paginate">
                                <?php echo paginate_links( $args ); ?>
                            </div><!-- .bwspattern-paginate -->
                        </div><!-- .bwspattern-paginate-wrapper -->
                    </div><!-- .d-flex justify-content-between -->
                    <div class="row bwspattern-mx-n1 bwspattern-search-results">
                        <?php if ( have_posts() ) {
                            while ( have_posts() ) {
                                the_post();
                                $title = get_the_title();
								$product_terms = get_the_terms( get_the_ID(), 'bike_type' );
								$post_permalink = get_post_permalink( get_the_id() );
								?>
								<div class="col-lg-3 col-12 px-1 my-lg-1 my-2 bwspattern-post-content-wrapper <?php echo $product_terms ? 'bkng_product' : '' ?>">
									<div class="bwspattern-post-thumbnail">
										<?php
										if ( has_post_thumbnail() ) {
											the_post_thumbnail( 'large' );
										} else {
											echo get_default_image();
										}
										?>
										<div class="bwspattern-post-thumbnail-blur"></div>
										<div class="bwspattern-post-thumbnail-blur-overlay"></div>
										<div class="bwspattern-post-thumbnail-corner"></div>
									</div>
									<div class="postmetadata">
									</div><!-- .postmetadata bwspattern-title-top -->
									<div class="bwspattern-product-type">
										<?php if ( $product_terms ) {
											echo esc_html( $product_terms[0]->name );
										} ?>
									</div>
									<?php if( '' != $title ) { ?>
										<h3 class="bwspattern-post-title">
											<?php echo esc_html( $title ); ?>
										</h3>
									<?php } ?>
									<?php if ( $product_terms ) { ?>
										<div class="bwspattern-hover-info d-flex flex-column">
											<div class="bwspattern-product-type">
												<?php echo esc_html( $product_terms[0]->name ); ?>
											</div>
											<?php
											$prices = bws_bkng_product_conditional_prices( get_the_ID() );
											$interval = bws_bkng_get_rent_interval();

											if ( is_array( $prices ) ) {
												/* check if array is multidimensional ( prices per interval ) */
												if ( count( $prices ) !== count( $prices, COUNT_RECURSIVE ) ) {
													?>
													<div class="bwspattern-hover-prices">
													<?php
														/* add price for one day if 1 day is not included in intervals */
														if ( $prices[ key( $prices ) ]['day_from'] > 1 ) {
															array_unshift( $prices, array(
																'day_from'  => '',
																'day_to'    => '1',
																'price'     => bws_bkng_get_product_price()
															) );
														}
														foreach ( $prices as $arr ) {
															if ( empty( $arr['day_to'] ) ) {
																$count = (int)$arr['day_from'];
																$label = sprintf( _n( 'From %d day', 'From %d days', $count, 'rent-a-bike' ), $arr['day_from'] );
															} elseif ( empty( $arr['day_from'] ) ) {
																$label = sprintf( __( '%d day', 'rent-a-bike' ), $arr['day_to'] );
															} else {
																$label = sprintf( __( '%d-%d days', 'rent-a-bike' ), $arr['day_from'], $arr['day_to'] );
															}
															$price = bws_bkng_price_format( $arr['price'] ) . $interval;
															?>
															<a href="<?php the_permalink(); ?>" class="d-flex justify-content-between">
																<?php echo esc_html( $label ); ?>
																<span><?php echo esc_html( $price ); ?></span>
															</a>
															<?php
														}
													?>
													</div>
													<?php
												} else {
													$price = bws_bkng_get_product_price();
													$season = array_search( $price, $prices );
													?>
													<a href="<?php the_permalink(); ?>" class="d-flex justify-content-between">
														<?php printf( __( '%s price', 'rent-a-bike' ), $season ); ?>
														<span><?php echo bws_bkng_price_format( $price ) . $interval; ?></span>
													</a>
													<?php
												}
											} else {
												?>
												<a href="<?php the_permalink(); ?>" class="d-flex justify-content-between">
													<?php _e( 'Rent for', 'rent-a-bike' ); ?>
													<span><?php echo bws_bkng_price_format( bws_bkng_get_product_price() ) . $interval; ?></span>
												</a>
												<?php
											}
											?>
											<div class="bwspattern-hover-info-bottom-buttons d-flex mt-auto mb-1">
												<a href="#"><i class="bws-i-settings"></i></a>
												<a href="#"><i class="bws-i-helmet"></i></a>
												<a href="#"><i class="bws-i-go-camera"></i></a>
												<a href="#"><i class="bws-i-delivery-bike"></i></a>
											</div>
											<div class="bwspattern-post-thumbnail-corner bwspattern-post-thumbnail-corner-hover"></div>
										</div><!-- .bwspattern-hover-info -->
									<?php } else { ?>
										<div class="bwspattern-hover-info d-flex flex-column">
											<a href="<?php echo esc_url( $post_permalink ) ?>"><?php echo esc_html( $title ); ?></a>
										</div>
									<?php } ?>
								</div><!-- .bwspattern-post-content-wrapper -->
                            <?php }
                        } ?>
                    </div><!-- .row -->
                </div><!-- .col-12 -->
            </div><!-- .row -->
        </div>
    </div>
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>
