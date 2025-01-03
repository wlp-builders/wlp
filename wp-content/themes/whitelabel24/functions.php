<?php
function whitelabel_24_setup() {
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'whitelabel_24_setup');

function whitelabel_24_enqueue_styles() {
    wp_enqueue_style('style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'whitelabel_24_enqueue_styles');

// Add theme settings menu page
function whitelabel_24_add_admin_menu() {
    add_menu_page('Whitelabel 24 Settings', 'Whitelabel 24', 'manage_options', 'whitelabel_24', 'whitelabel_24_settings_page');
}
add_action('admin_menu', 'whitelabel_24_add_admin_menu');

// Define the settings page structure
function whitelabel_24_settings_page() { ?>
    <div class="wrap">
        <h1>Whitelabel 24 Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('whitelabel_24_options');
            do_settings_sections('whitelabel_24');
            submit_button();
            ?>
        </form>
    </div>
<?php }

// Register theme settings
function whitelabel_24_settings_init() {
    register_setting('whitelabel_24_options', 'whitelabel_24_options');

    add_settings_section('whitelabel_24_section', 'User Info', null, 'whitelabel_24');

    add_settings_field('whitelabel_24_avatar', 'Avatar URL', 'whitelabel_24_avatar_render', 'whitelabel_24', 'whitelabel_24_section');
    add_settings_field('whitelabel_24_title', 'Title', 'whitelabel_24_title_render', 'whitelabel_24', 'whitelabel_24_section');
    add_settings_field('whitelabel_24_bio', 'Bio', 'whitelabel_24_bio_render', 'whitelabel_24', 'whitelabel_24_section');
    add_settings_field('whitelabel_24_email', 'Business Email', 'whitelabel_24_email_render', 'whitelabel_24', 'whitelabel_24_section');
    add_settings_field('whitelabel_24_work_title', 'Latest Work Section Title', 'whitelabel_24_work_title_render', 'whitelabel_24', 'whitelabel_24_section');
}
add_action('admin_init', 'whitelabel_24_settings_init');

// Render fields
function whitelabel_24_avatar_render() {
    $options = get_option('whitelabel_24_options'); ?>
    <input type="text" name="whitelabel_24_options[avatar]" value="<?php echo esc_attr($options['avatar'] ?? ''); ?>" />
<?php }

function whitelabel_24_title_render() {
    $options = get_option('whitelabel_24_options'); ?>
    <input type="text" name="whitelabel_24_options[title]" value="<?php echo esc_attr($options['title'] ?? ''); ?>" />
<?php }

function whitelabel_24_bio_render() {
    $options = get_option('whitelabel_24_options'); ?>
    <textarea name="whitelabel_24_options[bio]" rows="4"><?php echo esc_textarea($options['bio'] ?? ''); ?></textarea>
<?php }

function whitelabel_24_email_render() {
    $options = get_option('whitelabel_24_options'); ?>
    <input type="email" name="whitelabel_24_options[email]" value="<?php echo esc_attr($options['email'] ?? ''); ?>" />
<?php }

// New field for the "Latest Work & Research" section title
function whitelabel_24_work_title_render() {
    $options = get_option('whitelabel_24_options'); ?>
    <input type="text" name="whitelabel_24_options[work_title]" value="<?php echo esc_attr($options['work_title'] ?? 'Latest Work & Research'); ?>" />
<?php }

function insert_featured_image_to_content( $content ) {
    // Check if we are on a single post or page and if the post/page has a featured image
    if ( ( is_single() || is_page() ) && has_post_thumbnail() ) {
        // Get the post thumbnail HTML
        $featured_image = get_the_post_thumbnail( null, 'full' );

        // Add the featured image before the content (prepend) or after (append)
        // For example, we prepend the featured image:
        $content = $featured_image . $content;
    }
    return $content;
}
add_filter( 'the_content', 'insert_featured_image_to_content' ,1);


function modify_title_and_date_in_content( $content ) {
    // Check if we're on a single post or page and ensure we're on the main query
    if ( is_single() || is_page() && is_main_query() ) {
        // Get the post/page title
        $post_title = get_the_title();

        // Start with the title as an <h1>
        $title_html = '<h1 class="post-title">' . esc_html( $post_title ) . '</h1>';

        // If it's a post, get the date and prepend it as <h2>
        if ( is_single() ) {
            $post_date = get_the_date( 'F j, Y' );
            $date_html = '<h2 class="post-date">' . esc_html( $post_date ) . '</h2>';
            // Prepend both the title and date to the content
            $content = $title_html . $date_html . $content;
        } else {
            // For pages, just prepend the title
            $content = $title_html . $content;
        }
    }

    return $content;
}

// Apply the filter with a priority of 20
add_filter( 'the_content', 'modify_title_and_date_in_content', 2 );


function my_custom_header_widgets() {
    register_sidebar( array(
        'name'          => 'Site Header Widgets',
        'id'            => 'header-widget-1',
        'before_widget' => '<div class="header-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'my_custom_header_widgets' );
function my_custom_footer_widgets() {
    register_sidebar( array(
        'name'          => 'Site Footer Widgets',
        'id'            => 'footer-widget-1',
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'my_custom_footer_widgets' );

function mytheme_customize_register( $wp_customize ) {
    // Create a new panel for "Theme Settings".
    $wp_customize->add_panel( 'mytheme_settings_panel', array(
        'title'       => __( 'Theme Settings', 'mytheme' ),
        'description' => __( 'Customize your theme settings, including site identity and appearance.', 'mytheme' ),
        'priority'    => 10, // Adjust priority to control where the panel appears.
    ) );

    // Create a section for "Site Identity" under "Theme Settings".
    $wp_customize->add_section( 'mytheme_site_identity_section', array(
        'title'    => __( 'Site Identity', 'mytheme' ),
        'panel'    => 'mytheme_settings_panel', // Assign to "Theme Settings" panel.
        'priority' => 10,
    ) );

    // Add the setting for the header logo.
    $wp_customize->add_setting( 'mytheme_header_logo', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'capability'        => 'edit_theme_options',
    ) );

    // Add the control for uploading the header logo.
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'mytheme_header_logo', array(
        'label'       => __( 'Header Logo', 'mytheme' ),
        'section'     => 'mytheme_site_identity_section',
        'settings'    => 'mytheme_header_logo',
        'description' => __( 'Upload or select a logo for the header.', 'mytheme' ),
    ) ) );

    // (Optional) Add other settings or controls here.
}
add_action( 'customize_register', 'mytheme_customize_register' );

