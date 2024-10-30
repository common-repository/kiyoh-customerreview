(function () {
    jQuery(document).ready(function () {
        setTimeout(function(){
            toogleStatus(300);
            toogleSendMethod(300);
            toogleKiyohServer(500);
        },200);
        jQuery('select[name="kiyoh_option_event"]').change(function (event) {
            toogleStatus(300);
        });

        jQuery('select[name="kiyoh_option_send_method"]').change(function (event) {
            toogleSendMethod(300);
        });

        jQuery('select[name="kiyoh_option_server"]').change(function (event) {
            toogleKiyohServer(300);
        });
        jQuery('#kiyohform #submit').on('click',function(){
            disableNotvisibleElements();
        });
    });
    function toogleStatus(speed) {
        if (jQuery('select[name="kiyoh_option_event"]').length) {
            var my_event = jQuery('select[name="kiyoh_option_event"]').val();
            if (my_event == 'Orderstatus') {
                jQuery('#status').show(speed);
            } else {
                jQuery('#status').hide(speed);
            }
        }
    }

    function toogleSendMethod(speed) {
        if (jQuery('select[name="kiyoh_option_send_method"]').length) {
            var my_event = jQuery('select[name="kiyoh_option_send_method"]').val();
            if (my_event == 'my') {
                jQuery('.myserver').show(speed);
                jQuery('.kiyohserver').hide(speed);
            } else {
                jQuery('.myserver input').each(function () {
                    jQuery(this).val('');
                });
                jQuery('.myserver').hide(speed);
                jQuery('.kiyohserver').show(speed);
            }
            toogleKiyohServer(speed);
        }
    }

    function toogleKiyohServer(speed) {
        if (jQuery('select[name="kiyoh_option_server"]').length) {
            var my_event = jQuery('select[name="kiyoh_option_server"]').val();
            if (my_event == 'klantenvertellen.nl' || my_event=='newkiyoh.com') {
                jQuery('.dependsonKlantenvertellenserver').show(speed);
                jQuery('.dependsonkiyoh').hide(speed);
            } else {
                jQuery('.dependsonKlantenvertellenserver').hide(speed);
                jQuery('.dependsonkiyoh').show(speed);
            }
            if (my_event == 'kiyoh.com') {
                jQuery('.dependsonkiyohserver').show(speed);
            } else {
                jQuery('.dependsonkiyohserver').hide(speed);
            }

        }
    }
    function disableNotvisibleElements(evt) {

        // Disable things that we don't want to validate.
        jQuery(".required:hidden").attr("disabled", true);

        jQuery(".required:visible").removeAttr("disabled", true);
    };

})();