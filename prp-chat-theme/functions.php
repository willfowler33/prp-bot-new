<?php
/**
 * PRP Chat Theme Functions
 *
 * Child theme functions for PRP Bot full-screen chat interface.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent theme styles
 */
function prp_chat_theme_enqueue_styles() {
    // Enqueue parent theme stylesheet
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // Enqueue child theme stylesheet
    wp_enqueue_style(
        'prp-chat-theme-style',
        get_stylesheet_uri(),
        array('parent-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'prp_chat_theme_enqueue_styles');

/**
 * Add body class for chat page template
 */
function prp_chat_theme_body_class($classes) {
    if (is_page_template('page-chat.php') || is_page_template('prp-chat-template')) {
        $classes[] = 'prp-chat-page';
        $classes[] = 'no-header';
        $classes[] = 'no-footer';
    }
    return $classes;
}
add_action('body_class', 'prp_chat_theme_body_class');

/**
 * Remove admin bar on chat page
 */
function prp_chat_theme_hide_admin_bar() {
    if (is_page_template('page-chat.php') || is_page_template('prp-chat-template')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('template_redirect', 'prp_chat_theme_hide_admin_bar');

/**
 * Register chat page template from theme
 * This allows selecting "PRP Full-Screen Chat" from page attributes
 */
function prp_chat_theme_page_templates($templates) {
    $templates['page-chat.php'] = 'PRP Full-Screen Chat';
    return $templates;
}
add_filter('theme_page_templates', 'prp_chat_theme_page_templates');

/**
 * Load the chat page template
 */
function prp_chat_theme_template_include($template) {
    if (is_page()) {
        $page_template = get_page_template_slug();
        if ($page_template === 'page-chat.php') {
            // First check if theme has the template
            $theme_template = get_stylesheet_directory() . '/page-chat.php';
            if (file_exists($theme_template)) {
                return $theme_template;
            }

            // Fall back to plugin template
            $plugin_template = WP_PLUGIN_DIR . '/prp-bot/templates/chat-page.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    return $template;
}
add_filter('template_include', 'prp_chat_theme_template_include', 99);

/**
 * Theme setup
 */
function prp_chat_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'prp_chat_theme_setup');
