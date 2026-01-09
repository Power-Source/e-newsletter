<?php

defined('ABSPATH') || exit;

/**
 * Base class for add-ons and extensions. Provides basic functionality for options management,
 * initialization, and integration with the main newsletter plugin.
 * 
 * Add-ons that extend this class get automatic options handling with language support,
 * logging capabilities, and hooks for initialization and deactivation.
 */
class NewsletterAddon extends NewsletterModule {

    /**
     * @var string The add-on name (used for options prefix)
     */
    var $name;

    /**
     * @var string The add-on version
     */
    var $version;

    /**
     * @var array The add-on options
     */
    var $options = [];

    /**
     * Constructor for the add-on.
     * 
     * @param string $name The add-on name (will be used for options prefix)
     * @param string $version The add-on version
     * @param string $dir The add-on directory (optional)
     */
    function __construct($name, $version = '0.0.0', $dir = '') {
        $this->name = $name;
        $this->version = $version;
        parent::__construct($name);
    }

    /**
     * Get the add-on options.
     * 
     * @param string $set The options set name (defaults to add-on name)
     * @param string $language The language code (optional)
     * @return array
     */
    function get_options($set = '', $language = null) {
        if (empty($set)) {
            $set = $this->name;
        }
        return parent::get_options($set, $language);
    }

    /**
     * Get a single option value.
     * 
     * @param string $key The option key
     * @param string $sub The option subset (defaults to add-on name)
     * @param string $language The language code (optional)
     * @return mixed
     */
    function get_option($key, $sub = '', $language = null) {
        if (empty($sub)) {
            $sub = $this->name;
        }
        return parent::get_option($key, $sub, $language);
    }

    /**
     * Save options for the add-on.
     * 
     * @param array $options The options to save
     * @param string $set The options set name (defaults to add-on name)
     * @param string $language The language code (optional)
     */
    function save_options($options, $set = '', $language = null) {
        if (empty($set)) {
            $set = $this->name;
        }
        return $this->save_option_array($options, $this->get_prefix($set, $language));
    }

    /**
     * Setup options with default values for the add-on.
     * 
     * @param string $set The options set name (defaults to add-on name)
     * @param array $defaults The default options
     */
    function setup_options($set = '', $defaults = []) {
        if (empty($set)) {
            $set = $this->name;
        }
        $this->options = $this->get_options($set);
        if (!empty($defaults)) {
            $this->merge_defaults($defaults, $set);
        }
    }

    /**
     * Merge default options.
     * 
     * @param array $defaults The default values to merge
     * @param string $set The options set name (defaults to add-on name)
     */
    function merge_defaults($defaults, $set = '') {
        if (empty($set)) {
            $set = $this->name;
        }
        
        $options = $this->get_options($set);
        $merged = array_merge($defaults, $options);
        
        if (count(array_diff_assoc($merged, $options)) > 0) {
            $this->save_options($merged, $set);
            $this->options = $merged;
        }
    }

    /**
     * Called when the add-on is first installed.
     * Override this method to set up initial data.
     * 
     * @param bool $first_install Whether this is the first installation
     */
    function upgrade($first_install = false) {
        // Override in subclasses
    }

    /**
     * Initialize the add-on.
     * Called during WordPress initialization.
     * Override this method to register hooks and perform other initialization tasks.
     */
    function init() {
        // Override in subclasses
    }

    /**
     * Called when the add-on is deactivated.
     * Override this method to clean up scheduled events and other resources.
     */
    function deactivate() {
        // Override in subclasses
    }

    /**
     * Perform a weekly check (for license validation, updates, etc.).
     * Override this method to perform periodic maintenance tasks.
     */
    function weekly_check() {
        // Override in subclasses
    }

    /**
     * Get the option key prefix for the add-on.
     * 
     * @param string $set The options set name
     * @param string $language The language code (optional)
     * @return string
     */
    function get_prefix($set = '', $language = '') {
        if (empty($set)) {
            $set = $this->name;
        }
        $prefix = 'newsletter_' . $set;
        if (!empty($language)) {
            $prefix .= '_' . $language;
        }
        return $prefix;
    }
}
