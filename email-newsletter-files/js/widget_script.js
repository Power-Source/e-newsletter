jQuery( document ).ready( function() {

    jQuery("#subscribes_form").on('submit', function(event) {
        event.preventDefault(); //disable default behavior
    });
    jQuery("#subscribes_form .enewletter_widget_submit").on('click', function(event){
        var stop = 0;

        event.preventDefault(); //disable default behavior

        var parent = jQuery(this).closest('.e-newsletter-widget');

        parent.find("#newsletter_action").val(this.id);

        parent.find("#message").text( email_newsletter_widget_scripts.saving ).show();

        if ( this.id == "new_subscribe" ) {
            if ( "" == parent.find("#e_newsletter_email").val() ) {
                // append a error message
                parent.find("#message").text( email_newsletter_widget_scripts.empty_email ).show();
                stop = 1;
            }
        }

        if(stop == 0) {
            const e_newsletter_groups_id = []; //prepers data for pdata filter
            parent.find('input[name="e_newsletter_groups_id[]"]').each(function() {
                if(jQuery(this).is(':checked') || this.type == 'hidden')
                    e_newsletter_groups_id.push(this.value);
            });

            const e_newsletter_auto_groups_id = []; //prepers data for pdata filter
            parent.find('input[name="e_newsletter_auto_groups_id[]"]').each(function() {
                e_newsletter_auto_groups_id.push(this.value);
            });

            const e_newsletter_add_groups_id = []; //prepers data for pdata filter
            parent.find('input[name="e_newsletter_add_groups_id[]"]').each(function() {
                e_newsletter_add_groups_id.push(this.value);
            });

            const e_newsletter_remove_groups_id = []; //prepers data for pdata filter
            parent.find('input[name="e_newsletter_remove_groups_id[]"]').each(function() {
                e_newsletter_remove_groups_id.push(this.value);
            });

            const data = { //looks for and sets all variables used for export
                action: 'manage_subscriptions_ajax',
                newsletter_action: parent.find("#newsletter_action" ).val(),
                unsubscribe_code: parent.find("#unsubscribe_code" ).val(),
                e_newsletter_email: parent.find("#e_newsletter_email" ).val(),
                e_newsletter_name: parent.find("#e_newsletter_name" ).val(),
                e_newsletter_groups_id: e_newsletter_groups_id,
                e_newsletter_auto_groups_id: e_newsletter_auto_groups_id,
                e_newsletter_add_groups_id: e_newsletter_add_groups_id,
                e_newsletter_remove_groups_id: e_newsletter_remove_groups_id
            };

            // Use modern Fetch API instead of jQuery.post()
            fetch(email_newsletter_widget_scripts.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.text())
            .then(responseText => {
                try {
                    const responseData = JSON.parse(responseText);

                    if(typeof responseData.redirect !== 'undefined' && responseData.redirect) {
                        window.location = responseData.redirect;
                    } else {
                        const messageEl = parent.find("#message");
                        messageEl.hide();
                        messageEl.text(responseData.message).show();

                        if(typeof responseData.subscribe_groups !== "undefined") {
                            responseData.subscribe_groups.forEach(function(value) {
                                parent.find('.e_newsletter_groups_id_'+value).prop("checked", true);
                            });
                        }
                        if(typeof responseData.unsubscribe_code !== "undefined") {
                            parent.find("#unsubscribe_code").val(responseData.unsubscribe_code);
                        }
                        parent.find('#'+responseData.view).show();
                        parent.find('#'+responseData.hide).hide();
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});