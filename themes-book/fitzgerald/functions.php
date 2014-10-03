<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

/* ------------------------------------------------------------------------ *
 * Google Webfonts
 * ------------------------------------------------------------------------ */

function fitzgerald_enqueue_styles() {
	wp_enqueue_style( 'fitzgerald-fonts', '//fonts.googleapis.com/css?family=Crimson+Text:400,400italic,700|Roboto+Condensed:400,300,300italic,400italic' );
}
add_action( 'wp_print_styles', 'fitzgerald_enqueue_styles' );

add_filter("pb_lists_add_numbers_to_list_elements", function(){return true;});
add_filter("pb_lists_add_numbers_to_heading_levels", function(){return 1;});
