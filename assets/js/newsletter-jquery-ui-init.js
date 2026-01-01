/**
 * Newsletter jQuery UI Initialisierung
 * 
 * Stellt sicher, dass jQuery UI korrekt geladen und initialisiert wird
 */

(function($) {
    'use strict';
    
    // Warte bis jQuery UI geladen ist
    $(document).ready(function() {
        
        // Prüfe ob jQuery UI verfügbar ist
        if (typeof $.ui === 'undefined') {
            console.error('Newsletter Plugin: jQuery UI ist nicht geladen!');
            return;
        }
        
        if (typeof $.fn.sortable === 'undefined') {
            console.error('Newsletter Plugin: jQuery UI Sortable ist nicht verfügbar!');
            return;
        }
        
        if (typeof $.fn.draggable === 'undefined') {
            console.error('Newsletter Plugin: jQuery UI Draggable ist nicht verfügbar!');
            return;
        }
        
        console.log('Newsletter Plugin: jQuery UI erfolgreich geladen', {
            version: $.ui ? $.ui.version : 'unbekannt',
            sortable: typeof $.fn.sortable !== 'undefined',
            draggable: typeof $.fn.draggable !== 'undefined'
        });
        
        // Kleine Verzögerung für andere Scripts
        setTimeout(function() {
            // Trigger Event für andere Scripts die jQuery UI benötigen
            $(document).trigger('newsletter-jquery-ui-ready');
        }, 100);
    });
    
})(jQuery);
