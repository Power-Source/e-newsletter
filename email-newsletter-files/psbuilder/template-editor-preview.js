/**
 * E-Newsletter Template Editor - Live Preview
 * Handles live preview updates and content typography CSS generation
 */

(function($) {
    'use strict';
    
    // Global variable for content typography CSS
    window.enewsletterContentTypographyCss = '';
    
    /**
     * Generate Content Typography CSS
     */
    window.generateContentTypographyCss = function() {
        var css = '';
        var fontFamily = $('#content-font-family').val();
        var h1Size = $('#content-h1-size').val() || '28px';
        var h1Color = $('#content-h1-color').val() || '#333333';
        var h2Size = $('#content-h2-size').val() || '24px';
        var h2Color = $('#content-h2-color').val() || '#333333';
        var h3Size = $('#content-h3-size').val() || '20px';
        var h3Color = $('#content-h3-color').val() || '#333333';
        var pSize = $('#content-p-size').val() || '14px';
        var pLineHeight = $('#content-p-line-height').val() || '1.6';
        var linkDecoration = $('#content-link-decoration').val() || 'underline';
        var blockquoteColor = $('#content-blockquote-color').val() || '#666666';
        var blockquoteBorder = $('#content-blockquote-border').val() || '3px solid #cccccc';
        var containerBorder = $('#content-container-border').val() || 'none';
        var containerPadding = $('#content-container-padding').val() || '20px';
        
        if (fontFamily) {
            css += 'body, p, div, span { font-family: ' + fontFamily + '; }\n';
            css += 'h1 { font-size: ' + h1Size + '; color: ' + h1Color + '; }\n';
            css += 'h2 { font-size: ' + h2Size + '; color: ' + h2Color + '; }\n';
            css += 'h3 { font-size: ' + h3Size + '; color: ' + h3Color + '; }\n';
            css += 'p { font-size: ' + pSize + '; line-height: ' + pLineHeight + '; }\n';
            css += 'a { text-decoration: ' + linkDecoration + '; }\n';
            css += 'blockquote { color: ' + blockquoteColor + '; border-left: ' + blockquoteBorder + '; padding-left: 15px; margin: 15px 0; font-style: italic; }\n';
            css += '.email-content-container { border: ' + containerBorder + '; padding: ' + containerPadding + '; }\n';
        }
        
        window.enewsletterContentTypographyCss = css;
        return css;
    };
    
    /**
     * Update live preview iframe
     */
    window.updatePreview = function() {
        if (typeof window.htmlEditor === 'undefined' || typeof window.cssEditor === 'undefined') {
            return;
        }
        
        var htmlContent = window.htmlEditor.getValue();
        var styleCss = window.cssEditor.getValue();
        var builderCss = '';
        var templateBase = window.enewsletterTemplateUrl || '';
        var headerImagePath = window.builderSettings.header_image || '';
        var bgImagePath = window.builderSettings.bg_image || '';
        var resolvedHeader = '';
        var resolvedBg = '';
        
        if (headerImagePath) {
            resolvedHeader = /^https?:\/\//i.test(headerImagePath) ? headerImagePath : templateBase + headerImagePath.replace(/^\//, '');
        }
        if (bgImagePath) {
            resolvedBg = /^https?:\/\//i.test(bgImagePath) ? bgImagePath : templateBase + bgImagePath.replace(/^\//, '');
        }
        
        // Apply builder settings to live CSS
        builderCss += 'body { background-color: ' + (window.builderSettings.bg_color || '#ffffff') + ';';
        if (resolvedBg) {
            builderCss += ' background-image: url("' + resolvedBg + '"); background-size: cover; background-repeat: no-repeat;';
        }
        builderCss += ' color: ' + (window.builderSettings.body_color || '#333333') + '; }';
        builderCss += ' a { color: ' + (window.builderSettings.link_color || '#0073aa') + '; }';
        builderCss += ' h1, h2, h3, h4, h5, h6 { color: ' + (window.builderSettings.title_color || '#000000') + '; }';
        builderCss += ' .alt, .alternate, .alt-row { background-color: ' + (window.builderSettings.alternative_color || '#666666') + '; }';
        
        // Generate content typography CSS
        var contentTypographyCss = generateContentTypographyCss();
        
        // Sample data for placeholder replacement
        var sampleData = {
            'CONTENT': window.loremIpsum || '',
            'EMAIL_TITLE': window.builderSettings.email_title || 'Beispiel Newsletter-Titel',
            'EMAIL_BODY': window.loremIpsum || '',
            'UNSUBSCRIBE_LINK': '<a href="#unsubscribe">Abmelden</a>',
            'UNSUBSCRIBE_URL': '#unsubscribe',
            'VIEW_LINK': '<a href="#view">Im Browser anzeigen</a>',
            'VIEW_LINK_TEXT': 'Im Browser anzeigen',
            'FROM_NAME': window.enewsletterSiteData.name || '',
            'FROM_EMAIL': window.enewsletterSiteData.email || '',
            'FROM_EMAI': window.enewsletterSiteData.email || '',
            'BRANDING_HTML': window.generateBrandingHTML ? window.generateBrandingHTML() : '',
            'CONTACT_INFO': window.generateBrandingHTML ? window.generateBrandingHTML() : '',
            'HEADER_IMAGE': headerImagePath ? '<img src="' + resolvedHeader + '" alt="Header" style="max-width:100%; height:auto; display:block;">' : '',
            'EMAIL_SUBJECT': window.builderSettings.email_title || 'Beispiel Betreff',
            'DATE': new Date().toLocaleDateString()
        };
        
        // Replace all placeholders (both {PLACEHOLDER} and {{PLACEHOLDER}} format)
        for (var key in sampleData) {
            var value = sampleData[key];
            htmlContent = htmlContent.replace(new RegExp('\\{\\{' + key + '\\}\\}', 'g'), value);
            htmlContent = htmlContent.replace(new RegExp('\\{' + key + '\\}', 'g'), value);
        }
        
        // Build preview HTML
        var previewHtml = '<!DOCTYPE html>\n' +
            '<html>\n' +
            '<head>\n' +
            '    <meta charset="UTF-8">\n' +
            '    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n' +
            '    <style>\n' +
            '        * { margin: 0; padding: 0; box-sizing: border-box; }\n' +
            '        body { font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; }\n' +
            '        table { width: 100%; max-width: 600px; margin: 0 auto; background-color: white; }\n' +
            styleCss + '\n' +
            contentTypographyCss + '\n' +
            builderCss + '\n' +
            '    </style>\n' +
            '</head>\n' +
            '<body>\n' +
            htmlContent + '\n' +
            '</body>\n' +
            '</html>';
        
        var frame = document.getElementById('preview-frame');
        if (frame) {
            frame.srcdoc = previewHtml;
        }
    };
    
    /**
     * Initialize preview updates on content typography changes
     */
    $(document).ready(function() {
        // Update preview when content typography inputs change
        $('#content-font-family, #content-h1-size, #content-h1-color, ' +
          '#content-h2-size, #content-h2-color, #content-h3-size, #content-h3-color, ' +
          '#content-p-size, #content-p-line-height, #content-link-decoration, ' +
          '#content-blockquote-color, #content-blockquote-border, ' +
          '#content-container-border, #content-container-padding').on('input change', function() {
            if (typeof window.updatePreview === 'function') {
                window.updatePreview();
            }
        });
    });
    
})(jQuery);
