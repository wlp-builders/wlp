<?php get_header(); ?>

<div class="container">
    <!-- Page content -->
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            ?>
            <div class="page-content">
                <?php the_content(); ?>
            </div>
        <?php
        endwhile;
    else :
        echo '<p>No content found</p>';
    endif;
    ?>
</div>

<?php get_footer(); ?>

