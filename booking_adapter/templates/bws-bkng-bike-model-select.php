<?php
/**
 * Template Name: Bike Model Select
 *
 * @package WordPress
 * @subpackage Rent A Bike
 * @since Rent A Bike 1.0
 */
?>

<?php get_header(); ?>
<div class="bwspattern-content-wrapper bwspattern-search-results-section bwspattern-transparent-header">
	<div class="bwspattern-content-section">
		<div class="bwspattern-section-background"></div>
		<div class="container">
			<div class="row">
				<div class="col-12">
                    <?php
                    $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

                    $price_filter = isset( $_GET['price'] ) ? sanitize_text_field( stripslashes( $_GET['price'] ) ) : false;

                    $type_filter = isset( $_GET['bike_type'] ) ? sanitize_text_field( stripslashes( $_GET['bike_type'] ) ) : false;
                    $intended_filter = isset( $_GET['intended_for'] ) ? sanitize_text_field( stripslashes( $_GET['intended_for'] ) ) : false;
                    $brand_filter = isset( $_GET['bike_brand'] ) ? sanitize_text_field( stripslashes( $_GET['bike_brand'] ) ) : false;
					if( 'default' === $intended_filter ) {
						$intended_filter = false;
					}

					/* Return true if two or more variables are true */
                    $tax_query = array(
                        'relation' => ( $brand_filter ? ( $type_filter || $intended_filter ) : ( $type_filter && $intended_filter ) ) ? 'AND' : 'OR',
                    );

                    if ( $type_filter ) {
                        $tax_query[] = array(
                            'taxonomy' => 'bike_type',
                            'field' => 'slug',
                            'terms' => $type_filter,
                        );
                    }

                    add_filter( 'posts_join', array( $bws_booking_adapter, 'bws_bkng_bws_bike_join' ) );
                    add_filter( 'posts_where', array( $bws_booking_adapter, 'bws_bkng_bws_bike_where' ) );
                    add_filter( 'posts_orderby', array( $bws_booking_adapter, 'bws_bkng_bws_bike_orderby' ) );

                    $the_args = array(
                        'paged' => $paged,
                        'post_status' => 'publish',
                        'post_type' => 'bws_bike',
                        'posts_per_page' => 4,
                        'orderby' => 'date',
                        'order' => $price_filter,
                        'tax_query' => $tax_query,
                    );

                    $the_query = new WP_Query( $the_args );
					$big = 999999999;
                    $args = array(
                        'base'						=> str_replace( $big, '%#%', html_entity_decode( get_pagenum_link( $big ) ) ),
                        'format'					=> '?paged=%#%',
                        'total'						=> $the_query->max_num_pages,
                        'current'					=> max( 1, get_query_var('paged') ),
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
                    remove_filter( 'posts_join', array( $bws_booking_adapter, 'bws_bkng_bws_bike_join' ) );
                    remove_filter( 'posts_where', array( $bws_booking_adapter, 'bws_bkng_bws_bike_where' ) );
                    remove_filter( 'posts_orderby', array( $bws_booking_adapter, 'bws_bkng_bws_bike_orderby' ) );

                    $brands = $bws_booking_adapter->get_attribute_values( 'bws_bike', 'bike_brand' );
                    $intendeds = $bws_booking_adapter->get_attribute_values( 'bws_bike', 'intended_for' );
                    $types = get_terms( 'bike_type' ); ?>
					<div class="row d-lg-flex mb-4">
						<div class="d-lg-flex align-items-center col-lg-9 col-12 text-lg-left text-center bwspattern-filters bwspattern-categories">
							<div class="bwspattern-form-block" taxonomy="price">
								<select id="bwspattern-sort-by-price" class="bwspattern-select-dark">
									<option value=""><?php _e( 'Sort by price', 'rent-a-bike' ); ?></option>
									<option value="ASC" <?php if ( 'ASC' == $price_filter ) echo 'selected="selected"' ?> ><?php _e( 'Lowest first', 'rent-a-bike' ); ?></option>
									<option value="DESC" <?php if ( 'DESC' == $price_filter ) echo 'selected="selected"' ?> ><?php _e( 'Highest first', 'rent-a-bike' ); ?></option>
								</select>
							</div>
							<div class="bwspattern-form-block ml-lg-5 ml-0" taxonomy="bike_brand">
								<select id="bwspattern-brands" class="bwspattern-select-dark">
									<option value="default"><?php _e( 'All brands', 'rent-a-bike' ); ?></option>
									<?php foreach( $brands as $brand ) { ?>
										<option value="<?php echo esc_attr( $brand ); ?>" <?php if ( $brand == $brand_filter ) echo 'selected="selected"' ?> ><?php echo esc_html( $brand ); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="bwspattern-form-block ml-lg-5 ml-0" taxonomy="bike_type">
								<select id="bwspattern-types" class="bwspattern-select-dark">
									<option value="default"><?php _e( 'All types', 'rent-a-bike' ); ?></option>
                                    <?php foreach( $types as $type ) { ?>
										<option value="<?php echo esc_attr( $type->slug ) ?>" <?php if ( $type->slug == $type_filter ) echo 'selected="selected"' ?> ><?php echo esc_html( $type->name ); ?></option>
                                    <?php } ?>
								</select>
							</div>
							<div class="bwspattern-form-block ml-lg-5 ml-0" taxonomy="intended_for">
								<button type="button" value="default" class="bwspattern-category-button <?php if ( ! $intended_filter ) echo 'bwspattern-category-active'; ?>">All</button>
                                <?php
                                if( ! empty( $intendeds ) ) {
	                                foreach ( $intendeds as $intended ) { ?>
                                        <button type="button" value="<?php echo esc_attr( $intended ) ?>"
                                                class="bwspattern-category-button <?php if ( $intended == $intended_filter ) {
			                                        echo 'bwspattern-category-active';
		                                        } ?>"><?php echo esc_html( $intended ); ?></button>
	                                <?php }
                                }?>
							</div>
						</div>
						<div class="d-flex align-items-center col-lg-3 col-12 p-0 my-lg-0 mt-3 text-lg-right text-center bwspattern-paginate-wrapper">
							<div class="col-12 nav-links bwspattern-paginate">
                                <?php echo paginate_links( $args ); ?>
							</div><!-- .bwspattern-paginate -->
						</div><!-- .bwspattern-paginate-wrapper -->
					</div><!-- .d-flex justify-content-between -->
					<div class="row bwspattern-mx-n1 bwspattern-search-results">
                        <?php if ( $the_query->have_posts() ) {
                            while ( $the_query->have_posts() ) {
                                $the_query->the_post();
                                get_template_part( 'templates/search-results-template' );
                            }
                        } ?>
					</div><!-- .row -->
					<div class="d-lg-flex text-center justify-content-between my-5">
						<input type="submit" onclick="history.back(-1); return false;" value="<?php _e( 'Back', 'rent-a-bike' ); ?>">
					</div><!-- .d-flex justify-content-between -->
				</div><!-- .col-12 -->
			</div><!-- .row -->
		</div><!-- .row -->
	</div><!-- .container -->
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>
