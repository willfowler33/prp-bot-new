<?php
/**
 * PRP Bot Full-Screen Chat Page Template
 *
 * This template provides a full-viewport ChatGPT-like interface.
 * Used either via shortcode [prp_fullscreen_chat] or page template.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Require login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Override any theme styles */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            height: 100vh !important;
            width: 100vw !important;
        }
        /* Hide admin bar if present */
        #wpadminbar {
            display: none !important;
        }
        html {
            margin-top: 0 !important;
        }
    </style>
</head>
<body class="prp-chat-page">

<div class="prp-chat-app">
    <!-- Sidebar -->
    <aside class="prp-sidebar">
        <div class="prp-sidebar-header">
            <button class="prp-new-chat-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New chat
            </button>
        </div>

        <nav class="prp-conversation-list">
            <!-- Conversations loaded dynamically via JS -->
            <div class="prp-loading">
                <div class="prp-loading-spinner"></div>
            </div>
        </nav>

        <div class="prp-sidebar-footer">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="prp-user-info prp-logout-link" title="Click to logout">
                <?php echo get_avatar($user->ID, 32, '', '', array('class' => 'prp-user-avatar')); ?>
                <span class="prp-user-name"><?php echo esc_html($user->display_name); ?></span>
                <svg class="prp-logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div class="prp-sidebar-overlay"></div>

    <!-- Main Chat Area -->
    <main class="prp-chat-main">
        <header class="prp-chat-header">
            <div class="prp-chat-header-left">
                <button class="prp-sidebar-toggle" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="prp-conversation-title">New Chat</h1>
            </div>
        </header>

        <div class="prp-messages-container">
            <!-- Empty state shown by default -->
            <div class="prp-empty-state">
                <svg class="prp-empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h2 class="prp-empty-state-title">How can I help you today?</h2>
                <p class="prp-empty-state-subtitle">Ask me anything about our products and services.</p>
            </div>
        </div>

        <div class="prp-input-area">
            <div class="prp-input-wrapper">
                <div class="prp-input-container">
                    <textarea
                        class="prp-input"
                        placeholder="Message PRP Bot..."
                        rows="1"
                        autofocus
                    ></textarea>
                    <button class="prp-send-btn" aria-label="Send message">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <p class="prp-disclaimer">
                    PRP Bot can make mistakes. Consider checking important information.
                </p>
            </div>
        </div>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
