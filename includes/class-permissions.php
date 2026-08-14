<?php

class WP_Assessment_Permissions {
    public static function actions() { return array( 'view', 'create', 'edit', 'delete' ); }
    public static function entities() { return array( 'assessment' => 'Bài đánh giá', 'question' => 'Câu hỏi', 'answer' => 'Phương án trả lời' ); }
    public static function permission_key( $entity, $action ) { return $entity . '_' . $action; }
    public static function all_permission_keys() { $keys = array(); foreach ( array_keys( self::entities() ) as $entity ) { foreach ( self::actions() as $action ) { $keys[] = self::permission_key( $entity, $action ); } } return $keys; }
    public static function permissions() { $value = get_option( WP_ASSESSMENT_PERMISSION_OPTION, array() ); return is_array( $value ) ? $value : array(); }
    public static function role_has( $role, $permission ) {
        // Built-in Administrator and the requested Assessment Manager role have full module access.
        if ( in_array( $role, array( 'administrator', 'assessment_manager' ), true ) ) { return true; }
        $permissions = self::permissions();
        if ( ! empty( $permissions[ $role ][ $permission ] ) || ! empty( $permissions[ $role ]['manager'] ) ) { return true; }
        // Read legacy 4-column configuration during upgrade, until it is saved again.
        $parts = explode( '_', $permission );
        return count( $parts ) === 2 && ! empty( $permissions[ $role ][ $parts[1] ] );
    }
    public static function user_has( $action, $user_id = 0 ) {
        $user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
        if ( ! $user || ! $user->exists() || user_can( $user, 'manage_options' ) ) { return (bool) $user; }
        foreach ( (array) $user->roles as $role ) { if ( self::role_has( $role, $action ) ) { return true; } }
        return false;
    }
    public static function can( $entity, $action ) { return self::user_has( self::permission_key( $entity, $action ) ); }
    public static function can_view( $entity = 'assessment' ) { return self::can( $entity, 'view' ); }
    public static function can_create( $entity = 'assessment' ) { return self::can( $entity, 'create' ); }
    public static function can_edit( $entity = 'assessment' ) { return self::can( $entity, 'edit' ); }
    public static function can_delete( $entity = 'assessment' ) { return self::can( $entity, 'delete' ); }
    public static function can_manage_content() { return self::can_edit( 'assessment' ) && self::can_delete( 'assessment' ); }
    public static function can_create_question() { return self::can_create( 'question' ); }
    public static function can_create_answer() { return self::can_create( 'answer' ); }
    public static function can_manage_permissions() { return self::user_has( 'manager' ); }
    public static function sync_role_capabilities( $permissions ) {
        foreach ( wp_roles()->roles as $role_key => $role_data ) {
            $role = get_role( $role_key ); if ( ! $role ) { continue; }
            foreach ( array_merge( array( 'manage_assessments', 'manage_assessment_permissions', 'create_assessment_questions', 'create_assessment_answers', 'view_assessments', 'create_assessments', 'edit_assessments', 'delete_assessments' ), array_map( static function( $key ) { return $key . 's'; }, self::all_permission_keys() ) ) as $capability ) { $role->remove_cap( $capability ); }
            $config = ( $role_key === 'assessment_manager' ) ? array_fill_keys( self::all_permission_keys(), true ) : ( $permissions[ $role_key ] ?? array() );
            foreach ( self::all_permission_keys() as $key ) { if ( ! empty( $config[ $key ] ) ) { $role->add_cap( $key . 's' ); } }
            if ( $role_key === 'administrator' || $role_key === 'assessment_manager' || ! empty( $config['manager'] ) ) { $role->add_cap( 'manage_assessments' ); $role->add_cap( 'manage_assessment_permissions' ); }
        }
    }
}
