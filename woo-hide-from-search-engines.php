<?php
/*
Plugin Name: Woo Hide From Search Engines
Description: Adds a noindex meta tag to WooCommerce products in the "Video" category (category ID 20), including product variations, to prevent them from being indexed by search engines.
Version: 1.0.0
Author: Dataforge
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('whfse_add_noindex_meta_tag_for_video_category')) {
    function whfse_add_noindex_meta_tag_for_video_category() {
        if (is_product()) {
            global $post;
            $product = wc_get_product($post->ID);
            $categories = wp_get_post_terms($post->ID, 'product_cat');
            $category_ids = wp_list_pluck($categories, 'term_id');

            if (in_array(20, $category_ids)) {
                echo '<meta name="robots" content="noindex">';
            }

            // Check if it's a product variation
            if ($product && $product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $parent_categories = wp_get_post_terms($parent_id, 'product_cat');
                $parent_category_ids = wp_list_pluck($parent_categories, 'term_id');

                if (in_array(20, $parent_category_ids)) {
                    echo '<meta name="robots" content="noindex">';
                }
            }
        }
    }
    add_action('wp_head', 'whfse_add_noindex_meta_tag_for_video_category');
}
