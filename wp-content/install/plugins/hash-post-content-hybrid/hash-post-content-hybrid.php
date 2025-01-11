<?php
/**
 * Plugin Name: Hash Post Content (Hybrid)
 * Description: Appends a SHA3-512 hash of the entire post content (including HTML tags) after the .the-content element using JavaScript, with Base64 encoding and trimming.
 * Version: 1.0
 * Author: Your Name
 */

// Hook into 'the_content' filter to modify post content with high priority
function hash_post_content($content) {
    // Only modify the content for single post pages
    if (is_single() && is_main_query()) {
        // Trim the content to remove any leading/trailing whitespace and generate SHA3-512 hash
        $content = trim($content);
        
        // Generate SHA3-512 hash of the content
        $hash = hash('sha3-256', $content); // The third parameter `true` returns raw binary data
        
        
        // Pass the Base64-encoded hash to JavaScript by adding a data attribute to the body
        add_filter('wp_footer', function() use ($hash) {
            echo '<script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        var contentElement = document.querySelector(".the-content");
                        if (contentElement) {
                            var hashDiv = document.createElement("div");
                            hashDiv.classList.add("content-hash-sha3-256");
                            hashDiv.innerHTML = "' . esc_js($hash) . '";
                            contentElement.parentNode.insertBefore(hashDiv, contentElement.nextSibling);
                        }
                    });
                  </script>';
        });
    }

    return $content;
}

// Add the filter with high priority (ensuring it's the last one to run)
add_filter('the_content', 'hash_post_content', 9999);
