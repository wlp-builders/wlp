<?php
/*
Plugin Name: Signatures Post Type with Shortcode
Description: A custom post type for storing signatures with a URL and hash, and a shortcode to display the latest 100 signatures.
Version: 1.0
Author: Your Name
*/

// Create Custom Post Type for Signatures
function create_signature_post_type() {
    register_post_type('signature',
        array(
            'labels' => array(
                'name' => 'Signatures',
                'singular_name' => 'Signature',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Signature',
                'edit_item' => 'Edit Signature',
                'new_item' => 'New Signature',
                'view_item' => 'View Signature',
                'search_items' => 'Search Signatures',
                'not_found' => 'No Signatures found',
                'not_found_in_trash' => 'No Signatures found in Trash',
                'all_items' => 'All Signatures',
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'), // Only support title for simplicity
            'show_in_rest' => false, // Do not use Block Editor
            'menu_icon' => 'dashicons-signature', // Icon for the custom post type
        )
    );
}
add_action('init', 'create_signature_post_type');

// Remove the default editor for the Signatures post type
function remove_signature_editor() {
    remove_post_type_support('signature', 'editor');
}
add_action('init', 'remove_signature_editor');

// Add Meta Box for URL and Hash
function signature_meta_boxes() {
    add_meta_box(
        'signature_details',
        'Signature Details',
        'signature_meta_box_callback',
        'signature',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'signature_meta_boxes');

// Callback function to display fields for URL and Hash
function signature_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('signature_save_meta_box', 'signature_meta_box_nonce');

    // Get current values of custom fields (URL and Hash)
    $url = get_post_meta($post->ID, '_signature_url', true);
    $hash = get_post_meta($post->ID, '_signature_hash', true);

    // Display the input fields for URL and Hash
    echo '<label for="signature_url">URL</label>';
    echo '<input type="url" id="signature_url" name="signature_url" value="' . esc_attr($url) . '" size="25" />';

    echo '<label for="signature_hash">Hash</label>';
    echo '<input type="text" id="signature_hash" name="signature_hash" value="' . esc_attr($hash) . '" size="25" />';
}

// Save the data when the post is saved
function save_signature_meta_box($post_id) {
    if (!isset($_POST['signature_meta_box_nonce'])) {
        return $post_id;
    }

    if (!wp_verify_nonce($_POST['signature_meta_box_nonce'], 'signature_save_meta_box')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (isset($_POST['signature_url'])) {
        update_post_meta($post_id, '_signature_url', sanitize_text_field($_POST['signature_url']));
    }

    if (isset($_POST['signature_hash'])) {
        update_post_meta($post_id, '_signature_hash', sanitize_text_field($_POST['signature_hash']));
    }
}
add_action('save_post', 'save_signature_meta_box');

// Shortcode to display the latest 100 signatures
function display_latest_signatures($atts) {
    // Define default attributes for the shortcode
    $atts = shortcode_atts(
        array(
            'posts_per_page' => 100, // Default to 100 posts
        ),
        $atts,
        'latest_signatures'
    );

    // Query for the latest 100 signatures
    $args = array(
        'post_type' => 'signature',
        'posts_per_page' => $atts['posts_per_page'],
        'order' => 'DESC',
        'orderby' => 'date',
    );

    $signatures_query = new WP_Query($args);
    $output = '<div class="signatures-list">';

    if ($signatures_query->have_posts()) {
        while ($signatures_query->have_posts()) {
            $signatures_query->the_post();
            $url = get_post_meta(get_the_ID(), '_signature_url', true);
            $hash = get_post_meta(get_the_ID(), '_signature_hash', true);

            $output .= '<div class="signature">';
            $output .= '<h3>' . get_the_title() . '</h3>';
            $output .= '<p><strong>URL:</strong> <a href="' . esc_url($url) . '" target="_blank">' . esc_url($url) . '</a></p>';
            $output .= '<p><strong>Hash:</strong> ' . esc_html($hash) . '</p>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p>No signatures found.</p>';
    }

    $output .= '</div>';
    wp_reset_postdata(); // Reset the post data after the custom query

    return $output;
}
add_shortcode('latest_signatures', 'display_latest_signatures');

// Optional: Styling for the signatures display
function signatures_plugin_styles() {
    echo '<style>
        .signatures-list {
            font-family: Arial, sans-serif;
        }
        .signature {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }
        .signature h3 {
            margin: 0;
            font-size: 18px;
        }
        .signature p {
            margin: 5px 0;
        }
    </style>';
}
add_action('wp_head', 'signatures_plugin_styles');
