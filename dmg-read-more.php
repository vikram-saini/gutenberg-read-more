<?php
/**
 * Plugin Name: DMG Read More
 * Plugin URI: https://github.com/yourusername/dmg-read-more
 * Description: A WordPress plugin with a Gutenberg block for inserting stylized post links and a WP-CLI command for searching posts containing the block.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: dmg-read-more
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DMG_READ_MORE_VERSION', '1.0.0');
define('DMG_READ_MORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DMG_READ_MORE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register block scripts and styles
function dmg_read_more_register_block() {
    // Register block editor script
    wp_register_script(
        'dmg-read-more-editor',
        DMG_READ_MORE_PLUGIN_URL . 'build/index.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-url'),
        DMG_READ_MORE_VERSION,
        true
    );

    // Register block editor styles
    wp_register_style(
        'dmg-read-more-editor',
        DMG_READ_MORE_PLUGIN_URL . 'build/index.css',
        array('wp-edit-blocks'),
        DMG_READ_MORE_VERSION
    );

    // Register block frontend styles
    wp_register_style(
        'dmg-read-more-frontend',
        DMG_READ_MORE_PLUGIN_URL . 'build/style-index.css',
        array(),
        DMG_READ_MORE_VERSION
    );

    // Register the block
    register_block_type('dmg/read-more', array(
        'editor_script' => 'dmg-read-more-editor',
        'editor_style' => 'dmg-read-more-editor',
        'style' => 'dmg-read-more-frontend',
        'render_callback' => 'dmg_read_more_render_callback',
        'attributes' => array(
            'postId' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'postTitle' => array(
                'type' => 'string',
                'default' => '',
            ),
            'postUrl' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'dmg_read_more_register_block');

// Server-side rendering callback
function dmg_read_more_render_callback($attributes) {
    if (empty($attributes['postId']) || empty($attributes['postUrl'])) {
        return '';
    }

    // Ensure we have the latest post data
    $post = get_post($attributes['postId']);
    if (!$post || $post->post_status !== 'publish') {
        return '';
    }

    $title = esc_html($post->post_title);
    $url = esc_url(get_permalink($post));

    return sprintf(
        '<p class="dmg-read-more">Read More: <a href="%s">%s</a></p>',
        $url,
        $title
    );
}

// REST API endpoint for searching posts
function dmg_read_more_register_rest_routes() {
    register_rest_route('dmg-read-more/v1', '/search-posts', array(
        'methods' => 'GET',
        'callback' => 'dmg_read_more_search_posts',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'search' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'page' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 10,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));
}
add_action('rest_api_init', 'dmg_read_more_register_rest_routes');

// REST API callback for searching posts
function dmg_read_more_search_posts($request) {
    $search = $request->get_param('search');
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');

    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    // If search is numeric, search by ID
    if (!empty($search) && is_numeric($search)) {
        $args['p'] = intval($search);
        unset($args['posts_per_page']);
        unset($args['paged']);
    } elseif (!empty($search)) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    $posts = array();

    foreach ($query->posts as $post) {
        $posts[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'url' => get_permalink($post),
        );
    }

    return new WP_REST_Response(array(
        'posts' => $posts,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
    ), 200);
}

// Include WP-CLI command if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    require_once DMG_READ_MORE_PLUGIN_DIR . 'includes/class-dmg-read-more-cli.php';
}