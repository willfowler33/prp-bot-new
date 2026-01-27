<?php
/**
 * Conversation Manager for PRP Bot
 *
 * Handles CRUD operations for user conversations and chat history
 */

if (!defined('ABSPATH')) {
    exit;
}

class PRP_Conversation_Manager {

    private $conversations_table;
    private $messages_table;

    public function __construct() {
        global $wpdb;
        $this->conversations_table = $wpdb->prefix . 'tcp_tech_conversations';
        $this->messages_table = $wpdb->prefix . 'tcp_tech_chat_logs';
    }

    /**
     * Create database tables for conversations
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Conversations table
        $sql_conversations = "CREATE TABLE IF NOT EXISTS {$this->conversations_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) DEFAULT 'New Chat',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY updated_at (updated_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_conversations);

        // Add new columns to existing chat_logs table if they don't exist
        $this->maybe_add_columns();
    }

    /**
     * Add user_id and conversation_id columns to existing chat_logs table
     */
    private function maybe_add_columns() {
        global $wpdb;

        // Check if user_id column exists
        $user_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->messages_table} LIKE 'user_id'");
        if (empty($user_id_exists)) {
            $wpdb->query("ALTER TABLE {$this->messages_table} ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER id");
            $wpdb->query("ALTER TABLE {$this->messages_table} ADD INDEX user_id (user_id)");
        }

