<?php
/**
 * Plugin Name: PRP Bot
 * Plugin URI: https://tcptech.com/
 * Description: Integrate your Skald AI assistant chatbot into WordPress with a modern chat interface and full-screen GPT-like experience
 * Version: 2.0.0
 * Author: TCP Tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the Conversation Manager
require_once plugin_dir_path(__FILE__) . 'includes/class-conversation-manager.php';

class TCP_Tech_Bot_Chat {

    private $option_name = 'tcp_tech_bot_settings';
    private $table_name;
    private $conversation_manager;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tcp_tech_chat_logs';
        $this->conversation_manager = new PRP_Conversation_Manager();

        // Installation hook
        register_activation_hook(__FILE__, array($this, 'create_database_table'));
        register_activation_hook(__FILE__, array($this->conversation_manager, 'create_tables'));

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'maybe_create_table'));

        // CSV Export action - must be registered early
        add_action('admin_post_export_chat_logs_csv', array($this, 'export_chat_logs_csv'));

        // Frontend hooks
        add_shortcode('tcp_tech_chat', array($this, 'chat_shortcode'));
        add_shortcode('prp_fullscreen_chat', array($this, 'fullscreen_chat_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Register custom page template
        add_filter('theme_page_templates', array($this, 'add_chat_template'));
        add_filter('template_include', array($this, 'load_chat_template'));

        // AJAX hooks - Widget (allows non-logged in users)
        add_action('wp_ajax_tcp_tech_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_tcp_tech_chat', array($this, 'handle_chat_request'));

        // AJAX hooks - Full-screen chat (logged-in users only)
        add_action('wp_ajax_tcp_get_conversations', array($this, 'ajax_get_conversations'));
        add_action('wp_ajax_tcp_create_conversation', array($this, 'ajax_create_conversation'));
        add_action('wp_ajax_tcp_get_conversation', array($this, 'ajax_get_conversation'));
        add_action('wp_ajax_tcp_delete_conversation', array($this, 'ajax_delete_conversation'));
        add_action('wp_ajax_tcp_rename_conversation', array($this, 'ajax_rename_conversation'));
        add_action('wp_ajax_tcp_fullscreen_chat', array($this, 'handle_fullscreen_chat_request'));
    }

    public function maybe_create_table() {
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        if (!$table_exists) {
            $this->create_database_table();
        }
        // Also ensure conversations table exists
        $this->conversation_manager->create_tables();
    }

    public function create_database_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_message text NOT NULL,
            assistant_response text NOT NULL,
            response_time float DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'PRP Bot',
            'PRP Bot',
            'manage_options',
            'tcp-tech-bot',
            array($this, 'settings_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'tcp-tech-bot',
            'Chat Logs',
            'Chat Logs',
            'manage_options',
            'tcp-tech-chat-logs',
            array($this, 'chat_logs_page')
        );
    }

    public function export_chat_logs_csv() {
        // Log the attempt
        error_log('PRP Bot: CSV export requested');

        if (!current_user_can('manage_options')) {
            error_log('PRP Bot: CSV export - unauthorized user');
            wp_die('Unauthorized');
        }

        check_admin_referer('export_chat_logs');
        error_log('PRP Bot: CSV export - nonce verified');

        global $wpdb;

        $logs = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
        error_log('PRP Bot: CSV export - found ' . count($logs) . ' logs');

        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Prevent any other output
        nocache_headers();

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tcp-tech-chat-logs-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, array(
            'id',
            'session_id',
            'created_at',
            'question',
            'answer',
            'response_time_seconds'
        ));

        // Data rows
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->session_id,
                $log->created_at,
                $log->user_message,
                $log->assistant_response,
                $log->response_time
            ));
        }

        fclose($output);
        error_log('PRP Bot: CSV export - completed successfully');
        exit;
    }

    public function chat_logs_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $total_pages = ceil($total / $per_page);

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        ?>
        <div class="wrap">
            <h1>Chat Logs</h1>

            <div style="margin: 20px 0;">
                <p style="display: inline-block; margin-right: 20px;">
                    Total Conversations: <strong><?php echo number_format($total); ?></strong>
                </p>

                <?php if ($total > 0): ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                    <input type="hidden" name="action" value="export_chat_logs_csv">
                    <?php wp_nonce_field('export_chat_logs'); ?>
                    <button type="submit" class="button button-primary">
                        Export to CSV
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <?php
            // Debug: Test database write
            if (isset($_GET['test_insert'])) {
                global $wpdb;
                $test_result = $wpdb->insert(
                    $this->table_name,
                    array(
                        'session_id' => 'test_' . time(),
                        'user_message' => 'Test question',
                        'assistant_response' => 'Test answer',
                        'response_time' => 1.23,
                        'metadata' => null
                    ),
                    array('%s', '%s', '%s', '%f', '%s')
                );

                if ($test_result) {
                    echo '<div class="notice notice-success"><p>Test insert successful! ID: ' . $wpdb->insert_id . ' - Refresh page to see it.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Test insert failed: ' . $wpdb->last_error . '</p></div>';
                }
            }

            // Show test button
            echo '<p><a href="' . add_query_arg('test_insert', '1') . '" class="button">Test Database Insert</a></p>';
            ?>

            <p><strong>Table:</strong> <?php echo esc_html($this->table_name); ?> |
            <strong>Exists:</strong> <?php
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
            echo $table_exists ? 'YES' : 'NO';
            ?></p>

            <?php if (empty($logs)): ?>
                <p>No chat logs found. Try sending a message in the chat to test logging.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Date/Time</th>
                            <th>User Message</th>
                            <th>Assistant Response</th>
                            <th style="width: 100px;">Time (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td style="max-width: 300px;">
                                    <?php echo esc_html(substr($log->user_message, 0, 100)); ?>
                                    <?php if (strlen($log->user_message) > 100) echo '...'; ?>
                                </td>
                                <td style="max-width: 300px;">
                                    <?php echo esc_html(substr($log->assistant_response, 0, 100)); ?>
                                    <?php if (strlen($log->assistant_response) > 100) echo '...'; ?>
                                </td>
                                <td><?php echo $log->response_time ? number_format($log->response_time, 2) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('tcp_tech_bot_options', $this->option_name, array($this, 'sanitize_settings'));

        add_settings_section(
            'tcp_tech_bot_main',
            'API Configuration',
            array($this, 'section_callback'),
            'tcp-tech-bot'
        );

        add_settings_field(
            'api_key',
            'Skald API Key',
            array($this, 'api_key_field'),
            'tcp-tech-bot',
            'tcp_tech_bot_main'
        );

        add_settings_field(
            'project_id',
            'Project ID',
            array($this, 'project_id_field'),
            'tcp-tech-bot',
            'tcp_tech_bot_main'
        );

        add_settings_field(
            'welcome_message',
            'Welcome Message',
            array($this, 'welcome_message_field'),
            'tcp-tech-bot',
            'tcp_tech_bot_main'
        );

        add_settings_field(
            'system_prompt',
            'System Prompt (Optional)',
            array($this, 'system_prompt_field'),
            'tcp-tech-bot',
            'tcp_tech_bot_main'
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['project_id'])) {
            $sanitized['project_id'] = sanitize_text_field($input['project_id']);
        }

        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message']);
        }

        if (isset($input['system_prompt'])) {
            $sanitized['system_prompt'] = sanitize_textarea_field($input['system_prompt']);
        }

        return $sanitized;
    }

    public function section_callback() {
        echo '<p>Configure your Skald API credentials and settings.</p>';
    }

    public function api_key_field() {
        $options = get_option($this->option_name);
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        echo '<input type="password" name="' . $this->option_name . '[api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Skald API key (format: sk_proj_...)</p>';
    }

    public function project_id_field() {
        $options = get_option($this->option_name);
        $value = isset($options['project_id']) ? $options['project_id'] : '';
        echo '<input type="text" name="' . $this->option_name . '[project_id]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Skald project ID (optional if using project API key)</p>';
    }

    public function welcome_message_field() {
        $options = get_option($this->option_name);
        $value = isset($options['welcome_message']) ? $options['welcome_message'] : 'Hello! How can I help you with TCP Tech today?';
        echo '<textarea name="' . $this->option_name . '[welcome_message]" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Initial message displayed when chat opens</p>';
    }

    public function system_prompt_field() {
        $options = get_option($this->option_name);
        $value = isset($options['system_prompt']) ? $options['system_prompt'] : '';
        echo '<textarea name="' . $this->option_name . '[system_prompt]" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Custom system prompt to customize the AI agent\'s behavior (optional)</p>';
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('tcp_tech_bot_options');
                do_settings_sections('tcp-tech-bot');
                submit_button('Save Settings');
                ?>
            </form>

            <hr>

            <h2>Usage</h2>
            <p>Add the chat widget to any page or post using this shortcode:</p>
            <code>[tcp_tech_chat]</code>

            <h3>Shortcode Options:</h3>
            <ul>
                <li><code>[tcp_tech_chat button_text="Chat with us"]</code> - Custom button text</li>
                <li><code>[tcp_tech_chat position="inline"]</code> - Display inline instead of floating button</li>
            </ul>
        </div>
        <?php
    }

    public function enqueue_frontend_assets() {
        // Check if we're on the fullscreen chat template
        if (is_page()) {
            $page_template = get_page_template_slug();
            if ($page_template === 'prp-chat-template') {
                $this->enqueue_fullscreen_assets();
                return; // Don't load widget assets on fullscreen page
            }
        }

        // Use file modification time for cache busting
        $css_version = filemtime(plugin_dir_path(__FILE__) . 'css/chat-widget.css');
        $js_version = filemtime(plugin_dir_path(__FILE__) . 'js/chat-widget.js');

        wp_enqueue_style(
            'tcp-tech-bot-chat',
            plugin_dir_url(__FILE__) . 'css/chat-widget.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'tcp-tech-bot-chat',
            plugin_dir_url(__FILE__) . 'js/chat-widget.js',
            array('jquery'),
            $js_version,
            true
        );

        $options = get_option($this->option_name);
        $welcome_message = isset($options['welcome_message']) ? $options['welcome_message'] : 'Hello! How can I help you with TCP Tech today?';

        wp_localize_script('tcp-tech-bot-chat', 'tcpTechChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tcp_tech_chat_nonce'),
            'welcomeMessage' => $welcome_message
        ));
    }

    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'button_text' => 'Chat with us',
            'position' => 'floating' // floating or inline
        ), $atts);

        $class = $atts['position'] === 'inline' ? 'tcp-tech-chat-inline' : 'tcp-tech-chat-floating';

        ob_start();
        ?>
        <div class="tcp-tech-chat-container <?php echo esc_attr($class); ?>">
            <?php if ($atts['position'] === 'floating'): ?>
                <button class="tcp-tech-chat-button" aria-label="Open chat">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span><?php echo esc_html($atts['button_text']); ?></span>
                </button>
            <?php endif; ?>

            <div class="tcp-tech-chat-window" style="<?php echo $atts['position'] === 'inline' ? 'display: block;' : 'display: none;'; ?>">
                <div class="tcp-tech-chat-header">
                    <h3>PRP Bot</h3>
                    <?php if ($atts['position'] === 'floating'): ?>
                        <div class="tcp-tech-window-controls">
                            <button class="tcp-tech-window-control tcp-tech-chat-minimize" aria-label="Minimize chat" title="Minimize">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <button class="tcp-tech-window-control tcp-tech-chat-maximize" aria-label="Maximize chat" title="Maximize">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="tcp-tech-window-control tcp-tech-chat-close" aria-label="Close chat" title="Close">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-n"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-s"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-e"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-w"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-ne"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-nw"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-se"></div>
                <div class="tcp-tech-resize-handle tcp-tech-resize-sw"></div>

                <div class="tcp-tech-chat-messages">
                    <!-- Messages will be added here dynamically -->
                </div>

                <div class="tcp-tech-chat-input-container">
                    <textarea
                        class="tcp-tech-chat-input"
                        placeholder="Type your message..."
                        rows="1"
                    ></textarea>
                    <button class="tcp-tech-chat-send" aria-label="Send message">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_chat_request() {
        check_ajax_referer('tcp_tech_chat_nonce', 'nonce');

        if (!isset($_POST['message'])) {
            wp_send_json_error('No message provided');
            return;
        }

        $start_time = microtime(true);
        $message = sanitize_textarea_field($_POST['message']);

        if (empty(trim($message))) {
            wp_send_json_error('Message content cannot be empty');
            return;
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : uniqid('session_', true);

        $options = get_option($this->option_name);
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $project_id = isset($options['project_id']) ? $options['project_id'] : '';
        $system_prompt = isset($options['system_prompt']) ? $options['system_prompt'] : '';

        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }

        // Make API request to Skald
        $url = "https://api.useskald.com/api/v1/chat";

        $body = array(
            'query' => $message,
            'stream' => false,
            'rag_config' => array(
                'references' => array(
                    'enabled' => true
                ),
                'reranking' => array(
                    'enabled' => true,
                    'topK' => 10
                )
            )
        );

        // Add optional parameters
        if (!empty($system_prompt)) {
            $body['system_prompt'] = $system_prompt;
        }

        if (!empty($project_id)) {
            $body['project_id'] = $project_id;
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            wp_send_json_error('API request failed: ' . $response_body);
            return;
        }

        $data = json_decode($response_body, true);

        // Debug log the full API response
        error_log('PRP Bot: Full API response: ' . print_r($data, true));

        if (isset($data['ok']) && $data['ok'] === true && isset($data['response'])) {
            $assistant_message = $data['response'];
            $response_time = microtime(true) - $start_time;

            // Extract references if available - try different possible field names
            $references = array();
            if (isset($data['references'])) {
                $references = $data['references'];
            } elseif (isset($data['sources'])) {
                $references = $data['sources'];
            } elseif (isset($data['citations'])) {
                $references = $data['citations'];
            } elseif (isset($data['documents'])) {
                $references = $data['documents'];
            }

            error_log('PRP Bot: Extracted references: ' . print_r($references, true));

            $metadata = array(
                'references' => $references,
                'full_response' => $data // Store full response for debugging
            );

            // Log to database with references
            $this->log_chat($session_id, $message, $assistant_message, $response_time, $metadata);

            // Convert 1-indexed PHP array to 0-indexed for JavaScript
            $references_zero_indexed = is_array($references) ? array_values($references) : array();

            error_log('PRP Bot: Converting references to 0-indexed: ' . print_r($references_zero_indexed, true));

            wp_send_json_success(array(
                'message' => $assistant_message,
                'session_id' => $session_id,
                'references' => $references_zero_indexed
            ));
        } else {
            error_log('PRP Bot: API response error or missing data');
            wp_send_json_error('Invalid API response');
        }
    }

    private function log_chat($session_id, $user_message, $assistant_response, $response_time, $metadata = null) {
        global $wpdb;

        error_log('PRP Bot: Attempting to log chat - Session: ' . $session_id . ', Table: ' . $this->table_name);

        $insert_data = array(
            'session_id' => $session_id,
            'user_message' => $user_message,
            'assistant_response' => $assistant_response,
            'response_time' => $response_time,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null
        );

        $insert_format = array('%s', '%s', '%s', '%f', '%s');

        $result = $wpdb->insert($this->table_name, $insert_data, $insert_format);

        if ($result === false) {
            error_log('PRP Bot: Chat logging FAILED - ' . $wpdb->last_error);
            error_log('PRP Bot: Last query - ' . $wpdb->last_query);
        } else {
            error_log('PRP Bot: Chat logged successfully with ID: ' . $wpdb->insert_id);
        }
    }

    /**
     * Add custom chat template to page template dropdown
     */
    public function add_chat_template($templates) {
        $templates['prp-chat-template'] = 'PRP Full-Screen Chat';
        return $templates;
    }

    /**
     * Load the custom chat template
     */
    public function load_chat_template($template) {
        if (is_page()) {
            $page_template = get_page_template_slug();
            if ($page_template === 'prp-chat-template') {
                $plugin_template = plugin_dir_path(__FILE__) . 'templates/chat-page.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }

    /**
     * Full-screen chat shortcode
     */
    public function fullscreen_chat_shortcode($atts) {
        // Require user to be logged in
        if (!is_user_logged_in()) {
            return '<div class="prp-login-required">
                <p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access the chat.</p>
            </div>';
        }

        // Enqueue fullscreen assets
        $this->enqueue_fullscreen_assets();

        $user = wp_get_current_user();

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/chat-page.php';
        return ob_get_clean();
    }

    /**
     * Enqueue fullscreen chat assets
     */
    public function enqueue_fullscreen_assets() {
        $css_version = file_exists(plugin_dir_path(__FILE__) . 'css/chat-fullscreen.css')
            ? filemtime(plugin_dir_path(__FILE__) . 'css/chat-fullscreen.css')
            : '1.0.0';
        $js_version = file_exists(plugin_dir_path(__FILE__) . 'js/chat-fullscreen.js')
            ? filemtime(plugin_dir_path(__FILE__) . 'js/chat-fullscreen.js')
            : '1.0.0';

        wp_enqueue_style(
            'prp-chat-fullscreen',
            plugin_dir_url(__FILE__) . 'css/chat-fullscreen.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'prp-chat-fullscreen',
            plugin_dir_url(__FILE__) . 'js/chat-fullscreen.js',
            array('jquery'),
            $js_version,
            true
        );

        $options = get_option($this->option_name);
        $welcome_message = isset($options['welcome_message']) ? $options['welcome_message'] : 'Hello! How can I help you today?';

        $user = wp_get_current_user();

        wp_localize_script('prp-chat-fullscreen', 'prpChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('prp_chat_nonce'),
            'welcomeMessage' => $welcome_message,
            'userId' => $user->ID,
            'userName' => $user->display_name,
            'userAvatar' => get_avatar_url($user->ID, array('size' => 40))
        ));
    }

    /**
     * AJAX: Get all conversations for current user
     */
    public function ajax_get_conversations() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        $user_id = get_current_user_id();
        $conversations = $this->conversation_manager->get_user_conversations($user_id);

        wp_send_json_success(array('conversations' => $conversations));
    }

    /**
     * AJAX: Create a new conversation
     */
    public function ajax_create_conversation() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        $user_id = get_current_user_id();
        $conversation_id = $this->conversation_manager->create_conversation($user_id);

        if ($conversation_id === false) {
            wp_send_json_error('Failed to create conversation');
            return;
        }

        $conversation = $this->conversation_manager->get_conversation($conversation_id, $user_id);

        wp_send_json_success(array('conversation' => $conversation));
    }

    /**
     * AJAX: Get a specific conversation with messages
     */
    public function ajax_get_conversation() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        if (!$conversation_id) {
            wp_send_json_error('Invalid conversation ID');
            return;
        }

        $user_id = get_current_user_id();
        $conversation = $this->conversation_manager->get_conversation($conversation_id, $user_id);

        if (!$conversation) {
            wp_send_json_error('Conversation not found');
            return;
        }

        wp_send_json_success(array('conversation' => $conversation));
    }

    /**
     * AJAX: Delete a conversation
     */
    public function ajax_delete_conversation() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        if (!$conversation_id) {
            wp_send_json_error('Invalid conversation ID');
            return;
        }

        $user_id = get_current_user_id();
        $result = $this->conversation_manager->delete_conversation($conversation_id, $user_id);

        if (!$result) {
            wp_send_json_error('Failed to delete conversation');
            return;
        }

        wp_send_json_success(array('deleted' => true));
    }

    /**
     * AJAX: Rename a conversation
     */
    public function ajax_rename_conversation() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';

        if (!$conversation_id || empty($title)) {
            wp_send_json_error('Invalid parameters');
            return;
        }

        $user_id = get_current_user_id();
        $result = $this->conversation_manager->update_conversation_title($conversation_id, $title, $user_id);

        if (!$result) {
            wp_send_json_error('Failed to rename conversation');
            return;
        }

        wp_send_json_success(array('renamed' => true, 'title' => $title));
    }

    /**
     * AJAX: Handle fullscreen chat request (logged-in users only)
     */
    public function handle_fullscreen_chat_request() {
        check_ajax_referer('prp_chat_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }

        if (!isset($_POST['message'])) {
            wp_send_json_error('No message provided');
            return;
        }

        $start_time = microtime(true);
        $message = sanitize_textarea_field($_POST['message']);

        if (empty(trim($message))) {
            wp_send_json_error('Message content cannot be empty');
            return;
        }

        $user_id = get_current_user_id();
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;

        // Verify conversation ownership
        if ($conversation_id && !$this->conversation_manager->user_owns_conversation($conversation_id, $user_id)) {
            wp_send_json_error('Invalid conversation');
            return;
        }

        // Create new conversation if not provided
        if (!$conversation_id) {
            $conversation_id = $this->conversation_manager->create_conversation($user_id);
            if (!$conversation_id) {
                wp_send_json_error('Failed to create conversation');
                return;
            }
        }

        // Check if this is the first message in the conversation
        $existing_messages = $this->conversation_manager->get_conversation_messages($conversation_id);
        $is_first_message = empty($existing_messages);

        $options = get_option($this->option_name);
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $project_id = isset($options['project_id']) ? $options['project_id'] : '';
        $system_prompt = isset($options['system_prompt']) ? $options['system_prompt'] : '';

        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }

        // Make API request to Skald
        $url = "https://api.useskald.com/api/v1/chat";

        $body = array(
            'query' => $message,
            'stream' => false,
            'rag_config' => array(
                'references' => array(
                    'enabled' => true
                ),
                'reranking' => array(
                    'enabled' => true,
                    'topK' => 10
                )
            )
        );

        if (!empty($system_prompt)) {
            $body['system_prompt'] = $system_prompt;
        }

        if (!empty($project_id)) {
            $body['project_id'] = $project_id;
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            wp_send_json_error('API request failed: ' . $response_body);
            return;
        }

        $data = json_decode($response_body, true);

        if (isset($data['ok']) && $data['ok'] === true && isset($data['response'])) {
            $assistant_message = $data['response'];
            $response_time = microtime(true) - $start_time;

            // Extract references
            $references = array();
            if (isset($data['references'])) {
                $references = $data['references'];
            } elseif (isset($data['sources'])) {
                $references = $data['sources'];
            } elseif (isset($data['citations'])) {
                $references = $data['citations'];
            } elseif (isset($data['documents'])) {
                $references = $data['documents'];
            }

            $metadata = array(
                'references' => $references,
                'full_response' => $data
            );

            // Log to conversation
            $this->conversation_manager->log_message(
                $conversation_id,
                $user_id,
                $message,
                $assistant_message,
                $response_time,
                $metadata
            );

            // Auto-title if first message
            if ($is_first_message) {
                $this->conversation_manager->auto_title_conversation($conversation_id, $message);
            }

            // Get updated conversation for title
            $conversation = $this->conversation_manager->get_conversation($conversation_id, $user_id);

            wp_send_json_success(array(
                'message' => $assistant_message,
                'conversation_id' => $conversation_id,
                'conversation_title' => $conversation ? $conversation->title : 'New Chat',
                'references' => is_array($references) ? array_values($references) : array(),
                'is_new_conversation' => $is_first_message
            ));
        } else {
            wp_send_json_error('Invalid API response');
        }
    }
}

// Initialize the plugin
new TCP_Tech_Bot_Chat();
