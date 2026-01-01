/**
 * Newsletter Modal Initialisierung
 * 
 * Stellt sicher, dass jQuery Modal korrekt funktioniert
 */

(function($) {
    'use strict';
    
    function initializeModals() {
        console.log('Newsletter Plugin: Initialisiere Modals...');
        
        // Prüfe ob jQuery Modal verfügbar ist
        if (typeof $.modal === 'undefined') {
            console.error('Newsletter Plugin: jQuery Modal ist nicht geladen!');
            return;
        }
        
        // Standard Modal-Einstellungen
        $.modal.defaults = {
            escapeClose: true,
            clickClose: true,
            closeText: '✕',
            showClose: true,
            fadeDuration: 200,
            fadeDelay: 0.50,
            blockerClass: 'jquery-modal',
            modalClass: 'modal'
        };
        
        // Force Modal Event Handler neu setzen
        $(document).off('click.modal', 'a[rel~="modal:open"]');
        $(document).on('click.modal', 'a[rel~="modal:open"]', function(e) {
            e.preventDefault();
            console.log('Modal Link geklickt:', $(this).attr('href'));
            $(this).modal();
        });
        
        // Test alle Modal-Links
        $('[rel="modal:open"]').each(function() {
            const $this = $(this);
            const href = $this.attr('href');
            console.log('Modal Link gefunden:', href);
            
            // Prüfe ob Target-Element existiert
            if (href && href.startsWith('#')) {
                const target = $(href);
                if (target.length === 0) {
                    console.error('Modal Target nicht gefunden:', href);
                } else {
                    console.log('Modal Target OK:', href, target);
                }
            }
        });
        
        console.log('Newsletter Plugin: Modals erfolgreich initialisiert');
    }
    
    // Warte auf jQuery UI und dann initialisiere Modals
    $(document).on('newsletter-jquery-ui-ready', function() {
        initializeModals();
    });
    
    // Fallback falls newsletter-jquery-ui-ready nicht gefeuert wird
    $(document).ready(function() {
        setTimeout(function() {
            if (typeof $.modal !== 'undefined') {
                console.log('Newsletter Plugin: Modal Fallback-Initialisierung');
                initializeModals();
            }
        }, 1000);
    });
    
})(jQuery);
