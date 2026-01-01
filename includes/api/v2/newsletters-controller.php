<?php

defined('ABSPATH') || exit;

/**
 * REST API Newsletter Controller für Newsletter v2
 */
class Newsletter_REST_Newsletters_Controller extends Newsletter_REST_Controller {

    protected $namespace = 'newsletter/v2';
    protected $rest_base = 'newsletters';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Eindeutige Kennung für den Newsletter.', 'newsletter'),
                        'type'        => 'integer',
                    ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
                'args'                => array(
                    'force' => array(
                        'type'        => 'boolean',
                        'default'     => false,
                        'description' => __('Erforderlich, true ist, da Newsletter keine Papierkorb-Unterstützung haben.', 'newsletter'),
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/send', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'send_newsletter'),
                'permission_callback' => array($this, 'send_newsletter_permissions_check'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Eindeutige Kennung für den Newsletter.', 'newsletter'),
                        'type'        => 'integer',
                    ),
                ),
            ),
        ));
    }

    /**
     * Holt eine Liste von Newslettern
     */
    public function get_items($request) {
        global $wpdb;

        $prepared_args = array();
        $prepared_args['number']  = $request['per_page'];
        $prepared_args['offset']  = ($request['page'] - 1) * $prepared_args['number'];
        $prepared_args['search']  = $request['search'];
        $prepared_args['status']  = $request['status'];

        $table_name = $wpdb->prefix . 'newsletter_emails';
        
        $where_clauses = array("type = 'message'");
        $where_values = array();

        if (!empty($prepared_args['search'])) {
            $where_clauses[] = "(subject LIKE %s OR message LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($prepared_args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if (!empty($prepared_args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $prepared_args['status'];
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        // Gesamt anzahl holen
        $total_newsletters = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name $where_sql",
            $where_values
        ));

        // Newsletter holen
        $newsletters = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where_sql ORDER BY id DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($prepared_args['number'], $prepared_args['offset']))
        ));

        if (empty($newsletters)) {
            $newsletters = array();
        }

        $response = array();
        foreach ($newsletters as $newsletter) {
            $data = $this->prepare_item_for_response($newsletter, $request);
            $response[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($response);
        $response->header('X-Total-Count', $total_newsletters);
        $response->header('X-Total-Pages', ceil($total_newsletters / $prepared_args['number']));

        return $response;
    }

    /**
     * Holt einen einzelnen Newsletter
     */
    public function get_item($request) {
        $id = (int) $request['id'];
        $newsletter = $this->get_newsletter($id);

        if (is_wp_error($newsletter)) {
            return $newsletter;
        }

        $data = $this->prepare_item_for_response($newsletter, $request);
        return rest_ensure_response($data);
    }

    /**
     * Erstellt einen neuen Newsletter
     */
    public function create_item($request) {
        global $wpdb;

        $subject = sanitize_text_field($request['subject']);
        if (empty($subject)) {
            return new WP_Error('newsletter_rest_missing_subject', __('Newsletter-Betreff ist erforderlich.', 'newsletter'), array('status' => 400));
        }

        $message = wp_kses_post($request['message']);
        if (empty($message)) {
            return new WP_Error('newsletter_rest_missing_message', __('Newsletter-Inhalt ist erforderlich.', 'newsletter'), array('status' => 400));
        }

        $data = array(
            'subject' => $subject,
            'message' => $message,
            'type' => 'message',
            'status' => 'new',
            'created' => current_time('mysql'),
            'updated' => current_time('mysql'),
            'send_on' => 0,
            'track' => 1,
            'editor' => 0,
        );

        // Optionale Felder
        if (!empty($request['preheader'])) {
            $data['preheader'] = sanitize_text_field($request['preheader']);
        }

        $table_name = $wpdb->prefix . 'newsletter_emails';
        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            return new WP_Error('newsletter_rest_cannot_create', __('Newsletter konnte nicht erstellt werden.', 'newsletter'), array('status' => 500));
        }

        $newsletter_id = $wpdb->insert_id;
        $newsletter = $this->get_newsletter($newsletter_id);

        $data = $this->prepare_item_for_response($newsletter, $request);
        $response = rest_ensure_response($data);
        $response->set_status(201);

        return $response;
    }

    /**
     * Aktualisiert einen Newsletter
     */
    public function update_item($request) {
        global $wpdb;

        $id = (int) $request['id'];
        $newsletter = $this->get_newsletter($id);

        if (is_wp_error($newsletter)) {
            return $newsletter;
        }

        $data = array();

        if (!empty($request['subject'])) {
            $data['subject'] = sanitize_text_field($request['subject']);
        }

        if (!empty($request['message'])) {
            $data['message'] = wp_kses_post($request['message']);
        }

        if (!empty($request['preheader'])) {
            $data['preheader'] = sanitize_text_field($request['preheader']);
        }

        if (!empty($request['status'])) {
            $allowed_statuses = array('new', 'sending', 'sent', 'paused', 'error');
            if (in_array($request['status'], $allowed_statuses)) {
                $data['status'] = $request['status'];
            }
        }

        if (!empty($data)) {
            $data['updated'] = current_time('mysql');
            
            $table_name = $wpdb->prefix . 'newsletter_emails';
            $result = $wpdb->update($table_name, $data, array('id' => $id));

            if ($result === false) {
                return new WP_Error('newsletter_rest_cannot_update', __('Newsletter konnte nicht aktualisiert werden.', 'newsletter'), array('status' => 500));
            }
        }

        $newsletter = $this->get_newsletter($id);
        $data = $this->prepare_item_for_response($newsletter, $request);
        return rest_ensure_response($data);
    }

    /**
     * Löscht einen Newsletter
     */
    public function delete_item($request) {
        global $wpdb;

        $id = (int) $request['id'];
        $newsletter = $this->get_newsletter($id);

        if (is_wp_error($newsletter)) {
            return $newsletter;
        }

        $table_name = $wpdb->prefix . 'newsletter_emails';
        $result = $wpdb->delete($table_name, array('id' => $id));

        if ($result === false) {
            return new WP_Error('newsletter_rest_cannot_delete', __('Newsletter konnte nicht gelöscht werden.', 'newsletter'), array('status' => 500));
        }

        $data = $this->prepare_item_for_response($newsletter, $request);
        $response = rest_ensure_response($data);
        $response->set_data(array(
            'deleted' => true,
            'previous' => $response->get_data(),
        ));

        return $response;
    }

    /**
     * Sendet einen Newsletter
     */
    public function send_newsletter($request) {
        $id = (int) $request['id'];
        $newsletter = $this->get_newsletter($id);

        if (is_wp_error($newsletter)) {
            return $newsletter;
        }

        if ($newsletter->status !== 'new') {
            return new WP_Error('newsletter_rest_cannot_send', __('Nur neue Newsletter können gesendet werden.', 'newsletter'), array('status' => 400));
        }

        // Newsletter über die Newsletter-API senden
        $newsletter_instance = Newsletter::instance();
        $result = $newsletter_instance->send($newsletter);

        if (is_wp_error($result)) {
            return $result;
        }

        $newsletter = $this->get_newsletter($id);
        $data = $this->prepare_item_for_response($newsletter, $request);
        
        $response = rest_ensure_response($data);
        $response->set_data(array(
            'sent' => true,
            'newsletter' => $response->get_data(),
        ));

        return $response;
    }

    /**
     * Bereitet ein Element für die Antwort vor
     */
    public function prepare_item_for_response($newsletter, $request) {
        $data = array(
            'id'          => (int) $newsletter->id,
            'subject'     => $newsletter->subject,
            'preheader'   => $newsletter->preheader ?? '',
            'message'     => $newsletter->message,
            'status'      => $newsletter->status,
            'type'        => $newsletter->type,
            'created'     => $newsletter->created,
            'updated'     => $newsletter->updated,
            'send_on'     => (int) $newsletter->send_on,
            'sent'        => (int) $newsletter->sent,
            'total'       => (int) $newsletter->total,
            'track'       => (int) $newsletter->track,
        );

        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $request['context']);

        return $data;
    }

    /**
     * Holt einen Newsletter aus der Datenbank
     */
    private function get_newsletter($id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'newsletter_emails';
        $newsletter = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND type = 'message'",
            $id
        ));

        if (!$newsletter) {
            return new WP_Error('newsletter_rest_newsletter_invalid_id', __('Newsletter nicht gefunden.', 'newsletter'), array('status' => 404));
        }

        return $newsletter;
    }

    /**
     * Berechtigung zum Senden von Newsletter prüfen
     */
    public function send_newsletter_permissions_check($request) {
        return $this->create_item_permissions_check($request);
    }

    /**
     * Holt die Sammlungsparameter
     */
    public function get_collection_params() {
        return array(
            'context'  => $this->get_context_param(array('default' => 'view')),
            'page'     => array(
                'description'       => __('Aktuelle Seite der Sammlung.', 'newsletter'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __('Maximale Anzahl der Elemente, die im Ergebnissatz zurückgegeben werden sollen.', 'newsletter'),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'description'       => __('Schränkt Ergebnisse auf Elemente ein, die einer Suchanfrage entsprechen.', 'newsletter'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'status'   => array(
                'description'       => __('Schränkt Ergebnisse auf Newsletter mit einem bestimmten Status ein.', 'newsletter'),
                'type'              => 'string',
                'enum'              => array('new', 'sending', 'sent', 'paused', 'error'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}
