<?php
/*
Plugin Name: Markdown Importer
Description: A simple plugin to import Markdown content as a WLP post.
Version: 1.2
Author: Neil
*/

defined('ABSPATH') or die('No script kiddies please!');

// Include Parsedown
require_once plugin_dir_path(__FILE__) . 'parsedown/Parsedown.php';

function markdown_importer_menu() {
    add_menu_page('Markdown Importer', 
    'Markdown Importer', 
    'manage_options', 
    'markdown-importer', 
    'markdown_importer_page','dashicons-admin-post');
}

add_action('admin_menu', 'markdown_importer_menu');

function markdown_importer_page() {
    if (isset($_POST['submit'])) {
        $title = sanitize_text_field($_POST['title']);
        $markdown = wp_unslash($_POST['markdown']); // Get the raw Markdown content

        // Use Parsedown to convert Markdown to HTML
        $parsedown = new Parsedown();
        $content = $parsedown->text($markdown);

        // Create a new post
        $post_data = array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_category' => array(1) // Default category
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            echo "<div class='updated'><p>Post created successfully! <a href='" . get_permalink($post_id) . "' target='_blank'>View Post</a></p></div>";
        } else {
            echo "<div class='error'><p>Error creating post.</p></div>";
        }
    }

    ?>
    <div class="wrap">
        <h1>Markdown Importer</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input type="text" name="title" required /></td>
                </tr>
                <tr>
                    <th><label for="markdown">Markdown Content</label></th>
                    <td><textarea name="markdown" rows="10" cols="50" required></textarea></td>
                </tr>
            </table>
            <input type="submit" name="submit" class="button button-primary" value="Create Post" />
        </form>
    </div>
    <?php
}

