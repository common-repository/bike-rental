<?php
/**
 * Contains data to be installed during demo-data loading
 * @since    1.0.6
 * @package  Bike Rental
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

$images_path = plugin_dir_path( BWS_BKNG_PATH ) . 'images/demo/';
$categories = array(
    'bike_type' => array(
      'Comfort Bikes',
      'Active Bikes'
    ),
    'extra_type' => array(
	    'Bodyguard',
	    'Accessories',
	    'Services'
    )
);

$attributes = array(
    'bws_bike' => array(
        array(
            'name' => 'Pedals Type',
            'slug'  => 'pedals_type',
            'desc'  => '',
            'type'  => 3,
            'value' => array(
                'Clipless Bike Pedals',
                'Hybrid Pedals',
                'Pedal Toe Clips and Straps',
                'Platform Bike Pedals'
            )
        ),
        array(
            'name' => 'Size',
            'slug'  => 'size',
            'desc'  => '',
            'type'  => 3,
            'value' => array(
                'L',
                'M',
                'S',
                'XL',
                'XS',
                'XXL'
            )
        ),
        array(
            'name' => 'Features',
            'slug'  => 'features',
            'desc'  => '',
            'type'  => 3,
            'value' => array(
                '203 mm travel',
                'Carbon Frame',
                'High Speed',
                'Long Ride',
                'Rock Shox Boxxer'
            )
        ),
	    array(
		    'name' => 'Bike Brand',
		    'slug'  => 'bike_brand',
		    'desc'  => '',
		    'type'  => 5,
		    'value' => array(
			    'BMC',
			    'Eddy Merckx',
			    'Giant',
			    'Pinarello',
			    'Trek'
		    )
	    ),
	    array(
		    'name' => 'Intended For',
		    'slug'  => 'intended_for',
		    'desc'  => '',
		    'type'  => 5,
		    'value' => array(
			    'Male',
			    'Female',
			    'Kid'
		    )
	    )
    )
);

$posts = array(
    'bws_bike' => array(
        /*************/
        /** ACTIVE BIKES **/
        /*************/
        array(
	        'post_name'      => 'demo_mountain_bike',
            'post_title'     => 'DEMO Mountain Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Mountain-Bike.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
                	'BMC'
                ),
            ),
            'general' => array(
                'price'	       => 50,
                'quantity'	   => 1,
	            'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_mountain_bike_2',
            'post_title'     => 'DEMO Mountain Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Mountain-Bike-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
	            'intended_for'   => array(
	            	'Male'
	            ),
                'bike_brand'     => array(
	                'BMC'
                ),
            ),
            'general' => array(
                'price'	       => 40,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_cross_country_bike',
            'post_title'     => 'DEMO Cross Country Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Cross-Country.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Trek'
                ),
            ),
            'general' => array(
                'price'	       => 35,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_cross_country_bike_2',
            'post_title'     => 'DEMO Cross Country Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Cross-Country-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Trek'
                ),
            ),
            'general' => array(
                'price'	       => 35,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_fitness_bike',
            'post_title'     => 'DEMO Fitness Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Fitness.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Trek'
                ),
            ),
            'general' => array(
                'price'	       => 40,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_fitness_bike_2',
            'post_title'     => 'DEMO Fitness Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'active-bikes/Fitness-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Active Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Female'
                ),
                'bike_brand'     => array(
	                'Giant'
                ),
            ),
            'general' => array(
                'price'	       => 40,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        /*************/
        /** COMFORT BIKES **/
        /*************/
        array(
	        'post_name'      => 'demo_cruiser_bike',
            'post_title'     => 'DEMO Cruiser Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Cruiser.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Female'
                ),
                'bike_brand'     => array(
	                'Giant'
                ),
            ),
            'general' => array(
                'price'	       => 25,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_cruiser_bike_2',
            'post_title'     => 'DEMO Cruiser Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Cruiser-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Female'
                ),
                'bike_brand'     => array(
	                'Giant'
                ),
            ),
            'general' => array(
                'price'	       => 30,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_road_bike',
            'post_title'     => 'DEMO Road Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Road.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Pinarello'
                ),
            ),
            'general' => array(
                'price'	       => 30,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_road_bike_2',
            'post_title'     => 'DEMO Road Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Road-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Pinarello'
                ),
            ),
            'general' => array(
                'price'	       => 35,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_fixed_gear_bike',
	        'post_title'     => 'DEMO Fixed-Gear Bike',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Fixed-Gear.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Female'
                ),
                'bike_brand'     => array(
	                'Pinarello'
                ),
            ),
            'general' => array(
                'price'	       => 35,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
        array(
	        'post_name'      => 'demo_fixed_gear_bike_2',
            'post_title'     => 'DEMO Fixed-Gear Bike 2',
            'post_type'      => 'bws_bike',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
            'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
            'thumbnail'      => $images_path . 'comfort-bikes/Fixed-Gear-2.jpg', /* set it as url in order to load attachment from external sources */
            'terms'          => array(
                'bike_type'      => array( 'Comfort Bikes' ),
            ),
            'attributes' => array(
                'pedals_type'     => array(
	                'Clipless Bike Pedals',
	                'Hybrid Pedals',
	                'Pedal Toe Clips and Straps',
	                'Platform Bike Pedals'
                ),
                'size'            => array(
	                'L',
	                'M',
	                'S',
	                'XL',
	                'XS',
	                'XXL'
                ),
                'features'        => array(
	                '203 mm travel',
	                'Carbon Frame',
	                'High Speed',
	                'Long Ride',
	                'Rock Shox Boxxer'
                ),
                'intended_for'   => array(
                	'Male'
                ),
                'bike_brand'     => array(
	                'Pinarello'
                ),
            ),
            'general' => array(
                'price'	       => 35,
                'quantity'	   => 1,
                'statuses'     => 'available'
            ),
        ),
    ),
	'bws_extra' =>  array(
		array(
			'post_name'      => 'helmet',
			'post_title'     => 'Helmet',
			'post_type'      => 'bws_extra',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
			'thumbnail'      => $images_path . 'extras/helmet.jpg', /* set it as url in order to load attachment from external sources */
			'terms'          => array(
				'extra_type'      => array( 'Bodyguard' )
			),
			'general' => array(
				'price'	       => 20,
				'quantity'	   => 10,
				'statuses'     => 'available'
			),
			'post_meta' => array(
				'products_connection' => array(
					'related_post_type' => 'bws_bike',
					'product_slug' => array(
						'demo_mountain_bike',
						'demo_mountain_bike_2',
						'demo_cross_country_bike',
						'demo_cross_country_bike_2',
						'demo_fitness_bike',
						'demo_fitness_bike_2',
						'demo_cruiser_bike',
						'demo_cruiser_bike_2',
						'demo_road_bike',
						'demo_road_bike_2',
						'demo_fixed_gear_bike',
						'demo_fixed_gear_bike_2'
					)
				)
			),
		),
		array(
			'post_name'      => 'action_camera',
			'post_title'     => 'Action Camera',
			'post_type'      => 'bws_extra',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_content'   => 'Curabitur a nisi enim. Nulla sit amet condimentum magna. Aliquam nec vulputate lorem. Pellentesque non hendrerit mi, at dictum velit. Maecenas venenatis id turpis eget interdum. Nunc quis tortor semper, porta ante quis, eleifend nisl. Sed malesuada commodo neque, vehicula mollis est varius feugiat. Pellentesque at nulla aliquet, ultrices turpis ut, porttitor libero. Cras dapibus et ante a elementum. Aliquam erat volutpat. Donec id molestie odio. Etiam iaculis feugiat ipsum, lobortis ultrices augue porttitor fringilla. Suspendisse convallis, nibh quis posuere aliquet, velit nulla semper nulla, ut iaculis mi odio eu mi. Sed ullamcorper lorem orci, vitae lacinia ipsum fringilla et. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris ultricies nisl ac dui euismod dapibus.',
			'thumbnail'      => $images_path . 'extras/action-camera.jpg', /* set it as url in order to load attachment from external sources */
			'terms'          => array(
				'extra_type'      => array( 'Accessories' )
			),
			'general' => array(
				'price'	       => 20,
				'quantity'	   => 10,
				'statuses'     => 'available'
			),
			'post_meta' => array(
				'products_connection' => array(
					'related_post_type' => 'bws_bike',
					'product_slug' => array(
						'demo_mountain_bike',
						'demo_mountain_bike_2',
						'demo_cross_country_bike',
						'demo_cross_country_bike_2',
						'demo_fitness_bike',
						'demo_fitness_bike_2',
						'demo_cruiser_bike',
						'demo_cruiser_bike_2',
						'demo_road_bike',
						'demo_road_bike_2',
						'demo_fixed_gear_bike',
						'demo_fixed_gear_bike_2'
					)
				)
			),
		)
	)
);

$locations =  array(
    'bws_bike' => array(
        array(
            'location_name' => 'Location 1',
            'location_address' => 'Prospect Park Southwest',
            'location_latitude' => '40.660408',
            'location_longitude' => '-73.978737'
        ),
        array(
            'location_name' => 'Location 2',
            'location_address' => '15 Wolcott St',
            'location_latitude' => '40.675582',
            'location_longitude' => '-74.010370'
        )
    )
);

$pages = array(
	'products_page'     => array( 'slug' => 'products',  'title' => __( 'Bikes', BWS_BKNG_TEXT_DOMAIN ) ),
	'checkout_page'     => array( 'slug' => 'checkout',  'title' => __( 'Checkout', BWS_BKNG_TEXT_DOMAIN ) ),
	'thank_you_page'    => array( 'slug' => 'thank_you', 'title' => __( 'Thank you', BWS_BKNG_TEXT_DOMAIN ) ),
	'cart_page'         => array( 'slug' => 'checkout',  'title' => __( 'Checkout', BWS_BKNG_TEXT_DOMAIN ) ),
);

return array( 'categories' => $categories , 'attributes' => $attributes, 'posts' => $posts, 'locations' => $locations, 'pages' => $pages );
