<?php

/**
 * Newsletter V2 Subscribers API Controller
 */

class Newsletter_V2_Subscribers_Controller extends Newsletter_REST_Controller {

    protected $rest_base = 'subscribers';

    public function register_routes() {
        // GET /newsletter/v2/subscribers
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'permission_callback' => array($this, 'check_api_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ),
                    'status' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'list_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                )
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_item'),
                'permission_callback' => array($this, 'check_api_permission'),
                'args' => array(
                    'email' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_email',
                        'validate_callback' => array($this, 'validate_email'),
                    ),
                    'name' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'surname' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'lists' => array(
                        'default' => array(),
                        'sanitize_callback' => array($this, 'sanitize_list_ids'),
                    ),
                )
            )
        ));

        // GET/PUT/DELETE /newsletter/v2/subscribers/{id}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_item'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
        ));

        // GET /newsletter/v2/subscribers/by-email/{email}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/by-email/(?P<email>[^/]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_item_by_email'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
    }

    /**
     * Alle Subscriber abrufen
     */
    public function get_items($request) {
        $newsletter = Newsletter::instance();
        
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100); // Max 100 pro Seite
        $status = $request->get_param('status');
        $list_id = $request->get_param('list_id');

        // Query bauen
        $where = array('1=1');
        $where_values = array();

        if (!empty($status)) {
            $where[] = 'status = %s';
            $where_values[] = $status;
        }

        if ($list_id > 0) {
            $where[] = 'list_' . intval($list_id) . ' = 1';
        }

        $where_sql = implode(' AND ', $where);
        
        // Gesamt-Anzahl ermitteln
        global $wpdb;
        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter WHERE $where_sql";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));

        // Paginierte Ergebnisse
        $offset = ($page - 1) * $per_page;
        $query = "SELECT * FROM {$wpdb->prefix}newsletter WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $subscribers = $wpdb->get_results($wpdb->prepare($query, $where_values));

        // Daten für API aufbereiten
        $items = array();
        foreach ($subscribers as $subscriber) {
            $items[] = $this->prepare_subscriber_data($subscriber);
        }

        return $this->success_response(array(
            'items' => $items,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $total,
                'total_pages' => ceil($total / $per_page)
            )
        ));
    }

    /**
     * Einzelnen Subscriber abrufen
     */
    public function get_item($request) {
        $newsletter = Newsletter::instance();
        $id = $request->get_param('id');
        
        $subscriber = $newsletter->get_user($id);
        
        if (!$subscriber) {
            return $this->error_response(__('Subscriber nicht gefunden', 'newsletter'), 'not_found', 404);
        }

        return $this->success_response($this->prepare_subscriber_data($subscriber));
    }

    /**
     * Subscriber per E-Mail abrufen
     */
    public function get_item_by_email($request) {
        $newsletter = Newsletter::instance();
        $email = $request->get_param('email');
        
        $subscriber = $newsletter->get_user_by_email($email);
        
        if (!$subscriber) {
            return $this->error_response(__('Subscriber nicht gefunden', 'newsletter'), 'not_found', 404);
        }

        return $this->success_response($this->prepare_subscriber_data($subscriber));
    }

    /**
     * Neuen Subscriber erstellen
     */
    public function create_item($request) {
        $newsletter = Newsletter::instance();
        
        $email = $request->get_param('email');
        $name = $request->get_param('name');
        $surname = $request->get_param('surname');
        $lists = $request->get_param('lists');

        // Prüfen ob E-Mail bereits existiert
        $existing = $newsletter->get_user_by_email($email);
        if ($existing) {
            return $this->error_response(__('E-Mail-Adresse bereits vorhanden', 'newsletter'), 'email_exists', 400);
        }

        // Subscriber-Daten vorbereiten
        $user_data = array(
            'email' => $email,
            'name' => $name,
            'surname' => $surname,
            'status' => 'C' // Confirmed
        );

        // Listen hinzufügen
        if (is_array($lists)) {
            foreach ($lists as $list_id) {
                $user_data['list_' . intval($list_id)] = 1;
            }
        }

        // Subscriber erstellen
        $user_id = $newsletter->save_user($user_data);
        
        if (!$user_id) {
            return $this->error_response(__('Fehler beim Erstellen des Subscribers', 'newsletter'), 'creation_failed', 500);
        }

        $subscriber = $newsletter->get_user($user_id);
        
        return $this->success_response(
            $this->prepare_subscriber_data($subscriber),
            __('Subscriber erfolgreich erstellt', 'newsletter'),
            201
        );
    }

    /**
     * Subscriber aktualisieren
     */
    public function update_item($request) {
        $newsletter = Newsletter::instance();
        $id = $request->get_param('id');
        
        $subscriber = $newsletter->get_user($id);
        if (!$subscriber) {
            return $this->error_response(__('Subscriber nicht gefunden', 'newsletter'), 'not_found', 404);
        }

        // Update-Daten sammeln
        $update_data = array('id' => $id);
        
        $params = array('name', 'surname', 'status');
        foreach ($params as $param) {
            if ($request->has_param($param)) {
                $update_data[$param] = $request->get_param($param);
            }
        }

        // Listen aktualisieren
        if ($request->has_param('lists')) {
            $lists = $request->get_param('lists');
            
            // Alle Listen zurücksetzen
            $all_lists = $newsletter->get_lists();
            foreach ($all_lists as $list) {
                $update_data['list_' . $list->id] = 0;
            }
            
            // Neue Listen setzen
            if (is_array($lists)) {
                foreach ($lists as $list_id) {
                    $update_data['list_' . intval($list_id)] = 1;
                }
            }
        }

        // Aktualisierung durchführen
        $result = $newsletter->save_user($update_data);
        
        if (!$result) {
            return $this->error_response(__('Fehler beim Aktualisieren des Subscribers', 'newsletter'), 'update_failed', 500);
        }

        $updated_subscriber = $newsletter->get_user($id);
        
        return $this->success_response(
            $this->prepare_subscriber_data($updated_subscriber),
            __('Subscriber erfolgreich aktualisiert', 'newsletter')
        );
    }

    /**
     * Subscriber löschen
     */
    public function delete_item($request) {
        $newsletter = Newsletter::instance();
        $id = $request->get_param('id');
        
        $subscriber = $newsletter->get_user($id);
        if (!$subscriber) {
            return $this->error_response(__('Subscriber nicht gefunden', 'newsletter'), 'not_found', 404);
        }

        // Löschen
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'newsletter', array('id' => $id), array('%d'));
        
        if ($result === false) {
            return $this->error_response(__('Fehler beim Löschen des Subscribers', 'newsletter'), 'deletion_failed', 500);
        }

        return $this->success_response(
            array('id' => $id),
            __('Subscriber erfolgreich gelöscht', 'newsletter')
        );
    }

    /**
     * Listen-IDs bereinigen
     */
    public function sanitize_list_ids($lists) {
        if (!is_array($lists)) {
            return array();
        }
        
        return array_map('absint', $lists);
    }
}

// Controller registrieren
add_action('rest_api_init', function() {
    $controller = new Newsletter_V2_Subscribers_Controller();
    $controller->register_routes();
});
