<?php
/**
 * Template Name: Bike Simple Select
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
                    <?php

                    $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

                    $category_filter = isset( $_GET['bws_bkng_categories'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_categories'] ) ) : false;

                    if ( $category_filter ) {
                        $tax_query = array(
                            array(
	                            'taxonomy' => BWS_BKNG_CATEGORIES,
	                            'field' => 'slug',
	                            'terms' => $category_filter,
                            )
                        );
                    } else {
                    	$tax_query = false;
                    }

                    $the_args = array(
                        'paged' => $paged,
                        'post_status' => 'publish',
                        'post_type' => 'bws_bkng_products',
                        'posts_per_page' => 4,
                        'tax_query' => $tax_query,
                    );

                    $the_query = new WP_Query( $the_args );

                    $big = 999999999;
                    $args = array(
                        'base'						=> str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
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
                    ); ?>
                    <div class="d-lg-flex mb-4">
                        <div class="col-lg-9 col-12 p-0 text-lg-left text-center bwspattern-categories" taxonomy="<?php echo esc_attr( BWS_BKNG_CATEGORIES ) ?>">
                            <button type="button" value="default" class="bwspattern-category-button <?php if ( ! $category_filter ) echo 'bwspattern-category-active'; ?>">All</button>
                            <?php
                            $categories = bws_bkng_get_categories();
                            foreach ( $categories as $category ) { ?>
                                <button type="button" value="<?php echo esc_attr( $category->slug ) ?>" class="bwspattern-category-button <?php if ( $category->slug == $category_filter ) echo 'bwspattern-category-active'; ?>"><?php echo esc_html( $category->name ); ?></button>
                            <?php } ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-end col-lg-3 col-12 p-0 my-lg-0 mt-3 text-lg-right text-center bwspattern-paginate-wrapper">
                            <div class="nav-links bwspattern-paginate">
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
                        <input type="submit" value="<?php _e( 'Advanced select', 'rent-a-bike' ); ?>">
                        <input class="my-lg-0 mt-2" type="submit" value="<?php _e( 'View tours', 'rent-a-bike' ); ?>">
                    </div><!-- .d-flex justify-content-between -->
                </div><!-- .col-12 -->
            </div><!-- .row -->
        </div><!-- .row -->
    </div><!-- .container -->
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>