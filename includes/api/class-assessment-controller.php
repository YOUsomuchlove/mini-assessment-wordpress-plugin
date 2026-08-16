<?php
class WP_Assessment_Controller extends WP_REST_Controller {
    public function __construct() { $this->namespace = 'assessment/v1'; $this->rest_base = 'assessments'; }
    public function register_routes() {
        register_rest_route( $this->namespace, '/assessments', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'get_items' ), 'permission_callback' => '__return_true' ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'create_item' ), 'permission_callback' => array( $this, 'can_create' ) ),
        ) );
        register_rest_route( $this->namespace, '/assessments/(?P<id>\\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'get_item' ), 'permission_callback' => '__return_true' ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'update_item' ), 'permission_callback' => array( $this, 'can_edit' ) ),
            array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( $this, 'delete_item' ), 'permission_callback' => array( $this, 'can_delete' ) ),
        ) );
    }
    private function authenticated() { $auth = new WP_Assessment_JWT_Auth(); return $auth->authenticate_request(); }
    private function allow( $action ) { $auth = $this->authenticated(); return is_wp_error( $auth ) ? $auth : ( WP_Assessment_Permissions::can( 'assessment', $action ) ? true : new WP_Error( 'forbidden', 'You do not have permission for this action.', array( 'status' => 403 ) ) ); }
    public function can_create() { return $this->allow( 'create' ); } public function can_edit() { return $this->allow( 'edit' ); } public function can_delete() { return $this->allow( 'delete' ); }
    private function may_view_private() { $auth = $this->authenticated(); return ! is_wp_error( $auth ) && WP_Assessment_Permissions::can_view(); }
    private function valid_status( $status ) { return in_array( $status, array( 'draft', 'publish' ), true ); }
    public function get_items( $request ) {
        global $wpdb; $table = $wpdb->prefix . 'assessment'; $is_manager = $this->may_view_private();
        $per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ?: 10 ) ) ); $page = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
        $where = $is_manager ? '1=1' : "status = 'publish'";
        if ( $is_manager && $request->get_param( 'status' ) && $this->valid_status( sanitize_key( $request->get_param( 'status' ) ) ) ) { $where = $wpdb->prepare( 'status = %s', sanitize_key( $request->get_param( 'status' ) ) ); }
        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
        if ( $search !== '' ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where .= $wpdb->prepare( ' AND (title LIKE %s OR description LIKE %s)', $like, $like ); }
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY sort_order ASC, id DESC LIMIT %d OFFSET %d", $per_page, ( $page - 1 ) * $per_page ) );
        $response = rest_ensure_response( $items ); $response->header( 'X-WP-Total', $total ); $response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) ); return $response;
    }
    public function get_item( $request ) { global $wpdb; $table = $wpdb->prefix . 'assessment'; $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint( $request['id'] ) ) ); if ( ! $item || ( $item->status !== 'publish' && ! $this->may_view_private() ) ) { return new WP_Error( 'not_found', 'Assessment not found.', array( 'status' => 404 ) ); } return rest_ensure_response( $item ); }
    public function create_item( $request ) { global $wpdb; $title = sanitize_text_field( $request->get_param( 'title' ) ); $description = wp_kses_post( $request->get_param( 'description' ) ); $status = sanitize_key( $request->get_param( 'status' ) ?: 'publish' ); if ( $title === '' || ! $this->valid_status( $status ) ) { return new WP_Error( 'invalid_data', 'A title and valid status are required.', array( 'status' => 422 ) ); } $table = $wpdb->prefix . 'assessment'; if ( false === $wpdb->insert( $table, array( 'title' => $title, 'description' => $description, 'status' => $status ), array( '%s', '%s', '%s' ) ) ) { WP_Assessment_Logger::database_error( 'create_assessment' ); return new WP_Error( 'db_error', 'Could not create assessment.', array( 'status' => 500 ) ); } return new WP_REST_Response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $wpdb->insert_id ) ), 201 ); }
    public function update_item( $request ) { global $wpdb; $table = $wpdb->prefix . 'assessment'; $id = absint( $request['id'] ); $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) ); if ( ! $item ) { return new WP_Error( 'not_found', 'Assessment not found.', array( 'status' => 404 ) ); } $title = $request->has_param( 'title' ) ? sanitize_text_field( $request->get_param( 'title' ) ) : $item->title; $status = $request->has_param( 'status' ) ? sanitize_key( $request->get_param( 'status' ) ) : $item->status; if ( $title === '' || ! $this->valid_status( $status ) ) { return new WP_Error( 'invalid_data', 'A title and valid status are required.', array( 'status' => 422 ) ); } $wpdb->update( $table, array( 'title' => $title, 'description' => $request->has_param( 'description' ) ? wp_kses_post( $request->get_param( 'description' ) ) : $item->description, 'status' => $status ), array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) ); return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) ) ); }
    public function delete_item( $request ) { global $wpdb; $id = absint( $request['id'] ); $assessments = $wpdb->prefix . 'assessment'; $questions = $wpdb->prefix . 'assessment_questions'; $answers = $wpdb->prefix . 'assessment_answers'; if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $assessments WHERE id = %d", $id ) ) ) { return new WP_Error( 'not_found', 'Assessment not found.', array( 'status' => 404 ) ); } $question_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $questions WHERE assessment_id = %d", $id ) ); if ( $question_ids ) { $placeholders = implode( ',', array_fill( 0, count( $question_ids ), '%d' ) ); $wpdb->query( $wpdb->prepare( "DELETE FROM $answers WHERE question_id IN ($placeholders)", $question_ids ) ); } $wpdb->delete( $questions, array( 'assessment_id' => $id ), array( '%d' ) ); $wpdb->delete( $assessments, array( 'id' => $id ), array( '%d' ) ); return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) ); }
}
