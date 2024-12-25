<?php get_header(); ?>

<div class="container">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();

            // Display post content
            the_content();

            // Author info section
            echo '<div class="user-info">';
            echo '<img src="' . esc_url(get_option('whitelabel_24_options')['avatar']) . '" alt="User Avatar" class="user-avatar">';
            echo '<h2>' . esc_html(get_option('whitelabel_24_options')['title']) . '</h2>';
            echo '<p class="bio">' . esc_html(get_option('whitelabel_24_options')['bio']) . '</p>';
            echo '</div>';

        endwhile;
    else :
        echo '<p>No posts found.</p>';
    endif;
    ?>


<div class="connect">
	    For Business Inquiries | <a href="<?php echo 'mailto:'.esc_html(get_option('whitelabel_24_options')['email'] ?? './wp-admin'); ?>">
<?php echo esc_html(get_option('whitelabel_24_options')['email'] ?? 'Set in wp-admin'); ?>
</a>
        </div>


</div>
    
<div class="container">
<?php get_footer(); ?> 




