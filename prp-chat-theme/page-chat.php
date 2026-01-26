<?php
/**
 * Template Name: PRP Full-Screen Chat
 *
 * A full-viewport ChatGPT-like chat interface template.
 * This template can be assigned to any page from the Page Attributes panel.
 *
 * @package PRP_Chat_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Require login - redirect to login page if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Check if PRP Bot plugin is active and template exists
$plugin_template = WP_PLUGIN_DIR . '/prp-bot/templates/chat-page.php';

if (file_exists($plugin_template)) {
    // Use the plugin's template
    include $plugin_template;
} else {
    // Fallback if plugin template doesn't exist
    get_header();
    ?>

    <div class="prp-chat-missing">
        <div class="prp-chat-missing-content">
            <h1>Chat Not Available</h1>
            <p>The PRP Bot plugin needs to be installed and activated to use this page.</p>
            <p><a href="<?php echo home_url(); ?>">Return to Home</a></p>
        </div>
    </div>

    <style>
        .prp-chat-missing {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            padding: 40px 20px;
            text-align: center;
        }
        .prp-chat-missing-content {
            max-width: 400px;
        }
        .prp-chat-missing h1 {
            margin-bottom: 16px;
        }
        .prp-chat-missing p {
            color: #666;
            margin-bottom: 12px;
        }
        .prp-chat-missing a {
            color: #10a37f;
        }
    </style>

    <?php
    get_footer();
}
