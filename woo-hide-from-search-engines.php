<?php
/*
Plugin Name: Woo Hide From Search Engines
Description: Adds a noindex meta tag to WooCommerce products in the "Video" category (category ID 20), including product variations, to prevent them from being indexed by search engines.
Plugin URI: https://github.com/dataforge/woo-hide-from-search-engines
Version: 1.0.0
Author: Dataforge
GitHub Plugin URI: https://github.com/dataforge/woo-hide-from-search-engines
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

            $selected_category_ids = get_option('whfse_selected_category_ids', array());
            if (!is_array($selected_category_ids)) {
                $selected_category_ids = array();
            }

            if (array_intersect($selected_category_ids, $category_ids)) {
                echo '<meta name="robots" content="noindex">';
            }

            // Check if it's a product variation
            if ($product && $product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $parent_categories = wp_get_post_terms($parent_id, 'product_cat');
                $parent_category_ids = wp_list_pluck($parent_categories, 'term_id');

                if (array_intersect($selected_category_ids, $parent_category_ids)) {
                    echo '<meta name="robots" content="noindex">';
                }
            }
        }
    }
    add_action('wp_head', 'whfse_add_noindex_meta_tag_for_video_category');
}

 // Admin settings page for selecting categories
add_action('admin_menu', 'whfse_add_settings_page');
function whfse_add_settings_page() {
    // Add submenu under WooCommerce menu
    add_submenu_page(
        'woocommerce',
        'Woo Hide From Search Engines', // Page title
        'Woo Hide From Search Engines', // Menu title
        'manage_options',
        'whfse-settings',
        'whfse_render_settings_page'
    );
}

function whfse_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['whfse_save_settings'])) {
        $selected = isset($_POST['whfse_category_ids']) ? array_map('intval', $_POST['whfse_category_ids']) : array();
        update_option('whfse_selected_category_ids', $selected);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $selected_category_ids = get_option('whfse_selected_category_ids', array());
    if (!is_array($selected_category_ids)) {
        $selected_category_ids = array();
    }

    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));

    echo '<div class="wrap">';
    echo '<h1>Woo Hide From Search Engines Settings</h1>';
    echo '<form method="post">';
    echo '<table class="form-table"><tr><th>Select Categories to Hide from Search Engines</th><td>';
    foreach ($categories as $cat) {
        $checked = in_array($cat->term_id, $selected_category_ids) ? 'checked' : '';
        echo '<label><input type="checkbox" name="whfse_category_ids[]" value="' . esc_attr($cat->term_id) . '" ' . $checked . '> ' . esc_html($cat->name) . '</label><br>';
    }
    echo '</td></tr></table>';
    submit_button('Save Settings', 'primary', 'whfse_save_settings');
    echo '</form></div>';
}
