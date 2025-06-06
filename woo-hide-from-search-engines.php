<?php
/*
Plugin Name: Woo Hide From Search Engines
Description: Adds a noindex meta tag to selected WooCommerce products categories to prevent them from being indexed by search engines.
Plugin URI: https://github.com/dataforge/woo-hide-from-search-engines
Version: 1.10
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

    // Tab logic
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

    // Handle General tab (category selection)
    if ($active_tab === 'general') {
        // Save settings
        if (isset($_POST['whfse_save_settings'])) {
            $selected = isset($_POST['whfse_category_ids']) ? array_map('intval', $_POST['whfse_category_ids']) : array();
            update_option('whfse_selected_category_ids', $selected);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    }

    // Handle Plugin Updates tab
    $update_msg = '';
    if ($active_tab === 'updates') {
        if (isset($_POST['woo_inv_to_rs_check_update']) && check_admin_referer('woo_inv_to_rs_settings_nonce', 'woo_inv_to_rs_settings_nonce')) {
            // Simulate the cron event for plugin update check
            do_action('wp_update_plugins');
            if (function_exists('wp_clean_plugins_cache')) {
                wp_clean_plugins_cache(true);
            }
            // Remove the update_plugins transient to force a check
            delete_site_transient('update_plugins');
            // Call the update check directly as well
            if (function_exists('wp_update_plugins')) {
                wp_update_plugins();
            }
            // Get update info
            $plugin_file = plugin_basename(__FILE__);
            $update_plugins = get_site_transient('update_plugins');
            if (isset($update_plugins->response) && isset($update_plugins->response[$plugin_file])) {
                $new_version = $update_plugins->response[$plugin_file]->new_version;
                $update_msg = '<div class="updated"><p>Update available: version ' . esc_html($new_version) . '.</p></div>';
            } else {
                $update_msg = '<div class="updated"><p>No update available for this plugin.</p></div>';
            }
        }
    }

    // Tab navigation
    echo '<div class="wrap">';
    echo '<h1>Woo Hide From Search Engines Settings</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=whfse-settings&tab=general" class="nav-tab' . ($active_tab === 'general' ? ' nav-tab-active' : '') . '">General</a>';
    echo '<a href="?page=whfse-settings&tab=updates" class="nav-tab' . ($active_tab === 'updates' ? ' nav-tab-active' : '') . '">Plugin Updates</a>';
    echo '</h2>';

    // General tab content
if ($active_tab === 'general') {
        $selected_category_ids = get_option('whfse_selected_category_ids', array());
        if (!is_array($selected_category_ids)) {
            $selected_category_ids = array();
        }

        // Explanatory text about what the plugin does
        echo '<div style="margin-bottom: 1em; max-width: 700px;">';
        echo '<strong>What does this plugin do?</strong><br>';
        echo 'This plugin helps you prevent selected WooCommerce product categories from being indexed by search engines. ';
        echo 'When you select categories below, any product in those categories (or their variations) will have a <code><meta name="robots" content="noindex"></code> tag added to its page. ';
        echo 'The <strong>noindex</strong> tag tells search engines not to index those product pages, so they will not appear in search engine results.';
        echo '</div>';

        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        echo '<form method="post">';
        echo '<table class="form-table"><tr><th>Select Categories to Hide from Search Engines</th><td>';
        foreach ($categories as $cat) {
            $checked = in_array($cat->term_id, $selected_category_ids) ? 'checked' : '';
            echo '<label><input type="checkbox" name="whfse_category_ids[]" value="' . esc_attr($cat->term_id) . '" ' . $checked . '> ' . esc_html($cat->name) . '</label><br>';
        }
        echo '</td></tr></table>';
        submit_button('Save Settings', 'primary', 'whfse_save_settings');
        echo '</form>';
    }

    // Plugin Updates tab content
    if ($active_tab === 'updates') {
        if (!empty($update_msg)) {
            echo $update_msg;
        }
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('woo_inv_to_rs_settings_nonce', 'woo_inv_to_rs_settings_nonce'); ?>
            <input type="hidden" name="woo_inv_to_rs_check_update" value="1">
            <?php submit_button('Check for Plugin Updates', 'secondary'); ?>
        </form>
        <?php
    }

    echo '</div>';
}
