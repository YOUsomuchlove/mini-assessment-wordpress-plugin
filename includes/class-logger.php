<?php
class WP_Assessment_Logger {
    /**
     * Records database failures without logging request payloads, credentials or tokens.
     */
    public static function database_error( $operation ) {
        global $wpdb;

        $detail = ! empty( $wpdb->last_error ) ? sanitize_text_field( $wpdb->last_error ) : 'Unknown database error.';
        error_log( sprintf( '[Mini Assessment] Database error during %s: %s', sanitize_key( $operation ), $detail ) );
    }
}
