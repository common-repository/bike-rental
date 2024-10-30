<?php
/**
 * Template Name: Bike Activity Select
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
                <div class="col-12 bwspattern-main-content">
                    <?php $categories = bws_bkng_get_categories();
                    $limit = 3;
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                    $big = 999999999;
                    $args = array(
                        'base'						=> str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'					=> '?paged=%#%',
                        'total'						=> ceil( count( $categories ) / $limit ),
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
                    ); ?>
	                <div class="d-lg-flex mb-4">
		                <div class="col-12 p-0 my-lg-0 mt-3 text-lg-right text-center bwspattern-paginate-wrapper">
			                <div class="nav-links bwspattern-paginate">
                                <?php echo paginate_links( $args ); ?>
			                </div><!-- .bwspattern-paginate -->
		                </div><!-- .bwspattern-paginate-wrapper -->
	                </div><!-- .d-flex justify-content-between -->
                    <div class="row bwspattern-mx-n1 bwspattern-search-results">
                        <?php if ( $categories ) {
                            foreach ( array_slice( $categories, ( $paged - 1 ) * $limit, $limit ) as $category ) {

                                $posts_in_category = get_posts(
                                    array(
                                        'post_type' => 'bws_bkng_products',
                                        'fields' => 'ids',
                                        'numberposts' => -1,
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'bws_bkng_categories',
                                                'field' => 'id',
                                                'terms' => $category->term_id,
                                                'include_children' => false
                                            )
                                        )
                                    )
                                );

                                $bike_types_in_category = wp_get_object_terms ( $posts_in_category, 'bkng_type' );
                                $lowest_price = 999999999;
                                foreach ( $posts_in_category as $id ) {
                                    $price = bws_bkng_get_product_price( $id );
                                    if ( $price < $lowest_price ) {
                                        $lowest_price = $price;
                                    }
                                } ?>
                                <div class="col-lg-4 col-12 px-1 my-lg-0 my-2 bwspattern-post-content-wrapper bwspattern-three-post">
                                    <?php if( has_post_thumbnail( $posts_in_category[0] ) ) { ?>
                                        <div class="bwspattern-post-thumbnail">
                                            <?php echo get_the_post_thumbnail( $posts_in_category[0],'large' ); ?>
                                            <div class="bwspattern-post-thumbnail-blur"></div>
                                            <div class="bwspattern-post-thumbnail-blur-overlay"></div>
                                            <div class="bwspattern-post-thumbnail-corner"></div>
                                        </div>
                                    <?php } ?>
                                    <div class="postmetadata">
                                    </div><!-- .postmetadata bwspattern-title-top -->
                                    <h3 class="bwspattern-post-title">
                                        <?php echo esc_html( $category->name ); ?>
                                    </h3>
                                    <div class="bwspattern-hover-info">
                                        <h3 class="bwspattern-product-type">
                                            <?php echo esc_html( $category->name ); ?>
                                        </h3>
                                        <?php if ( $bike_types_in_category ) {
                                            foreach ( $bike_types_in_category as $bike_type ) { ?>
                                                <a href="#" class="d-flex justify-content-between">
                                                    <?php echo esc_html( $bike_type->name ); ?>
                                                    <span><?php echo __('from', BWS_BKNG_TEXT_DOMAIN) . ' ' . bws_bkng_get_currency() . ' ' . esc_html( 999999 ); ?></span>
                                                </a>
                                            <?php }
                                        } ?>
                                        <div class="bwspattern-hover-info-bottom-buttons">
                                            <a href="#"><i class="bws-i-settings"></i></a>
                                            <a href="#"><i class="bws-i-helmet"></i></a>
                                            <a href="#"><i class="bws-i-go-camera"></i></a>
                                            <a href="#"><i class="bws-i-delivery-bike"></i></a>
                                        </div>
                                        <div class="bwspattern-post-thumbnail-corner bwspattern-post-thumbnail-corner-hover"></div>
                                    </div><!-- .bwspattern-hover-info -->
                                </div><!-- .bwspattern-post-content-wrapper -->
                            <?php }
                        } ?>
                    </div><!-- .row -->
                    <div class="d-lg-flex text-center justify-content-between my-5">
                        <input type="submit" value="<?php _e( 'Simple select', 'rent-a-bike' ); ?>">
<!--                        <input class="my-lg-0 mt-2" type="submit" value="--><?php //_e( 'View tours', 'rent-a-bike' ); ?><!--">-->
                    </div><!-- .d-flex justify-content-between -->
                </div><!-- .col-12 -->
            </div><!-- .row -->
        </div><!-- .row -->
    </div><!-- .container -->
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>
