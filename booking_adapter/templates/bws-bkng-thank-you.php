<?php get_header(); ?>
<div class="bwspattern-content-wrapper bwspattern-thank-you-section bwspattern-transparent-header">
    <div class="bwspattern-content-section">
        <div class="bwspattern-section-background"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-8 text-center bwspattern-main-content">
                    <h1><?php the_title(); ?></h1>
                </div><!-- .col-lg-8 -->
            </div><!-- .row -->
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-8 text-center bwspattern-main-content">
                    <div class="bwspattern-thank-you-content">
						<?php the_content(); ?>
                    </div>
                </div><!-- .col-lg-8 -->
            </div><!-- .row -->
            <div class="row justify-content-center">
                <div class="col-xl-4 col-lg-6 col-sm-8 bwspattern-main-content">
                    <div class="btn-toolbar justify-content-lg-around justify-content-around">
                        <a href="<?php echo esc_url( get_home_url() ) ?>"><input type="submit" class="button button-primary" value="Home page" /></a>
                    </div><!-- .btn-toolbar -->
                </div><!-- .col-xl-4 -->
            </div><!-- .row -->
        </div><!-- .container -->
    </div><!-- .bwspattern-content-section -->
</div><!-- .bwspattern-content-wrapper -->
<div class="bwspattern-prefooter-block"></div>
<?php get_footer( 'short' ); ?>
