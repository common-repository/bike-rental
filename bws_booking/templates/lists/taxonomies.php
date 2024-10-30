<?php
/**
 * Displays the list of links еo which the product is bind
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$category = bws_bkng_get_product_category();
$tags     = bws_bkng_get_product_tags();

do_action( 'bws_bkkng_before_product_links' ); ?>

<?php if ( $category || $tags ) {?>

<div class="bws_bkng_post_taxonomies">

	<?php if ( $category ) { ?>

		<div class="bws_bkng_meta_row bws_bkng_product_category">
			<span class="dashicons dashicons-category" title="<?php _e( 'Category', BWS_BKNG_TEXT_DOMAIN ); ?>"></span>
			<a class="bws_bkng_category_link" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		</div><!-- .bws_bkng_meta_row -->

	<?php }

	if ( $tags ) { ?>

		<div class="bws_bkng_meta_row bws_bkng_product_tags">
			<span class="dashicons dashicons-tag" title="<?php _e( 'Tags', BWS_BKNG_TEXT_DOMAIN  ); ?>"></span>
			<?php foreach( $tags as $tag ) { ?>
				<a class="bws_bkng_tag_link" href="<?php echo esc_url( get_term_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
			<?php } ?>
		</div><!-- .bws_bkng_meta_row -->

	<?php } ?>

</div><!-- .bws_bkng_post_meta -->
			<?php }?>
<?php do_action( 'bws_bkkng_after_product_links' );