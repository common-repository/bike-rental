<?php get_header();
the_post(); ?>
    <div class="bwspattern-content-wrapper">
        <div class="bwspattern-content-section">
            <div class="container">
                <div <?php echo bws_bkng_get_product_classes(); ?>>
                    <?php $filter = BWS_BKNG_Content_Filter::get_instance();
                    $content = get_the_content(); ?>
                    <div class="bws_bkng_product_content">
                        <h2 class="bws_bkng_product_title"><span><?php _e( 'Bike', 'rent-a-bike' ); ?>:</span> <?php the_title(); ?></h2>
                        <div class="d-md-flex justify-content-between">
                            <div class="bws_bkng_column bws_bkng_thumbnail_column">
                                <?php the_post_thumbnail( 'bkng_catalog_' . get_post_type() . '_image' ); ?>
                                <div class="bws_bkng_product_gallery"><?php echo $filter->get_template_parts( 'gallery' ); ?></div>
                            </div><!-- .bws_bkng_thumbnail_column -->
                            <div class="bws_bkng_column bws_bkng_content_column">
                                <?php echo ( empty( $content ) ? '' : esc_html( $content ) );
                                echo $filter->get_template_parts( 'lists/taxonomies', 'product-attributes' ); ?>
                                <?php echo $filter->get_template_parts( 'search-results-template' ); ?>
                            </div>
                        </div><!-- .row d-flex justify-content-between -->
                    </div><!-- .bws_bkng_product_content -->
                </div><!--  bws_bkng_get_product_classes() -->
            </div><!-- .container -->
        </div><!-- .bwspattern-content-section -->
    </div><!-- .bwspattern-content-wrapper -->
<?php get_footer(); ?>