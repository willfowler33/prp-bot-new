<?php
/**
 * Bot Manager for PRP Bot
 *
 * Handles multiple Skald bot configurations and per-user/role access control.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PRP_Bot_Manager {

    private $bots_table;
    private $access_table;
    private $conversations_table;

    public function __construct() {
        global $wpdb;
        $this->bots_table          = $wpdb->prefix . 'prp_bots';
        $this->access_table        = $wpdb->prefix . 'prp_bot_access';
        $this->conversations_table = $wpdb->prefix . 'tcp_tech_conversations';
    }

    /**
     * Create bot tables and add bot_id column to conversations table.
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_bots = "CREATE TABLE IF NOT EXISTS {$this->bots_table} (
            id              bigint(20)   NOT NULL AUTO_INCREMENT,
            name            varchar(100) NOT NULL DEFAULT 'Bot',
            api_key         varchar(255) NOT NULL DEFAULT '',
            project_id      varchar(100) NOT NULL DEFAULT '',
            welcome_message text         NOT NULL,
            system_prompt   text         NOT NULL,
            sort_order      int(11)      NOT NULL DEFAULT 0,
            is_active       tinyint(1)   NOT NULL DEFAULT 1,
            created_at      datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sort_order (sort_order),
            KEY is_active  (is_active)
        ) $charset_collate;";

        $sql_access = "CREATE TABLE IF NOT EXISTS {$this->access_table} (
            id        bigint(20)   NOT NULL AUTO_INCREMENT,
            bot_id    bigint(20)   NOT NULL,
            role_name varchar(100)          DEFAULT NULL,
            user_id   bigint(20)            DEFAULT NULL,
            PRIMARY KEY (id),
            KEY bot_id    (bot_id),
            KEY role_name (role_name),
            KEY user_id   (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_bots );
        dbDelta( $sql_access );

        $this->maybe_add_bot_id_to_conversations();
    }

    /**
     * Idempotently add bot_id column to conversations table.
     */
    private function maybe_add_bot_id_to_conversations() {
        global $wpdb;

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->conversations_table}'" );
        if ( ! $table_exists ) {
            return;
        }

        $col = $wpdb->get_results( "SHOW COLUMNS FROM {$this->conversations_table} LIKE 'bot_id'" );
        if ( empty( $col ) ) {
            $wpdb->query( "ALTER TABLE {$this->conversations_table} ADD COLUMN bot_id bigint(20) DEFAULT NULL AFTER user_id" );
            $wpdb->query( "ALTER TABLE {$this->conversations_table} ADD INDEX bot_id_idx (bot_id)" );
        }
    }

    /**
     * One-time migration: import legacy single-bot settings as the first bot.
     * Runs on admin_init; guarded by 'prp_bot_migrated_v1' option.
     */
    public function maybe_migrate_legacy_settings() {
        if ( get_option( 'prp_bot_migrated_v1' ) ) {
            return;
        }

        // Ensure tables exist before migration
        $this->create_tables();

        global $wpdb;
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->bots_table}" );
        if ( $count > 0 ) {
            update_option( 'prp_bot_migrated_v1', true );
            return;
        }

        $legacy = get_option( 'tcp_tech_bot_settings', array() );

        if ( empty( $legacy['api_key'] ) ) {
            update_option( 'prp_bot_migrated_v1', true );
            return;
        }

        $bot_id = $this->create_bot( array(
            'name'            => 'Default Bot',
            'api_key'         => $legacy['api_key'],
            'project_id'      => isset( $legacy['project_id'] )      ? $legacy['project_id']      : '',
            'welcome_message' => isset( $legacy['welcome_message'] )  ? $legacy['welcome_message'] : '',
            'system_prompt'   => isset( $legacy['system_prompt'] )    ? $legacy['system_prompt']   : '',
            'sort_order'      => 0,
            'is_active'       => 1,
        ) );

        if ( $bot_id ) {
            // Grant access to all roles by default so existing users aren't locked out
            $all_roles = array_keys( wp_roles()->roles );
            $this->set_bot_access( $bot_id, $all_roles, array() );
        }

        update_option( 'prp_bot_migrated_v1', true );
    }

    // =========================================================================
    // CRUD Methods
    // =========================================================================

    /**
     * Create a new bot.
     *
     * @param array $data Keys: name, api_key, project_id, welcome_message, system_prompt, sort_order, is_active
     * @return int|false New bot ID or false on failure.
     */
    public function create_bot( array $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->bots_table,
            array(
                'name'            => sanitize_text_field( $data['name'] ),
                'api_key'         => sanitize_text_field( $data['api_key'] ),
                'project_id'      => sanitize_text_field( isset( $data['project_id'] ) ? $data['project_id'] : '' ),
                'welcome_message' => sanitize_textarea_field( isset( $data['welcome_message'] ) ? $data['welcome_message'] : '' ),
                'system_prompt'   => sanitize_textarea_field( isset( $data['system_prompt'] ) ? $data['system_prompt'] : '' ),
                'sort_order'      => isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0,
                'is_active'       => isset( $data['is_active'] ) ? (int) (bool) $data['is_active'] : 1,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing bot.
     *
     * @param int   $bot_id Bot ID.
     * @param array $data   Fields to update.
     * @return bool
     */
    public function update_bot( $bot_id, array $data ) {
        global $wpdb;

        $update  = array();
        $formats = array();

        $field_map = array(
            'name'            => '%s',
            'api_key'         => '%s',
            'project_id'      => '%s',
            'welcome_message' => '%s',
            'system_prompt'   => '%s',
            'sort_order'      => '%d',
            'is_active'       => '%d',
        );

        foreach ( $field_map as $field => $fmt ) {
            if ( array_key_exists( $field, $data ) ) {
                if ( $fmt === '%s' ) {
                    $update[ $field ] = in_array( $field, array( 'name', 'api_key', 'project_id' ), true )
                        ? sanitize_text_field( $data[ $field ] )
                        : sanitize_textarea_field( $data[ $field ] );
                } else {
                    $update[ $field ] = intval( $data[ $field ] );
                }
                $formats[] = $fmt;
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        $result = $wpdb->update(
            $this->bots_table,
            $update,
            array( 'id' => intval( $bot_id ) ),
            $formats,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete a bot and all its access grants.
     *
     * @param int $bot_id
     * @return bool
     */
    public function delete_bot( $bot_id ) {
        global $wpdb;

        $wpdb->delete( $this->access_table, array( 'bot_id' => intval( $bot_id ) ), array( '%d' ) );

        return $wpdb->delete(
            $this->bots_table,
            array( 'id' => intval( $bot_id ) ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Get a single bot by ID.
     *
     * @param int $bot_id
     * @return object|null
     */
    public function get_bot( $bot_id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->bots_table} WHERE id = %d",
            intval( $bot_id )
        ) );
    }

    /**
     * Get all bots.
     *
     * @param bool $active_only Return only active bots.
     * @return array
     */
    public function get_all_bots( $active_only = false ) {
        global $wpdb;

        $where = $active_only ? 'WHERE is_active = 1' : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            "SELECT * FROM {$this->bots_table} {$where} ORDER BY sort_order ASC, name ASC"
        ) ?: array();
    }

    // =========================================================================
    // Access Control
    // =========================================================================

    /**
     * Replace all access grants for a bot.
     *
     * @param int      $bot_id
     * @param string[] $role_names WordPress role slugs.
     * @param int[]    $user_ids   WordPress user IDs.
     */
    public function set_bot_access( $bot_id, array $role_names, array $user_ids ) {
        global $wpdb;

        $bot_id = intval( $bot_id );

        // Delete existing grants
        $wpdb->delete( $this->access_table, array( 'bot_id' => $bot_id ), array( '%d' ) );

        foreach ( $role_names as $role_name ) {
            $role_name = sanitize_text_field( $role_name );
            if ( $role_name ) {
                $wpdb->insert(
                    $this->access_table,
                    array( 'bot_id' => $bot_id, 'role_name' => $role_name, 'user_id' => null ),
                    array( '%d', '%s', '%s' )
                );
            }
        }

        foreach ( $user_ids as $uid ) {
            $uid = intval( $uid );
            if ( $uid > 0 ) {
                $wpdb->insert(
                    $this->access_table,
                    array( 'bot_id' => $bot_id, 'role_name' => null, 'user_id' => $uid ),
                    array( '%d', '%s', '%d' )
                );
            }
        }
    }

    /**
     * Get all access grants for a bot.
     *
     * @param int $bot_id
     * @return array { roles: string[], users: int[] }
     */
    public function get_bot_access( $bot_id ) {
        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT role_name, user_id FROM {$this->access_table} WHERE bot_id = %d",
            intval( $bot_id )
        ) ) ?: array();

        $roles = array();
        $users = array();

        foreach ( $rows as $row ) {
            if ( ! is_null( $row->role_name ) ) {
                $roles[] = $row->role_name;
            } elseif ( ! is_null( $row->user_id ) ) {
                $users[] = (int) $row->user_id;
            }
        }

        return array( 'roles' => $roles, 'users' => $users );
    }

    /**
     * Get all active bots a specific user can access.
     *
     * @param int $user_id WordPress user ID.
     * @return array Array of bot row objects.
     */
    public function get_bots_for_user( $user_id ) {
        global $wpdb;

        $user_id = intval( $user_id );
        $user    = get_userdata( $user_id );

        if ( ! $user ) {
            return array();
        }

        $roles      = (array) $user->roles;
        $role_count = count( $roles );

        if ( $role_count === 0 ) {
            // No roles - can only match by user ID
            $bot_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT bot_id FROM {$this->access_table} WHERE user_id = %d",
                $user_id
            ) );
        } else {
            $placeholders = implode( ', ', array_fill( 0, $role_count, '%s' ) );
            $args         = array_merge( $roles, array( $user_id ) );

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $bot_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT bot_id FROM {$this->access_table} WHERE role_name IN ($placeholders) OR user_id = %d",
                ...$args
            ) );
        }

        if ( empty( $bot_ids ) ) {
            return array();
        }

        $id_placeholders = implode( ', ', array_fill( 0, count( $bot_ids ), '%d' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->bots_table} WHERE id IN ($id_placeholders) AND is_active = 1 ORDER BY sort_order ASC, name ASC",
            ...$bot_ids
        ) ) ?: array();
    }

    /**
     * Check whether a user can access a specific bot.
     *
     * @param int $user_id
     * @param int $bot_id
     * @return bool
     */
    public function user_can_access_bot( $user_id, $bot_id ) {
        $bots = $this->get_bots_for_user( $user_id );
        foreach ( $bots as $bot ) {
            if ( (int) $bot->id === (int) $bot_id ) {
                return true;
            }
        }
        return false;
    }
}
