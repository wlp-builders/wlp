<?php get_header(); ?>

<div class="category-page">

    <!-- Main Content Area -->
    <div class="category-main-content">
        <!-- Display the category title and description -->
        <header class="category-header">
            <h1><?php single_cat_title(); ?></h1>
            <div class="category-description"><?php echo category_description(); ?></div>
        </header>

        <!-- Start of the WordPress Loop -->
        <?php if ( have_posts() ) : ?>
            <div class="category-posts">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="category-post">
                        <h2 class="post-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <div class="post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination for Category Posts -->
            <div class="category-pagination">
                <?php the_posts_pagination(); ?>
            </div>

        <?php else : ?>
            <p>No posts found in this category.</p>
        <?php endif; ?>
    </div>

    <!-- Sidebar Area (to show widgets) -->
    <div class="category-sidebar">
        <?php if ( is_active_sidebar( 'category-sidebar' ) ) : ?>
            <?php dynamic_sidebar( 'category-sidebar' ); ?>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>

