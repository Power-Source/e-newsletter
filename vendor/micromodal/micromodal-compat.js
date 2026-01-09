/**
 * jQuery Modal Kompatibilitäts-Wrapper für Micromodal.js
 * Ersetzt jQuery Modal durch moderne Vanilla-Lösung
 */

(function() {
    'use strict';
    
    // Initialisiere Micromodal
    if (typeof MicroModal !== 'undefined') {
        MicroModal.init({
            disableScroll: true,
            disableFocus: false,
            awaitOpenAnimation: true,
            awaitCloseAnimation: true,
            debugMode: false
        });
    }
    
    // jQuery Plugin Wrapper
    if (typeof jQuery !== 'undefined') {
        (function($) {
            // $.modal Namespace für Kompatibilität
            $.modal = {
                defaults: {
                    fadeDuration: 250,
                    closeExisting: true,
                    escapeClose: true,
                    clickClose: true,
                    closeText: 'Close',
                    showClose: true
                },
                
                close: function(modalId) {
                    // Schließe aktuelles Modal oder spezifisches Modal
                    if (modalId && typeof modalId === 'string') {
                        MicroModal.close(modalId);
                    } else {
                        // Schließe alle offenen Modals
                        document.querySelectorAll('.modal.is-open').forEach(function(modal) {
                            MicroModal.close(modal.id);
                        });
                    }
                },
                
                isActive: function() {
                    return document.querySelector('.modal.is-open') !== null;
                }
            };
            
            // jQuery Plugin: $(element).modal()
            $.fn.modal = function(options) {
                return this.each(function() {
                    var $this = $(this);
                    var modalId = $this.attr('id');
                    
                    if (!modalId) {
                        // Generiere ID falls keine vorhanden
                        modalId = 'modal-' + Math.random().toString(36).substr(2, 9);
                        $this.attr('id', modalId);
                    }
                    
                    // Stelle sicher dass Modal die richtigen Klassen hat
                    if (!$this.hasClass('modal')) {
                        $this.addClass('modal micromodal-slide');
                        
                        // Wrapping-Struktur für Micromodal
                        if (!$this.find('.modal__overlay').length) {
                            var content = $this.html();
                            $this.html(
                                '<div class="modal__overlay" tabindex="-1">' +
                                    '<div class="modal__container" role="dialog" aria-modal="true">' +
                                        '<button class="modal__close" aria-label="Close modal" data-micromodal-close>&times;</button>' +
                                        '<div class="modal__content">' + content + '</div>' +
                                    '</div>' +
                                '</div>'
                            );
                        }
                    }
                    
                    // Öffne Modal
                    MicroModal.show(modalId, options);
                });
            };
            
            // Event-Handler für rel="modal:open" Links
            $(document).on('click', 'a[rel~="modal:open"]', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                
                if (href && href.charAt(0) === '#') {
                    var $target = $(href);
                    if ($target.length) {
                        $target.modal();
                    }
                }
            });
            
            // Event-Handler für rel="modal:close" Links
            $(document).on('click', 'a[rel~="modal:close"], [data-micromodal-close]', function(e) {
                e.preventDefault();
                $.modal.close();
            });
            
        })(jQuery);
    }
})();
