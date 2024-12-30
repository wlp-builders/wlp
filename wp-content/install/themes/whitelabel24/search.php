<?php get_header(); ?>

<div class="search-page">
    <h1>Search Results for: <?php echo get_search_query(); ?></h1>

    <?php if ( have_posts() ) : ?>
        <div class="search-results">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="search-result">
                    <h2 class="search-result-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <div class="search-result-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination for search results -->
            <div class="search-pagination">
                <?php the_posts_pagination(); ?>
            </div>

        </div>
    <?php else : ?>
        <p>No results found for your search.</p>
    <?php endif; ?>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>

