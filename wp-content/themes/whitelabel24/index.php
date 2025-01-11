<?php
get_header(); 
?>

<div class="container container--home">
    <!-- Header: business card style -->

    <div class="connect">
        For Business Inquiries | <a href="<?php echo 'mailto:'.esc_html(get_option('whitelabel_24_options')['email'] ?? './wp-admin'); ?>">
            <?php echo esc_html(get_option('whitelabel_24_options')['email'] ?? 'Set in wp-admin'); ?>
</a> | <a href="<?php echo get_site_url();?>/.well-known/did.json">DID Document 🛡️</a>
    </div>

    <!-- Latest posts section with customizable title -->
    <section class="latest-posts">
        <h2><?php echo esc_html(get_option('whitelabel_24_options')['work_title'] ?? 'Daily Work & Research'); ?></h2>

        <?php
        $recent_posts = new WP_Query(array('posts_per_page' => 10));
        if ($recent_posts->have_posts()) :
            while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
                <div class="post">
                    <div class="post-date"><?php the_date(); ?></div>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </div>
            <?php endwhile;
            wp_reset_postdata();
        else :
            echo '<p>No recent posts available.</p>';
        endif;
        ?>
    </section>

    <!-- Pagination -->
    <div class="pagination">
        <?php
        previous_posts_link('&laquo;');
        next_posts_link('&raquo;');
        ?>
    </div>

</div>

<?php get_footer(); ?>

