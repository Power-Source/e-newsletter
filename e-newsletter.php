<?php
/*
Plugin Name: PS-eNewsletter
Plugin URI: https://psource.eimen.net/wiki/ps-enewsletter-dokumentation/
Description: Das ultimative Newsletter Plugin für ClassicPress. Keine Drittanbieterdienste oder Abo-Kosten, Newsletter direkt aus dem ClassicPress-Dashboard managen und versenden.
Version: 1.0.8
Text Domain: email-newsletter
Author: PSOURCE
Author URI: https://psource.eimen.net/


Copyright 2018-2026 PSOURCE (https://psource.eimen.net/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.
*/

require_once( 'email-newsletter-files/class.functions.php' );
require_once( 'email-newsletter-files/class.builder-v2.php' );
include_once( 'email-newsletter-files/class.wp_widgets.php' );
/**
* Plugin main class
**/

class Email_Newsletter extends Email_Newsletter_functions {

    var $plugin_ver;
    var $plugin_main_file;
    var $plugin_dir;
    var $plugin_url;
    var $template_directory;
    var $template_custom_directory;
    var $settings;
    var $tb_prefix;
    var $cron_send_name;
    var $cron_bounce_name;
    var $plugin_templates = array();
    var $capabilities = array();
    var $loaded_theme_options = '';
    var $builder_v2;

    var $debug;

    /**
     * PHP 5 constructor
     **/
    function __construct() {
        global $wpdb;


        $this->plugin_ver = '1.0.8';

        // Debug flag is resolved after plugin settings are loaded.
        $this->debug = 0;

        //checking for MultiSite
        if ( 1 < $wpdb->blogid )
            $this->tb_prefix = $wpdb->base_prefix . $wpdb->blogid . '_';
        else
            $this->tb_prefix = $wpdb->base_prefix;

        //set cron names
        $this->cron_send_name = 'e_newsletter_cron_send_' . $wpdb->blogid;
        $this->cron_bounce_name = 'e_newsletter_cron_check_bounces_' . $wpdb->blogid;

        //setup proper directories
        $this->plugin_main_file = __FILE__;
        $this->plugin_dir = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugins_url( '/', __FILE__ );
        if(!isset($this->plugin_dir) || !isset($this->plugin_url))
            wp_die( __('Es gab ein Problem beim Bestimmen des Plugin-Pfads oder der URL', 'email-newsletter' ) );

        //templates directories
        $this->template_directory = $this->plugin_dir . 'email-newsletter-files/templates';
        $this->template_custom_directory = $this->get_custom_theme_dir();

        //get all setting of plugin
        $this->settings = $this->get_settings();
        $settings_debug = ( is_array( $this->settings ) && ! empty( $this->settings['debug_enabled'] ) ) ? 1 : 0;
        $constant_debug = ( defined( 'ENEWSLETTER_DEBUG' ) && ENEWSLETTER_DEBUG ) ? 1 : 0;
        $this->debug = ( $constant_debug || $settings_debug ) ? 1 : 0;
        $this->debug = apply_filters( 'email_newsletter_debug_enabled', $this->debug ? 1 : 0 );
        $this->builder_v2 = new Email_Newsletter_Builder_V2( $this );

        // Setup all plugin capabilities
        $this->capabilities['create_newsletter'] = __('Newsletter erstellen','email-newsletter');
        $this->capabilities['save_newsletter'] = __('Newsletter bearbeiten','email-newsletter');
        $this->capabilities['send_newsletter'] = __('Newsletter senden','email-newsletter');
        $this->capabilities['delete_newsletter'] = __('Newsletter löschen','email-newsletter');
        $this->capabilities['create_newsletter_group'] = __('Newsletter-Gruppen erstellen','email-newsletter');
        $this->capabilities['edit_newsletter_group'] = __('Newsletter-Gruppen bearbeiten','email-newsletter');
        $this->capabilities['delete_newsletter_group'] = __('Newsletter-Gruppen löschen','email-newsletter');
        $this->capabilities['change_newsletter_group'] = __('Newsletter-Gruppen ändern','email-newsletter');
        $this->capabilities['view_newsletter_members'] = __('Newsletter-Abonnenten anzeigen','email-newsletter');
        $this->capabilities['add_newsletter_member'] = __('Newsletter-Abonnenten hinzufügen','email-newsletter');
        $this->capabilities['edit_newsletter_member'] = __('Newsletter-Abonnenten bearbeiten','email-newsletter');
        $this->capabilities['delete_newsletter_member'] = __('Newsletter-Abonnenten löschen','email-newsletter');
        $this->capabilities['add_members_group'] = __('Abonnenten zur Gruppe hinzufügen','email-newsletter');
        $this->capabilities['delete_members_group'] = __('Abonnenten aus Gruppe löschen','email-newsletter');
        $this->capabilities['save_newsletter_settings'] = __('Newsletter-Einstellungen Speichern','email-newsletter');
        $this->capabilities['view_newsletter_dashboard'] = __('Dashboard-Seite anzeigen','email-newsletter');
        $this->capabilities['import_newsletter_members'] = __('Abonnenten importieren','email-newsletter');
        $this->capabilities['install_newsletter'] = __('Erstinstallation','email-newsletter');
        $this->capabilities['uninstall_newsletter'] = __('Deinstalliere alle Newsletter-Daten','email-newsletter');

        //Activate/deactivate actions
        register_activation_hook( $this->plugin_dir . 'e-newsletter.php', array( &$this, 'do_activation' ) );
        register_deactivation_hook( $this->plugin_dir . 'e-newsletter.php', array( &$this, 'do_deactivation' ) );

        //add new rewrite rules
        add_filter( 'rewrite_rules_array', array( &$this, 'insert_rewrite_rules' ) );
        add_filter( 'query_vars', array( &$this, 'insert_query_vars' ) );

        add_action('plugins_loaded',array(&$this,'upgrade_check'));

        add_action( 'email_newsletter_upgrade_cron',array( &$this, 'upgrade_cron' ) );

        add_action( 'admin_init', array( &$this, 'admin_init' ) );
        add_action( 'admin_enqueue_scripts', array(&$this,'admin_enqueue_scripts'));

        // filter schedules
        add_filter( 'cron_schedules', array( &$this, 'add_new_cron_time' ) );

        add_action( 'init', array( &$this, 'init' ), 999 );

        //some actions for MultiSite
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            add_action( 'wpmu_activate_user', array( &$this, 'user_create' ) );
            add_action( 'wpmu_new_user', array( &$this, 'user_create' ) );
            add_action( 'added_existing_user', array( &$this, 'user_create' ) );
            add_action( 'remove_user_from_blog', array( &$this, 'user_remove_from_site' ) );
            add_action( 'wpmu_delete_user', array( &$this, 'user_delete' ) );
            add_action( 'delete_blog', array( &$this, 'uninstall' ) );
            add_action( 'network_admin_menu', array( &$this, 'admin_page' ) );
        }
        //changing list of members when we create or delete user of the standard site
        add_action( 'user_register', array( &$this, 'user_create' ) );
        add_action( 'delete_user', array( &$this, 'user_delete' ) );

        //Update member when editing user action
        add_action( 'edit_user_profile_update', array( &$this, 'edit_user_update_member' ) );
        add_action( 'personal_options_update', array( &$this, 'edit_user_update_member' ) );

        add_action( 'edit_user_profile', array( &$this, 'wp_admins_profile' ) );
        add_action( 'show_user_profile', array( &$this, 'wp_admins_profile' ) );

        //creating menu of the plugin
        add_action( 'admin_menu', array( &$this, 'admin_page' ) );

        //send email by WP-CRON
        add_action( $this->cron_send_name, array( &$this, 'send_by_wpcron' ) );

        //check bounces email by WP-CRON
        add_action( $this->cron_bounce_name .'_1', array( &$this, 'check_bounces' ) );
        add_action( $this->cron_bounce_name .'_2', array( &$this, 'check_bounces' ) );

        add_action( 'enewsletter_campaign_runner', array( &$this, 'campaigns_run_due' ) );
        add_action( 'publish_post', array( &$this, 'campaigns_handle_publish_post' ), 10, 2 );
        add_action( 'publish_product', array( &$this, 'campaigns_handle_publish_post' ), 10, 2 );
        add_action( 'publish_mp_product', array( &$this, 'campaigns_handle_publish_post' ), 10, 2 );

