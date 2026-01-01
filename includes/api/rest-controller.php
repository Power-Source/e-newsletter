<?php

/**
 * Newsletter REST Controller Basis
 */

abstract class Newsletter_REST_Controller extends WP_REST_Controller {

    /**
     * Namespace für API
     */
    protected $namespace = 'newsletter/v2';

    /**
     * Prüft API-Berechtigung
     */
    public function check_api_permission($request) {
        // API Key aus Header oder Parameter holen
        $api_key = $request->get_header('X-API-Key') ?: $request->get_param('api_key');
        $api_secret = $request->get_header('X-API-Secret') ?: $request->get_param('api_secret');

        if (!$api_key || !$api_secret) {
            return new WP_Error(
                'newsletter_api_missing_credentials',
                __('API-Zugangsdaten fehlen', 'newsletter'),
                array('status' => 401)
            );
        }

        $api = NewsletterRestApi::instance();
        $key_data = $api->validate_api_key($api_key, $api_secret);

        if (!$key_data) {
            return new WP_Error(
                'newsletter_api_invalid_credentials',
                __('Ungültige API-Zugangsdaten', 'newsletter'),
                array('status' => 401)
            );
        }

        // Permissions prüfen
        $permissions = json_decode($key_data->permissions, true) ?: array();
        $required_permission = $this->get_required_permission($request);

        if (!$this->has_permission($permissions, $required_permission, $request->get_method())) {
            return new WP_Error(
                'newsletter_api_insufficient_permissions',
                __('Unzureichende API-Berechtigung', 'newsletter'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Ermittelt erforderliche Berechtigung basierend auf Endpoint
     */
    protected function get_required_permission($request) {
        $route = $request->get_route();
        
        if (strpos($route, '/subscribers') !== false) {
            return 'subscribers';
        } elseif (strpos($route, '/lists') !== false) {
            return 'lists';
        } elseif (strpos($route, '/newsletters') !== false) {
            return 'newsletters';
        } elseif (strpos($route, '/subscriptions') !== false) {
            return 'subscriptions';
        } elseif (strpos($route, '/extrafields') !== false) {
            return 'extrafields';
        }
        
        return 'general';
    }

    /**
     * Prüft spezifische Berechtigung
     */
    protected function has_permission($user_permissions, $resource, $method) {
        if (!isset($user_permissions[$resource])) {
            return false;
        }

        $required_action = $this->method_to_action($method);
        return in_array($required_action, $user_permissions[$resource]);
    }

    /**
     * Konvertiert HTTP-Methode zu Action
     */
    protected function method_to_action($method) {
        switch (strtoupper($method)) {
            case 'GET':
                return 'read';
            case 'POST':
                return 'write';
            case 'PUT':
            case 'PATCH':
                return 'write';
            case 'DELETE':
                return 'delete';
            default:
                return 'read';
        }
    }

    /**
     * Standardisierte Erfolgs-Antwort
     */
    protected function success_response($data, $message = '', $status = 200) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data,
            'message' => $message
        ), $status);
    }

    /**
     * Standardisierte Fehler-Antwort
     */
    protected function error_response($message, $code = 'error', $status = 400) {
        return new WP_Error($code, $message, array('status' => $status));
    }

    /**
     * Paginierung für Listen
     */
    protected function paginate_results($query_results, $page = 1, $per_page = 10) {
        $total = count($query_results);
        $offset = ($page - 1) * $per_page;
        $items = array_slice($query_results, $offset, $per_page);

        return array(
            'items' => $items,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            )
        );
    }

    /**
     * Validierung für E-Mail-Adressen
     */
    protected function validate_email($email) {
        if (!is_email($email)) {
            return new WP_Error(
                'newsletter_api_invalid_email',
                __('Ungültige E-Mail-Adresse', 'newsletter'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Subscriber-Daten bereinigen für API-Ausgabe
     */
    protected function prepare_subscriber_data($subscriber) {
        $data = array(
            'id' => (int) $subscriber->id,
            'email' => $subscriber->email,
            'name' => $subscriber->name,
            'surname' => $subscriber->surname,
            'status' => $subscriber->status,
            'created' => $subscriber->created,
            'updated' => $subscriber->updated,
            'lists' => array(),
            'extra_fields' => array()
        );

        // Listen hinzufügen
        $newsletter = Newsletter::instance();
        $lists = $newsletter->get_lists();
        foreach ($lists as $list) {
            if (isset($subscriber->{'list_' . $list->id}) && $subscriber->{'list_' . $list->id}) {
                $data['lists'][] = array(
                    'id' => (int) $list->id,
                    'name' => $list->name
                );
            }
        }

        // Extra Fields hinzufügen
        for ($i = 1; $i <= 20; $i++) {
            if (isset($subscriber->{'profile_' . $i}) && !empty($subscriber->{'profile_' . $i})) {
                $data['extra_fields']['profile_' . $i] = $subscriber->{'profile_' . $i};
            }
        }

        return $data;
    }
}
