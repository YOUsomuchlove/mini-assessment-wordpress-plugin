<?php
class WP_Assessment_DB_Manager {

    private $version = '1.3.0';

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_assessment = $wpdb->prefix . 'assessment';
        $sql1 = "CREATE TABLE $table_assessment (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'draft' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta( $sql1 );

        $table_questions = $wpdb->prefix . 'assessment_questions';
        $sql2 = "CREATE TABLE $table_questions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            assessment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            content text NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY assessment_status_order (assessment_id, status, sort_order),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta( $sql2 );

        $table_answers = $wpdb->prefix . 'assessment_answers';
        $sql3 = "CREATE TABLE $table_answers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            content text NOT NULL,
            score int(11) DEFAULT 0 NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY question_order (question_id, sort_order),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta( $sql3 );

        // Normalize values created by the initial demo version.
        $wpdb->query( "UPDATE $table_assessment SET status = 'publish' WHERE status = 'active'" );
        $wpdb->query( "UPDATE $table_questions SET status = 'publish' WHERE status = 'active'" );
        update_option( 'wp_assessment_db_version', $this->version, false );
    }
}