        //subscribe widget stuff
        add_shortcode( 'enewsletter_subscribe', array( &$this, 'subscribe_shortcode' ) );
		add_shortcode( 'enews_product', array( &$this, 'enews_product_shortcode' ) );
		add_shortcode( 'enews_products', array( &$this, 'enews_products_shortcode' ) );
        add_shortcode( 'enews_post', array( &$this, 'enews_post_shortcode' ) );
        add_shortcode( 'enews_posts', array( &$this, 'enews_posts_shortcode' ) );
        add_shortcode( 'enews_post_links', array( &$this, 'enews_post_links_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'email_newsletter_widgets_scripts' ) );

        //unsubscribe message
        add_shortcode( 'enewsletter_unsubscribe_message', array( &$this, 'unsubscribe_message_shortcode' ) );
        //unsubscribe message
        add_shortcode( 'enewsletter_subscribe_message', array( &$this, 'subscribe_message_shortcode' ) );


        //ajax action for sent preview (test) email
        //add_action( 'wp_ajax_nopriv_send_preview', array( &$this, 'send_preview_ajax' ) );
        add_action( 'wp_ajax_send_email_preview', array( &$this, 'send_preview_ajax' ) );
        add_action( 'wp_ajax_enews_builder_v2_preview', array( &$this, 'builder_v2_preview_ajax' ) );
        add_action( 'wp_ajax_enews_builder_v2_search_items', array( &$this, 'builder_v2_search_items_ajax' ) );
        add_action( 'wp_ajax_enews_builder_v2_save_preset', array( &$this, 'builder_v2_save_preset_ajax' ) );
        add_action( 'wp_ajax_enews_builder_v2_delete_preset', array( &$this, 'builder_v2_delete_preset_ajax' ) );

        //ajax action for change member's group on members page
        add_action( 'wp_ajax_nopriv_change_groups', array( &$this, 'change_groups_ajax' ) );
        add_action( 'wp_ajax_change_groups', array( &$this, 'change_groups_ajax' ) );

        //ajax action for show transparent image 1x1 for check that email was opened
        add_action( 'wp_ajax_nopriv_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );
        add_action( 'wp_ajax_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );

        //ajax action for tracked link clicks in emails
        add_action( 'wp_ajax_nopriv_check_email_clicked', array( &$this, 'check_email_clicked_ajax' ) );
        add_action( 'wp_ajax_check_email_clicked', array( &$this, 'check_email_clicked_ajax' ) );

        //ajax action for test connection to bounces email
        add_action( 'wp_ajax_nopriv_test_bounces', array( &$this, 'test_bounces_ajax' ) );
        add_action( 'wp_ajax_test_bounces', array( &$this, 'test_bounces_ajax' ) );

        //ajax action for test connection to smtp server
        add_action( 'wp_ajax_nopriv_test_smtp', array( &$this, 'test_smtp_ajax' ) );
        add_action( 'wp_ajax_test_smtp', array( &$this, 'test_smtp_ajax' ) );

        //ajax action for sand email to member
        add_action( 'wp_ajax_nopriv_send_email_to_member', array( &$this, 'send_email_to_member' ) );
        add_action( 'wp_ajax_send_email_to_member', array( &$this, 'send_email_to_member' ) );

        //ajax action for subscribing
        add_action( 'wp_ajax_manage_subscriptions_ajax', array( &$this, 'manage_subscriptions_ajax' ));
        add_action( 'wp_ajax_nopriv_manage_subscriptions_ajax', array( &$this, 'manage_subscriptions_ajax'));

        // filter does shortcodes
        add_filter('email_newsletter_make_email_content', 'do_shortcode', 11);


        add_action( 'template_redirect', array( &$this, 'template_redirect' ), 12 );


        //privacy
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_plugin_exporter' ), 10 );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_plugin_eraser' ), 10 );


        //depraciated
        add_action( 'wp_ajax_nopriv_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );
        add_action( 'wp_ajax_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );
        add_action( 'wp_ajax_nopriv_newsletter_unsubscribe', array( &$this, 'unsubscribe_ajax' ) );
        add_action( 'wp_ajax_newsletter_unsubscribe', array( &$this, 'unsubscribe_ajax' ) );
    }

    /**
     * Do the stuff on activation
     *
     */
    function do_activation() {
        //Update rewrite_rules
        flush_rewrite_rules( false );

        //create folder for custom themes
        $custom_theme_dir = $this->get_custom_theme_dir();

        if (!is_dir($custom_theme_dir)) {
            mkdir($custom_theme_dir);
        }

        //sets up cron
        $settings = $this->get_settings();
        if ( 1 == $settings['cron_enable'] ) {
            if ( wp_next_scheduled( $this->cron_send_name ) )
                wp_clear_scheduled_hook( $this->cron_send_name );

            wp_schedule_event( time(), '2mins', $this->cron_send_name );
        }
        else {
            if ( wp_next_scheduled( $this->cron_send_name ) )
                wp_clear_scheduled_hook( $this->cron_send_name );
        }

        $this->ensure_campaign_tables();
        if ( ! wp_next_scheduled( 'enewsletter_campaign_runner' ) ) {
            wp_schedule_event( time() + 60, 'hourly', 'enewsletter_campaign_runner' );
        }
    }

    /**
     * Do the stuff on deactivation
     *
     */
    function do_deactivation() {
        if ( wp_next_scheduled( $this->cron_send_name ) )
            wp_clear_scheduled_hook( $this->cron_send_name );
        if ( wp_next_scheduled( $this->cron_bounce_name .'_1' ) )
            wp_clear_scheduled_hook( $this->cron_bounce_name .'_1' );
        if ( wp_next_scheduled( $this->cron_bounce_name .'_2' ) )
            wp_clear_scheduled_hook( $this->cron_bounce_name .'_2' );
        if ( wp_next_scheduled( 'enewsletter_campaign_runner' ) )
            wp_clear_scheduled_hook( 'enewsletter_campaign_runner' );
    }

    /**
     * Do the stuff on upgrade
     *
     */
    function upgrade_check() {
        //check if upgrade is necessary
        if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file))) {
            $prev = get_site_option('email_newsletter_version', 2);
            $upgraded_cron = get_site_option('email_newsletter_upgraded_cron', 1);
        }
        else {
            $prev = get_option('email_newsletter_version', 1.25);
            $upgraded_cron = get_option('email_newsletter_upgraded_cron', 1);
        }

        if ( version_compare( (string) $this->plugin_ver, (string) $prev, '>' ) ) {
            $this->upgrade('', $prev);

            if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file)))
                update_site_option('email_newsletter_version', $this->plugin_ver);
            else
                update_option('email_newsletter_version', $this->plugin_ver);
        }
        if(!$upgraded_cron && !wp_next_scheduled('email_newsletter_upgrade_cron')) {
            wp_schedule_single_event(time(), 'email_newsletter_upgrade_cron');
        }
    }

    /**
     * Adding a new rule
     **/
    function insert_rewrite_rules( $rules ) {
        $newrules = array();
        $newrules['e-newsletter/unsubscribe/([\w\d]{15})(\d*)/?$'] = 'index.php?unsubscribe_page=1&unsubscribe_code=$matches[1]&unsubscribe_member_id=$matches[2]';
        $newrules['e-newsletter/view/([\w\d]{15})(\d*)/?$'] = 'index.php?view_newsletter=1&view_newsletter_code=$matches[1]&view_newsletter_send_id=$matches[2]';
        return $newrules + $rules;
    }

    /**
     * Adding the var for unsubscribe page
     **/
    function insert_query_vars( $vars ) {
        array_push( $vars, 'subscribe_page' );
        array_push( $vars, 'subscribe_code' );
        array_push( $vars, 'subscribe_member_id' );

        array_push( $vars, 'unsubscribe_page' );
        array_push( $vars, 'unsubscribe_code' );
        array_push( $vars, 'unsubscribe_member_id' );

        array_push( $vars, 'view_newsletter' );
        array_push( $vars, 'view_newsletter_code' );
        array_push( $vars, 'view_newsletter_send_id' );
        return $vars;
    }

    /**
     * Loads script for enewsletter admin area
     */
    function admin_enqueue_scripts($hook) {
        global $wp_version;

         //including JS scripts and CSS for Newsletter pages
        if ( isset( $_REQUEST['page'] ) && 1 == $this->is_enewsletter_page( $_REQUEST['page'] ) ) {
            wp_enqueue_script( 'jquery' );

            //all jQuery UI dependencies removed - now using native JavaScript and HTML5 elements

            // tooltips handled by local helper (no external CDN)

            //progressbar now uses native HTML5 <progress> element instead of jQuery UI
            //wp_enqueue_script( 'jquery-ui-widget' );
            //wp_enqueue_script( 'jquery-ui-progressbar' );

            // Including CSS file
            wp_register_style( 'enewsletter-style', $this->plugin_url . 'email-newsletter-files/css/admin.css' );
            wp_enqueue_style( 'enewsletter-style' );

            wp_register_style( 'enewsletter-admin-modern', $this->plugin_url . 'email-newsletter-files/ui/admin-modern.css', array( 'enewsletter-style' ), $this->plugin_ver );
            wp_enqueue_style( 'enewsletter-admin-modern' );

            // Including JS file
            wp_register_script( 'enewsletter-script', $this->plugin_url . 'email-newsletter-files/js/admin.js' );
            wp_enqueue_script( 'enewsletter-script' );

            wp_register_script( 'enewsletter-admin-modern', $this->plugin_url . 'email-newsletter-files/ui/admin-modern.js', array(), $this->plugin_ver, true );
            wp_enqueue_script( 'enewsletter-admin-modern' );

            if ( isset( $_REQUEST['page'] ) && 'newsletters-builder-v2' === $_REQUEST['page'] ) {
                $newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? intval( $_REQUEST['newsletter_id'] ) : 0;
                $builder_state = $newsletter_id ? $this->builder_v2->get_state( $newsletter_id ) : $this->builder_v2->get_default_state();
				$fonts_css_endpoint = 'https://eimen.net/fonts/css.php';
				wp_enqueue_media();

                wp_register_style( 'enewsletter-builder-v2', $this->plugin_url . 'email-newsletter-files/css/builder-v2.css', array(), $this->plugin_ver );
                wp_enqueue_style( 'enewsletter-builder-v2' );

                wp_register_script( 'enewsletter-builder-v2', $this->plugin_url . 'email-newsletter-files/js/builder-v2.js', array(), $this->plugin_ver, true );
                wp_enqueue_script( 'enewsletter-builder-v2' );
                wp_localize_script( 'enewsletter-builder-v2', 'enewsBuilderV2', array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'fontsCssEndpoint' => $fonts_css_endpoint,
                    'googleFontCatalog' => array(
                        'ABeeZee', 'Abel', 'Abril Fatface', 'Alegreya', 'Alegreya Sans', 'Alfa Slab One',
                        'Alice', 'Anton', 'Archivo', 'Arimo', 'Asap', 'Bangers', 'Barlow', 'Bebas Neue',
                        'Bitter', 'Cabin', 'Cairo', 'Cardo', 'Catamaran', 'Cinzel', 'Comfortaa', 'Cormorant Garamond',
                        'Crimson Text', 'DM Sans', 'Dancing Script', 'Domine', 'EB Garamond', 'Exo 2',
                        'Figtree', 'Fira Sans', 'Francois One', 'Great Vibes', 'Heebo', 'Hind',
                        'Inconsolata', 'Inter', 'Josefin Sans', 'Kanit', 'Karla', 'Lato',
                        'Libre Baskerville', 'Libre Franklin', 'Lobster', 'Lora', 'Manrope', 'Maven Pro',
                        'Merriweather', 'Merriweather Sans', 'Montserrat', 'Mulish', 'Nanum Gothic', 'Noto Sans',
                        'Noto Serif', 'Nunito', 'Nunito Sans', 'Open Sans', 'Oswald', 'Outfit',
                        'PT Sans', 'PT Serif', 'Pacifico', 'Playfair Display', 'Plus Jakarta Sans', 'Poppins',
                        'Prompt', 'Public Sans', 'Quattrocento Sans', 'Quicksand', 'Raleway', 'Roboto',
                        'Roboto Condensed', 'Roboto Slab', 'Rubik', 'Sarabun', 'Satisfy', 'Source Sans 3',
                        'Space Grotesk', 'Teko', 'Titillium Web', 'Ubuntu', 'Urbanist', 'Varela Round',
                        'Work Sans', 'Yanone Kaffeesatz', 'Zilla Slab'
                    ),
					'previewNonce' => wp_create_nonce( 'enews_builder_v2_preview' ),
                    'sendPreviewNonce' => wp_create_nonce( 'enews_send_preview' ),
                    'searchNonce' => wp_create_nonce( 'enews_builder_v2_search_items' ),
					'presetsNonce' => wp_create_nonce( 'enews_builder_v2_presets' ),
					'newsletterId' => $newsletter_id,
                    'previewEmail' => isset( $this->settings['preview_email'] ) && ! empty( $this->settings['preview_email'] ) ? $this->settings['preview_email'] : $this->settings['from_email'],
                    'state' => $builder_state,
                    'modules' => $this->builder_v2->get_client_available_modules(),
                    'presets' => $this->builder_v2->get_template_presets(),
                    'l10n' => array(
                        'emptyCanvas' => __( 'Noch keine Module vorhanden. Ziehe ein Modul hierher oder fuege es links per Klick hinzu.', 'email-newsletter' ),
                        'emptyPreview' => __( 'Die Vorschau erscheint, sobald Module vorhanden sind.', 'email-newsletter' ),
                        'previewLoading' => __( 'Vorschau wird aktualisiert ...', 'email-newsletter' ),
                        'previewError' => __( 'Die Server-Vorschau konnte nicht geladen werden.', 'email-newsletter' ),
                        'globalSettings' => __( 'Mail-Rahmen & Branding', 'email-newsletter' ),
                        'moduleSettings' => __( 'Modul-Einstellungen', 'email-newsletter' ),
                        'showGlobalSettings' => __( 'Mail-Rahmen & Branding', 'email-newsletter' ),
                        'duplicate' => __( 'Duplizieren', 'email-newsletter' ),
                        'moveUp' => __( 'Hoch', 'email-newsletter' ),
                        'moveDown' => __( 'Runter', 'email-newsletter' ),
                        'remove' => __( 'Loeschen', 'email-newsletter' ),
                        'presetsTitle' => __( 'Template-Presets', 'email-newsletter' ),
                        'presetsDescription' => __( 'Starte mit einer typischen Newsletter-Struktur.', 'email-newsletter' ),
                        'selectPreset' => __( 'Preset auswaehlen', 'email-newsletter' ),
                        'applyPreset' => __( 'Preset anwenden', 'email-newsletter' ),
                        'savePreset' => __( 'Als Preset speichern', 'email-newsletter' ),
                        'deletePreset' => __( 'Preset loeschen', 'email-newsletter' ),
                        'presetNamePrompt' => __( 'Name fuer das Preset', 'email-newsletter' ),
                        'presetSaved' => __( 'Preset gespeichert.', 'email-newsletter' ),
                        'presetDeleted' => __( 'Preset geloescht.', 'email-newsletter' ),
                        'presetSaveError' => __( 'Preset konnte nicht gespeichert werden.', 'email-newsletter' ),
                        'presetDeleteError' => __( 'Preset konnte nicht geloescht werden.', 'email-newsletter' ),
                        'applyPresetConfirm' => __( 'Aktuelles Layout durch Preset ersetzen?', 'email-newsletter' ),
                        'presetApplied' => __( 'Preset angewendet.', 'email-newsletter' ),
                        'desktopView' => __( 'Desktop', 'email-newsletter' ),
                        'mobileView' => __( 'Mobil', 'email-newsletter' ),
                        'undo' => __( 'Rueckgaengig', 'email-newsletter' ),
                        'redo' => __( 'Wiederholen', 'email-newsletter' ),
                        'searchPlaceholderProducts' => __( 'Produkte nach Titel suchen ...', 'email-newsletter' ),
                        'searchPlaceholderPosts' => __( 'Beitraege nach Titel suchen ...', 'email-newsletter' ),
                        'searchButton' => __( 'Suchen', 'email-newsletter' ),
                        'searchResults' => __( 'Ergebnisse', 'email-newsletter' ),
                        'selectedItems' => __( 'Ausgewaehlte IDs', 'email-newsletter' ),
                        'addItem' => __( 'Hinzufuegen', 'email-newsletter' ),
                        'removeItem' => __( 'Entfernen', 'email-newsletter' ),
                        'noSearchResults' => __( 'Keine Treffer gefunden.', 'email-newsletter' ),
                        'searchMinChars' => __( 'Mindestens 2 Zeichen eingeben.', 'email-newsletter' ),
                        'searchError' => __( 'Suche konnte nicht geladen werden.', 'email-newsletter' ),
                        'contentWidth' => __( 'Inhaltsbreite', 'email-newsletter' ),
                        'emailTitle' => __( 'E-Mail-Titel', 'email-newsletter' ),
                        'fullWidth' => __( 'Fullwidth-Inhalt', 'email-newsletter' ),
                        'backgroundColor' => __( 'Hintergrundfarbe aussen', 'email-newsletter' ),
                        'contentBackground' => __( 'Hintergrundfarbe innen', 'email-newsletter' ),
                        'textColor' => __( 'Standard-Textfarbe', 'email-newsletter' ),
                        'fontFamily' => __( 'Schriftfamilie', 'email-newsletter' ),
                        'fontSearchTitle' => __( 'Google-Schrift auf dem Server suchen', 'email-newsletter' ),
                        'fontSearchPlaceholder' => __( 'Schrift suchen ...', 'email-newsletter' ),
                        'fontServerReady' => __( '', 'email-newsletter' ),
                        'fontServerMissing' => __( 'Schriften konnten gerade nicht geladen werden.', 'email-newsletter' ),
                        'fontSearchNeedQuery' => __( 'Bitte einen Schriftnamen eingeben.', 'email-newsletter' ),
                        'fontsLoading' => __( 'Schriften werden geladen ...', 'email-newsletter' ),
                        'fontSelectedOne' => __( 'Ausgewaehlt: %s', 'email-newsletter' ),
                        'fontLoadedOne' => __( 'Schrift geladen: %s', 'email-newsletter' ),
                        'fontNotFound' => __( 'Nicht gefunden. Tipp: nur einen Teil eingeben, dann aus der Liste waehlen.', 'email-newsletter' ),
                        'fontPreviewText' => __( 'Vorschau: Der schnelle braune Fuchs springt ueber den faulen Hund. 1234567890', 'email-newsletter' ),
                        'headingFontSize' => __( 'Ueberschriften: Groesse', 'email-newsletter' ),
                        'headingColor' => __( 'Ueberschriften: Farbe', 'email-newsletter' ),
                        'headingDecoration' => __( 'Ueberschriften: Text-Dekoration', 'email-newsletter' ),
                        'paragraphFontSize' => __( 'Absaetze: Groesse', 'email-newsletter' ),
                        'paragraphColor' => __( 'Absaetze: Farbe', 'email-newsletter' ),
                        'paragraphDecoration' => __( 'Absaetze: Text-Dekoration', 'email-newsletter' ),
                        'quoteFontSize' => __( 'Zitate: Groesse', 'email-newsletter' ),
                        'quoteColor' => __( 'Zitate: Farbe', 'email-newsletter' ),
                        'quoteDecoration' => __( 'Zitate: Text-Dekoration', 'email-newsletter' ),
                        'decorationNone' => __( 'Keine', 'email-newsletter' ),
                        'decorationUnderline' => __( 'Unterstrichen', 'email-newsletter' ),
                        'decorationLineThrough' => __( 'Durchgestrichen', 'email-newsletter' ),
                        'decorationOverline' => __( 'Ueberstrichen', 'email-newsletter' ),
                        'sectionGap' => __( 'Abstand zwischen Modulen', 'email-newsletter' ),
                        'brandingHtml' => __( 'Branding-HTML', 'email-newsletter' ),
                        'contactInfo' => __( 'Kontaktinfo', 'email-newsletter' ),
                        'viewBrowserHtml' => __( 'Browser-Link HTML', 'email-newsletter' ),
                        'testMailNeedEmail' => __( 'Bitte gib eine gueltige Test-E-Mail-Adresse ein.', 'email-newsletter' ),
                        'testMailSending' => __( 'Test-Mail wird gesendet ...', 'email-newsletter' ),
                        'testMailError' => __( 'Test-Mail konnte nicht gesendet werden.', 'email-newsletter' ),
                        'text' => __( 'Text', 'email-newsletter' ),
                        'level' => __( 'Ebene', 'email-newsletter' ),
                        'align' => __( 'Ausrichtung', 'email-newsletter' ),
                        'left' => __( 'Links', 'email-newsletter' ),
                        'center' => __( 'Zentriert', 'email-newsletter' ),
                        'right' => __( 'Rechts', 'email-newsletter' ),
                        'color' => __( 'Farbe', 'email-newsletter' ),
                        'fontSize' => __( 'Schriftgroesse', 'email-newsletter' ),
                        'label' => __( 'Label', 'email-newsletter' ),
                        'title' => __( 'Titel', 'email-newsletter' ),
                        'url' => __( 'URL', 'email-newsletter' ),
                        'radius' => __( 'Radius', 'email-newsletter' ),
                        'image' => __( 'Bild', 'email-newsletter' ),
                        'imageUrl' => __( 'Bild-URL', 'email-newsletter' ),
                        'altText' => __( 'Alt-Text', 'email-newsletter' ),
                        'linkUrl' => __( 'Link-URL', 'email-newsletter' ),
                        'mediaLibrary' => __( 'Mediathek', 'email-newsletter' ),
                        'selectImage' => __( 'Bild waehlen', 'email-newsletter' ),
                        'changeImage' => __( 'Bild aendern', 'email-newsletter' ),
                        'clearImage' => __( 'Bild entfernen', 'email-newsletter' ),
                        'width' => __( 'Breite', 'email-newsletter' ),
                        'moduleWidthColumns' => __( 'Modulbreite (Spalten)', 'email-newsletter' ),
                        'moduleMinHeight' => __( 'Mindesthoehe im Canvas (px)', 'email-newsletter' ),
                        'lockFullWidth' => __( 'Breite sperren (immer volle Breite)', 'email-newsletter' ),
                        'quickLayouts' => __( 'Quick-Layouts', 'email-newsletter' ),
                        'quickLayoutNeedsNext' => __( 'Fuer 3/9, 4/8 oder 6/6 brauchst Du ein direkt folgendes Modul.', 'email-newsletter' ),
                        'quickLayoutLocked' => __( 'Quick-Layout nicht moeglich, solange eines der beteiligten Module auf volle Breite gesperrt ist.', 'email-newsletter' ),
                        'lockedSuffix' => __( 'gesperrt', 'email-newsletter' ),
                        'collapseSettings' => __( 'Einstellungen einklappen', 'email-newsletter' ),
                        'expandSettings' => __( 'Einstellungen ausklappen', 'email-newsletter' ),
                        'shortcodeDetected' => __( 'Legacy-Shortcode erkannt.', 'email-newsletter' ),
                        'convertShortcode' => __( 'In Modul umwandeln', 'email-newsletter' ),
                        'thickness' => __( 'Staerke', 'email-newsletter' ),
                        'height' => __( 'Hoehe', 'email-newsletter' ),
                        'html' => __( 'HTML / Shortcode', 'email-newsletter' ),
                        'ids' => __( 'IDs', 'email-newsletter' ),
                        'queryMode' => __( 'Inhaltsquelle', 'email-newsletter' ),
                        'queryManual' => __( 'Manuelle IDs', 'email-newsletter' ),
                        'queryLatest' => __( 'Neueste Inhalte automatisch', 'email-newsletter' ),
                        'queryTrigger' => __( 'Aus Automation-Trigger', 'email-newsletter' ),
                        'queryScope' => __( 'Zeitraum', 'email-newsletter' ),
                        'queryScopeAll' => __( 'Alle (ohne Zeitraumfilter)', 'email-newsletter' ),
                        'queryScopeWeek' => __( 'Diese Woche', 'email-newsletter' ),
                        'queryScopeMonth' => __( 'Dieser Monat', 'email-newsletter' ),
                        'queryLimit' => __( 'Anzahl Elemente', 'email-newsletter' ),
                        'layout' => __( 'Layout', 'email-newsletter' ),
                        'eyebrow' => __( 'Eyebrow / Kicker', 'email-newsletter' ),
                        'buttonBackground' => __( 'Button-Hintergrund', 'email-newsletter' ),
                        'buttonColor' => __( 'Button-Farbe', 'email-newsletter' ),
                        'leftColumn' => __( 'Linke Spalte', 'email-newsletter' ),
                        'rightColumn' => __( 'Rechte Spalte', 'email-newsletter' ),
                        'leftBackground' => __( 'Hintergrund links', 'email-newsletter' ),
                        'rightBackground' => __( 'Hintergrund rechts', 'email-newsletter' ),
                        'columnGap' => __( 'Spalten-Abstand', 'email-newsletter' ),
                        'list' => __( 'Liste', 'email-newsletter' ),
                        'grid' => __( 'Grid', 'email-newsletter' ),
                        'single' => __( 'Single', 'email-newsletter' ),
                        'links' => __( 'Links', 'email-newsletter' ),
                        'slider' => __( 'Slider', 'email-newsletter' ),
                        'showImage' => __( 'Bild anzeigen', 'email-newsletter' ),
                        'showPrice' => __( 'Preis anzeigen', 'email-newsletter' ),
                        'showOldPrice' => __( 'Altpreis anzeigen', 'email-newsletter' ),
                        'showButton' => __( 'Button anzeigen', 'email-newsletter' ),
                        'showBadge' => __( 'Badge anzeigen', 'email-newsletter' ),
                        'showExcerpt' => __( 'Excerpt anzeigen', 'email-newsletter' ),
                        'excerptWords' => __( 'Excerpt-Woerter', 'email-newsletter' ),
                        'track' => __( 'Tracking aktiv', 'email-newsletter' ),
                        'badgeText' => __( 'Badge-Text', 'email-newsletter' ),
                        'buttonText' => __( 'Button-Text', 'email-newsletter' ),
                        'company' => __( 'Firma', 'email-newsletter' ),
                        'address' => __( 'Adresse', 'email-newsletter' ),
                        'legalText' => __( 'Hinweistext', 'email-newsletter' ),
                        'manageUrl' => __( 'Profil-URL', 'email-newsletter' ),
                        'viewUrl' => __( 'Browser-Ansicht URL', 'email-newsletter' ),
                        'unsubscribeUrl' => __( 'Abmelde-URL', 'email-newsletter' ),
                        'linkColor' => __( 'Linkfarbe', 'email-newsletter' ),
                        'facebook' => __( 'Facebook URL', 'email-newsletter' ),
                        'instagram' => __( 'Instagram URL', 'email-newsletter' ),
                        'linkedin' => __( 'LinkedIn URL', 'email-newsletter' ),
                        'youtube' => __( 'YouTube URL', 'email-newsletter' ),
                        'xLabel' => __( 'X / Twitter URL', 'email-newsletter' ),
                        'yes' => __( 'Ja', 'email-newsletter' ),
                        'no' => __( 'Nein', 'email-newsletter' ),
                        'noIds' => __( 'Keine IDs', 'email-newsletter' ),
                        'imageEmpty' => __( 'Keine Bild-URL gesetzt', 'email-newsletter' ),
                        'dividerSummary' => __( 'Horizontaler Trenner', 'email-newsletter' ),
                    ),
                ) );
            }

            $admin_js_options = array(
                'edit' => __( 'Bearbeiten', 'email-newsletter' ),
                'close' => __( 'Schließen', 'email-newsletter' ),
                'save' => __( 'Speichern', 'email-newsletter' ),
                'write_email' => __( 'Bitte schreibe eine E-Mailadresse des Abonnents', 'email-newsletter' ),
                'show_add_member' => __( 'Öffne Formular für neue Abonnenten/Importe an', 'email-newsletter' ),
                'hide_add_member' => __( 'Blende die neuen Abonnenten-/Importformulare aus', 'email-newsletter' ),
                'show_export_member' => __( 'Öffne Formular für Abonnentenexport an', 'email-newsletter' ),
                'hide_export_member' => __( 'Blende das Formular für Exportmitglieder aus', 'email-newsletter' ),
                'proper_email' => __( 'Bitte verwende die richtige E-Mail', 'email-newsletter' ),
                'proper_email' => __( 'Bitte verwende die richtige E-Mail', 'email-newsletter' ),
                'confirm' => __( 'Bist Du Dir sicher?', 'email-newsletter' ),
                'save_groups' => __( 'Gruppen speichern', 'email-newsletter' ),
                'change_groups' => __( 'Gruppen ändern', 'email-newsletter' ),
                'select_members' => __( 'Wähle Abonnenten aus.', 'email-newsletter' ),
                'settings_tab' => (isset($_GET['tab'])) ? $_GET['tab'] : (!$this->settings ? 'tabs-2' : 'tabs-1'),
                'smtp_warning' => __( 'Bitte schreibe SMTP Outgoing Server oder wähle eine andere Sendemethode!', 'email-newsletter' ),
                'smtp_test_nonce' => wp_create_nonce( 'enews_test_smtp' ),
                'bounce_test_nonce' => wp_create_nonce( 'enews_test_bounces' ),
                'send_preview_nonce' => wp_create_nonce( 'enews_send_preview' )
            );
            wp_localize_script( 'enewsletter-script', 'enewsletter', $admin_js_options );
        }

        wp_register_style( 'enewsletter-mp6', $this->plugin_url . 'email-newsletter-files/css/mp6.css');
        wp_enqueue_style('enewsletter-mp6');
    }

    /**
     * Manage page redirects if necessary
     **/
    function template_redirect() {
        if ( $this->is_enewsletter_page( 'unsubscribe_page' ) ) {
            $is_one_click_post = 'POST' === strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' )
                && isset( $_POST['List-Unsubscribe'] )
                && 'One-Click' === trim( wp_unslash( $_POST['List-Unsubscribe'] ) );

            $member_id = get_query_var( 'unsubscribe_member_id' );
            $unsubscribe_code = get_query_var( 'unsubscribe_code' );
            $result = $this->unsubscribe_by_code( $unsubscribe_code );
            if ( !$result['error'] ) {
                $message = $result['message'];
                $unsubscribed = 1;
            }
            else {
                $message = $result['message'];
                $unsubscribed = 0;
            }

            if ( $is_one_click_post ) {
                status_header( 200 );
                nocache_headers();
                header( 'Content-Type: text/plain; charset=UTF-8' );
                echo $unsubscribed ? 'OK' : 'NOOP';
                exit;
            }

            if(isset($this->settings['unsubscribe_page_id']) && is_numeric($this->settings['unsubscribe_page_id']) && get_post($this->settings['unsubscribe_page_id']))
                wp_redirect( add_query_arg( array('member_id' => $member_id, 'message' => urlencode($message), 'enewsletter_unsubscribed' => $unsubscribed), get_permalink($this->settings['unsubscribe_page_id']) ) );
            else {
                if($unsubscribed)
                    echo "<center><br /><br /><br /><h2 style='color: #19700A;'>" . $message . "</h2></center>";
                else
                    echo "<center><br /><br /><br /><h2 style='color: #ff0000;'>" . $message . "</h2></center>";
            }
            exit;
        }
        if ( $this->is_enewsletter_page( 'subscribe_page' ) ) {
            global $wpdb;

            $subscribed = 0;

            $member_id = get_query_var( 'subscribe_member_id' );
            $subscribe_code = get_query_var( 'subscribe_code' );

            if ( $subscribe_code != md5( "sometext123" . $member_id ) )
                $message = __( 'Fehler: Falsche Abonnementdaten!', 'email-newsletter' );

            $member_data = $this->get_member( $member_id );
            if($member_data) {
                if(empty($member_data['unsubscribe_code'])) {
                    $unsubscribe_code = $this->gen_unsubscribe_code();

                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET member_info = '', unsubscribe_code = '%s' WHERE member_id = %d", $unsubscribe_code, $member_id ) );

                    //creating new list of groups for user
                    if ( is_array( $member_data['future_groups_id'] ) )
                        $this->add_members_to_groups( $member_id, $member_data['future_groups_id'] );

                    if($this->settings['subscribe_newsletter']) {
                        $send_details = $this->add_send_email_info( $this->settings['subscribe_newsletter'], $member_id, 0, 'waiting_send' );
                        $this->send_email_to_member($send_details['send_id']);
                    }

                    $subscribed = 1;
                    $message = __( 'Erfolgreiches Abonnement!', 'email-newsletter' );
                }
                else {
                    $message = __( 'Abonnent bereits abonniert!', 'email-newsletter' );
                }
            }
            else {
                $message = __( 'Beim Abonnieren ist ein Problem aufgetreten!', 'email-newsletter' );
            }

            if(isset($this->settings['subscribe_page_id']) && is_numeric($this->settings['subscribe_page_id']) && get_post($this->settings['subscribe_page_id']))
                wp_redirect( add_query_arg( array('member_id' => $member_id, 'message' => urlencode($message), 'enewsletter_subscribed' => $subscribed), get_permalink($this->settings['subscribe_page_id']) ) );
            else {
                if($subscribed)
                    echo "<center><br /><br /><br /><h2 style='color: #19700A;'>" . $message . "</h2></center>";
                else
                    echo "<center><br /><br /><br /><h2 style='color: #ff0000;'>" . $message . "</h2></center>";
            }
            exit;
        }
        elseif ( $this->is_enewsletter_page( 'view_newsletter' ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-view-newsletter.php" );
            exit;
        }
    }

    /**
     * init for admin
     **/
    function admin_init() {
        $mu_cap = (function_exists('is_multisite' && is_multisite()) ? 'manage_network_options' : 'manage_options');

        $this->ensure_campaign_tables();
        if ( ! wp_next_scheduled( 'enewsletter_campaign_runner' ) ) {
            wp_schedule_event( time() + 60, 'hourly', 'enewsletter_campaign_runner' );
        }

        if ( isset( $_REQUEST['newsletter_builder_action'] ) && ! defined( 'DOING_AJAX' ) ) {
            $action = sanitize_key( wp_unslash( $_REQUEST['newsletter_builder_action'] ) );
            $default_return = admin_url( 'admin.php?page=newsletters' );
            $return = isset( $_REQUEST['return'] ) ? wp_validate_redirect( wp_unslash( $_REQUEST['return'] ), $default_return ) : '';

            if ( 'create_newsletter' === $action ) {
                if ( ! ( current_user_can( 'create_newsletter' ) || current_user_can( $mu_cap ) ) ) {
                    wp_die( __( 'Dazu hast Du keine Berechtigung', 'email-newsletter' ) );
                }

                $newsletter_id = $this->create_newsletter_for_builder();
                if ( ! $newsletter_id ) {
                    wp_die( __( 'Der Newsletter konnte nicht erstellt werden.', 'email-newsletter' ) );
                }

                $target = add_query_arg(
                    array(
                        'page' => 'newsletters-builder-v2',
                        'newsletter_id' => intval( $newsletter_id ),
                    ),
                    admin_url( 'admin.php' )
                );

                if ( ! empty( $return ) ) {
                    $target = add_query_arg( 'return', $return, $target );
                }

                wp_redirect( $target );
                exit();
            }

            if ( 'edit_newsletter' === $action ) {
                if ( ! ( current_user_can( 'save_newsletter' ) || current_user_can( $mu_cap ) ) ) {
                    wp_die( __( 'Dazu hast Du keine Berechtigung', 'email-newsletter' ) );
                }

                $newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? intval( $_REQUEST['newsletter_id'] ) : 0;
                if ( ! $newsletter_id ) {
                    wp_die( __( 'Newsletter-ID fehlt.', 'email-newsletter' ) );
                }

                $target = add_query_arg(
                    array(
                        'page' => 'newsletters-builder-v2',
                        'newsletter_id' => $newsletter_id,
                    ),
                    admin_url( 'admin.php' )
                );

                if ( ! empty( $return ) ) {
                    $target = add_query_arg( 'return', $return, $target );
                }

                wp_redirect( $target );
                exit();
            }
        }

        // Save Builder V2 state before scripts are enqueued/localized, then redirect to GET.
        if (
            isset( $_REQUEST['page'] )
            && 'newsletters-builder-v2' === sanitize_key( wp_unslash( $_REQUEST['page'] ) )
            && isset( $_POST['enews_builder_v2_action'] )
            && 'save' === sanitize_key( wp_unslash( $_POST['enews_builder_v2_action'] ) )
            && ! defined( 'DOING_AJAX' )
        ) {
            if ( ! current_user_can( 'save_newsletter' ) ) {
                wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
            }

            $newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? intval( $_REQUEST['newsletter_id'] ) : 0;
            if ( $newsletter_id <= 0 ) {
                wp_die( __( 'Newsletter-ID fehlt.', 'email-newsletter' ) );
            }

            check_admin_referer( 'enews_builder_v2_save_' . $newsletter_id );

            $raw_state = isset( $_POST['builder_state_json'] ) ? wp_unslash( $_POST['builder_state_json'] ) : '';
            $decoded = json_decode( $raw_state, true );
            $updated = 'false';
            $notice_message = __( 'Der Builder-Status konnte nicht gelesen werden.', 'email-newsletter' );

            if ( is_array( $decoded ) ) {
                $this->builder_v2->save_state( $newsletter_id, $decoded );
                $updated = 'true';
                $notice_message = __( 'Newsletter gespeichert. Der Inhalt wurde aktualisiert.', 'email-newsletter' );
            }

            $default_return = admin_url( 'admin.php?page=newsletters' );
            $return = isset( $_REQUEST['return'] ) ? wp_validate_redirect( wp_unslash( $_REQUEST['return'] ), $default_return ) : $default_return;

            $target = add_query_arg(
                array(
                    'page' => 'newsletters-builder-v2',
                    'newsletter_id' => $newsletter_id,
                    'updated' => $updated,
                    'message' => urlencode( $notice_message ),
                ),
                admin_url( 'admin.php' )
            );

            if ( ! empty( $return ) ) {
                $target = add_query_arg( 'return', $return, $target );
            }

            wp_redirect( $target );
            exit();
        }

        //Force caps for admin
        $admin_role = get_role('administrator');
        if(is_object($admin_role))
            foreach($this->capabilities as $key => $cap) {
                if(!isset($admin_role->capabilities[$key]) || $admin_role->capabilities[$key] == false ) {
                    $admin_role->add_cap($key,true);
                }
            }

        //private actions of the plugin
        if ( isset( $_REQUEST['newsletter_action'] ) ) {
            // CSRF Protection: Verify nonce for all admin actions
            if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'enewsletter_admin_action' ) ) {
                wp_die( __( 'Sicherheitsüberprüfung fehlgeschlagen. Bitte versuche es erneut.', 'email-newsletter' ) );
            }

            //handle custom redirects
            if(isset($_REQUEST['redirect_to']))
                $redirect = esc_url_raw($_REQUEST['redirect_to']);

            switch( $_REQUEST[ 'newsletter_action' ] ) {

                //action for save Newsletter
                case "clone_newsletter":
                    if(! (current_user_can('create_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->clone_newsletter( $_REQUEST['newsletter_id'], $_REQUEST['page'] );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters', 'updated' => 'true', 'message' => urlencode( __( 'Der Newsletter wurde kopiert!', 'email-newsletter' ) ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for delete Newsletter
                case "delete_newsletter":
                    if(! (current_user_can('delete_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->delete_newsletter( $_REQUEST['newsletter_id'] );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters', 'updated' => 'true', 'message' => urlencode( __( 'Der Newsletter wird gelöscht!', 'email-newsletter' ) ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for create new group
                case "create_group":
                    if(! (current_user_can('create_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $edit_public = ( isset( $_REQUEST['public'] ) ) ? '1' : '0';
                    $result = $this->create_edit_group( $_REQUEST['group_name'], $edit_public );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for edit group
                case "edit_group":
                    if(! (current_user_can('edit_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $edit_public = ( isset( $_REQUEST['edit_public'] ) ) ? '1' : '0';
                    $result = $this->create_edit_group( $_REQUEST['edit_group_name'], $edit_public, $_REQUEST['group_id'] );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for delete group
                case "delete_group":
                    if(! (current_user_can('delete_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->delete_group( $_REQUEST['group_id'] );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'message' => urlencode( __( 'Gruppe wird gelöscht!', 'email-newsletter' ) ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action add new member
                case "add_member":
                    if(! (current_user_can('add_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->add_member( $_REQUEST['member'] );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action edit member
                case "edit_member":
                    if(! (current_user_can('edit_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->edit_member( $_REQUEST['member_id'], $_REQUEST['edit_member_nicename'], $_REQUEST['edit_member_email'] );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ) ) ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action delete member
                case "delete_member":
                    if(! (current_user_can('delete_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $member_id = array($_REQUEST['member_id']);
                    $result = $this->delete_members( $member_id );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ) ) ) : $redirect;
                    wp_redirect( $redirect );
                    exit;
                break;

                //Bulk action delete members
                case "delete_members":
                    if(! (current_user_can('delete_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->delete_members( $_REQUEST['members_id'] );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //Bulk action add members to group
                case "add_members_group":

                    if(! (current_user_can('add_members_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    if($_REQUEST['list_group_id'] == 'subscribed' || $_REQUEST['list_group_id'] == 'unsubscribed') {
                        global $wpdb;

                        foreach ($_REQUEST['members_id'] as $member_id) {
                            $member_data = $this->get_member( $member_id );

                            if($member_data) {
                                if(empty($member_data['unsubscribe_code']) && $_REQUEST['list_group_id'] == 'subscribed')
                                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '%s' WHERE member_id = %d", $this->gen_unsubscribe_code(), $member_id ) );
                                elseif(!empty($member_data['unsubscribe_code']) && $_REQUEST['list_group_id'] == 'unsubscribed')
                                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE member_id = %d", $member_id ) );

                            }
                        }
                    }
                    else
                        $result = $this->add_members_to_groups( $_REQUEST['members_id'], $_REQUEST['list_group_id'] );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( __( 'Abonnenten werden der Gruppe hinzugefügt!', 'email-newsletter' ) ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for change group
                case "change_group":

                    if(! (current_user_can('change_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    //subscribe/unsubscribe if necessary TODO Consider turning it into function
                    $member_data = $this->get_member( $_REQUEST['member_id'] );
                    if($member_data) {
                        global $wpdb;

                        $subscribed = isset($_REQUEST['groups_id']) ? array_search('subscribed', $_REQUEST['groups_id']) : false;
                        if($subscribed !== false && empty($member_data['unsubscribe_code']))
                            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '%s' WHERE member_id = %d", $this->gen_unsubscribe_code(), $_REQUEST['member_id'] ) );
                        elseif($subscribed === false && !empty($member_data['unsubscribe_code']))
                            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE member_id = %d", $_REQUEST['member_id'] ) );
                    }
                    if($subscribed !== false && isset($_REQUEST['groups_id'][$subscribed]))
                        unset($_REQUEST['groups_id'][$subscribed]);

                    $groups_id = ( isset( $_REQUEST['groups_id'] ) ) ? $_REQUEST['groups_id'] : array();
                    $result = $this->add_members_to_groups( $_REQUEST['member_id'], $groups_id, 1 );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( __( 'Gruppen werden geändert!', 'email-newsletter' ) ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit;
                break;

                //Bulk action add members to group
                case "delete_members_group":

                    if(! (current_user_can('delete_members_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->delete_members_group( $_REQUEST['members_id'], $_REQUEST['list_group_id'] );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( __( 'Abonnenten werden aus der Gruppe gelöscht!', 'email-newsletter' ) ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                case "set_members_status":
                    if(! (current_user_can('add_members_group') || current_user_can('delete_members_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $status = isset( $_REQUEST['members_status'] ) ? sanitize_text_field( $_REQUEST['members_status'] ) : '';
                    $result = $this->set_members_subscription_status( $_REQUEST['members_id'], $status );

                    $message = ( 'unsubscribed' === $status )
                        ? __( 'Status auf abbestellt gesetzt.', 'email-newsletter' )
                        : __( 'Status auf abonniert gesetzt.', 'email-newsletter' );

                    if ( false === $result ) {
                        $message = __( 'Status konnte nicht gesetzt werden.', 'email-newsletter' );
                    }

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => ( false === $result ? 'false' : 'true' ), 'message' => urlencode( $message ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                case "move_members_group":
                    if(! (current_user_can('add_members_group') || current_user_can('delete_members_group') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->move_members_to_group( $_REQUEST['members_id'], $_REQUEST['list_group_id'] );
                    $message = false === $result
                        ? __( 'Liste konnte nicht geändert werden.', 'email-newsletter' )
                        : __( 'Liste wurde für ausgewählte Abonnenten ersetzt.', 'email-newsletter' );

                    $redirect = !isset($redirect) ? esc_url_raw(add_query_arg( array( 'page' => 'newsletters-members', 'updated' => ( false === $result ? 'false' : 'true' ), 'message' => urlencode( $message ) ) )) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //Bulk action add members to group
                case "export_members":

                    if(! (current_user_can('edit_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    if($_REQUEST['separ_sign'] == 1)
                        $separate_by = ';';
                    else
                        $separate_by = ',';

                    $groups_id = isset($_REQUEST['groups_id']) ? $_REQUEST['groups_id'] : array();
                    $groups_ungrouped = isset($_REQUEST['groups_ungrouped']) ? $_REQUEST['groups_ungrouped'] : 0;

                    $this->export_members($groups_id, $groups_ungrouped, $separate_by);
                break;

                //action save settings
                case "save_settings":

                    if(! (current_user_can('save_newsletter_settings') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $this->save_settings( $_REQUEST['settings'] );
                break;

                case "clear_logs":
                    if(! (current_user_can('save_newsletter_settings') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $result = $this->clear_debug_log();
                    $redirect = add_query_arg(
                        array(
                            'page' => 'newsletters-logs',
                            'updated' => $result ? 'true' : 'false',
                            'message' => urlencode( $result ? __( 'Logs wurden geleert.', 'email-newsletter' ) : __( 'Logs konnten nicht geleert werden.', 'email-newsletter' ) ),
                        ),
                        'admin.php'
                    );
                    wp_redirect( $redirect );
                    exit();
                break;

                case "download_logs":
                    if(! (current_user_can('save_newsletter_settings') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $log_file = $this->get_debug_log_file_path();
                    if ( ! file_exists( $log_file ) ) {
                        $redirect = add_query_arg(
                            array(
                                'page' => 'newsletters-logs',
                                'updated' => 'false',
                                'message' => urlencode( __( 'Keine Log-Datei gefunden.', 'email-newsletter' ) ),
                            ),
                            'admin.php'
                        );
                        wp_redirect( $redirect );
                        exit();
                    }

                    nocache_headers();
                    header( 'Content-Type: text/plain; charset=utf-8' );
                    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $this->get_debug_log_download_name() ) . '"' );
                    header( 'Content-Length: ' . filesize( $log_file ) );
                    readfile( $log_file );
                    exit();
                break;

                //action send newsletter
                case "send_newsletter":

                    if(! (current_user_can('send_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    //Handles sending ajax sent stopped newsletters
                    if ( isset( $_REQUEST['cron'] ) && 'add_to_cron' == $_REQUEST['cron'] )
                        $this->add_to_cron( $_REQUEST['newsletter_id'], $_REQUEST['send_id'] );
                    elseif ( isset( $_REQUEST['cron'] ) && 'remove_from_cron' == $_REQUEST['cron'] )
                        $this->remove_from_cron( $_REQUEST['newsletter_id'], $_REQUEST['send_id'] );
                    //handles main send buttons
                    else if ( isset( $_REQUEST['action'] ) && 'send' == $_REQUEST["action"] )
                        $this->send_newsletter( $_REQUEST['newsletter_id'] );
                break;

                case "save_campaign":
                    if(! (current_user_can('save_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $campaign_id = $this->save_campaign_from_request( $_REQUEST );
                    $redirect = add_query_arg(
                        array(
                            'page' => 'newsletters-campaign-edit',
                            'campaign_id' => intval( $campaign_id ),
                            'updated' => 'true',
                            'message' => urlencode( __( 'Kampagne/Automation gespeichert.', 'email-newsletter' ) ),
                        ),
                        'admin.php'
                    );
                    wp_redirect( $redirect );
                    exit();
                break;

                case "pause_campaign":
                case "resume_campaign":
                case "stop_campaign":
                    if(! (current_user_can('save_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $campaign_id = isset( $_REQUEST['campaign_id'] ) ? intval( $_REQUEST['campaign_id'] ) : 0;
                    $status = 'paused';
                    if ( 'resume_campaign' === $_REQUEST['newsletter_action'] ) {
                        $status = 'active';
                    } elseif ( 'stop_campaign' === $_REQUEST['newsletter_action'] ) {
                        $status = 'stopped';
                    }
                    if ( $campaign_id ) {
                        $this->set_campaign_status( $campaign_id, $status );
                    }
                    $redirect = add_query_arg(
                        array(
                            'page' => 'newsletters-campaigns',
                            'updated' => 'true',
                            'message' => urlencode( __( 'Status aktualisiert.', 'email-newsletter' ) ),
                        ),
                        'admin.php'
                    );
                    wp_redirect( $redirect );
                    exit();
                break;

                case "delete_campaign":
                    if(! (current_user_can('save_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $campaign_id = isset( $_REQUEST['campaign_id'] ) ? intval( $_REQUEST['campaign_id'] ) : 0;
                    if ( $campaign_id ) {
                        $this->delete_campaign( $campaign_id );
                    }
                    $redirect = add_query_arg(
                        array(
                            'page' => 'newsletters-campaigns',
                            'updated' => 'true',
                            'message' => urlencode( __( 'Kampagne/Automation gelöscht.', 'email-newsletter' ) ),
                        ),
                        'admin.php'
                    );
                    wp_redirect( $redirect );
                    exit();
                break;

                //action import members
                case "import_members":

                    if(! (current_user_can('import_newsletter_members') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $this->import_members();
                break;

                //action install data in DB
                case "install":

                    if(! (current_user_can('install_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $this->install();
                    $this->save_settings( $_REQUEST['settings'] );
                break;

                //action uninstall data from DB
                case "uninstall":

                    if(! (current_user_can('uninstall_newsletter') || current_user_can($mu_cap)) )
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    $this->uninstall();

                    //redirection must happen here because it will stop site deleting in network
                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-settings', 'updated' => 'true', 'message' => urlencode( __( "eNewsletter Daten wurden gelöscht.", 'email-newsletter' ) ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                case "dismiss_install":

                    if(!current_user_can($mu_cap))
                        wp_die('Du hast keine Erlaubnis das zu tun');

                    update_option('email_newsletter_install_dismissed', 1);
                break;

            }
        }
        if(!$this->settings && get_option('email_newsletter_install_dismissed', 0) == 0 && current_user_can($mu_cap))
            add_action( 'all_admin_notices', array( &$this, 'install_notice' ), 5 );

        //privacy stuff
        if(function_exists('wp_add_privacy_policy_content')) {
            $privacy_text = __( "Diese Website verfügt über ein Newsletter-Anmeldeformular, das Folgendes erfasst: Name des Besuchers, E-Mail-Adresse, Beitrittszeit und Abonnementgruppen. Diese Details werden später verwendet, um Newsletter an Abonnenten zu senden. Statistiken zum Newsletter (gesendet, geöffnet und zurückgeschickt) werden ebenfalls gesammelt. Benutzer können sich jederzeit mit einem Link in jedem Newsletter abmelden.", 'email-newsletter' );
            wp_add_privacy_policy_content('E-Newsletter', $privacy_text);
        }
    }

    function install_notice() {
        echo '<div class="updated fade"><p>' . sprintf(__('Bitte <strong><a href="%s" title="Installiere jetzt &raquo;">Installiere und konfiguriere PS-eNewsletter</a></strong> um alle Features nutzen zu können. <small><a style="color:red;" href="%s">(Verwerfen)</a></small>', 'email-newsletter'), admin_url('admin.php?page=newsletters-settings'), add_query_arg('newsletter_action', 'dismiss_install')) . '</a></p></div>';
    }

    /**
     * init for all users
     **/
    function init() {

        //load translation files
        load_plugin_textdomain( 'email-newsletter', false, dirname( plugin_basename( __FILE__ ) ) . '/email-newsletter-files/languages/' );

        //public actions of the plugin
        if ( isset( $_REQUEST['newsletter_action'] ) && !defined('DOING_AJAX') ) {
            //handle custom redirects
            if(isset($_REQUEST['redirect_to']))
                $redirect = $_REQUEST['redirect_to'];

            switch( $_REQUEST['newsletter_action'] ) {
                //action for subscribe
                case "new_subscribe":
                    $result = $this->new_subscribe();

                    $redirect = isset($redirect) ? $redirect : (isset($result['data']['redirect']) ? $result['data']['redirect'] : 0);
                    if($redirect) {
                        wp_redirect( $redirect );
                        exit();
                    }
                break;

                //action for subscribe
                case "subscribe":
                    $result = $this->subscribe();

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( $result['message'] )), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for save selected groups of subscribe
                case "save_subscribes":
                    $result = $this->save_subscribes();

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( $result['message'] ) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;

                //action for Unsubscribe
                case "unsubscribe":
                    $unsubscribe_code = (isset($_REQUEST['unsubscribe_code'])) ? $_REQUEST['unsubscribe_code'] : '';
                    $result = $this->unsubscribe_by_code( $unsubscribe_code );

                    $redirect = !isset($redirect) ? add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( $result['message']) ), 'admin.php' ) : $redirect;
                    wp_redirect( $redirect );
                    exit();
                break;
            }
        }
    }

    /**
     * subscribtion actions moslty(only:)) for ajax widget
     */
    function manage_subscriptions_ajax() {
        if ( isset( $_REQUEST['newsletter_action'] ) ) {
            switch( $_REQUEST['newsletter_action'] ) {
                //action for save selected groups of subscribe
                case "save_subscribes":
                    $result = $this->save_subscribes();

                    $data['message'] = $result['message'];
                    $data['view'] = 'manage_subscriptions';
                    $data['hide'] = '';

                    echo json_encode($data);
                    die();
                break;

                //action for forcing into groups
                case "subscribe_to_groups":
                    $result = $this->save_subscribes('add');

                    $data['message'] = $result['message'];
                    $data['view'] = 'unsubscribe_from_groups';
                    $data['hide'] = 'subscribe_to_groups';

                    echo json_encode($data);
                    die();
                break;

                //action for forcing out off groups
                case "unsubscribe_from_groups":
                    $result = $this->save_subscribes('remove');

                    $data['message'] = $result['message'];
                    $data['view'] = 'subscribe_to_groups';
                    $data['hide'] = 'unsubscribe_from_groups';

                    echo json_encode($data);
                    die();
                break;

                //action for subscribe
                case "subscribe":
                    $result = $this->subscribe();

                    $data['message'] = $result['message'];
                    if(!$result['error']) {
                        if(isset($this->settings['subscribe_page_id']) && is_numeric($this->settings['subscribe_page_id']) && get_post($this->settings['subscribe_page_id']))
                            $data['redirect'] = add_query_arg(array('message' => urlencode($result['message']), 'enewsletter_subscribed' => 1), get_permalink($this->settings['subscribe_page_id']));

                        $data['view'] = 'manage_subscriptions';
                        $data['hide'] = 'subscribe';
                        $data['unsubscribe_code'] = $result['data']['unsubscribe_code'];
                        if(isset($result['data']['subscribe_groups']))
                            $data['subscribe_groups'] = $result['data']['subscribe_groups'];
                    }
                    else {
                        $data['view'] = 'subscribe';
                        $data['hide'] = 'subscribe';
                    }

                    echo json_encode($data);
                    die();
                break;

                //action for Unsubscribe
                case "unsubscribe":
                    $unsubscribe_code = (isset($_REQUEST['unsubscribe_code'])) ? $_REQUEST['unsubscribe_code'] : '';
                    $result = $this->unsubscribe_by_code( $unsubscribe_code );

                    if(isset($this->settings['unsubscribe_page_id']) && is_numeric($this->settings['unsubscribe_page_id']) && get_post($this->settings['unsubscribe_page_id']))
                        $data['redirect'] = add_query_arg(array('message' => urlencode($result['message']), 'enewsletter_unsubscribed' => 1), get_permalink($this->settings['unsubscribe_page_id']));

                    $data['message'] = $result['message'];
                    $data['view'] = 'subscribe';
                    $data['hide'] = 'manage_subscriptions';

                    echo json_encode($data);
                    die();
                break;

                //action for Subscribe of public member (not user of site)
                case "new_subscribe":
                    $result = $this->new_subscribe();

                    $data = array('message' => $result['message']);
                    if(isset($result['data']['redirect']))
                        $data['redirect'] = $result['data']['redirect'];

                    echo json_encode($data);
                    die();
                break;
            }
        }

        die();
    }



    /**
     * Add new member
     **/
    function add_member( $member_data, $double_opt_in = 0 ) {
        global $wpdb;

        do_action( 'enewsletter_before_user_add', $member_data );

        //first lets check if email exists somewhere
        if ( email_exists( $member_data['email'] ) !== false ) {
            //if email of new member == email of site user
            $wp_user_id = email_exists( $member_data['email'] );
            $member_id = $this->get_members_by_wp_user_id( $wp_user_id );

            //check that this site's user there is on list of members
            if ( 0 < $member_id )
                $message =  __( 'Diese E-Mail wird bereits verwendet!', 'email-newsletter' );

        } else {
            //check email of new member that isn't on list of members
            $member =  $this->get_member_by_email($member_data['email']);
            if ( isset($member['unsubscribe_code']) && !empty($member['unsubscribe_code']) )
                $message =   __( 'Diese E-Mail ist bereits abonniert!', 'email-newsletter' );
        }
        if(isset($message))
            return array('action' => 'email_exists', 'error' => true, 'message' => $message, 'data' => array());

        //New email, lets add it!
        $subscribe = $double_opt_in ? "" : 1;

        $member_data['member_info'] = $double_opt_in ? serialize(array("future_groups_id" => $member_data['groups_id'])) : '';
        $member_data_ready = array(
                'member_fname' => $member_data['fname'],
                'member_lname' => $member_data['lname'],
                'member_email' => $member_data['email'],
                'member_info' => $member_data['member_info']
            );
        $result = $this->create_update_member_user('', $member_data_ready, $subscribe);
        $member_id = $result['member_id'];
        do_action( 'enewsletter_after_user_add', $member_id );

        if ( $double_opt_in ) {
            $status = $this->do_double_opt_in( $member_id );
            if($status)
                return array('action' => 'optin_sent', 'error' => false, 'message' => __( 'Bestätigungs-E-Mail wurde gesendet! Bitte bestätige Dein Abonnement.', 'email-newsletter' ));
            else
                return array('action' => 'optin_sent', 'error' => true, 'message' => __( 'Fehler beim Senden der Anmelde-E-Mail. Stelle sicher, dass Du diese noch nicht in Deinem Posteingang hast.', 'email-newsletter' ));
        } else {
            //creating new list of groups for user
            if ( isset( $member_data['groups_id'] ) && is_array( $member_data['groups_id'] ) )
                $this->add_members_to_groups( $member_id, $member_data['groups_id'] );

            //set sending welcome newsletter if necessary
            if($this->settings['subscribe_newsletter']) {
                $send_details = $this->add_send_email_info( $this->settings['subscribe_newsletter'], $member_id, 0, 'waiting_send' );
                $this->send_email_to_member($send_details['send_id']);
            }
        }

        return array('action' => 'member_added', 'error' => false, 'message' => __( 'Das neue Abonnent wird hinzugefügt!', 'email-newsletter' ));
    }

    /**
     *  Public Subscribe on Newsletters
     **/
    function new_subscribe() {
        global $wpdb;

        if(!is_email($_REQUEST['e_newsletter_email'])) {
            $data['message'] = __( 'Bitte verwende die richtige E-Mail-Adresse!', 'email-newsletter' );

            return array('action' => 'new_subscribed', 'error' => true, 'message' => $data['message']);
        }

        $settings = $this->get_settings();

        //Sets up groups to subscribe to on the beginning
        $subscribe_groups = (isset($settings['subscribe_groups'])) ? explode(',', $settings['subscribe_groups']) : array();
        if(isset($_REQUEST['e_newsletter_groups_id']))
            $subscribe_groups = array_merge($_REQUEST['e_newsletter_groups_id'], $subscribe_groups);
        if(isset($_REQUEST['e_newsletter_auto_groups_id']))
            $subscribe_groups = array_merge($_REQUEST['e_newsletter_auto_groups_id'], $subscribe_groups);
        $subscribe_groups = array_unique($subscribe_groups);

        //set up if double opt in is on
        $double_opt_in = ( isset( $this->settings['double_opt_in'] ) && $this->settings['double_opt_in'] ) ? 1 : 0;

        $member_data['email']       =  ( isset( $_REQUEST['e_newsletter_email'] ) ) ? $_REQUEST['e_newsletter_email'] : '';
        $member_data['fname']       =  ( isset( $_REQUEST['e_newsletter_name'] ) ) ? $_REQUEST['e_newsletter_name'] : '';
        $member_data['lname']       =  '';
        $member_data['groups_id']   =  $subscribe_groups;

        $result = $this->add_member( $member_data, $double_opt_in );
        if(!$result['error']) {
            if($result['action'] != 'optin_sent' && isset($this->settings['subscribe_page_id']) && is_numeric($this->settings['subscribe_page_id']) && get_post($this->settings['subscribe_page_id']))
                $data['redirect'] = add_query_arg(array('message' => urlencode($result['message']), 'enewsletter_subscribed' => 1), get_permalink($this->settings['subscribe_page_id']));
            else
                $data['redirect'] = 0;

            if($result['action'] == 'optin_sent')
                $data['message'] = $result['message'];
                $data['message'] = __( 'Du wurdest erfolgreich abonniert!', 'email-newsletter' );

            return array('action' => 'new_subscribed', 'error' => false, 'message' => $data['message'], 'data' => array('redirect' => $data['redirect']));
        }
        else
            return array('action' => 'new_subscribed', 'error' => true, 'message' => $result['message']);
    }

    /**
     *  Subscribe on Newsletters
     **/
    function subscribe() {
        global $wpdb;

        $current_user = wp_get_current_user();
        $user_id = $current_user->data->ID;
        $member_data = array();

        $result = $this->create_update_member_user($user_id, $member_data, 1);
        $member_id = $result['member_id'];

        if($member_id) {
            do_action( 'enewsletter_user_subscribe', $member_id );

            //Sets up groups to subscribe to on the beginning
            $subscribe_groups = (isset($this->settings['subscribe_groups'])) ? explode(',', $this->settings['subscribe_groups']) : array();
            if(isset($_REQUEST['e_newsletter_groups_id']))
                $subscribe_groups = array_merge($_REQUEST['e_newsletter_groups_id'], $subscribe_groups);
            if(isset($_REQUEST['e_newsletter_auto_groups_id']))
                $subscribe_groups = array_merge($_REQUEST['e_newsletter_auto_groups_id'], $subscribe_groups);
            $subscribe_groups = array_unique($subscribe_groups);

            $result_groups = $this->add_members_to_groups( $member_id, $subscribe_groups );

            if($this->settings['subscribe_newsletter']) {
                $send_details = $this->add_send_email_info( $this->settings['subscribe_newsletter'], $member_id, 0, 'waiting_send' );
                $this->send_email_to_member($send_details['send_id']);
            }

            return array('action' => 'subscribed', 'error' => false, 'message' => __( 'Du hast Dich erfolgreich angemeldet!', 'email-newsletter' ), 'data' => array('member_id' => $member_id, 'unsubscribe_code' => $result['unsubscribe_code'], 'subscribe_groups' => $subscribe_groups));
        }
        else
            return array('action' => 'subscribed', 'error' => true, 'message' => __( 'Beim Abonnieren ist ein Fehler aufgetreten!', 'email-newsletter' ), 'data' => array('member_id' => $member_id, 'unsubscribe_code' => $result['unsubscribe_code']));
    }

    /**
     * Save Subscribes
     **/
    function save_subscribes($type = 'both') {
        $current_user = wp_get_current_user();
        $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );
        $remove_old = $type == 'both' ? 1 : 0;

        //remove from unsubscribed if action like this is preformed
        $member_data = $this->get_member( $member_id );
        if($member_data)
            if(empty($member_data['unsubscribe_code'])) {
                global $wpdb;
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '%s' WHERE member_id = %d", $this->gen_unsubscribe_code(), $member_id ) );
            }


        if($type == 'both' || $type == 'add') {
            if($type == 'both') {
                $groups_id = isset($_REQUEST['e_newsletter_groups_id']) ? $_REQUEST['e_newsletter_groups_id'] : array();
                $message = __( 'Abonnements wurden gespeichert!', 'email-newsletter' );
            }
            else {
                $groups_id = isset($_REQUEST['e_newsletter_add_groups_id']) ? $_REQUEST['e_newsletter_add_groups_id'] : array();
                $message = __( 'Du hast Dich erfolgreich angemeldet!', 'email-newsletter' );
            }
           $result = $this->add_members_to_groups( $member_id, $groups_id, $remove_old );
        }
        elseif($type == 'remove') {
            $groups_id = (isset($_REQUEST['e_newsletter_remove_groups_id'])) ? $_REQUEST['e_newsletter_remove_groups_id'] : array();
            $result = $this->delete_members_group( $member_id, $groups_id );
            $message = __( 'Du wurdest erfolgreich abgemeldet!', 'email-newsletter' );
        }

        do_action( 'enewsletter_user_save_subscribe', $member_id, $groups_id );

        return array('action' => 'subscription_saved', 'error' => false, 'message' => __( 'Abonnements wurden gespeichert!', 'email-newsletter' ), 'data' => array('member_id' => $member_id, 'groups_ids' => $groups_id));
    }

    /**
     * Edit member
     **/
    function edit_member( $member_id, $member_nicename, $member_email ) {
        global $wpdb;

        do_action( 'enewsletter_user_edit', $member_id, $member_nicename, $member_email );

        if ( $member_id && is_email( $member_email ) ) {
            $member_name = explode(' ', $member_nicename);
            if(!isset($member_name[1]))
                $member_name[1] = '';

            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET
            member_fname = %s,
            member_lname = %s,
            member_email = %s
            WHERE member_id = %d
            ", $member_name[0], $member_name[1], $member_email, $member_id ) );
        }

        if(isset($result) && $result)
            return array('action' => 'member_updated', 'error' => false, 'message' => __( 'Benutzer aktualisiert!', 'email-newsletter' ));
        else
            return array('action' => 'member_updated', 'error' => true, 'message' => __( 'Aktualisierung des Benutzers fehlgeschlagen!', 'email-newsletter' ));
    }

    /**
     * Unsubscribe on Newsletters
     **/
    function unsubscribe_by_code( $unsubscribe_code ) {
        global $wpdb;
        if ($unsubscribe_code) {
            $member =  $this->get_member_id_by_code($unsubscribe_code);
            if ( 0 < $member['member_id'] ) {
                if(apply_filters('email_newsletter_keep_unsubscribed', false)) {
                    //delete unsubscribe_code of member
                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE unsubscribe_code = '%s'", $unsubscribe_code ) );
                }
                else {
                    $result = $this->delete_members( array($member['member_id']) );
                }

                return array('action' => 'unsubscribed', 'error' => false, 'message' => __( 'Du bist abgemeldet!', 'email-newsletter' ));
            }
            elseif( 0 < $member['wp_only_user_id'] ) {
                update_user_meta( $member['wp_only_user_id'], 'email_newsletter_unsubscribe_code', 'unsubscribed' );
                return array('action' => 'unsubscribed', 'error' => false, 'message' => __( 'Du bist abgemeldet!', 'email-newsletter' ));
            }
            return array('action' => 'unsubscribed', 'error' => false, 'message' => __( 'Du bist bereits abgemeldet oder noch nicht abonniert!', 'email-newsletter' ));
        }
    }

    /**
     * Delete members
     **/
    function delete_members( $members_id ) {
        global $wpdb;

        do_action( 'enewsletter_user_delete', $members_id );

        if ( $members_id ) {
            foreach( ( array ) $members_id as $member_id ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
            }

            if(count($members_id) == 1)
                return array('action' => 'member_deleted', 'error' => false, 'message' => __( 'Abonnent gelöscht!', 'email-newsletter' ));
            elseif(count($members_id) == 0)
                return array('action' => 'member_deleted', 'error' => true, 'message' => __( 'Keine zu löschenden Abonnenten!', 'email-newsletter' ));
            else
                return array('action' => 'member_deleted', 'error' => false, 'message' => __( 'Abonnenten gelöscht!', 'email-newsletter' ));
        }
    }

    /**
     * Adding new member when create new user
     **/
    function user_create( $userID ) {
        if( !isset( $this->settings['wp_user_register_subscribe'] ) || ( isset( $this->settings['wp_user_register_subscribe'] ) && $this->settings['wp_user_register_subscribe'] ))
            $subscribe = 1;
        else
            $subscribe = 0;

        $result = $this->create_update_member_user($userID, array(), $subscribe);

        $member_id = $result['member_id'];

        if($member_id) {
            global $wpdb;

            if($this->settings['subscribe_newsletter']) {
                $send_details = $this->add_send_email_info( $this->settings['subscribe_newsletter'], $member_id, 0, 'waiting_send' );
                $this->send_email_to_member($send_details['send_id']);
            }

            //creating new list of groups for user
            $subscribe_groups = isset($this->settings['subscribe_groups']) ? explode(',', $this->settings['subscribe_groups']) : 0;
            if ( $subscribe_groups && is_array( $subscribe_groups ) )
                $this->add_members_to_groups( $member_id, $subscribe_groups );
        }
    }

    /**
     * Updates newsletters member details when updating any wp profile
     **/
    function edit_user_update_member( $user_id ) {
        if ( current_user_can('edit_user',$user_id) ) {
            if(is_email( $_POST['email'] )) {
                $blogs = get_blogs_of_user( $user_id );
                foreach ($blogs as $blog) {
                    if(is_multisite()) {
                        if(!isset($current_blog) || !$current_blog)
                            $current_blog = get_current_blog_id();
                        switch_to_blog( $blog->userblog_id  );
                    }

                    $member_data_ready = array(
                            'wp_user_id' => $user_id,
                            'member_email' => $_POST['email']
                        );
                    if(!empty($_POST['first_name'])) {
                        $member_data_ready['member_fname'] = $_POST['first_name'];
                        if(!empty($_POST['last_name']))
                            $member_data_ready['member_lname'] = $_POST['last_name'];
                    }
                    elseif(!empty($_POST['nickname'])) {
                        $member_data_ready['member_fname'] = $_POST['nickname'];
                        $member_data_ready['member_lname'] = '';
                    }
                    else {
                        $member_data_ready['member_fname'] = '';
                        $member_data_ready['member_lname'] = '';
                    }

                    $result = $this->create_update_member_user($user_id, $member_data_ready, '', 1);
                }

                if(is_multisite())
                    switch_to_blog( $current_blog  );
            }

            if(isset($_POST['email_newsletter_unsubscribe_code'])) {
                if($_POST['email_newsletter_unsubscribe_code'] == 'unsubscribed')
                    update_user_meta( $user_id, 'email_newsletter_unsubscribe_code', 'unsubscribed' );
                elseif($_POST['email_newsletter_unsubscribe_code'] == 'yes')
                    update_user_meta( $user_id, 'email_newsletter_unsubscribe_code', $this->gen_unsubscribe_code(1) );
            }
        }
    }

    /**
     * Allows admins to control if they want to recieve mass newsletters for admins
     **/
    function wp_admins_profile() {
        global $user_ID, $wpdb;

        if ( !empty( $_GET['user_id'] ) ) {
            $user_id = $_GET['user_id'];
        } else {
            $user_id = $user_ID;
        }
        if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file))) {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key LIKE %s AND meta_value LIKE %s AND user_id = %d", '%capabilities', '%administrator%', $user_id) );
            if($results) {
                $unsubscribe_code = get_user_meta( $user_id, 'email_newsletter_unsubscribe_code', true );
                ?>
                <h3><?php _e('Multisite Benutzer Newsletters', 'email-newsletter'); ?></h3>

                <table class="form-table">
                <tr>
                    <th><label for="email_newsletter_unsubscribe_code"><?php _e('Erhalte Newsletter für Seiten-Administratoren', 'email-newsletter'); ?></label></th>
                    <td>
                        <select name="email_newsletter_unsubscribe_code" id="email_newsletter_unsubscribe_code">
                                <option value="yes"<?php if ( $unsubscribe_code != 'unsubscribed' ) { echo ' selected="selected" '; } ?>><?php _e('Ja', 'email-newsletter'); ?></option>
                                <option value="unsubscribed"<?php if ( $unsubscribe_code == 'unsubscribed' ) { echo ' selected="selected" '; } ?>><?php _e('Nein', 'email-newsletter'); ?></option>
                        </select>
                    </td>

                </tr>
                </table>
            <?php
            }
        }
    }

    /**
     * Deleting member's groups and member when delete site user
     **/
    function user_delete( $userID ) {
        global $wpdb;

        if ( function_exists('is_multisite' ) && is_multisite() ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
                $blogids[] = 1;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix TODO - maybe function?
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            if($this->get_settings($tb_prefix)) {
                $member_id = $this->get_members_by_wp_user_id( $userID, $blog_id );

                if ( 0 < $member_id ) {
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
                }
            }
        }
    }

    /**
     * Deleting member's groups and member when remove user from site
     **/
    function user_remove_from_site( $userID ) {
        global $wpdb;

        $member_id = $this->get_members_by_wp_user_id( $userID );

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
    }


    /**
     * Create/Edit new Group
     **/
    function create_edit_group( $group_name, $public, $group_id = 0 ) {
        global $wpdb;

        //update when editing group
        if ( $group_id ) {

            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s' WHERE group_id = %d", trim( $group_name ), $public, $group_id ) );
            if( $result && $result['group_id'] != $group_id )
                return array('action' => 'group_modified', 'error' => false, 'message' => __( 'Die Gruppe wurde geändert.', 'email-newsletter' ));
        } else {
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_groups WHERE LOWER(group_name) = '%s'",  strtolower( $group_name ) ), "ARRAY_A");
            if ( $result )
                return array('action' => 'group_exists', 'error' => true, 'message' => __( 'Die Gruppe existiert bereits!', 'email-newsletter' ));

            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s'", trim( $group_name), $public ) );
                return array('action' => 'group_created', 'error' => false, 'message' => __( 'Die Gruppe wurde erstellt.', 'email-newsletter' ));
        }
    }


    /**
     * Delete Group
     **/
    function delete_group( $group_id ) {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE group_id = %d", $group_id ) );
        $result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_groups WHERE group_id = %d", $group_id ) );

        return $result;
    }

    /**
     * Add members to groups
     **/
    function add_members_to_groups( $members_id, $groups_id, $delete_old = 0 ) {
        global $wpdb;
        $result = 0;

        if(!is_array($members_id))
            $members_id = array($members_id);
        if(!is_array($groups_id))
            $groups_id = array($groups_id);

        if ( count($members_id) > 0 )
            foreach( $members_id as $member_id ) {
                //deleting old list of groups if necessary
                if($delete_old)
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );

                foreach( $groups_id as $group_id ) {
                    if(!$this->get_group_by_id( $group_id ))
                        continue;

                    $result = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d AND group_id = %d", $member_id, $group_id ) );
                    if ( !$result )
                        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );
                }
            }

        return $result;
    }

    /**
     * Bulk option -  delete member from group
     **/
    function delete_members_group( $members_id, $groups_id ) {
        global $wpdb;

        if(!is_array($members_id))
            $members_id = array($members_id);
        if(!is_array($groups_id))
            $groups_id = array($groups_id);

        if ( count($members_id) > 0 )
            foreach( $members_id as $member_id ) {
                foreach( $groups_id as $group_id ) {
                    if(!$this->get_group_by_id( $group_id ))
                        continue;

                    $result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d AND group_id = %d", $member_id, $group_id ) );
                }
            }

        return $result;
    }

    /**
     * Bulk option - set member subscription status.
     **/
    function set_members_subscription_status( $members_id, $status ) {
        global $wpdb;

        if ( ! in_array( $status, array( 'subscribed', 'unsubscribed' ), true ) ) {
            return false;
        }

        if(!is_array($members_id))
            $members_id = array($members_id);

        $result = 0;
        foreach ( $members_id as $member_id ) {
            $member_id = intval( $member_id );
            if ( $member_id <= 0 ) {
                continue;
            }

            if ( 'subscribed' === $status ) {
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '%s' WHERE member_id = %d", $this->gen_unsubscribe_code(), $member_id ) );
            } else {
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE member_id = %d", $member_id ) );
            }
        }

        return $result;
    }

    /**
     * Bulk option - move members to one list (replace all list assignments).
     **/
    function move_members_to_group( $members_id, $group_id ) {
        global $wpdb;

        $group_id = intval( $group_id );
        if ( $group_id <= 0 || ! $this->get_group_by_id( $group_id ) ) {
            return false;
        }

        if ( ! is_array( $members_id ) ) {
            $members_id = array( $members_id );
        }

        $result = 0;
        foreach ( $members_id as $member_id ) {
            $member_id = intval( $member_id );
            if ( $member_id <= 0 ) {
                continue;
            }

            $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id = %d", $member_id, $group_id ) );
        }

        return $result;
    }




    /**
     * Delete Newsletter
     **/
    function delete_newsletter( $newsletter_id ) {
        global $wpdb;

        do_action( 'enewsletter_delete_newsletter', $newsletter_id );

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_newsletters WHERE newsletter_id = %d", $newsletter_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_meta WHERE email_id = %d", $newsletter_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE A FROM {$this->tb_prefix}enewsletter_send_members A INNER JOIN wp_enewsletter_send B ON A.send_id = B.send_id WHERE B.newsletter_id = %d", $newsletter_id ) );
        //$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_send WHERE newsletter_id = %d", $newsletter_id ) );

        $this->delete_newsletter_meta($newsletter_id);

        return true;
    }

    /**
     * Clone Newsletter
     **/
    function clone_newsletter( $page_redirect, $newsletter_id = NULL  ) {
        global $wpdb;

        do_action( 'enewsletter_clone_newsletter', $newsletter_id );

        $result = $wpdb->query( $wpdb->prepare( "
            INSERT INTO {$this->tb_prefix}enewsletter_newsletters
            (create_date, template, subject, from_name, from_email, content, contact_info, bounce_email, sent, opened, bounced)
            SELECT %d, template, subject, from_name, from_email, content, contact_info, bounce_email, 0, 0, 0
            FROM {$this->tb_prefix}enewsletter_newsletters
            WHERE newsletter_id = %d
            "
             , time(), $newsletter_id  ) );

        $new_newsletter_id = $wpdb->insert_id;

        $result = $wpdb->query( $wpdb->prepare( "
            INSERT INTO {$this->tb_prefix}enewsletter_meta
            (email_id, meta_key, meta_value)
            SELECT %d, meta_key, meta_value
            FROM {$this->tb_prefix}enewsletter_meta
            WHERE email_id = %d
            "
             , $new_newsletter_id, $newsletter_id  ) );

        return true;
    }

    /**
     * Check that email was opened
     **/
    function check_email_opened_ajax() {
        global $wpdb;

        //write opened time to table
        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members a LEFT JOIN {$this->tb_prefix}enewsletter_send b ON (a.send_id = b.send_id) SET opened_time = %d WHERE (a.send_id = %d OR b.start_time = %d) AND a.member_id = %d AND a.wp_only_user_id = %d AND a.opened_time = 0" , time(), $_REQUEST['send_id'], $_REQUEST['send_id'], $_REQUEST['member_id'], $_REQUEST['wp_only_user_id'] ) );
        if($result) {
            $this->plus_one_member_stats($_REQUEST['member_id'], 'opened');
            $newsletter = $wpdb->get_row( $wpdb->prepare(
                "SELECT b.newsletter_id AS newsletter_id
                FROM {$this->tb_prefix}enewsletter_send_members a
                LEFT JOIN {$this->tb_prefix}enewsletter_send b ON (a.send_id = b.send_id)
                WHERE (a.send_id = %d OR b.start_time = %d)",
                $_REQUEST['send_id'], $_REQUEST['send_id']
            ), "ARRAY_A" );

            if(isset($newsletter['newsletter_id']) && $newsletter['newsletter_id'])
                $this->plus_one_newsletter_stats($newsletter['newsletter_id'], 'opened');
        }

        //show blank image 1x1
        header('Content-Type: image/jpeg');
        $filename = $this->plugin_dir . "email-newsletter-files/images/spacer.gif";
        $handle = fopen( $filename, "r" );
        $content = fread( $handle, filesize( $filename ) );
        fclose( $handle );
        echo $content;
        die();
    }

    function wrap_email_click_links( $contents, $send_id, $member_id, $wp_only_user_id ) {
        $contents = (string) $contents;
        $send_id = intval( $send_id );
        $member_id = intval( $member_id );
        $wp_only_user_id = intval( $wp_only_user_id );

        if ( '' === $contents || $send_id <= 0 ) {
            return $contents;
        }

        if ( false === strpos( strtolower( $contents ), 'href=' ) ) {
            return $contents;
        }

        $callback = function( $matches ) use ( $send_id, $member_id, $wp_only_user_id ) {
            $quote = isset( $matches[1] ) ? $matches[1] : '"';
            $url = isset( $matches[2] ) ? trim( $matches[2] ) : '';

            if ( '' === $url || 0 === strpos( $url, '#' ) || 0 === stripos( $url, 'mailto:' ) || 0 === stripos( $url, 'tel:' ) ) {
                return $matches[0];
            }

            if ( false !== strpos( $url, 'admin-ajax.php?action=check_email_clicked' ) ) {
                return $matches[0];
            }

            $target_url = rawurlencode( base64_encode( $url ) );
            $track_url = add_query_arg(
                array(
                    'action' => 'check_email_clicked',
                    'send_id' => $send_id,
                    'member_id' => $member_id,
                    'wp_only_user_id' => $wp_only_user_id,
                    'target' => $target_url,
                ),
                admin_url( 'admin-ajax.php' )
            );

            return 'href=' . $quote . esc_url( $track_url ) . $quote;
        };

        return preg_replace_callback( '/href\\s*=\\s*(["\'])(.*?)\\1/i', $callback, $contents );
    }

    function check_email_clicked_ajax() {
        $send_id = isset( $_REQUEST['send_id'] ) ? intval( $_REQUEST['send_id'] ) : 0;
        $member_id = isset( $_REQUEST['member_id'] ) ? intval( $_REQUEST['member_id'] ) : 0;
        $wp_only_user_id = isset( $_REQUEST['wp_only_user_id'] ) ? intval( $_REQUEST['wp_only_user_id'] ) : 0;
        $target = isset( $_REQUEST['target'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['target'] ) ) : '';

        $target_url = '';
        if ( '' !== $target ) {
            $decoded = base64_decode( rawurldecode( $target ), true );
            if ( false !== $decoded ) {
                $target_url = trim( $decoded );
            }
        }

        if ( '' === $target_url ) {
            $target_url = home_url();
        }

        if ( ! wp_http_validate_url( $target_url ) && 0 !== strpos( $target_url, '/' ) ) {
            $target_url = home_url();
        }

        $run = $this->get_campaign_run_by_send_id( $send_id );
        if ( $run ) {
            $this->campaign_record_click( $run, $member_id, $wp_only_user_id, $target_url );
        }

        wp_redirect( $target_url );
        exit;
    }

    /**
     * Write inforamtion of Send newsletter to DB
     **/
    function send_newsletter( $newsletter_id ) {
        global $wpdb;

        do_action( 'enewsletter_before_send', $newsletter_id );

        $members_id = array();
        if ( isset( $_REQUEST["all_members"] ) && "1" == $_REQUEST["all_members"] ) {
            $args = array (
                'where' => "unsubscribe_code != ''"
            );

            $members = $this->get_members( $args, 0, 0 );
            foreach ( $members as $member ) {
                $members_id[] = $member['member_id'];
            }
        } else {
            //Get ids for eNewsletter group members
            if ( isset( $_REQUEST["target"]["groups"] ) && is_array($_REQUEST["target"]["groups"]) )
                foreach ( $_REQUEST["target"]["groups"] as $group_id ) {
                    $members_id = array_merge ( $members_id,  $this->get_members_of_group( $group_id, '', 1 ) );
                }

            //Get ids for Membership 2 subscribers
            if ( isset( $_REQUEST["target"]["m2"] ) && is_array($_REQUEST["target"]["m2"]) ) {
                foreach ( $_REQUEST["target"]["m2"] as $membership_id ) {
                    $users_id = $this->get_members_of_membership2($membership_id);
                    foreach ( $users_id as $user_id ) {
                        $member_id = $this->get_members_by_wp_user_id( $user_id, '', 1 );
                        if ( 0 < $member_id )
                            $members_id[] = $member_id;
                    }
                }
            }

            // Deprecated: Membership plugin was replaced by M2 (above)
            //Get ids for Membership levels being subscribed eNewsletter members
            if ( isset( $_REQUEST["target"]["membership_levels"] ) && is_array($_REQUEST["target"]["membership_levels"]) )
                foreach ( $_REQUEST["target"]["membership_levels"] as $membership_level ) {
                    $members = $this->get_members_of_membership($membership_level);
                    foreach ( $members as $member ) {
                        $members_id[] = $member['member_id'];
                    }
                }

            //Get ids for Roles being eNewsletter members
            if ( isset( $_REQUEST["target"]["roles"] ) && is_array($_REQUEST["target"]["roles"]) )
                foreach ( $_REQUEST["target"]["roles"] as $role_name ) {
                    $users_id = get_users( array( 'role' => $role_name ) );
                    foreach ( $users_id as $user_id ) {
                        $member_id = $this->get_members_by_wp_user_id( $user_id->ID, '', 1 );
                        if ( 0 < $member_id )
                            $members_id[] = $member_id;
                    }
                }

            $members_id = array_unique( $members_id );
        }
        //Get ids for admins of other sites
        if ( isset( $_REQUEST["target"]["site_admins"] ) && $_REQUEST["target"]["site_admins"] == 'yes' )
            $wp_only_users_id = $this->get_global_wp_user_ids();
        else
            $wp_only_users_id = 0;

        if ( 'cron_time' == $_REQUEST['cron_time'] ) {
            $time_str = $_REQUEST['aa'].'-'.$_REQUEST['mm'].'-'.$_REQUEST['jj'].' '.($_REQUEST['hh']-get_option('gmt_offset')).':'.$_REQUEST['mn'].':00 GMT';
            $status = $start_time = strtotime($time_str);
        }
        elseif ( 'cron' == $_REQUEST["cron"] )
            $status = 'by_cron';
        else
            $status = 'waiting_send';

        $dont_send_duplicate = (isset( $_REQUEST['dont_send_duplicate'] )) ? $_REQUEST['dont_send_duplicate'] : 0;
        $send_to_bounced = (isset( $_REQUEST['send_to_bounced'] )) ? $_REQUEST['send_to_bounced'] : 0;

        $result = $this->add_send_email_info( $newsletter_id, $members_id, $wp_only_users_id, $status, $dont_send_duplicate, $send_to_bounced );
        if ( !$result['count'] )
            wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'Alle Abonnenten haben es bereits erhalten oder es ist kein Benutzer abonniert!', 'email-newsletter' ) ) ), 'admin.php' ) );
        else
            if ( 'cron' == $_REQUEST["cron"] )
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( $result['count'] . ' ' . __( 'Abonnenten werden zur CRON-Liste hinzugefügt', 'email-newsletter' ) ) ), 'admin.php' ) );
            else
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'send_id' => $result['send_id'], 'check_key' => $_REQUEST['check_key'] ), 'admin.php' ) );

        exit();
    }

    /**
     * Add email or send to CRON list
     **/
    function add_to_cron( $newsletter_id, $send_id ) {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'by_cron' WHERE send_id = %d AND status = 'waiting_send'", $send_id ) );

        wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'Abonnenten werden zur CRON-Liste hinzugefügt', 'email-newsletter' ) ) ), 'admin.php' ) );

        exit;
    }

    /**
     * Remove from CRON list
     **/
    function remove_from_cron( $newsletter_id, $send_id ) {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'waiting_send' WHERE send_id = %d AND status != 'waiting_send'", $send_id ) );

        wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'Abonnenten werden aus der CRON-Liste entfernt', 'email-newsletter' ) ) ), 'admin.php' ) );

        exit;
    }

    /**
     * Send email to member
     **/
    function send_email_to_member($send_id = 0) {
        global $wpdb;

        if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'send_email_to_member' && defined('DOING_AJAX') && !wp_verify_nonce( $_REQUEST['check_key'], 'newsletter_send' ) )
             die( 'Security check' );

        if(!$send_id)
            $send_id = $_REQUEST['send_id'];

        do_action( 'enewsletter_before_send_newsletter', $send_id );

        $send_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = 'waiting_send' LIMIT 0, 1",  $send_id ), "ARRAY_A");

        if ( ! $send_member ) {
            if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' ) )
                wp_schedule_single_event( time() + 60*2, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
            else {
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
                wp_schedule_single_event( time() + 60*2, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
            }

            $message = 'end';
            do_action( 'enewsletter_after_send_newsletter', $send_id );
        }
        else{
            //configure correct bounce hash to detect if standard user or wp only user
            if($send_member['member_id']) {
                $member_data = $this->get_member( $send_member['member_id'] );
                $bounce_id = $send_member['member_id'];
                $bounce_hash = md5( 'Hash of bounce member_id='. $bounce_id . ', send_id='. $send_id );
            }
            elseif($send_member['wp_only_user_id']) {
                $member_data = $this->get_wp_user_only( $send_member['wp_only_user_id'] );
                $bounce_id = $send_member['wp_only_user_id'];
                $bounce_hash = md5( 'Hash of bounce wp_only_user_id='. $bounce_id . ', send_id='. $send_id );
            }

            $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_id ), "ARRAY_A");

            if( !empty($member_data["member_email"]) && is_email($member_data["member_email"]) ) {
                //get data of newsletter

                $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

                $contents = $send_data['email_body'];

                //Replace some content inside the email body
                $user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);
                $first_name = $this->get_firstname($member_data['wp_user_id'], $member_data['member_nicename']);
                $last_name = $this->get_lastname($member_data['wp_user_id'], $member_data['member_nicename']);

                $replacements = array(
                    'user_name' => $user_name,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'to_email' => $member_data["member_email"]
                );


                $contents = $this->personalise_email_body( $contents, $send_member['member_id'], $send_member['wp_only_user_id'], $member_data['join_date'], $member_data['unsubscribe_code'], $send_member['send_id'], $replacements );

                $newsletter_data["subject"] = $this->personalise_email_body($newsletter_data["subject"], $send_member['member_id'], $send_member['wp_only_user_id'], $member_data['join_date'], $member_data['unsubscribe_code'], $send_member['send_id'], array('user_name' => $user_name, 'first_name' => $first_name, 'to_email' => $member_data["member_email"]));

                if((isset($newsletter_data['bounce_email']) && !empty($newsletter_data['bounce_email'])) || (isset($this->settings['bounce_email']) && !empty($this->settings['bounce_email'])))
                    $options['bounce_email'] = (isset($newsletter_data['bounce_email']) && !empty($newsletter_data['bounce_email'])) ? $newsletter_data['bounce_email'] : $this->settings['bounce_email'];

                if ( ! empty( $member_data['unsubscribe_code'] ) ) {
                    $unsubscribe_member_id = intval( $send_member['member_id'] ) > 0 ? intval( $send_member['member_id'] ) : intval( $send_member['wp_only_user_id'] );
                    $options['unsubscribe_url'] = add_query_arg(
                        array(
                            'unsubscribe_page' => '1',
                            'unsubscribe_code' => $member_data['unsubscribe_code'],
                            'unsubscribe_member_id' => $unsubscribe_member_id,
                        ),
                        home_url()
                    );
                }

                $from_domain = explode('@',$newsletter_data['from_email']);
                $from_domain = isset($from_domain[1]) ? '@'.$from_domain[1] : '';
                $options['message_id'] = 'newsletters-' . $bounce_id . '-' . $send_id . '-'. $bounce_hash.$from_domain;

                $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $member_data["member_email"], $newsletter_data["subject"], $contents, $options );
                $this->write_log( 'Send status: '.$sent_status);
                if( $sent_status === true ) {
                    //write info of Sent in DB
                    $result = $this->set_send_email_status('sent', $send_id, $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id'] );
                    if ( $result )
                        $message = 'ok';
                    else
                        $message = __( 'Fehler beim Aktualisieren der Datenbank.', 'email-newsletter' );
                } else {
                    if( $sent_status == 'recipients_failed' || $sent_status == 'invalid_address' || strpos( strtolower( $sent_status ), 'recipient') !== false ) {
                        $result = $this->set_send_email_status( 'bounced', $send_id, $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id'] );
                        if ( $result )
                            $message = 'ok';
                        else
                            $message = __( 'Fehler beim Aktualisieren der Datenbank.', 'email-newsletter' );
                    }
                    else {
                        $message = __( 'Fehler beim Senden der E-Mail. Bitte überprüfe die Einstellungen für ausgehende E-Mails.', 'email-newsletter' );
                    }
                }
            }
            else {
                $result = $this->set_send_email_status( 'bounced', $send_id, $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id'] );
                if ( $result )
                    $message = 'ok';
                else
                    $message = __( 'Fehler beim Aktualisieren der Datenbank.', 'email-newsletter' );
            }
        }

        if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'send_email_to_member' && defined('DOING_AJAX') )
            die($message);
        else
            return $message;
    }

    /**
     * Send email to member
     **/
    function send_by_wpcron() {
        global $wpdb;

        @set_time_limit( 0 );

        if ( 1 > $wpdb->get_var( "SELECT Count(send_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE status = 'by_cron' OR status < UNIX_TIMESTAMP()") )
            return false;

        $process_id = time();
        //writing some information in the plugin log file
        $this->write_log( $process_id . " 01 - start" );

        if ( ! get_option( 'enewsletter_cron_send_run' ) ) {

            //writing some information in the plugin log file
            $this->write_log( $process_id . " 02 - before enewsletter_cron_send_run 1" );

            //add new column for check limit
            if ( 1 != $wpdb->query( "DESCRIBE {$this->tb_prefix}enewsletter_send_members sent_time" ) ) {
                $wpdb->query( "ALTER TABLE {$this->tb_prefix}enewsletter_send_members ADD sent_time INT" );
            }

            update_option( 'enewsletter_cron_send_run', time() );

            //writing some information in the plugin log file
            $this->write_log( $process_id . " 03 - set enewsletter_cron_send_run 1" );


            if ( 0 < $this->settings['send_limit'] ) {

                $month  = date( 'n', time() );
                $year   = date( 'Y', time() );
                $day    = date( 'j', time() );
                $hour   = date( 'H', time() );
                $min    = date( 'i', time() );

                switch ( $this->settings['cron_time'] ) {
                case '1':
                    $limit_time_start   = mktime( $hour , 0, 0, $month, $day, $year ) ;
                    $limit_time_end     = mktime( $hour + 1, 0, -1, $month, $day, $year );
                    break;
                case '2':
                    $limit_time_start   = mktime( 0, 0, 0, $month, $day, $year );
                    $limit_time_end     = mktime( 0, 0, -1, $month, $day + 1, $year );
                    break;
                case '3':
                    $limit_time_start   =  mktime( 0, 0, 0, $month, 1, $year);
                    $limit_time_end     =  mktime( 0, 0, -1, $month + 1, 1, $year);
                    break;
                }

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 04 - cron_time: " . $this->settings['cron_time'] . "  limit_time_start:" . $limit_time_start . "  limit_time_end:" . $limit_time_end );

                $current_count_sent = $wpdb->get_var( $wpdb->prepare( "SELECT Count(send_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE sent_time BETWEEN %d AND %d", $limit_time_start, $limit_time_end ) );

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 05 - current_count_sent: " . $current_count_sent  . "  send_limit:" . $this->settings['send_limit'] );
            }


            if ( ! isset( $current_count_sent ) || $current_count_sent < $this->settings['send_limit'] ) {
                $send_limit = 'LIMIT 0, 500';
                if(!isset($current_count_sent))
                    $current_count_sent = 0;

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 06 - NOT LIMIT YET" );

                //Remember not to use numbers as status other then unixtimestamp (status > 0)
                $send_members = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE status = 'by_cron' OR (status > 0 and status < UNIX_TIMESTAMP()) " . $send_limit , "ARRAY_A");

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 07 - send_members count:" . count($send_members) );

                if ( ! $send_members ) {
                    delete_option( 'enewsletter_cron_send_run' );
                    die(1);
                }

                foreach ( $send_members as $send_member ) {
                    do_action( 'enewsletter_before_cron_send_newsletter', $send_member );

                    update_option( 'enewsletter_cron_send_run', time() );

                    if($send_member['member_id']) {
                        $member_data = $this->get_member( $send_member['member_id'] );
                        $bounce_id = $send_member['member_id'];
                        $bounce_hash = md5( 'Hash of bounce member_id='. $bounce_id . ', send_id='. $send_member['send_id'] );
                    }
                    elseif($send_member['wp_only_user_id']) {
                        $member_data = $this->get_wp_user_only( $send_member['wp_only_user_id'] );
                        $bounce_id = $send_member['wp_only_user_id'];
                        $bounce_hash = md5( 'Hash of bounce wp_only_user_id='. $bounce_id . ', send_id='. $send_member['send_id'] );
                    }

                    $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_member['send_id'] ), "ARRAY_A");
                    if( !empty($member_data["member_email"]) && is_email($member_data["member_email"]) ) {
                        //get data of newsletter
                        $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

                        if( !empty($newsletter_data) ) {
                            $this->write_log( $process_id . " 07-2 - send_member_id:" . $send_member['member_id'] . "/" . $send_member['wp_only_user_id'] . "/" . $send_data['newsletter_id'] . "/" . $newsletter_data['from_name'] . "/" . $send_member['send_id'] );
                            $options = array();
                            if(isset($this->settings['cron_wait']) && is_numeric($this->settings['cron_wait']) && $this->settings['cron_wait'])
                                $options['cron_wait'] = $this->settings['cron_wait'];

                            $contents = $send_data['email_body'];

                            //Replace some content inside the email body
                            $user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);
                            $first_name = $this->get_firstname($member_data['wp_user_id'], $member_data['member_nicename']);
                            $contents = $this->personalise_email_body($contents, $send_member['member_id'], $send_member['wp_only_user_id'], $member_data['join_date'], $member_data['unsubscribe_code'], $send_member['send_id'], array('user_name' => $user_name, 'first_name' => $first_name, 'to_email' => $member_data["member_email"]));

                            if((isset($newsletter_data['bounce_email']) && !empty($newsletter_data['bounce_email'])) || (isset($this->settings['bounce_email']) && !empty($this->settings['bounce_email'])))
                                $options['bounce_email'] = (isset($newsletter_data['bounce_email']) && !empty($newsletter_data['bounce_email'])) ? $newsletter_data['bounce_email'] : $this->settings['bounce_email'];

                            if ( ! empty( $member_data['unsubscribe_code'] ) ) {
                                $unsubscribe_member_id = intval( $send_member['member_id'] ) > 0 ? intval( $send_member['member_id'] ) : intval( $send_member['wp_only_user_id'] );
                                $options['unsubscribe_url'] = add_query_arg(
                                    array(
                                        'unsubscribe_page' => '1',
                                        'unsubscribe_code' => $member_data['unsubscribe_code'],
                                        'unsubscribe_member_id' => $unsubscribe_member_id,
                                    ),
                                    home_url()
                                );
                            }

                            $from_domain = explode('@',$newsletter_data['from_email']);
                            $from_domain = isset($from_domain[1]) ? '@'.$from_domain : '';
                            $options['message_id'] = 'Newsletters-' . $bounce_id . '-' . $send_member['send_id'] . '-'. $bounce_hash;

                            $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $member_data["member_email"], $newsletter_data["subject"], $contents, $options );
                            if( $sent_status === true ) {
                                //write info of Sent in DB
                                $result = $this->set_send_email_status('sent', $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id']);

                                //writing some information in the plugin log file
                                $this->write_log( $process_id . " 09 - send OK" );

                                if ( ++$current_count_sent == $this->settings['send_limit'] ) {
                                    //writing some information in the plugin log file
                                    $this->write_log( $process_id . " 10 - STOP - LIMIT" );

                                    delete_option( 'enewsletter_cron_send_run' );
                                    die(2);
                                }
                            } else {
                                //writing some information in the plugin log file
                                $this->write_log( $process_id . " 08 - send_errors" );
                            }
                        }
                        else {
                            $result = $this->set_send_email_status('bounced', $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id']);

                            $this->write_log( $process_id . " 08 - send_errors:" . " newsletter data empty" );
                        }
                    }
                    else {
                        $result = $this->set_send_email_status('bounced', $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id'], $send_data['newsletter_id']);

                        $this->write_log( $process_id . " 08 - send_errors:" . " no_email" );
                    }

                    do_action( 'enewsletter_after_cron_send_newsletter', $send_member );
                }

                if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' ) )
                    wp_schedule_single_event( time() + 60*5, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                else {
                    wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                    wp_schedule_single_event( time() + 60*5, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                }

            } else {
                delete_option( 'enewsletter_cron_send_run' );
            }
        } elseif ( get_option( 'enewsletter_cron_send_run' ) < time() - 3*60 ) {
            //writing some information in the plugin log file
            $this->write_log( $process_id . " 11 - CRON works more 3 min - restart CRON" );

            delete_option( 'enewsletter_cron_send_run' );
            die(3);
        }
        //writing some information in the plugin log file
        $this->write_log( $process_id . " 12 - END" );

        die(4);
    }

    /**
     * Check bounces email
     **/
    function check_bounces() {
        if(!function_exists('imap_open'))
            return false;

        global $wpdb;

        @set_time_limit( 0 );
        $email_address  = $this->settings['bounce_email'];
        $email_username = $this->settings['bounce_username'];
        $email_password = $this->settings['bounce_password'];
        $email_host     = trim( $this->settings['bounce_host'] );
        $email_port     = ( $this->settings['bounce_port'] ) ? $this->settings['bounce_port'] : 110;

        $email_password = $this->_decrypt($email_password);

        if( ! $email_host )
            return true;

        $email_security = ( $this->settings['bounce_security'] ) ? $this->settings['bounce_security'] : '';

        $mbox = $this->pop3_connet($email_host, $email_port, $email_security, $email_username, $email_password );

        if( ! $mbox ) {
            $this->write_log( 'bounce: error cant connect. Error details: '.strip_tags(imap_last_error()) );
            return 'Error: Failed to connect when checking bounces!';
        } else {
            $this->write_log('bounce: connected to check bounce');

            $MC     = imap_check( $mbox );
            $mails  = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

            foreach ( $mails as $mail ) {
                $this->write_log('bounce: checked email');


                if(
                    strpos($mail->from,'MAILER-DAEMON') !== FALSE ||
                    strpos($mail->from,'mailer-daemon') !== FALSE
                ){
                    $body = imap_body ( $mbox, $mail->msgno );

                    if(
                        preg_match( '/X-Mailer:\s*<?Newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches) ||
                        preg_match( '/X-Mailer:\s*<?newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches) ||
                        preg_match( '/Message-ID:\s*<?Newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches)  ||
                        preg_match( '/Message-ID:\s*<?newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches)
                    ) {

                        $member_id      = ( int ) $matches[1];
                        $send_id        = ( int ) $matches[2];
                        $email_hash     = trim( $matches[3] );
                        $hash           = md5( 'Hash of bounce member_id='. $member_id . ', send_id='. $send_id );
                        $hash_wp        = md5( 'Hash of bounce wp_only_user_id='. $member_id . ', send_id='. $send_id );

                        $this->write_log('bounce: data: '.$member_id.'/'.$send_id.'/'.$email_hash.'/'.$hash.'/'.$hash_wp);

                        if( $email_hash == $hash || $email_hash == $hash_wp ){
                            if($email_hash == $hash_wp) {
                                $wp_only_user_id = $member_id;
                                $member_id = 0;
                            }
                            else {
                                $wp_only_user_id = 0;
                            }

                            $newsletter = $wpdb->get_row( $wpdb->prepare( "SELECT newsletter_id FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d LIMIT 1",  $send_id ), "ARRAY_A");
                            if(isset($newsletter['newsletter_id']) && $newsletter['newsletter_id'])
                                $result = $this->set_send_email_status('bounced', $send_id, $member_id, $wp_only_user_id, $newsletter['newsletter_id']);
                            imap_delete( $mbox, $mail->msgno );
                            echo 'ok';
                        } else {
                            echo 'Error: hash';
                        }

                        $this->write_log('bounce: found bounce:'.$member_id.'/'.$wp_only_user_id);
                    }
                    else {
                        preg_match_all("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i", $body, $possible_emails);

                        foreach ($possible_emails as $possible_email) {
                            if($possible_email[0] != $this->settings['from_email'] && $possible_email[0] != $this->settings['bounce_email']) {
                                $member_id = $this->get_member_by_email( $possible_email[0] );
                                if($member_id ) {
                                    $this->write_log('bounce: found bounce:'.$member_id);
                                    $this->plus_one_member_stats($member_id, 'bounced');

                                    imap_delete( $mbox, $mail->msgno );
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            imap_expunge( $mbox );
            imap_close( $mbox );
        }
        die();
    }

    /**
     * Send Preview (Test) newsletter email
     **/
    function send_preview_ajax() {
        $newsletter_id = (isset($_REQUEST['newsletter_id']) ? $_REQUEST['newsletter_id'] : false);
        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

        if(!$newsletter_id)
            die( __( 'Keine gültige Newsletter-ID angegeben.', 'email-newsletter' ) );

        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'enews_send_preview' ) ) {
            die( __( 'Sicherheitspruefung fehlgeschlagen.', 'email-newsletter' ) );
        }

        $preview_email = isset( $_REQUEST['preview_email'] ) ? sanitize_email( wp_unslash( $_REQUEST['preview_email'] ) ) : '';
        if ( ! is_email( $preview_email ) ) {
            die( __( 'Bitte gib eine gueltige E-Mail-Adresse ein.', 'email-newsletter' ) );
        }

        $newsletter_data = $this->get_newsletter_data( $newsletter_id );
        $content = '';
        $raw_builder_state = isset( $_REQUEST['builder_state_json'] ) ? wp_unslash( $_REQUEST['builder_state_json'] ) : '';

        if ( '' !== $raw_builder_state && isset( $this->builder_v2 ) && $this->builder_v2 ) {
            $decoded_builder_state = json_decode( $raw_builder_state, true );
            if ( ! is_array( $decoded_builder_state ) ) {
                die( __( 'Ungueltiger Builder-Status.', 'email-newsletter' ) );
            }
            $live_state = $this->builder_v2->sanitize_state( $decoded_builder_state, intval( $newsletter_id ) );
            $content = $this->builder_v2->render_state_to_preview( $live_state );
        } else {
            $content = $this->make_email_body($newsletter_id);
        }
        $links = array(
            '{VIEW_LINK}',
            '%7BVIEW_LINK%7D',
            '{UNSUBSCRIBE_URL}',
            '%7BUNSUBSCRIBE_URL%7D',
        );
        $content = str_replace( $links, '#', $content );
        $content = str_replace( array( '{OPENED_TRACKER}', '%7BOPENED_TRACKER%7D', ),
            '<div style="font-size: 0px; line-height:0px; display:none; visibility: hidden;"><img src="#" width="1" height="1"/></div>', $content );
        if($newsletter_data && $content) {
            $live_subject = '';
            if ( isset( $live_state ) && is_array( $live_state ) && isset( $live_state['global'] ) && is_array( $live_state['global'] ) ) {
                $live_subject = isset( $live_state['global']['subject'] ) ? sanitize_text_field( $live_state['global']['subject'] ) : '';
            }
            $subject_source = '' !== $live_subject ? $live_subject : $newsletter_data['subject'];
            $subject = '(PREVIEW) ' . $subject_source;
            if( $this->settings['bounce_email'] ) {
                $options = array('bounce_email' => $this->settings['bounce_email']);
            }
            else
                $options = array();

            $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $preview_email, $subject, $content, $options );
            if( $sent_status === true )
                die( __( 'Test-E-Mail wurde gesendet.', 'email-newsletter' ) );
            else
                die( __( 'Test-E-Mail konnte nicht gesendet werden! Stelle sicher, dass die eingegebene E-Mail korrekt ist, und überprüfe auch die Einstellungen für ausgehende E-Mails.', 'email-newsletter' ) );
        } else {
            die( __( 'E-Mail-Text konnte nicht generiert werden.', 'email-newsletter' ) );
        }
    }

    /**
     * Test smtp settings
     **/
    function test_smtp_ajax(){
        if ( ! current_user_can( 'save_newsletter_settings' ) ) {
            die( __( 'Keine Berechtigung.', 'email-newsletter' ) );
        }

        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'enews_test_smtp' ) ) {
            die( __( 'Sicherheitspruefung fehlgeschlagen.', 'email-newsletter' ) );
        }

        @set_time_limit( 0 );

        //Send test email on bounces address
        $email_id           = time();
        $email_to           = $_REQUEST['smtp_from'];
        $email_from         = $_REQUEST['smtp_from'];
        $email_subject      = "Test-Connection-Send-". $email_id;
        $email_contents     = 'Test';

        $server_host = $_REQUEST['smtp_host'];
        $server_username = $_REQUEST['smtp_username'];
        $server_password = $_REQUEST['smtp_password'];
        if($server_password == '********') {
            $settings = $this->get_settings();
            $server_password = $this->_decrypt($settings['smtp_pass']);
        }

        $server_port = $_REQUEST['smtp_port'];
        $server_security = $_REQUEST['smtp_security'];

        if ( !class_exists( 'ePHPMailer' ) )
            require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        $mail = new ePHPMailer();
        $mail->CharSet = 'UTF-8';

        $mail->IsSMTP();
        $mail->Host = $server_host;

        if($server_security == 'tls' || $server_security == 'ssl')
            $mail->SMTPSecure = $server_security;
        if(!empty($server_port))
            $mail->Port = $server_port;

        $mail->SMTPAuth = ( strlen( $server_username ) > 0 );

        if( $mail->SMTPAuth ){
            $mail->Username = esc_attr($server_username);
            $mail->Password = $server_password;
        }

        $mail->From = $email_from;
        $mail->Sender = $email_from;
        $mail->Subject = $email_subject;
        $mail->isHTML( true );
        $mail->MsgHTML( $email_contents );
        $mail->AddAddress( $email_to );

        $send_status = $mail->Send();
        if( $send_status != true ) {
            die( __( 'Test-E-Mail konnte nicht gesendet werden! - Bitte überprüfe Deine Einstellungen für ausgehende E-Mails und die Serverkonfiguration, um festzustellen, ob ausgewählte Ports geöffnet sind. Fehlerdetails: ', 'email-newsletter' ).strip_tags($mail->ErrorInfo) );
        }
        else
            die( __( 'Testnachricht erfolgreich gesendet! Vergiss bitte nicht Deine Einstellungen zu speichern. Beachte bitte, dass Dein Server möglicherweise die Anzahl der zulässigen Nachrichten begrenzt, welche pro Stunde/Tag/Woche gesendet werden dürfen.', 'email-newsletter' ) );
    }

    /**
     * Test bounces settings
     **/
    function test_bounces_ajax(){
        if ( ! current_user_can( 'save_newsletter_settings' ) ) {
            die( __( 'Keine Berechtigung.', 'email-newsletter' ) );
        }

        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'enews_test_bounces' ) ) {
            die( __( 'Sicherheitspruefung fehlgeschlagen.', 'email-newsletter' ) );
        }

        if(!function_exists('imap_open'))
            die( __( 'PHP Imap wird nicht unterstützt', 'email-newsletter' ) );

        @set_time_limit( 0 );

        //Send test email on bounces address
        $email_id           = time();
        $email_to           = $_REQUEST['bounce_email'];
        $email_from         = ( $this->settings['from_email'] ) ? $this->settings['from_email'] : $_REQUEST['bounce_email'];
        $email_from_name    = ( $this->settings['from_name'] ) ? $this->settings['from_name'] : $_REQUEST['bounce_email'];
        $email_subject      = "Test-Connection-Bounce-". $email_id;
        $email_contents     = 'Test';
        $options            = array ();

        $send_status = $this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents, $options );
        if( $send_status !== true )
            die( __( 'Test-E-Mail konnte nicht gesendet werden! Bitte überprüfe Deine Einstellungen für ausgehende E-Mails.', 'email-newsletter' )  );

        sleep(10);

        //Set value for connect to email server
        $email_address  = $_REQUEST['bounce_email'];
        $email_username = $_REQUEST['bounce_username'];
        $email_password = $_REQUEST['bounce_password'];
        $email_host     = trim( $_REQUEST['bounce_host'] );
        $email_port     = ( $_REQUEST['bounce_port'] ) ? $_REQUEST['bounce_port'] : 110;

        if($email_password == '********') {
            $settings = $this->get_settings();
            $email_password = $this->_decrypt($settings['bounce_password']);
        }

        if( ! $email_host )
            return true;

        sleep( 20 );

        $email_security = isset($_REQUEST['bounce_security']) ? $_REQUEST['bounce_security'] : '';

        $mbox = $this->pop3_connet($email_host, $email_port, $email_security, $email_username, $email_password );

        if( ! $mbox ) {
            die( __( 'Verbindung beim Überprüfen der Bounces fehlgeschlagen! Bitte überprüfe Deine Bounce-Einstellungen und die Serverkonfiguration, um festzustellen, ob ausgewählte Ports geöffnet sind. Fehlerdetails: ', 'email-newsletter' ).strip_tags(imap_last_error()) );
        } else {
            $i = 1;
            while ($i <= 5) {
                $i++;
                $MC = imap_check( $mbox );

                $this->write_log('bounce_test: find bounce attempt: '.$i);

                //get all emails
                $mails = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

                foreach ( $mails as $mail ) {
                    $this->write_log('bounce_test: subject: '.$mail->subject);
                    //Search test email on server
                    if( $mail->subject == 'Test-Connection-Bounce-'. $email_id ) {
                        imap_delete( $mbox, $mail->uid, FT_UID );
                        imap_expunge( $mbox );
                        imap_close( $mbox );

                        $this->write_log('bounce_test: subject found!');
                        die( __( 'Erfolgreich verbunden! Vergiss bitte nicht Deine Einstellungen zu speichern.', 'email-newsletter' ) );
                    }
                }
                imap_expunge( $mbox );
                imap_close( $mbox );

                sleep( 5 );
            }
            die(  __( 'Bounce-Testnachricht nicht gefunden!', 'email-newsletter' ) );
        }
    }


    /**
     * Send Confirm email for subscribe
     **/
     function do_double_opt_in( $member_id ){
        $message = '';
        if( isset( $this->settings['double_opt_in'] ) && $this->settings['double_opt_in'] ) {
            $member_data = $this->get_member( $member_id );

            $email_to           = $member_data['member_email'];
            $email_from         = $this->settings['from_email'];
            $email_from_name    = $this->settings['from_name'];
            $email_subject      = ( isset($this->settings['double_opt_in_subject']) && !empty($this->settings['double_opt_in_subject']) ) ? $this->settings['double_opt_in_subject'] : __('Bestätige bitte Dein Newsletter-Abonnement','email-newsletter');

            // Determine our locale to check for a specific template
            $locale = get_locale();
            $template = apply_filters( 'email_newsletter_email_double_optin_template', $this->plugin_dir . 'email-newsletter-files/emails/double_optin-'.$locale.'.html', $locale );
            if( file_exists( $template ) ) {
                $email_contents     = file_get_contents( $template );
            } else {
                ob_start();
                include($this->plugin_dir . "email-newsletter-files/emails/double_optin.php");
                $email_contents = ob_get_clean();
            }

            $replace = array(
                "from_name"=>$email_from_name,
                "CONFIRM_SUBSCRIPTION"=> $this->get_confirmation_link($member_id),
                "first_name"=>$member_data['member_fname'],
                "last_name"=>$member_data['member_lname'],
                "email"=>$member_data['member_email'],
            );

            foreach( $replace as $key=>$val ) {
                if( is_array( $val ) )continue;
                $email_contents = preg_replace( '/\{'.strtoupper( preg_quote( $key,'/' ) ).'\}/', $val, $email_contents );
                $email_subject = preg_replace( '/\{'.strtoupper( preg_quote( $key,'/' ) ).'\}/', $val, $email_subject );
            }

            $sent_status = $this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents );
            $this->write_log('double opt in send status:'.$sent_status);
            return $sent_status;
        }

    }

    /**
     * Send Confirm email for subscribe
     **/
    function get_confirmation_link($member_id) {
        return add_query_arg( array('subscribe_page' => '1', 'subscribe_code' => md5( "sometext123" . $member_id ), 'subscribe_member_id' => $member_id), home_url() );
    }

    /**
     * Creating admin menu
     **/
    function admin_page() {

        $mu_cap = ( $this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file)) ) ? 'manage_network_options' : 'view_newsletter_dashboard';

        if ( $this->settings ) {
            global $submenu;
            $possible_menu_parent = array(
                'view_newsletter_dashboard' => 'newsletters-dashboard',
                'save_newsletter' => 'newsletters',
                'edit_newsletter_group' => 'newsletters-groups',
                'view_newsletter_members' => 'newsletters-members',
                'save_newsletter_settings' => 'newsletters-settings'
                );
            $capability = 'read';
            $slug = 'newsletters-subscribes';
            foreach ($possible_menu_parent as $possible_capability => $possible_slug)
                if(current_user_can($possible_capability)) {
                    $capability = $possible_capability;
                    $slug = $possible_slug;
                    break;
                }

            add_menu_page( __( 'PS-eNewsletter', 'email-newsletter' ), __( 'PS-eNewsletter', 'email-newsletter' ), $capability, $slug, '', $this->plugin_url . 'email-newsletter-files/images/icon.png');
            add_submenu_page( $slug, __( 'Berichte', 'email-newsletter' ), __( 'Berichte', 'email-newsletter' ), 'view_newsletter_dashboard', 'newsletters-dashboard', array( &$this, 'newsletters_dashboard_page' ) );
            add_submenu_page( $slug, __( 'Newsletters', 'email-newsletter' ), __( 'Newsletters', 'email-newsletter' ), 'save_newsletter', 'newsletters', array( &$this, 'newsletters_page' ) );
            add_submenu_page( $slug, __( 'Neuer Newsletter', 'email-newsletter' ), __( 'Neuer Newsletter', 'email-newsletter' ), 'create_newsletter', 'newsletters-new', array( &$this, 'newsletters_new_page' ) );
            // Hidden page: keep direct links (Edit/Create flow) without cluttering the sidebar menu.
            add_submenu_page( null, __( 'Newsletter Builder', 'email-newsletter' ), __( 'Newsletter Builder', 'email-newsletter' ), 'save_newsletter', 'newsletters-builder-v2', array( &$this, 'newsletters_builder_v2_page' ) );
            add_submenu_page( $slug, __( 'Gruppen', 'email-newsletter' ), __( 'Gruppen', 'email-newsletter' ), 'edit_newsletter_group', 'newsletters-groups', array( &$this, 'member_groups_page' ) );
            add_submenu_page( $slug, __( 'Abonnenten', 'email-newsletter' ), __( 'Abonnenten', 'email-newsletter' ), 'view_newsletter_members', 'newsletters-members',  array( &$this, 'members_page' ) );
            add_submenu_page( $slug, __( 'Kampagnen & Automationen', 'email-newsletter' ), __( 'Kampagnen & Automationen', 'email-newsletter' ), 'save_newsletter', 'newsletters-campaigns',  array( &$this, 'campaigns_page' ) );
            add_submenu_page( null, __( 'Kampagne bearbeiten', 'email-newsletter' ), __( 'Kampagne bearbeiten', 'email-newsletter' ), 'save_newsletter', 'newsletters-campaign-edit',  array( &$this, 'campaign_edit_page' ) );
            add_submenu_page( null, __( 'Kampagnen-Metriken', 'email-newsletter' ), __( 'Kampagnen-Metriken', 'email-newsletter' ), 'save_newsletter', 'newsletters-campaign-stats',  array( &$this, 'campaign_stats_page' ) );
            add_submenu_page( $slug, __( 'Logs', 'email-newsletter' ), __( 'Logs', 'email-newsletter' ), 'save_newsletter_settings', 'newsletters-logs', array( &$this, 'logs_page' ) );
            add_submenu_page( $slug, __( 'Einstellungen', 'email-newsletter' ), __( 'Einstellungen', 'email-newsletter' ), 'save_newsletter_settings', 'newsletters-settings', array( &$this, 'settings_page' ) );

            //menu for lowest level users
            add_submenu_page( $slug, __( 'Meine Abonnements', 'email-newsletter' ), __( 'Meine Abonnements', 'email-newsletter' ), 'read', 'newsletters-subscribes', array( &$this, 'newsletters_subscribe_page' ) );
        } else {
            //first start of plugin
            add_menu_page( __( 'PS-eNewsletter', 'email-newsletter' ), __( 'PS-eNewsletter', 'email-newsletter' ), $mu_cap, 'newsletters-settings' );
            add_submenu_page( 'newsletters-settings', __( 'Basis-Einstellung', 'email-newsletter' ), __( 'Basis-Einstellung', 'email-newsletter' ), $mu_cap, 'newsletters-settings', array( &$this, 'settings_page' ) );
        }
    }

    function get_campaigns_table_name() {
        return $this->tb_prefix . 'enewsletter_campaigns';
    }

    function get_campaign_runs_table_name() {
        return $this->tb_prefix . 'enewsletter_campaign_runs';
    }

    function get_campaign_dedupe_table_name() {
        return $this->tb_prefix . 'enewsletter_campaign_dedupe';
    }

    function get_campaign_clicks_table_name() {
        return $this->tb_prefix . 'enewsletter_campaign_clicks';
    }

    function ensure_campaign_tables() {
        global $wpdb;

        $campaigns_table = $this->get_campaigns_table_name();
        $runs_table = $this->get_campaign_runs_table_name();
        $dedupe_table = $this->get_campaign_dedupe_table_name();
        $clicks_table = $this->get_campaign_clicks_table_name();

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$campaigns_table}'" ) != $campaigns_table ) {
            $wpdb->query( "CREATE TABLE `{$campaigns_table}` (
                `campaign_id` int(11) NOT NULL auto_increment,
                `entity_type` varchar(20) NOT NULL,
                `title` varchar(255) NOT NULL,
                `status` varchar(20) NOT NULL,
                `newsletter_id` int(11) NOT NULL,
                `settings` longtext,
                `targets` longtext,
                `last_run` int(11) DEFAULT '0',
                `next_run` int(11) DEFAULT '0',
                `created_at` int(11) NOT NULL,
                `updated_at` int(11) NOT NULL,
                `created_by` int(11) DEFAULT '0',
                PRIMARY KEY (`campaign_id`)
            ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$runs_table}'" ) != $runs_table ) {
            $wpdb->query( "CREATE TABLE `{$runs_table}` (
                `run_id` int(11) NOT NULL auto_increment,
                `campaign_id` int(11) NOT NULL,
                `run_key` varchar(191) NOT NULL,
                `send_id` int(11) DEFAULT '0',
                `source_post_id` int(11) DEFAULT '0',
                `scheduled_at` int(11) DEFAULT '0',
                `started_at` int(11) DEFAULT '0',
                `finished_at` int(11) DEFAULT '0',
                `status` varchar(20) NOT NULL,
                `queued` int(11) DEFAULT '0',
                `sent` int(11) DEFAULT '0',
                `opened` int(11) DEFAULT '0',
                `clicked` int(11) DEFAULT '0',
                `bounced` int(11) DEFAULT '0',
                `failed` int(11) DEFAULT '0',
                `meta` longtext,
                PRIMARY KEY (`run_id`),
                UNIQUE KEY `run_key` (`run_key`)
            ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );
        }

        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$runs_table}` LIKE 'send_id'" ) ) {
            $wpdb->query( "ALTER TABLE `{$runs_table}` ADD `send_id` int(11) DEFAULT '0' AFTER `run_key`" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$dedupe_table}'" ) != $dedupe_table ) {
            $wpdb->query( "CREATE TABLE `{$dedupe_table}` (
                `dedupe_id` int(11) NOT NULL auto_increment,
                `campaign_id` int(11) NOT NULL,
                `dedupe_key` varchar(191) NOT NULL,
                `created_at` int(11) NOT NULL,
                PRIMARY KEY (`dedupe_id`),
                UNIQUE KEY `campaign_dedupe` (`campaign_id`,`dedupe_key`)
            ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$clicks_table}'" ) != $clicks_table ) {
            $wpdb->query( "CREATE TABLE `{$clicks_table}` (
                `click_id` int(11) NOT NULL auto_increment,
                `run_id` int(11) NOT NULL,
                `campaign_id` int(11) NOT NULL,
                `send_id` int(11) NOT NULL,
                `member_key` varchar(32) NOT NULL,
                `target_url` text,
                `target_hash` char(32) NOT NULL,
                `click_count` int(11) DEFAULT '1',
                `first_clicked` int(11) DEFAULT '0',
                `last_clicked` int(11) DEFAULT '0',
                PRIMARY KEY (`click_id`),
                UNIQUE KEY `run_member_target` (`run_id`,`member_key`,`target_hash`),
                KEY `run_id` (`run_id`),
                KEY `campaign_id` (`campaign_id`),
                KEY `send_id` (`send_id`)
            ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );
        }
    }

    function get_campaign( $campaign_id ) {
        global $wpdb;
        $campaign_id = intval( $campaign_id );
        if ( ! $campaign_id ) {
            return false;
        }

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->get_campaigns_table_name()} WHERE campaign_id = %d", $campaign_id ), 'ARRAY_A' );
    }

    function get_campaigns( $where_sql = '' ) {
        global $wpdb;
        $query = "SELECT * FROM {$this->get_campaigns_table_name()}";
        if ( ! empty( $where_sql ) ) {
            $query .= ' WHERE ' . $where_sql;
        }
        $query .= ' ORDER BY updated_at DESC, campaign_id DESC';
        $rows = $wpdb->get_results( $query, 'ARRAY_A' );
        return is_array( $rows ) ? $rows : array();
    }

    function get_campaign_runs( $campaign_id = 0, $limit = 100 ) {
        global $wpdb;
        $limit = max( 1, min( 500, intval( $limit ) ) );
        $query = "SELECT * FROM {$this->get_campaign_runs_table_name()}";
        if ( intval( $campaign_id ) > 0 ) {
            $query .= $wpdb->prepare( ' WHERE campaign_id = %d', intval( $campaign_id ) );
        }
        $query .= ' ORDER BY run_id DESC LIMIT ' . intval( $limit );
        $rows = $wpdb->get_results( $query, 'ARRAY_A' );
        return is_array( $rows ) ? $rows : array();
    }

    function get_campaign_run_summary( $campaign_id = 0 ) {
        global $wpdb;
        $query = "SELECT
            COUNT(*) AS runs,
            SUM(queued) AS queued,
            SUM(sent) AS sent,
            SUM(opened) AS opened,
            SUM(clicked) AS clicked,
            SUM(bounced) AS bounced,
            SUM(failed) AS failed
            FROM {$this->get_campaign_runs_table_name()}";
        if ( intval( $campaign_id ) > 0 ) {
            $query .= $wpdb->prepare( ' WHERE campaign_id = %d', intval( $campaign_id ) );
        }

        $row = $wpdb->get_row( $query, 'ARRAY_A' );
        if ( ! is_array( $row ) ) {
            return array(
                'runs' => 0,
                'queued' => 0,
                'sent' => 0,
                'opened' => 0,
                'clicked' => 0,
                'bounced' => 0,
                'failed' => 0,
            );
        }

        foreach ( array( 'runs', 'queued', 'sent', 'opened', 'clicked', 'bounced', 'failed' ) as $key ) {
            $row[ $key ] = intval( isset( $row[ $key ] ) ? $row[ $key ] : 0 );
        }
        return $row;
    }

    function get_campaign_run_send_id( $run ) {
        if ( isset( $run['send_id'] ) && intval( $run['send_id'] ) > 0 ) {
            return intval( $run['send_id'] );
        }

        $meta = $this->decode_campaign_json( isset( $run['meta'] ) ? $run['meta'] : '' );
        return isset( $meta['send_id'] ) ? intval( $meta['send_id'] ) : 0;
    }

    function get_campaign_run_by_send_id( $send_id ) {
        global $wpdb;
        $send_id = intval( $send_id );
        if ( $send_id <= 0 ) {
            return false;
        }

        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_campaign_runs_table_name()} WHERE send_id = %d ORDER BY run_id DESC LIMIT 1",
                $send_id
            ),
            'ARRAY_A'
        );

        if ( is_array( $run ) ) {
            return $run;
        }

        $runs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_campaign_runs_table_name()} WHERE meta LIKE %s ORDER BY run_id DESC LIMIT 50",
                '%"send_id":' . $send_id . '%'
            ),
            'ARRAY_A'
        );

        foreach ( (array) $runs as $row ) {
            if ( $this->get_campaign_run_send_id( $row ) === $send_id ) {
                return $row;
            }
        }

        return false;
    }

    function get_campaign_top_links( $campaign_id = 0, $run_id = 0, $limit = 20 ) {
        global $wpdb;

        $limit = max( 1, min( 100, intval( $limit ) ) );
        $where = array();

        if ( intval( $campaign_id ) > 0 ) {
            $where[] = $wpdb->prepare( 'campaign_id = %d', intval( $campaign_id ) );
        }

        if ( intval( $run_id ) > 0 ) {
            $where[] = $wpdb->prepare( 'run_id = %d', intval( $run_id ) );
        }

        $query = "SELECT
            target_hash,
            MAX(target_url) AS target_url,
            SUM(click_count) AS clicks,
            COUNT(DISTINCT member_key) AS unique_clickers,
            MAX(last_clicked) AS last_clicked
            FROM {$this->get_campaign_clicks_table_name()}";

        if ( ! empty( $where ) ) {
            $query .= ' WHERE ' . implode( ' AND ', $where );
        }

        $query .= ' GROUP BY target_hash ORDER BY clicks DESC, last_clicked DESC LIMIT ' . intval( $limit );

        $rows = $wpdb->get_results( $query, 'ARRAY_A' );
        return is_array( $rows ) ? $rows : array();
    }

    function campaign_build_click_member_key( $member_id, $wp_only_user_id ) {
        $member_id = intval( $member_id );
        $wp_only_user_id = intval( $wp_only_user_id );

        if ( $member_id > 0 ) {
            return 'm:' . $member_id;
        }

        if ( $wp_only_user_id > 0 ) {
            return 'w:' . $wp_only_user_id;
        }

        return 'm:0';
    }

    function campaign_sync_run_clicked_metric( $run_id ) {
        global $wpdb;

        $run_id = intval( $run_id );
        if ( $run_id <= 0 ) {
            return;
        }

        $clicked = intval( $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT member_key) FROM {$this->get_campaign_clicks_table_name()} WHERE run_id = %d",
            $run_id
        ) ) );

        $wpdb->update(
            $this->get_campaign_runs_table_name(),
            array( 'clicked' => $clicked ),
            array( 'run_id' => $run_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    function campaign_record_click( $run, $member_id, $wp_only_user_id, $target_url ) {
        global $wpdb;

        if ( ! is_array( $run ) || empty( $run['run_id'] ) ) {
            return false;
        }

        $target_url = trim( (string) $target_url );
        if ( '' === $target_url ) {
            return false;
        }

        $run_id = intval( $run['run_id'] );
        $campaign_id = intval( $run['campaign_id'] );
        $send_id = $this->get_campaign_run_send_id( $run );
        $member_key = $this->campaign_build_click_member_key( $member_id, $wp_only_user_id );
        $target_hash = md5( $target_url );
        $now = current_time( 'timestamp' );

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->get_campaign_clicks_table_name()}
                (run_id, campaign_id, send_id, member_key, target_url, target_hash, click_count, first_clicked, last_clicked)
                VALUES (%d, %d, %d, %s, %s, %s, 1, %d, %d)
                ON DUPLICATE KEY UPDATE click_count = click_count + 1, last_clicked = VALUES(last_clicked)",
                $run_id,
                $campaign_id,
                $send_id,
                $member_key,
                $target_url,
                $target_hash,
                $now,
                $now
            )
        );

        $this->campaign_sync_run_clicked_metric( $run_id );
        return true;
    }

    function campaigns_sync_run_metrics( $campaign_id = 0 ) {
        global $wpdb;

        $where = "status IN ('queued','running')";
        if ( intval( $campaign_id ) > 0 ) {
            $where .= $wpdb->prepare( ' AND campaign_id = %d', intval( $campaign_id ) );
        }

        $runs = $wpdb->get_results(
            "SELECT * FROM {$this->get_campaign_runs_table_name()} WHERE {$where} ORDER BY run_id DESC LIMIT 200",
            'ARRAY_A'
        );

        foreach ( (array) $runs as $run ) {
            $send_id = $this->get_campaign_run_send_id( $run );
            if ( $send_id <= 0 ) {
                continue;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        COUNT(*) AS queued,
                        SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) AS sent,
                        SUM(CASE WHEN status='bounced' THEN 1 ELSE 0 END) AS bounced,
                        SUM(CASE WHEN opened_time > 0 THEN 1 ELSE 0 END) AS opened,
                        SUM(CASE WHEN status='by_cron' OR status='waiting_send' THEN 1 ELSE 0 END) AS pending
                    FROM {$this->tb_prefix}enewsletter_send_members
                    WHERE send_id = %d",
                    $send_id
                ),
                'ARRAY_A'
            );

            if ( ! is_array( $row ) ) {
                continue;
            }

            $queued = intval( $row['queued'] );
            $sent = intval( $row['sent'] );
            $opened = intval( $row['opened'] );
            $bounced = intval( $row['bounced'] );
            $pending = intval( $row['pending'] );
            $failed = max( 0, $queued - $sent - $bounced - $pending );
            $status = $pending > 0 ? 'running' : 'finished';
            $clicked = intval( $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT member_key) FROM {$this->get_campaign_clicks_table_name()} WHERE run_id = %d",
                intval( $run['run_id'] )
            ) ) );
            $previous_status = isset( $run['status'] ) ? $run['status'] : '';

            $wpdb->update(
                $this->get_campaign_runs_table_name(),
                array(
                    'status' => $status,
                    'queued' => $queued,
                    'sent' => $sent,
                    'opened' => $opened,
                    'clicked' => $clicked,
                    'bounced' => $bounced,
                    'failed' => $failed,
                    'finished_at' => $pending > 0 ? intval( $run['finished_at'] ) : current_time( 'timestamp' ),
                ),
                array( 'run_id' => intval( $run['run_id'] ) ),
                array( '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d' ),
                array( '%d' )
            );

            if ( $status !== $previous_status ) {
                $this->log_event(
                    'campaign.run_status_changed',
                    'info',
                    array(
                        'run_id' => intval( $run['run_id'] ),
                        'campaign_id' => intval( $run['campaign_id'] ),
                        'from' => $previous_status,
                        'to' => $status,
                        'queued' => $queued,
                        'sent' => $sent,
                        'opened' => $opened,
                        'clicked' => $clicked,
                        'bounced' => $bounced,
                        'failed' => $failed,
                    )
                );
            }
        }
    }

    function sanitize_campaign_entity_type( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'campaign', 'automation' ), true ) ? $value : 'campaign';
    }

    function sanitize_campaign_status( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'draft', 'active', 'paused', 'stopped' ), true ) ? $value : 'draft';
    }

    function sanitize_campaign_targets( $raw ) {
        $targets = array(
            'group_ids' => array(),
        );

        if ( ! is_array( $raw ) || ! isset( $raw['group_ids'] ) ) {
            return $targets;
        }

        $group_ids = is_array( $raw['group_ids'] ) ? $raw['group_ids'] : array( $raw['group_ids'] );
        foreach ( $group_ids as $group_id ) {
            $group_id = intval( $group_id );
            if ( $group_id > 0 ) {
                $targets['group_ids'][] = $group_id;
            }
        }
        $targets['group_ids'] = array_values( array_unique( $targets['group_ids'] ) );
        return $targets;
    }

    function sanitize_campaign_settings( $entity_type, $raw ) {
        $settings = array();
        if ( ! is_array( $raw ) ) {
            $raw = array();
        }

        if ( 'campaign' === $entity_type ) {
            $settings['interval_value'] = max( 1, min( 365, intval( isset( $raw['interval_value'] ) ? $raw['interval_value'] : 7 ) ) );
            $interval_unit = isset( $raw['interval_unit'] ) ? sanitize_key( $raw['interval_unit'] ) : 'days';
            $settings['interval_unit'] = in_array( $interval_unit, array( 'hours', 'days', 'weeks' ), true ) ? $interval_unit : 'days';
            $settings['start_at'] = isset( $raw['start_at'] ) ? intval( $raw['start_at'] ) : 0;
        } else {
            $trigger_type = isset( $raw['trigger_type'] ) ? sanitize_key( $raw['trigger_type'] ) : 'new_post';
            $settings['trigger_type'] = in_array( $trigger_type, array( 'new_post', 'new_product', 'digest' ), true ) ? $trigger_type : 'new_post';
            $digest_period = isset( $raw['digest_period'] ) ? sanitize_key( $raw['digest_period'] ) : 'weekly';
            $settings['digest_period'] = in_array( $digest_period, array( 'weekly', 'monthly' ), true ) ? $digest_period : 'weekly';
            $settings['weekday'] = max( 1, min( 7, intval( isset( $raw['weekday'] ) ? $raw['weekday'] : 1 ) ) );
            $settings['month_day'] = max( 1, min( 31, intval( isset( $raw['month_day'] ) ? $raw['month_day'] : 1 ) ) );
            $send_hour = max( 0, min( 23, intval( isset( $raw['send_hour'] ) ? $raw['send_hour'] : 9 ) ) );
            $send_minute = max( 0, min( 59, intval( isset( $raw['send_minute'] ) ? $raw['send_minute'] : 0 ) ) );
            $settings['send_hour'] = $send_hour;
            $settings['send_minute'] = $send_minute;
        }

        return $settings;
    }

    function save_campaign_from_request( $request ) {
        global $wpdb;

        $campaign_id = isset( $request['campaign_id'] ) ? intval( $request['campaign_id'] ) : 0;
        $entity_type = $this->sanitize_campaign_entity_type( isset( $request['entity_type'] ) ? $request['entity_type'] : 'campaign' );
        $title = sanitize_text_field( isset( $request['title'] ) ? wp_unslash( $request['title'] ) : '' );
        if ( '' === $title ) {
            $title = 'campaign' === $entity_type ? __( 'Neue Kampagne', 'email-newsletter' ) : __( 'Neue Automation', 'email-newsletter' );
        }
        $status = $this->sanitize_campaign_status( isset( $request['status'] ) ? $request['status'] : 'draft' );
        $newsletter_id = isset( $request['newsletter_id'] ) ? intval( $request['newsletter_id'] ) : 0;
        $campaign_raw = isset( $request['campaign'] ) ? wp_unslash( $request['campaign'] ) : array();
        $settings = $this->sanitize_campaign_settings( $entity_type, $campaign_raw );
        $targets = $this->sanitize_campaign_targets( $campaign_raw );
        $now = current_time( 'timestamp' );

        $data = array(
            'entity_type' => $entity_type,
            'title' => $title,
            'status' => $status,
            'newsletter_id' => $newsletter_id,
            'settings' => wp_json_encode( $settings ),
            'targets' => wp_json_encode( $targets ),
            'updated_at' => $now,
        );

        if ( $campaign_id > 0 ) {
            $wpdb->update( $this->get_campaigns_table_name(), $data, array( 'campaign_id' => $campaign_id ) );
        } else {
            $data['created_at'] = $now;
            $data['created_by'] = get_current_user_id();
            $data['last_run'] = 0;
            $data['next_run'] = 0;
            $wpdb->insert( $this->get_campaigns_table_name(), $data );
            $campaign_id = intval( $wpdb->insert_id );
        }

        $campaign = $this->get_campaign( $campaign_id );
        if ( $campaign ) {
            $next_run = $this->compute_campaign_next_run( $campaign );
            if ( 'active' !== $campaign['status'] ) {
                $next_run = 0;
            }
            $wpdb->update( $this->get_campaigns_table_name(), array( 'next_run' => $next_run ), array( 'campaign_id' => $campaign_id ) );
        }

        return $campaign_id;
    }

    function set_campaign_status( $campaign_id, $status ) {
        global $wpdb;
        $campaign_id = intval( $campaign_id );
        if ( ! $campaign_id ) {
            return;
        }

        $status = $this->sanitize_campaign_status( $status );
        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return;
        }

        $next_run = 0;
        if ( 'active' === $status ) {
            $campaign['status'] = 'active';
            $next_run = $this->compute_campaign_next_run( $campaign );
        }

        $wpdb->update(
            $this->get_campaigns_table_name(),
            array(
                'status' => $status,
                'next_run' => $next_run,
                'updated_at' => current_time( 'timestamp' ),
            ),
            array( 'campaign_id' => $campaign_id )
        );
    }

    function delete_campaign( $campaign_id ) {
        global $wpdb;
        $campaign_id = intval( $campaign_id );
        if ( ! $campaign_id ) {
            return;
        }

        $wpdb->delete( $this->get_campaign_dedupe_table_name(), array( 'campaign_id' => $campaign_id ) );
        $wpdb->delete( $this->get_campaign_clicks_table_name(), array( 'campaign_id' => $campaign_id ) );
        $wpdb->delete( $this->get_campaign_runs_table_name(), array( 'campaign_id' => $campaign_id ) );
        $wpdb->delete( $this->get_campaigns_table_name(), array( 'campaign_id' => $campaign_id ) );
    }

    function decode_campaign_json( $json ) {
        $decoded = json_decode( (string) $json, true );
        return is_array( $decoded ) ? $decoded : array();
    }

    function compute_campaign_next_run( $campaign ) {
        $campaign = is_array( $campaign ) ? $campaign : array();
        $status = isset( $campaign['status'] ) ? $campaign['status'] : 'draft';
        if ( 'active' !== $status ) {
            return 0;
        }

        $entity_type = isset( $campaign['entity_type'] ) ? $campaign['entity_type'] : 'campaign';
        $settings = $this->decode_campaign_json( isset( $campaign['settings'] ) ? $campaign['settings'] : '' );
        $now = current_time( 'timestamp' );

        if ( 'campaign' === $entity_type ) {
            $value = max( 1, intval( isset( $settings['interval_value'] ) ? $settings['interval_value'] : 7 ) );
            $unit = isset( $settings['interval_unit'] ) ? $settings['interval_unit'] : 'days';
            $seconds = DAY_IN_SECONDS;
            if ( 'hours' === $unit ) {
                $seconds = HOUR_IN_SECONDS;
            } elseif ( 'weeks' === $unit ) {
                $seconds = WEEK_IN_SECONDS;
            }

            $start_at = intval( isset( $settings['start_at'] ) ? $settings['start_at'] : 0 );
            $last_run = intval( isset( $campaign['last_run'] ) ? $campaign['last_run'] : 0 );
            if ( $last_run > 0 ) {
                return $last_run + ( $value * $seconds );
            }
            if ( $start_at > $now ) {
                return $start_at;
            }
            return $now + ( $value * $seconds );
        }

        $trigger_type = isset( $settings['trigger_type'] ) ? $settings['trigger_type'] : 'new_post';
        if ( in_array( $trigger_type, array( 'new_post', 'new_product' ), true ) ) {
            return 0;
        }

        $hour = max( 0, min( 23, intval( isset( $settings['send_hour'] ) ? $settings['send_hour'] : 9 ) ) );
        $minute = max( 0, min( 59, intval( isset( $settings['send_minute'] ) ? $settings['send_minute'] : 0 ) ) );
        $digest_period = isset( $settings['digest_period'] ) ? $settings['digest_period'] : 'weekly';

        if ( 'monthly' === $digest_period ) {
            $day = max( 1, min( 31, intval( isset( $settings['month_day'] ) ? $settings['month_day'] : 1 ) ) );
            $year = intval( date( 'Y', $now ) );
            $month = intval( date( 'n', $now ) );
            $last_day = intval( date( 't', mktime( 0, 0, 0, $month, 1, $year ) ) );
            $target_day = min( $day, $last_day );
            $candidate = mktime( $hour, $minute, 0, $month, $target_day, $year );
            if ( $candidate <= $now ) {
                $month += 1;
                if ( $month > 12 ) {
                    $month = 1;
                    $year += 1;
                }
                $last_day = intval( date( 't', mktime( 0, 0, 0, $month, 1, $year ) ) );
                $target_day = min( $day, $last_day );
                $candidate = mktime( $hour, $minute, 0, $month, $target_day, $year );
            }

            return $candidate;
        }

        $weekday = max( 1, min( 7, intval( isset( $settings['weekday'] ) ? $settings['weekday'] : 1 ) ) );
        $current_weekday = intval( date( 'N', $now ) );
        $delta = $weekday - $current_weekday;
        $candidate = mktime( $hour, $minute, 0, intval( date( 'n', $now ) ), intval( date( 'j', $now ) ) + $delta, intval( date( 'Y', $now ) ) );
        if ( $candidate <= $now ) {
            $candidate += WEEK_IN_SECONDS;
        }

        return $candidate;
    }

    function resolve_campaign_members( $campaign ) {
        $targets = $this->decode_campaign_json( isset( $campaign['targets'] ) ? $campaign['targets'] : '' );
        $group_ids = isset( $targets['group_ids'] ) && is_array( $targets['group_ids'] ) ? $targets['group_ids'] : array();

        if ( empty( $group_ids ) ) {
            $members = $this->get_members( array( 'where' => "A.unsubscribe_code != ''" ), 0, 0 );
            $ids = array();
            foreach ( (array) $members as $member ) {
                if ( isset( $member['member_id'] ) ) {
                    $ids[] = intval( $member['member_id'] );
                }
            }
            return array_values( array_unique( array_filter( $ids ) ) );
        }

        $ids = array();
        foreach ( $group_ids as $group_id ) {
            $group_member_ids = $this->get_members_of_group( intval( $group_id ), '', 1 );
            if ( is_array( $group_member_ids ) ) {
                $ids = array_merge( $ids, $group_member_ids );
            }
        }

        $ids = array_map( 'intval', $ids );
        $ids = array_filter( $ids );
        return array_values( array_unique( $ids ) );
    }

    function build_campaign_run_key( $campaign_id, $dedupe_key ) {
        return 'c' . intval( $campaign_id ) . ':' . md5( (string) $dedupe_key );
    }

    function has_campaign_dedupe( $campaign_id, $dedupe_key ) {
        global $wpdb;
        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT dedupe_id FROM {$this->get_campaign_dedupe_table_name()} WHERE campaign_id = %d AND dedupe_key = %s",
                intval( $campaign_id ),
                (string) $dedupe_key
            )
        );

        return ! empty( $value );
    }

    function register_campaign_dedupe( $campaign_id, $dedupe_key ) {
        global $wpdb;
        $wpdb->insert(
            $this->get_campaign_dedupe_table_name(),
            array(
                'campaign_id' => intval( $campaign_id ),
                'dedupe_key' => (string) $dedupe_key,
                'created_at' => current_time( 'timestamp' ),
            ),
            array( '%d', '%s', '%d' )
        );
    }

    function dispatch_campaign_send( $campaign, $context = array() ) {
        global $wpdb;

        $campaign_id = intval( isset( $campaign['campaign_id'] ) ? $campaign['campaign_id'] : 0 );
        $newsletter_id = intval( isset( $campaign['newsletter_id'] ) ? $campaign['newsletter_id'] : 0 );
        if ( ! $campaign_id || ! $newsletter_id ) {
            $this->log_event(
                'campaign.dispatch_invalid',
                'warning',
                array(
                    'campaign_id' => $campaign_id,
                    'newsletter_id' => $newsletter_id,
                )
            );
            return false;
        }

        $dedupe_key = isset( $context['dedupe_key'] ) ? (string) $context['dedupe_key'] : 'run:' . gmdate( 'YmdHi' );
        if ( $this->has_campaign_dedupe( $campaign_id, $dedupe_key ) ) {
            $this->log_event(
                'campaign.dispatch_dedupe_skip',
                'info',
                array(
                    'campaign_id' => $campaign_id,
                    'dedupe_key' => $dedupe_key,
                )
            );
            return false;
        }

        $member_ids = $this->resolve_campaign_members( $campaign );
        $run_key = $this->build_campaign_run_key( $campaign_id, $dedupe_key );
        $status = 'queued';
        $queued_count = count( $member_ids );
        $send_id = 0;

        if ( $queued_count > 0 ) {
            $result = $this->add_send_email_info( $newsletter_id, $member_ids, 0, 'by_cron', 1, 0 );
            if ( isset( $result['send_id'] ) ) {
                $send_id = intval( $result['send_id'] );
            }

            if ( $send_id > 0 && isset( $this->builder_v2 ) && $this->builder_v2 && $this->builder_v2->has_saved_state( $newsletter_id ) ) {
                $email_body = $this->builder_v2->render_newsletter_email( $newsletter_id, 'send', $context );
                if ( ! empty( $email_body ) ) {
                    $wpdb->update(
                        $this->tb_prefix . 'enewsletter_send',
                        array( 'email_body' => $email_body ),
                        array( 'send_id' => $send_id ),
                        array( '%s' ),
                        array( '%d' )
                    );
                }
            }
        } else {
            $status = 'skipped';
        }

        $wpdb->insert(
            $this->get_campaign_runs_table_name(),
            array(
                'campaign_id' => $campaign_id,
                'run_key' => $run_key,
                'send_id' => $send_id,
                'source_post_id' => intval( isset( $context['source_post_id'] ) ? $context['source_post_id'] : 0 ),
                'scheduled_at' => intval( isset( $context['scheduled_at'] ) ? $context['scheduled_at'] : current_time( 'timestamp' ) ),
                'started_at' => current_time( 'timestamp' ),
                'finished_at' => current_time( 'timestamp' ),
                'status' => $status,
                'queued' => $queued_count,
                'meta' => wp_json_encode( array( 'send_id' => $send_id ) ),
            ),
            array( '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s' )
        );
        $run_id = intval( $wpdb->insert_id );

        $this->log_event(
            'campaign.dispatch_created',
            'info',
            array(
                'campaign_id' => $campaign_id,
                'run_id' => $run_id,
                'send_id' => $send_id,
                'status' => $status,
                'queued' => $queued_count,
                'dedupe_key' => $dedupe_key,
                'source_post_id' => intval( isset( $context['source_post_id'] ) ? $context['source_post_id'] : 0 ),
            )
        );

        $this->register_campaign_dedupe( $campaign_id, $dedupe_key );

        $now = current_time( 'timestamp' );
        $campaign['last_run'] = $now;
        $campaign['next_run'] = $this->compute_campaign_next_run( $campaign );
        $wpdb->update(
            $this->get_campaigns_table_name(),
            array(
                'last_run' => $campaign['last_run'],
                'next_run' => $campaign['next_run'],
                'updated_at' => $now,
            ),
            array( 'campaign_id' => $campaign_id )
        );

        return true;
    }

    function campaigns_run_due() {
        $lock_key = 'enewsletter_campaign_runner_lock_' . get_current_blog_id();
        $lock_token = wp_generate_password( 20, false, false );
        if ( false !== get_transient( $lock_key ) ) {
            $this->log_event( 'campaign.runner_skipped_lock', 'debug', array() );
            return;
        }

        set_transient( $lock_key, $lock_token, 15 * MINUTE_IN_SECONDS );

        $this->ensure_campaign_tables();
        $now = current_time( 'timestamp' );
        $campaigns = $this->get_campaigns( "status = 'active' AND next_run > 0 AND next_run <= " . intval( $now ) );

        $this->log_event(
            'campaign.runner_tick',
            'debug',
            array(
                'due_count' => count( (array) $campaigns ),
                'now' => $now,
            )
        );

        foreach ( $campaigns as $campaign ) {
            $dedupe_key = 'schedule:' . gmdate( 'YmdHi', intval( $campaign['next_run'] ) );
            $this->dispatch_campaign_send(
                $campaign,
                array(
                    'dedupe_key' => $dedupe_key,
                    'scheduled_at' => intval( $campaign['next_run'] ),
                )
            );
        }

        $this->campaigns_sync_run_metrics();

        $existing_lock = get_transient( $lock_key );
        if ( $existing_lock === $lock_token ) {
            delete_transient( $lock_key );
        }
    }

    function campaigns_handle_publish_post( $post_id, $post ) {
        if ( ! is_object( $post ) || 'publish' !== $post->post_status ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $this->ensure_campaign_tables();
        $campaigns = $this->get_campaigns( "status = 'active' AND entity_type = 'automation'" );
        $triggered = 0;
        foreach ( $campaigns as $campaign ) {
            $settings = $this->decode_campaign_json( $campaign['settings'] );

            $trigger_type = isset( $settings['trigger_type'] ) ? $settings['trigger_type'] : 'new_post';
            if ( 'new_post' === $trigger_type && 'post' !== $post->post_type ) {
                continue;
            }
            if ( 'new_product' === $trigger_type && ! in_array( $post->post_type, array( 'product', 'mp_product' ), true ) ) {
                continue;
            }
            if ( ! in_array( $trigger_type, array( 'new_post', 'new_product' ), true ) ) {
                continue;
            }

            $dispatched = $this->dispatch_campaign_send(
                $campaign,
                array(
                    'dedupe_key' => 'post:' . intval( $post_id ),
                    'source_post_id' => intval( $post_id ),
                    'scheduled_at' => current_time( 'timestamp' ),
                )
            );

            if ( $dispatched ) {
                $triggered++;
            }
        }

        $this->log_event(
            'campaign.publish_post_trigger',
            'info',
            array(
                'post_id' => intval( $post_id ),
                'triggered_runs' => $triggered,
            )
        );
    }

    function campaigns_page() {
        $this->campaigns_sync_run_metrics();
        require_once( $this->plugin_dir . 'email-newsletter-files/page-campaigns.php' );
    }

    function campaign_edit_page() {
        require_once( $this->plugin_dir . 'email-newsletter-files/page-campaign-edit.php' );
    }

    function campaign_stats_page() {
        $this->campaigns_sync_run_metrics( isset( $_REQUEST['campaign_id'] ) ? intval( $_REQUEST['campaign_id'] ) : 0 );
        require_once( $this->plugin_dir . 'email-newsletter-files/page-campaign-stats.php' );
    }

    function logs_page() {
        require_once( $this->plugin_dir . 'email-newsletter-files/page-logs.php' );
    }

    /**
     *  Tempalate of the Newsletters Dashboard page
     **/
    function newsletters_dashboard_page() {
        //including file for send newsletter
        if ( isset( $_REQUEST['newsletter_action'] ) && "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }
        require_once( $this->plugin_dir . "email-newsletter-files/page-newsletters-dashboard.php" );
    }

    /**
     *  Tempalate of the Newsletters page
     **/
    function newsletters_page() {
        //including file for send newsletter
        if ( isset( $_REQUEST['newsletter_action'] ) && "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }

        require_once( $this->plugin_dir . "email-newsletter-files/page-newsletters.php" );
    }

    /**
     * Template of the native builder page
     **/
    function newsletters_builder_v2_page() {
        if ( isset( $_REQUEST['create'] ) && intval( $_REQUEST['create'] ) ) {
            if ( ! current_user_can( 'create_newsletter' ) ) {
                wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
            }

            $newsletter_id = $this->create_newsletter_for_builder();
            if ( ! $newsletter_id ) {
                wp_die( __( 'Der Newsletter konnte nicht erstellt werden.', 'email-newsletter' ) );
            }

            $target = add_query_arg(
                array(
                    'page' => 'newsletters-builder-v2',
                    'newsletter_id' => intval( $newsletter_id ),
                ),
                admin_url( 'admin.php' )
            );

            if ( isset( $_REQUEST['return'] ) ) {
                $default_return = admin_url( 'admin.php?page=newsletters' );
                $return = wp_validate_redirect( wp_unslash( $_REQUEST['return'] ), $default_return );
                if ( ! empty( $return ) ) {
                    $target = add_query_arg( 'return', $return, $target );
                }
            }

            if ( ! headers_sent() ) {
                wp_redirect( $target );
                exit();
            }

            echo '<script>window.location.replace(' . wp_json_encode( $target ) . ');</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $target ) . '" /></noscript>';
            exit();
        }

        require_once( $this->plugin_dir . "email-newsletter-files/page-newsletters-builder-v2.php" );
    }

    function newsletters_new_page() {
        if ( ! current_user_can( 'create_newsletter' ) ) {
            wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
        }

        $target = add_query_arg(
            array(
                'page' => 'newsletters-builder-v2',
                'create' => 1,
            ),
            admin_url( 'admin.php' )
        );

        wp_redirect( $target );
        exit();
    }

    function builder_v2_preview_ajax() {
        $this->builder_v2->ajax_preview();
    }

    function builder_v2_search_items_ajax() {
        $this->builder_v2->ajax_search_items();
    }

    function builder_v2_save_preset_ajax() {
        $this->builder_v2->ajax_save_user_preset();
    }

    function builder_v2_delete_preset_ajax() {
        $this->builder_v2->ajax_delete_user_preset();
    }

    function get_default_builder_template_slug() {
        $arg = array(
            'limit' => 'LIMIT 1',
            'orderby' => 'create_date',
            'order' => 'desc',
        );

        $latest_newsletter = $this->get_newsletters( $arg, 0, 0 );
        if ( isset( $latest_newsletter[0]['template'] ) && ! empty( $latest_newsletter[0]['template'] ) ) {
            return $latest_newsletter[0]['template'];
        }

        return 'iletter';
    }

    function create_newsletter_for_builder() {
        global $wpdb;

        $template = $this->get_default_builder_template_slug();
        $inserted = $wpdb->insert(
            "{$this->tb_prefix}enewsletter_newsletters",
            array(
                'create_date' => time(),
                'template' => $template,
                'subject' => __( 'Neuer Newsletter', 'email-newsletter' ),
                'from_name' => isset( $this->settings['from_name'] ) ? $this->settings['from_name'] : '',
                'from_email' => isset( $this->settings['from_email'] ) ? $this->settings['from_email'] : '',
                'content' => '',
                'contact_info' => isset( $this->settings['contact_info'] ) ? $this->settings['contact_info'] : '',
                'bounce_email' => isset( $this->settings['bounce_email'] ) ? $this->settings['bounce_email'] : '',
                'sent' => 0,
                'opened' => 0,
                'bounced' => 0,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
        );

        if ( false === $inserted ) {
            return 0;
        }

        $newsletter_id = intval( $wpdb->insert_id );

        if ( ! empty( $this->settings['branding_html'] ) ) {
            $this->update_newsletter_meta( $newsletter_id, 'branding_html', $this->settings['branding_html'] );
        }

        do_action( 'enewsletter_newsletter_saved', $newsletter_id, array( 'template' => $template ), array() );

        return $newsletter_id;
    }

    /**
     *  Tempalate of the Groups list
     **/
    function member_groups_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-groups.php" );
    }

    /**
     *  Tempalate of the Memebers page
     **/
    function members_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-members.php" );
    }

    /**
     * Change group on the Memebers page
     **/
    function change_groups_ajax() {
        $users_group = $this->get_memeber_groups( $_REQUEST['member_id'] );
        if ( ! is_array( $users_group ) )
            $users_group = array();

        $groups = $this->get_groups();
            $content = "<p>".__( 'Gruppe für diesen Benutzer wählen:', 'email-newsletter' ) . "</p>";

            $member_data = $this->get_member( $_REQUEST['member_id'] );
            $subscribed = (!empty($member_data['unsubscribe_code'])) ? 'checked="checked"' : '';

            $content .= '<p><label><strong><input type="checkbox" name="groups_id[]" value="subscribed" ' . $subscribed . ' /> ' .__( 'Abonniert', 'email-newsletter' ). '</strong></label></p>';
            if ( 0 < count( $groups ) ) {
                $content .= "<p>";
                foreach( $groups as $group ){
                    if ( false === array_search ( $group['group_id'], $users_group ) )
                        $checked = '';
                    else
                        $checked = 'checked="checked"';
                    $content .= '<label><input type="checkbox" name="groups_id[]" value="' . $group['group_id'] . '" ' . $checked . ' /> ' . $group['group_name'] . '</label><br />';
                }
                $content .= "</p>";
            }
            else
                $content = "<p>".__( 'Bitte erstelle einige Gruppen.', 'email-newsletter' ) . "</p>";

        die($content);
    }

    /**
     *  Tempalate of the Settings page
     **/
    function settings_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-settings.php" );
    }

    /**
     *  Template Editor page
     **/
    function template_editor_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-template-editor.php" );
    }

    /**
     *  Tempalate of the Settings page
     **/
    function newsletters_subscribe_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-subscribe.php" );
    }

    function email_newsletter_widgets_scripts() {
        wp_register_script( 'email-newsletter-widget-scripts', plugins_url( '/email-newsletter-files/js/widget_script.js', __FILE__ ), array( 'jquery' ), 4 );
        wp_enqueue_script( 'email-newsletter-widget-scripts' );

        $protocol = isset( $_SERVER["HTTPS"] ) ? 'https://' : 'http://'; //This is used to set correct adress if secure protocol is used so ajax calls are working
        $params = array(
            'ajax_url' => admin_url( 'admin-ajax.php', $protocol ),
            'empty_email' => __( 'Bitte Deine Admin E-Mail!', 'email-newsletter' ),
            'saving' => __( 'Speichere...', 'email-newsletter' )
        );

        wp_localize_script( 'email-newsletter-widget-scripts', 'email_newsletter_widget_scripts', $params );
    }

    function subscribe_widget($show_name = false, $show_groups = true, $subscribe_to_groups = array()) {
        global $email_newsletter;

        $current_user = wp_get_current_user();
        $groups = $this->get_groups(1);

        if ( isset($current_user->data->ID) ) {
            $member_id      = $this->get_members_by_wp_user_id( $current_user->data->ID );
            $member_data    = $this->get_member( $member_id );
            $only_public = (isset($this->settings['non_public_group_access']) && $this->settings['non_public_group_access'] == 'nobody') ? 1 : 0;
            $groups = $this->get_groups();

            /*if ( "" != $member_data['unsubscribe_code'] )
                $member_groups = $this->get_memeber_groups( $member_id );*/

            if ( "" != is_array($member_data) || !isset($member_data['unsubscribe_code'] ) )
                $member_groups = $this->get_memeber_groups( $member_id );

            if ( !isset($member_groups) || ! is_array( $member_groups ) )
                $member_groups = array();

            if(!$subscribe_to_groups)
                $show_groups = true;
        }
        else
            $groups = $this->get_groups(1);

        if ( !isset($current_user->data->ID) ) {
            $view = "add_member";
        } else if ( $current_user->data && $subscribe_to_groups && !$show_groups ) {
            if( $member_groups && !array_diff($subscribe_to_groups, $member_groups) )
                $view = "unsubscribe_from_groups";
            else
                $view = "subscribe_to_groups";
        } else if ( isset( $member_data['unsubscribe_code'] ) && "" != $member_data['unsubscribe_code'] && 0 < $current_user->data->ID ) {
            $view = "manage_subscriptions";
        } else if ( $current_user->data && 0 < $current_user->data->ID ) {
            $view = "subscribe";
        } else {
            $view = "";
        }
        $return = '
        <div class="e-newsletter-widget">
            <div id="message" style="color:#000000; display:none; background-color: #FFFFE0;border-color: #E6DB55;margin: 5px 0 15px;-moz-border-radius: 3px 3px 3px 3px;border-style: solid;border-width: 1px;padding: 5px;"></div>

            <form action="" method="post" name="subscribes_form" id="subscribes_form">
                <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />';
        if(is_array($subscribe_to_groups))
            foreach($subscribe_to_groups as $group_id )
                if(is_numeric($group_id))
                    $return .= '<input type="hidden" name="e_newsletter_auto_groups_id[]" value="'.$group_id.'" />';

        if($view != 'add_member')
            $return .= '
                <div id="add_member" class="e-newsletter-widget-screen" style="display:none;">';
        else
            $return .=
                '<div id="add_member" class="e-newsletter-widget-screen">';
        $return .= '
                    <p>
                        <label for="e_newsletter_email">'.__( 'Deine Email:', 'email-newsletter' ).'</label>
                        <input type="text" name="e_newsletter_email" id="e_newsletter_email" value="" />';
        if( isset($show_name) && $show_name )
            $return .= '
                        <br/>

                        <label for="e_newsletter_name">'.__( 'Dein Name:', 'email-newsletter' ).'</label>
                        <input type="text" name="e_newsletter_name" id="e_newsletter_name" />';
        $return .= '
                    </p>';
        if( $show_groups && count($groups) > 0 ) {
            $return .='
                        <h3>'.__( 'Newsletter abonnieren:', 'email-newsletter' ).'</h3>
                        <p>
                            <ul class="subscribe_groups" style="list-style: none outside none;">';
            foreach( ( array ) $groups as $group ) {
                if( ! $group['public'] ) continue;
                    $return .= '
                                    <li>

                                        <input type="checkbox" name="e_newsletter_groups_id[]" value="'.$group['group_id'].'" id="e_newsletter_groups_id_'.$group['group_id'].'" class="e_newsletter_groups_id_'.$group['group_id'].'" />
                                        <label for="e_newsletter_groups_id_'.$group['group_id'].'">'.$group['group_name'].'</label>

                                    </li>';
            }
            $return .= '
                            </ul>
                        </p>';

        }
        $return .='
                    <p>
                        <input type="submit" id="new_subscribe" class="enewletter_widget_submit" value="'.__( 'Newsletter abonnieren', 'email-newsletter' ).'" />
                    </p>

                </div>';



        if($view != 'subscribe_to_groups')
            $return .= '
                <div id="subscribe_to_groups" class="e-newsletter-widget-screen" style="display:none;">';
        else
            $return .='
                <div id="subscribe_to_groups" class="e-newsletter-widget-screen">';

        if( count($groups) > 0 )
            foreach( (array) $subscribe_to_groups as $subscribe_to_group_id )
                $return .= '
                    <input type="hidden" name="e_newsletter_add_groups_id[]" value="'.$subscribe_to_group_id.'"/>';

        $return .= '
                    <p>
                        <input type="submit" id="subscribe_to_groups" class="enewletter_widget_submit" value="'.__( 'Newsletter abonnieren', 'email-newsletter' ).'" />
                    </p>';
        $return .= '
                </div>';

        if($view != 'unsubscribe_from_groups')
            $return .= '
                <div id="unsubscribe_from_groups" class="e-newsletter-widget-screen" style="display:none;">';
        else
            $return .='
                <div id="unsubscribe_from_groups" class="e-newsletter-widget-screen">';

        if( count($groups) > 0 )
            foreach( (array) $subscribe_to_groups as $subscribe_to_group_id )
                $return .= '
                    <input type="hidden" name="e_newsletter_remove_groups_id[]" value="'.$subscribe_to_group_id.'"/>';

        $return .= '
                    <p>
                        <input type="submit" id="unsubscribe_from_groups" class="enewletter_widget_submit" value="'.__( 'Abmelden', 'email-newsletter' ).'" />
                    </p>';
        $return .= '
                </div>';



        if($view != 'manage_subscriptions')
            $return .= '
                <div id="manage_subscriptions" class="e-newsletter-widget-screen" style="display:none;">';
        else
            $return .='
                <div id="manage_subscriptions" class="e-newsletter-widget-screen">';
        $unsubscribe_code = isset( $member_data['unsubscribe_code'] ) ? $member_data['unsubscribe_code'] : '';
        $return .='
                    <input type="hidden" name="unsubscribe_code" id="unsubscribe_code" value="'.$unsubscribe_code.'" />';

        if( $show_groups && count($groups) > 0 ) {
            if( isset($only_public) && $only_public == 1 )
                foreach( (array) $groups as $group )
                    if (!$group['public'] && in_array($group['group_id'], $member_groups) )
                        $return .= '
                        <input type="hidden" name="e_newsletter_groups_id[]" value="'.$group['group_id'].'"/>';

            $return .= '
                        <h3>'.__( 'Newsletter abonnieren:', 'email-newsletter' ).'</h3>
                        <p>
                            <ul class="subscribe_groups" style="list-style: none outside none;">';
            foreach( (array) $groups as $group ){
                if ( isset($member_groups) && in_array($group['group_id'], $member_groups) )
                    $checked = 'checked="checked"';
                else
                    $checked = '';
                if(!isset($only_public) || ($only_public && $group['public']) || !$only_public)
                    $return .= '
                                    <li>
                                        <input type="checkbox" name="e_newsletter_groups_id[]" value="'.$group['group_id'].'" '.$checked.' id="e_newsletter_groups_id_'.$group['group_id'].'" class="e_newsletter_groups_id_'.$group['group_id'].'" />
                                        <label for="e_newsletter_groups_id_'.$group['group_id'].'">'.$group['group_name'].'</label>
                                    </li>';
            }
            $return .= '
                            </ul>
                        </p>
                    <p>
                        <input type="submit" id="save_subscribes" class="enewletter_widget_submit" value="'.__( 'Abonnements speichern', 'email-newsletter' ).'" />
                    </p>';
        }
        $return .= '
                <p>
                    <a href="#" id="unsubscribe" class="enewletter_widget_submit" >'.__( 'Abmelden', 'email-newsletter' ).'</a>
                </p>';
        $return .= '
                </div>';
        if($view != 'subscribe')
            $return .= '
                <div id="subscribe" class="e-newsletter-widget-screen" style="display:none;">';
        else
            $return .= '
                <div id="subscribe" class="e-newsletter-widget-screen">';
        $return .= '
                    <input type="submit" id="subscribe" class="enewletter_widget_submit" value="'.__( 'Newsletter abonnieren', 'email-newsletter' ).'" />
                </div>
            </form>
        </div><!--//e-newsletter-widget  -->';

        return $return;
    }

    function subscribe_shortcode( $atts ) {
        extract( shortcode_atts( array(
            'show_name' => false,
            'show_groups' => true,
            'subscribe_to_groups' => array(),
        ), $atts ) );

        if(!empty($subscribe_to_groups))
            $subscribe_to_groups = explode(',',$subscribe_to_groups);
        if($show_groups == 'false')
            $show_groups = false;

        $subscribe = $this->subscribe_widget($show_name, $show_groups, $subscribe_to_groups);

        return $subscribe;
    }

    function newsletter_bool( $value, $default = true ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_numeric( $value ) ) {
            return (int) $value === 1;
        }

        $value = strtolower( trim( (string) $value ) );
        if ( $value === '' ) {
            return (bool) $default;
        }

        return in_array( $value, array( '1', 'yes', 'true', 'on' ), true );
    }

    function parse_newsletter_ids( $ids_string ) {
        $ids = array();
        foreach ( explode( ',', (string) $ids_string ) as $raw_id ) {
            $id = absint( trim( $raw_id ) );
            if ( $id > 0 ) {
                $ids[] = $id;
            }
        }

        return array_values( array_unique( $ids ) );
    }

    function get_marketpress_product_post_type() {
        if ( class_exists( 'MP_Product' ) && method_exists( 'MP_Product', 'get_post_type' ) ) {
            return MP_Product::get_post_type();
        }

        return 'product';
    }

    function newsletter_track_link( $url, $atts, $type, $id ) {
        if ( ! $this->newsletter_bool( isset( $atts['track'] ) ? $atts['track'] : 1, true ) ) {
            return $url;
        }

        $params = array(
            'utm_source' => isset( $atts['utm_source'] ) ? $atts['utm_source'] : 'newsletter',
            'utm_medium' => isset( $atts['utm_medium'] ) ? $atts['utm_medium'] : 'email',
            'utm_campaign' => isset( $atts['utm_campaign'] ) ? $atts['utm_campaign'] : 'ps-enewsletter',
            'utm_content' => isset( $atts['utm_content'] ) && ! empty( $atts['utm_content'] ) ? $atts['utm_content'] : $type . '-' . absint( $id ),
        );

        return add_query_arg( $params, $url );
    }

    function get_newsletter_product_price_html( $product_id, $show_old_price = true ) {
        if ( ! class_exists( 'MP_Product' ) ) {
            return '';
        }

        $product = new MP_Product( $product_id );
        if ( ! $product->exists() ) {
            return '';
        }

        $price = $product->get_price();
        $currency = function_exists( 'mp_get_setting' ) ? mp_get_setting( 'currency' ) : '';

        if ( $product->on_sale() && isset( $price['sale']['amount'] ) ) {
            $sale = function_exists( 'mp_format_currency' ) ? mp_format_currency( $currency, $price['sale']['amount'] ) : $price['sale']['amount'];
            $html = '<span style="font-weight:700;color:#111111;">' . esc_html( $sale ) . '</span>';

            if ( $show_old_price && isset( $price['regular'] ) ) {
                $regular = function_exists( 'mp_format_currency' ) ? mp_format_currency( $currency, $price['regular'] ) : $price['regular'];
                $html .= ' <span style="color:#777777;text-decoration:line-through;">' . esc_html( $regular ) . '</span>';
            }

            return $html;
        }

        if ( function_exists( 'mp_product_price' ) ) {
            return wp_kses_post( mp_product_price( false, $product_id, '' ) );
        }

        return '';
    }

    function render_newsletter_product_card( $product_id, $atts = array() ) {
        $title = get_the_title( $product_id );
        $url = get_permalink( $product_id );
        if ( empty( $title ) || empty( $url ) ) {
            return '';
        }

        $url = $this->newsletter_track_link( $url, $atts, 'product', $product_id );

        $show_image = $this->newsletter_bool( isset( $atts['show_image'] ) ? $atts['show_image'] : 1 );
        $show_price = $this->newsletter_bool( isset( $atts['show_price'] ) ? $atts['show_price'] : 1 );
        $show_button = $this->newsletter_bool( isset( $atts['show_button'] ) ? $atts['show_button'] : 1 );
        $show_badge = $this->newsletter_bool( isset( $atts['show_badge'] ) ? $atts['show_badge'] : 0, false );
        $show_old_price = $this->newsletter_bool( isset( $atts['show_old_price'] ) ? $atts['show_old_price'] : 1 );
        $layout = isset( $atts['layout'] ) ? strtolower( trim( (string) $atts['layout'] ) ) : 'list';
        $is_grid_layout = ( 'grid' === $layout );

        $badge_html = '';
        if ( $show_badge && class_exists( 'MP_Product' ) ) {
            $mp_product = new MP_Product( $product_id );
            if ( $mp_product->exists() && $mp_product->on_sale() ) {
                $badge_text = ! empty( $atts['badge_text'] ) ? $atts['badge_text'] : __( 'Sale', 'email-newsletter' );
                $badge_html = '<p style="margin:0 0 8px 0;"><span style="display:inline-block;padding:2px 8px;background:#d63638;color:#ffffff;border-radius:2px;font-size:11px;line-height:1.3;font-weight:700;">' . esc_html( $badge_text ) . '</span></p>';
            }
        }

        $image_html = '';
        if ( $show_image ) {
            $image_url = get_the_post_thumbnail_url( $product_id, 'medium' );
            if ( $image_url ) {
                $image_style = $is_grid_layout
                    ? 'display:block;max-width:260px;width:100%;height:auto;border:0;'
                    : 'display:block;width:100%;height:auto;border:0;';
                $image_html = '<p style="margin:0 0 10px 0;"><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" style="' . esc_attr( $image_style ) . '" /></a></p>';
            }
        }

        $price_html = '';
        if ( $show_price ) {
            $raw = $this->get_newsletter_product_price_html( $product_id, $show_old_price );
            if ( ! empty( $raw ) ) {
                $price_html = '<p style="margin:0 0 10px 0;font-size:14px;line-height:1.4;">' . $raw . '</p>';
            }
        }

        $button_html = '';
        if ( $show_button ) {
            $button_label = ! empty( $atts['button_text'] ) ? $atts['button_text'] : __( 'Zum Produkt', 'email-newsletter' );
            $button_html = '<p style="margin:0;"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:8px 14px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:3px;font-size:13px;line-height:1.2;">' . esc_html( $button_label ) . '</a></p>';
        }

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;"><tr><td style="padding:12px;border:1px solid #e5e5e5;">'
            . $badge_html
            . $image_html
            . '<h3 style="margin:0 0 8px 0;font-size:18px;line-height:1.3;"><a href="' . esc_url( $url ) . '" style="color:#111111;text-decoration:none;">' . esc_html( $title ) . '</a></h3>'
            . $price_html
            . $button_html
            . '</td></tr></table>';
    }

    function render_newsletter_cards_grid( $cards, $columns = 1 ) {
        $columns = absint( $columns );
        if ( $columns !== 2 ) {
            $columns = 1;
        }

        if ( empty( $cards ) ) {
            return '';
        }

        if ( $columns === 1 ) {
            return implode( '<div style="height:12px;line-height:12px;">&nbsp;</div>', $cards );
        }

        $out = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">';
        $chunks = array_chunk( $cards, 2 );
        foreach ( $chunks as $row ) {
            $left = isset( $row[0] ) ? $row[0] : '';
            $right = isset( $row[1] ) ? $row[1] : '';
            $out .= '<tr>'
                . '<td width="50%" valign="top" style="padding:0 8px 12px 0;">' . $left . '</td>'
                . '<td width="50%" valign="top" style="padding:0 0 12px 8px;">' . $right . '</td>'
                . '</tr>';
        }
        $out .= '</table>';

        return $out;
    }

    function enews_product_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'show_image' => 1,
                'show_price' => 1,
                'show_old_price' => 1,
                'show_button' => 1,
                'button_text' => __( 'Zum Produkt', 'email-newsletter' ),
                'show_badge' => 0,
                'badge_text' => __( 'Sale', 'email-newsletter' ),
                'track' => 1,
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'ps-enewsletter',
                'utm_content' => '',
            ),
            $atts,
            'enews_product'
        );

        $product_id = absint( $atts['id'] );
        if ( ! $product_id || get_post_type( $product_id ) !== $this->get_marketpress_product_post_type() ) {
            return '';
        }

        return $this->render_newsletter_product_card( $product_id, $atts );
    }

    function enews_products_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'ids' => '',
                'show_image' => 1,
                'show_price' => 1,
                'show_old_price' => 1,
                'show_button' => 1,
                'button_text' => __( 'Zum Produkt', 'email-newsletter' ),
                'show_badge' => 0,
                'badge_text' => __( 'Sale', 'email-newsletter' ),
                'columns' => 1,
                'layout' => 'list',
                'track' => 1,
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'ps-enewsletter',
                'utm_content' => '',
            ),
            $atts,
            'enews_products'
        );

        $ids = $this->parse_newsletter_ids( $atts['ids'] );
        if ( empty( $ids ) ) {
            return '';
        }

        $post_type = $this->get_marketpress_product_post_type();
        $cards = array();
        foreach ( $ids as $product_id ) {
            if ( get_post_type( $product_id ) !== $post_type ) {
                continue;
            }
            $card = $this->render_newsletter_product_card( $product_id, $atts );
            if ( ! empty( $card ) ) {
                $cards[] = $card;
            }
        }

        $columns = ( isset( $atts['layout'] ) && $atts['layout'] === 'grid' ) ? 2 : absint( $atts['columns'] );
        return $this->render_newsletter_cards_grid( $cards, $columns );
    }

    function render_newsletter_post_card( $post_id, $atts = array() ) {
        if ( get_post_type( $post_id ) !== 'post' ) {
            return '';
        }

        $title = get_the_title( $post_id );
        $url = get_permalink( $post_id );
        if ( empty( $title ) || empty( $url ) ) {
            return '';
        }

        $url = $this->newsletter_track_link( $url, $atts, 'post', $post_id );
        $show_image = $this->newsletter_bool( isset( $atts['show_image'] ) ? $atts['show_image'] : 1 );
        $show_excerpt = $this->newsletter_bool( isset( $atts['show_excerpt'] ) ? $atts['show_excerpt'] : 1 );
        $show_button = $this->newsletter_bool( isset( $atts['show_button'] ) ? $atts['show_button'] : 1 );
        $excerpt_words = isset( $atts['excerpt_words'] ) ? max( 8, absint( $atts['excerpt_words'] ) ) : 24;

        $image_html = '';
        if ( $show_image ) {
            $image_url = get_the_post_thumbnail_url( $post_id, 'medium' );
            if ( $image_url ) {
                $image_html = '<p style="margin:0 0 10px 0;"><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" style="display:block;max-width:260px;width:100%;height:auto;border:0;" /></a></p>';
            }
        }

        $excerpt_html = '';
        if ( $show_excerpt ) {
            $raw_excerpt = get_the_excerpt( $post_id );
            if ( empty( $raw_excerpt ) ) {
                $raw_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
            }
            $excerpt = wp_trim_words( $raw_excerpt, $excerpt_words, ' ...' );
            if ( ! empty( $excerpt ) ) {
                $excerpt_html = '<p style="margin:0 0 10px 0;font-size:14px;line-height:1.45;color:#333333;">' . esc_html( $excerpt ) . '</p>';
            }
        }

        $button_html = '';
        if ( $show_button ) {
            $button_text = ! empty( $atts['button_text'] ) ? $atts['button_text'] : __( 'Weiterlesen', 'email-newsletter' );
            $button_html = '<p style="margin:0;"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:8px 14px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:3px;font-size:13px;line-height:1.2;">' . esc_html( $button_text ) . '</a></p>';
        }

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;"><tr><td style="padding:12px;border:1px solid #e5e5e5;">'
            . $image_html
            . '<h3 style="margin:0 0 8px 0;font-size:18px;line-height:1.3;"><a href="' . esc_url( $url ) . '" style="color:#111111;text-decoration:none;">' . esc_html( $title ) . '</a></h3>'
            . $excerpt_html
            . $button_html
            . '</td></tr></table>';
    }

    function enews_post_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'show_image' => 1,
                'show_excerpt' => 1,
                'excerpt_words' => 24,
                'show_button' => 1,
                'button_text' => __( 'Weiterlesen', 'email-newsletter' ),
                'track' => 1,
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'ps-enewsletter',
                'utm_content' => '',
            ),
            $atts,
            'enews_post'
        );

        $post_id = absint( $atts['id'] );
        if ( ! $post_id ) {
            return '';
        }

        return $this->render_newsletter_post_card( $post_id, $atts );
    }

    function enews_posts_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'ids' => '',
                'count' => 4,
                'layout' => 'grid',
                'columns' => 2,
                'show_image' => 1,
                'show_excerpt' => 1,
                'excerpt_words' => 24,
                'show_button' => 1,
                'button_text' => __( 'Weiterlesen', 'email-newsletter' ),
                'track' => 1,
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'ps-enewsletter',
                'utm_content' => '',
            ),
            $atts,
            'enews_posts'
        );

        $ids = $this->parse_newsletter_ids( $atts['ids'] );
        if ( empty( $ids ) ) {
            $count = max( 1, min( 12, absint( $atts['count'] ) ) );
            $ids = get_posts(
                array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => $count,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'fields' => 'ids',
                )
            );
        }

        $cards = array();
        foreach ( $ids as $post_id ) {
            $card = $this->render_newsletter_post_card( (int) $post_id, $atts );
            if ( ! empty( $card ) ) {
                $cards[] = $card;
            }
        }

        $layout = strtolower( trim( $atts['layout'] ) );
        if ( $layout === 'slider' ) {
            // Slider is not supported by email clients; use a static two-column grid fallback.
            return $this->render_newsletter_cards_grid( $cards, 2 );
        }
        if ( $layout === 'list' ) {
            return $this->render_newsletter_cards_grid( $cards, 1 );
        }

        $columns = absint( $atts['columns'] );
        if ( $columns < 1 ) {
            $columns = 2;
        }

        return $this->render_newsletter_cards_grid( $cards, $columns > 1 ? 2 : 1 );
    }

    function enews_post_links_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'ids' => '',
                'count' => 5,
                'show_image' => 1,
                'show_excerpt' => 1,
                'excerpt_words' => 16,
                'track' => 1,
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'ps-enewsletter',
                'utm_content' => '',
            ),
            $atts,
            'enews_post_links'
        );

        $ids = $this->parse_newsletter_ids( $atts['ids'] );
        if ( empty( $ids ) ) {
            $count = max( 1, min( 12, absint( $atts['count'] ) ) );
            $ids = get_posts(
                array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => $count,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'fields' => 'ids',
                )
            );
        }

        if ( empty( $ids ) ) {
            return '';
        }

        $items = '';
        foreach ( $ids as $post_id ) {
            if ( get_post_type( $post_id ) !== 'post' ) {
                continue;
            }

            $title = get_the_title( $post_id );
            $url = get_permalink( $post_id );
            if ( empty( $title ) || empty( $url ) ) {
                continue;
            }

            $url = $this->newsletter_track_link( $url, $atts, 'post', $post_id );
            $preview = '';
            if ( $this->newsletter_bool( $atts['show_excerpt'], true ) ) {
                $raw_excerpt = get_the_excerpt( $post_id );
                if ( empty( $raw_excerpt ) ) {
                    $raw_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
                }
                $preview = '<div style="margin-top:4px;font-size:13px;line-height:1.4;color:#555555;">' . esc_html( wp_trim_words( $raw_excerpt, absint( $atts['excerpt_words'] ), ' ...' ) ) . '</div>';
            }

            $thumb = '';
            if ( $this->newsletter_bool( $atts['show_image'], true ) ) {
                $img = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
                if ( $img ) {
                    $thumb = '<img src="' . esc_url( $img ) . '" alt="" style="width:56px;height:56px;display:block;border:0;object-fit:cover;" />';
                }
            }

            $items .= '<tr>'
                . '<td valign="top" style="padding:0 10px 12px 0;width:56px;">' . $thumb . '</td>'
                . '<td valign="top" style="padding:0 0 12px 0;"><a href="' . esc_url( $url ) . '" style="font-size:15px;line-height:1.35;color:#111111;text-decoration:none;font-weight:600;">' . esc_html( $title ) . '</a>' . $preview . '</td>'
                . '</tr>';
        }

        if ( empty( $items ) ) {
            return '';
        }

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">' . $items . '</table>';
    }

    function unsubscribe_message_shortcode( $atts ) {
        if(isset($_REQUEST['enewsletter_unsubscribed']) && isset($_REQUEST['message']))
            return $_REQUEST['message'];
        else
            return '';
    }

    function subscribe_message_shortcode( $atts ) {
        if(isset($_REQUEST['enewsletter_subscribed']) && isset($_REQUEST['message']))
            return $_REQUEST['message'];
        else
            return '';
    }


    function register_plugin_exporter($exporters) {
		$exporters['e-newsletter'] = array(
			'exporter_friendly_name' => 'E-Newsletter',
			'callback' => array( $this, 'plugin_exporter' ),
		);
		return $exporters;
    }
    function plugin_exporter($email) {
        $member = $this->get_member_by_email($email);
        if($member) {
            $member_groups = array();
            $groups_id = $this->get_memeber_groups( $member['member_id'] );
            if($groups_id) {
                foreach ( $groups_id as $group_id) {
                    $group = $this->get_group_by_id( $group_id );
                    $member_groups[] = $group['group_name'];
                }
            }
            $export_items = array();
            $export_items[] = array(
                'group_id' => 'enewsletter',
                'group_label' => 'E-Newsletter Subscription',
                'item_id' => 'user',
                'data' => array(
                    array(
                        'name' => __( 'First Name', 'appointments' ),
                        'value' => $member['member_fname'],
                    ),
                    array(
                        'name' => __( 'Last Name', 'appointments' ),
                        'value' => $member['member_lname'],
                    ),
                    array(
                        'name' => __( 'Email', 'appointments' ),
                        'value' => $member['member_email'],
                    ),
                    array(
                        'name' => __( 'Join Date', 'appointments' ),
                        'value' => get_date_from_gmt(date('d.m.Y H:i:s', $member['join_date'])),
                    ),
                    array(
                        'name' => __( 'Groups', 'appointments' ),
                        'value' => implode(', ', $member_groups),
                    ),
                ),
            );

            return array(
                'data' => $export_items,
                'done' => true,
            );
        }
        else {
            return;
        }
    }

    function register_plugin_eraser($erasers) {
		$erasers['e-newsletter'] = array(
			'eraser_friendly_name' => 'E-Newsletter',
			'callback' => array( $this, 'plugin_eraser' ),
		);
		return $erasers;
    }
    function plugin_eraser($email) {
        $member = $this->get_member_by_email($email);
        if($member) {
            $result = $this->delete_members(array($member['member_id']));

            return array(
                'items_removed' => 1,
                'items_retained' => 0,
                'messages' => array(__('PS-eNewsletter Abonnentendaten entfernt.', 'email-newsletter')),
                'done' => true,
            );
        }
        else {
            return;
        }
    }


    /**
     * Deprecated
     **/
    function confirm_subscibe_ajax() {
        wp_redirect(add_query_arg( array('subscribe_page' => '1', 'subscribe_code' => $_REQUEST['hash'], 'subscribe_member_id' => $_REQUEST['member_id']), home_url() ));
        die();
    }
    function unsubscribe_ajax() {
        $this->unsubscribe_by_code( $_REQUEST['unsubscribe_code'] );
        die();
    }
}

global $email_newsletter;
$email_newsletter = new Email_Newsletter();

// Load CRM Integration API
require_once( plugin_dir_path( __FILE__ ) . 'email-newsletter-files/crm-integration-api.php' );