        // Check if conversation_id column exists
        $conversation_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->messages_table} LIKE 'conversation_id'");
        if (empty($conversation_id_exists)) {
            $wpdb->query("ALTER TABLE {$this->messages_table} ADD COLUMN conversation_id bigint(20) DEFAULT NULL AFTER user_id");
            $wpdb->query("ALTER TABLE {$this->messages_table} ADD INDEX conversation_id (conversation_id)");
        }
    }

    /**
     * Create a new conversation
     *
     * @param int $user_id WordPress user ID
     * @param string|null $title Optional conversation title
     * @return int|false Conversation ID or false on failure
     */
    public function create_conversation($user_id, $title = null) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->conversations_table,
            array(
                'user_id' => $user_id,
                'title' => $title ?: 'New Chat'
            ),
            array('%d', '%s')
        );

        if ($result === false) {
            error_log('PRP Bot: Failed to create conversation - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get all conversations for a user
     *
     * @param int $user_id WordPress user ID
     * @param int $limit Max number of conversations to return
     * @return array Array of conversation objects
     */
    public function get_user_conversations($user_id, $limit = 50) {
        global $wpdb;

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM {$this->messages_table} m WHERE m.conversation_id = c.id) as message_count,
                    (SELECT m2.user_message FROM {$this->messages_table} m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) as last_message
             FROM {$this->conversations_table} c
             WHERE c.user_id = %d
             ORDER BY c.updated_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));

        return $conversations ?: array();
    }

    /**
     * Get a single conversation with its messages
     *
     * @param int $conversation_id Conversation ID
     * @param int|null $user_id Optional user ID to verify ownership
     * @return object|null Conversation with messages or null
     */
    public function get_conversation($conversation_id, $user_id = null) {
        global $wpdb;

        // Ensure table exists
        $this->ensure_tables_exist();

        // Build query with optional user verification
        $where_clause = "id = %d";
        $params = array($conversation_id);

        if ($user_id !== null) {
            $where_clause .= " AND user_id = %d";
            $params[] = $user_id;
        }

        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->conversations_table} WHERE $where_clause",
            ...$params
        ));

        if (!$conversation) {
            error_log("PRP Bot: Conversation $conversation_id not found for user $user_id. Last error: " . $wpdb->last_error);
            return null;
        }

        // Get messages for this conversation
        $conversation->messages = $this->get_conversation_messages($conversation_id);

        return $conversation;
    }

    /**
     * Ensure tables exist (called on first access)
     */
    private function ensure_tables_exist() {
        global $wpdb;

        // Check if conversations table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->conversations_table}'");
        if (!$table_exists) {
            $this->create_tables();
        }
    }

    /**
     * Get all messages in a conversation
     *
     * @param int $conversation_id Conversation ID
     * @return array Array of message objects
     */
    public function get_conversation_messages($conversation_id) {
        global $wpdb;

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_message, assistant_response, metadata, created_at
             FROM {$this->messages_table}
             WHERE conversation_id = %d
             ORDER BY created_at ASC",
            $conversation_id
        ));

        // Parse metadata JSON for each message
        if ($messages) {
            foreach ($messages as &$message) {
                if (!empty($message->metadata)) {
                    $message->metadata = json_decode($message->metadata, true);
                }
            }
        }

        return $messages ?: array();
    }

    /**
     * Update conversation title
     *
     * @param int $conversation_id Conversation ID
     * @param string $title New title
     * @param int|null $user_id Optional user ID to verify ownership
     * @return bool Success
     */
    public function update_conversation_title($conversation_id, $title, $user_id = null) {
        global $wpdb;

        $where = array('id' => $conversation_id);
        $where_format = array('%d');

        if ($user_id !== null) {
            $where['user_id'] = $user_id;
            $where_format[] = '%d';
        }

        $result = $wpdb->update(
            $this->conversations_table,
            array('title' => $title),
            $where,
            array('%s'),
            $where_format
        );

        return $result !== false;
    }

    /**
     * Update conversation timestamp (called when new message added)
     *
     * @param int $conversation_id Conversation ID
     * @return bool Success
     */
    public function touch_conversation($conversation_id) {
        global $wpdb;

        return $wpdb->update(
            $this->conversations_table,
            array('updated_at' => current_time('mysql')),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        ) !== false;
    }

    /**
     * Delete a conversation and all its messages
     *
     * @param int $conversation_id Conversation ID
     * @param int|null $user_id Optional user ID to verify ownership
     * @return bool Success
     */
    public function delete_conversation($conversation_id, $user_id = null) {
        global $wpdb;

        // Verify ownership if user_id provided
        if ($user_id !== null) {
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$this->conversations_table} WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ));

            if (!$conversation) {
                return false;
            }
        }

        // Delete messages first
        $wpdb->delete(
            $this->messages_table,
            array('conversation_id' => $conversation_id),
            array('%d')
        );

        // Delete conversation
        $result = $wpdb->delete(
            $this->conversations_table,
            array('id' => $conversation_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Log a chat message to a conversation
     *
     * @param int $conversation_id Conversation ID
     * @param int $user_id WordPress user ID
     * @param string $user_message User's message
     * @param string $assistant_response Assistant's response
     * @param float $response_time Response time in seconds
     * @param array|null $metadata Additional metadata
     * @return int|false Message ID or false on failure
     */
    public function log_message($conversation_id, $user_id, $user_message, $assistant_response, $response_time, $metadata = null) {
        global $wpdb;

        $session_id = 'conv_' . $conversation_id . '_' . time();

        $result = $wpdb->insert(
            $this->messages_table,
            array(
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'user_message' => $user_message,
                'assistant_response' => $assistant_response,
                'response_time' => $response_time,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null
            ),
            array('%d', '%d', '%s', '%s', '%s', '%f', '%s')
        );

        if ($result === false) {
            error_log('PRP Bot: Failed to log message - ' . $wpdb->last_error);
            return false;
        }

        // Update conversation timestamp
        $this->touch_conversation($conversation_id);

        return $wpdb->insert_id;
    }

    /**
     * Auto-generate conversation title from first message
     *
     * @param int $conversation_id Conversation ID
     * @param string $first_message First user message
     * @return bool Success
     */
    public function auto_title_conversation($conversation_id, $first_message) {
        // Truncate to 50 chars and clean up
        $title = substr(trim($first_message), 0, 50);
        if (strlen($first_message) > 50) {
            $title .= '...';
        }

        // Remove any line breaks
        $title = str_replace(array("\r", "\n"), ' ', $title);

        return $this->update_conversation_title($conversation_id, $title);
    }

    /**
     * Check if a conversation belongs to a user
     *
     * @param int $conversation_id Conversation ID
     * @param int $user_id WordPress user ID
     * @return bool
     */
    public function user_owns_conversation($conversation_id, $user_id) {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->conversations_table} WHERE id = %d AND user_id = %d",
            $conversation_id,
            $user_id
        ));

        return $result !== null;
    }
}
