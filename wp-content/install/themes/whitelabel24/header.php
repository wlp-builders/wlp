<!DOCTYPE html>
<html lang="<?php language_attributes(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Add a more descriptive title -->
    <title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
    
    <!-- Meta description for SEO -->
    <meta name="description" content="<?php echo get_bloginfo( 'description' ); ?>">

    <!-- Open Graph for social sharing -->
    <meta property="og:title" content="<?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?>">
    <meta property="og:description" content="<?php echo get_bloginfo( 'description' ); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url( home_url() ); ?>">

    <!-- Canonical URL for avoiding duplicate content issues -->
    <link rel="canonical" href="<?php echo esc_url( home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) ); ?>">

    <!-- Include WordPress head hooks and styles -->
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php if ( is_active_sidebar( 'header-widget-1' ) ) : ?>
    <div class="header-widget-area">
        <?php dynamic_sidebar( 'header-widget-1' ); ?>
    </div>
<?php endif; ?>

<!-- searchform.php -->
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <div class="search-header">
        <!-- Logo (Replace with your logo) -->
        <div class="logo-container">
<a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <!-- Dynamically get the WordPress site logo -->
		<img style="height:42px;" src="<?php echo esc_url(get_option('whitelabel_24_options')['avatar']);?>" alt="Site Logo">
	    </a>

        </div>

        <!-- Search Input Field -->
        <div class="search-input-container">
            <input type="search" id="search-field" class="search-field" placeholder="Search..." value="<?php echo get_search_query(); ?>" name="s" />
            <button type="submit" class="search-submit">Search</button>
        </div>
    </div>
</form>

<!-- Add this to your theme's style.css -->
<style>

/* Search Form Container */
.search-form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    max-width: 1200px;
}

/* Header (Logo + Search Field) */
.search-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1200px;
}

/* Logo Styling */
.logo-container {
    flex: 1;
    display: flex;
    justify-content: flex-start;
}

.logo img {
    max-width: 150px;
    height: auto;
}

/* Search Form Styling */
.search-input-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 50%;
	margin-right:25%;
    border-radius: 8px;
}

/* Search Input Field */
.search-field {
    width: 100%;
    padding: 12px;
    border: none!important;	
outline:none!important;
box-shadow:none!important;
    border-radius: 5px;
    color: #fff;
    background-color: #0d0d0d;
    font-size: 16px;
}
.search-field:hover {
background:#222;
}

.search-field::placeholder {
    color: #aaa;
}

/* Search Button */
.search-submit {
display:none;
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 12px 20px;
    margin-left: 10px;
    cursor: pointer;
}

.search-submit:hover {
    background-color: #005f8f;
}
</style>

