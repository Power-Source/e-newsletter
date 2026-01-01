<?php

defined('ABSPATH') || exit;

/**
 * REST API Listen Controller für Newsletter v2
 */
class Newsletter_REST_Lists_Controller extends Newsletter_REST_Controller {

    protected $namespace = 'newsletter/v2';
    protected $rest_base = 'lists';

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
                        'description' => __('Eindeutige Kennung für die Liste.', 'newsletter'),
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
                        'description' => __('Erforderlich, true ist, da Listen keine Papierkorb-Unterstützung haben.', 'newsletter'),
                    ),
                ),
            ),
        ));
    }

    /**
     * Holt eine Liste von Listen
     */
    public function get_items($request) {
        global $wpdb;

        $prepared_args = array();
        $prepared_args['number']  = $request['per_page'];
        $prepared_args['offset']  = ($request['page'] - 1) * $prepared_args['number'];
        $prepared_args['search']  = $request['search'];

        $lists = array();
        
        // Newsletter-Listen von 1-20 (Standard-Listen)
        for ($i = 1; $i <= 20; $i++) {
            $list_name = Newsletter::instance()->get_option('list_' . $i);
            if (!empty($list_name)) {
                $lists[] = array(
                    'id' => $i,
                    'name' => $list_name,
                    'slug' => 'list_' . $i,
                    'description' => '',
                    'count' => $this->get_list_subscriber_count($i)
                );
            }
        }

        // Filtern nach Suchbegriff
        if (!empty($prepared_args['search'])) {
            $search = strtolower($prepared_args['search']);
            $lists = array_filter($lists, function($list) use ($search) {
                return strpos(strtolower($list['name']), $search) !== false;
            });
        }

        // Paginierung anwenden
        $total_lists = count($lists);
        if ($prepared_args['offset']) {
            $lists = array_slice($lists, $prepared_args['offset']);
        }
        if ($prepared_args['number']) {
            $lists = array_slice($lists, 0, $prepared_args['number']);
        }

        $response = array();
        foreach ($lists as $list) {
            $data = $this->prepare_item_for_response($list, $request);
            $response[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($response);
        $response->header('X-Total-Count', $total_lists);
        $response->header('X-Total-Pages', ceil($total_lists / $prepared_args['number']));

        return $response;
    }

    /**
     * Holt eine einzelne Liste
     */
    public function get_item($request) {
        $id = (int) $request['id'];
        
        if ($id < 1 || $id > 20) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Ungültige Listen-ID.', 'newsletter'), array('status' => 404));
        }

        $list_name = Newsletter::instance()->get_option('list_' . $id);
        if (empty($list_name)) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Liste nicht gefunden.', 'newsletter'), array('status' => 404));
        }

        $list = array(
            'id' => $id,
            'name' => $list_name,
            'slug' => 'list_' . $id,
            'description' => '',
            'count' => $this->get_list_subscriber_count($id)
        );

        $data = $this->prepare_item_for_response($list, $request);
        return rest_ensure_response($data);
    }

    /**
     * Erstellt eine neue Liste
     */
    public function create_item($request) {
        if (!empty($request['id'])) {
            return new WP_Error('newsletter_rest_list_exists', __('Liste-ID kann nicht festgelegt werden.', 'newsletter'), array('status' => 400));
        }

        // Suche freie Liste (1-20)
        $free_id = null;
        for ($i = 1; $i <= 20; $i++) {
            $existing = Newsletter::instance()->get_option('list_' . $i);
            if (empty($existing)) {
                $free_id = $i;
                break;
            }
        }

        if (!$free_id) {
            return new WP_Error('newsletter_rest_list_limit', __('Maximale Anzahl von Listen erreicht (20).', 'newsletter'), array('status' => 400));
        }

        $list_name = sanitize_text_field($request['name']);
        if (empty($list_name)) {
            return new WP_Error('newsletter_rest_missing_name', __('Listen-Name ist erforderlich.', 'newsletter'), array('status' => 400));
        }

        // Liste speichern
        Newsletter::instance()->save_option('list_' . $free_id, $list_name);

        $list = array(
            'id' => $free_id,
            'name' => $list_name,
            'slug' => 'list_' . $free_id,
            'description' => '',
            'count' => 0
        );

        $data = $this->prepare_item_for_response($list, $request);
        $response = rest_ensure_response($data);
        $response->set_status(201);

        return $response;
    }

    /**
     * Aktualisiert eine Liste
     */
    public function update_item($request) {
        $id = (int) $request['id'];
        
        if ($id < 1 || $id > 20) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Ungültige Listen-ID.', 'newsletter'), array('status' => 404));
        }

        $existing = Newsletter::instance()->get_option('list_' . $id);
        if (empty($existing)) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Liste nicht gefunden.', 'newsletter'), array('status' => 404));
        }

        $list_name = sanitize_text_field($request['name']);
        if (empty($list_name)) {
            return new WP_Error('newsletter_rest_missing_name', __('Listen-Name ist erforderlich.', 'newsletter'), array('status' => 400));
        }

        // Liste aktualisieren
        Newsletter::instance()->save_option('list_' . $id, $list_name);

        $list = array(
            'id' => $id,
            'name' => $list_name,
            'slug' => 'list_' . $id,
            'description' => '',
            'count' => $this->get_list_subscriber_count($id)
        );

        $data = $this->prepare_item_for_response($list, $request);
        return rest_ensure_response($data);
    }

    /**
     * Löscht eine Liste
     */
    public function delete_item($request) {
        $id = (int) $request['id'];
        
        if ($id < 1 || $id > 20) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Ungültige Listen-ID.', 'newsletter'), array('status' => 404));
        }

        $existing = Newsletter::instance()->get_option('list_' . $id);
        if (empty($existing)) {
            return new WP_Error('newsletter_rest_list_invalid_id', __('Liste nicht gefunden.', 'newsletter'), array('status' => 404));
        }

        $list = array(
            'id' => $id,
            'name' => $existing,
            'slug' => 'list_' . $id,
            'description' => '',
            'count' => $this->get_list_subscriber_count($id)
        );

        // Liste löschen
        Newsletter::instance()->save_option('list_' . $id, '');

        $data = $this->prepare_item_for_response($list, $request);
        $response = rest_ensure_response($data);
        $response->set_data(array(
            'deleted' => true,
            'previous' => $response->get_data(),
        ));

        return $response;
    }

    /**
     * Bereitet ein Element für die Antwort vor
     */
    public function prepare_item_for_response($list, $request) {
        $data = array(
            'id'          => $list['id'],
            'name'        => $list['name'],
            'slug'        => $list['slug'],
            'description' => $list['description'],
            'count'       => $list['count'],
        );

        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $request['context']);

        return $data;
    }

    /**
     * Holt die Anzahl der Abonnenten für eine Liste
     */
    private function get_list_subscriber_count($list_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsletter';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE list_$list_id = 1 AND status = 'C'",
        ));
        
        return (int) $count;
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
        );
    }
}
