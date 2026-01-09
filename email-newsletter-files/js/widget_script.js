jQuery( document ).ready( function() {

    jQuery("#subscribes_form").submit(function(event) {
        event.preventDefault(); //disable default behavior
    });
    jQuery("#subscribes_form .enewletter_widget_submit").on('click', function(event){
        var stop = 0;

        event.preventDefault(); //disable default behavior

        var parent = jQuery(this).closest('.e-newsletter-widget');

        parent.find("#newsletter_action").val(jQuery(this).attr('id'));

        parent.find("#message").text( email_newsletter_widget_scripts.saving ).css('display', 'block').css('opacity', '1');

        if ( jQuery(this).attr('id') == "new_subscribe" ) {
            if ( "" == parent.find("#e_newsletter_email").val() ) {
                // append a error message
                parent.find("#message").text( email_newsletter_widget_scripts.empty_email ).css('display', 'block').css('opacity', '1');
                stop = 1;
            }
        }

        if(stop == 0) {
            var e_newsletter_groups_id = []; //prepers data for pdata filter
            jQuery.each(parent.find('input[name="e_newsletter_groups_id[]"]' ), function() {
                if(jQuery(this).is(':checked') || jQuery(this).attr('type') == 'hidden')
                    e_newsletter_groups_id.push(jQuery(this).val());
            });

            var e_newsletter_auto_groups_id = []; //prepers data for pdata filter
            jQuery.each(parent.find('input[name="e_newsletter_auto_groups_id[]"]' ), function() {
                e_newsletter_auto_groups_id.push(jQuery(this).val());
            });

            var e_newsletter_add_groups_id = []; //prepers data for pdata filter
            jQuery.each(parent.find('input[name="e_newsletter_add_groups_id[]"]' ), function() {
                e_newsletter_add_groups_id.push(jQuery(this).val());
            });

            var e_newsletter_remove_groups_id = []; //prepers data for pdata filter
            jQuery.each(parent.find('input[name="e_newsletter_remove_groups_id[]"]' ), function() {
                e_newsletter_remove_groups_id.push(jQuery(this).val());
            });

            var data = { //looks for and sets all variables used for export
                action: 'manage_subscriptions_ajax',
                newsletter_action: parent.find("#newsletter_action" ).val(),
                unsubscribe_code: parent.find("#unsubscribe_code" ).val(),
                e_newsletter_email: parent.find("#e_newsletter_email" ).val(),
                e_newsletter_name: parent.find("#e_newsletter_name" ).val(),
                newsletter_action: parent.find("#newsletter_action" ).val(),
                e_newsletter_groups_id: e_newsletter_groups_id,
                e_newsletter_auto_groups_id: e_newsletter_auto_groups_id,
                e_newsletter_add_groups_id: e_newsletter_add_groups_id,
                e_newsletter_remove_groups_id: e_newsletter_remove_groups_id
            };

            jQuery.post(email_newsletter_widget_scripts.ajax_url, data, function(data){ //post data to specified action trough special WP ajax page
                data = JSON.parse(data);

                if(typeof data.redirect !== 'undefined' && data.redirect)
                    window.location = data.redirect;
                else {
                    const messageEl = parent.find("#message");
                    messageEl.css('display', 'none');
                    messageEl.text(data.message).css('display', 'block').css('opacity', '1');

                    if(typeof data.subscribe_groups !== "undefined") {
                        jQuery.each(data.subscribe_groups, function(index, value) {
                            parent.find('.e_newsletter_groups_id_'+value).prop("checked", true);
                        });
                    }
                    if(typeof data.unsubscribe_code !== "undefined") {
                        parent.find("#unsubscribe_code").val(data.unsubscribe_code);
                    }
                    parent.find('#'+data.view).css('display', 'block').css('opacity', '1');
                    parent.find('#'+data.hide).css('display', 'none');
                }
            });
        }
    });
});