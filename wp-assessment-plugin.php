<?php
/**
 * Plugin Name: Mini Assessment Plugin
 * Description: Headless WordPress assessment API with role-based permissions and JWT authentication.
 * Version: 1.3.0
 * Author: Freelancer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WP_ASSESSMENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_ASSESSMENT_PLUGIN_VERSION', '1.3.0' );
define( 'WP_ASSESSMENT_PERMISSION_OPTION', 'wp_assessment_role_permissions' );

require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/class-db-manager.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/class-permissions.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/class-jwt-auth.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/class-admin.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/api/class-assessment-controller.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/api/class-question-controller.php';
require_once WP_ASSESSMENT_PLUGIN_DIR . 'includes/api/class-answer-controller.php';

register_activation_hook( __FILE__, 'wp_assessment_plugin_activate' );
function wp_assessment_plugin_activate() {
    ( new WP_Assessment_DB_Manager() )->create_tables();
    wp_assessment_plugin_ensure_admin_capabilities();
    wp_assessment_plugin_ensure_manager_role();
}

function wp_assessment_plugin_ensure_admin_capabilities() {
    $administrator = get_role( 'administrator' );
    if ( $administrator ) {
        foreach ( array( 'manage_assessments', 'manage_assessment_permissions', 'create_assessment_questions', 'create_assessment_answers' ) as $capability ) {
            $administrator->add_cap( $capability );
        }
    }
}

function wp_assessment_plugin_ensure_manager_role() {
    add_role( 'assessment_manager', 'Quản lý bài đánh giá', array( 'read' => true ) );
    WP_Assessment_Permissions::sync_role_capabilities( WP_Assessment_Permissions::permissions() );
}

add_action( 'plugins_loaded', function() {
    // Existing installations may have been activated before these capabilities existed.
    wp_assessment_plugin_ensure_admin_capabilities();
    wp_assessment_plugin_ensure_manager_role();
    if ( get_option( 'wp_assessment_db_version' ) !== WP_ASSESSMENT_PLUGIN_VERSION ) {
        ( new WP_Assessment_DB_Manager() )->create_tables();
    }
} );

add_action( 'rest_api_init', 'wp_assessment_plugin_init_api' );
function wp_assessment_plugin_init_api() {
    foreach ( array( new WP_Assessment_Controller(), new WP_Assessment_Question_Controller(), new WP_Assessment_Answer_Controller(), new WP_Assessment_JWT_Auth() ) as $controller ) {
        $controller->register_routes();
    }
}

add_action( 'admin_menu', array( 'WP_Assessment_Admin', 'register_menu' ) );

add_action( 'rest_api_init', function() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {
        $origin = get_http_origin();
        $allowed_origins = (array) apply_filters( 'wp_assessment_allowed_origins', array( 'http://localhost:5173' ) );
        if ( $origin && in_array( $origin, $allowed_origins, true ) ) {
            header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
            header( 'Vary: Origin' );
            header( 'Access-Control-Allow-Credentials: true' );
        }
        header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE' );
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
        return $value;
    } );
}, 15 );
