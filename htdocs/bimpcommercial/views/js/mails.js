function onEMailSelectChange($select) {
    if ($.isOk($select)) {
        var $inputContainer = $select.findParentByClass('inputContainer');

        if ($.isOk($inputContainer)) {
            var field_name = $inputContainer.data('field_name');
            if (field_name) {
                if ($select.val() === 'custom') {
                    $inputContainer.find('.selectMailHelp').slideUp(250);
                    $inputContainer.find('.mail_custom_value').find('input').attr('name', field_name + '_add_value');
                    $select.attr('name', field_name + 'add_value_select');
                    $inputContainer.find('.mail_custom_value').slideDown(250);
                } else {
                    $inputContainer.find('.mail_custom_value').find('input').attr('name', field_name + '_add_value_custom');
                    $select.attr('name', field_name + '_add_value');
                    if($inputContainer.find('.emails_select.principal').val() !== 'custom'){
                        $inputContainer.find('.selectMailHelp').slideDown(250);
                        $inputContainer.find('.mail_custom_value').slideUp(250);
                    }
                }
            }
        }
    }
}

function onMailFormLoaded($form) {
    if ($.isOk($form)) {
        if (!parseInt($form.data('mail_form_events_init'))) {
            $form.find('.emails_select').each(function () {
                if (!parseInt($(this).data('mails_select_events_init'))) {
                    $(this).change(function () {
                        onEMailSelectChange($(this));
                    });
                    $(this).data('mails_select_events_init', 1);
                }
            });

            var id_form = $form.data('identifier');

            if (!parseInt($('body').data(id_form + '_mails_select_reload_events_init'))) {
                $('body').on('inputReloaded', function (e) {
                    if (e.$form.data('identifier') === id_form) {
                        if (e.input_name === 'mail_to' ||
                                e.input_name === 'copy_to') {
                            e.$form.find('.emails_select').each(function () {
                                if (!parseInt($(this).data('mails_select_events_init'))) {
                                    $(this).change(function () {
                                        onEMailSelectChange($(this));
                                    });
                                    $(this).data('mails_select_events_init', 1);
                                }
                            });
                        }
                    }
                });
                $('body').data(id_form + '_mails_select_reload_events_init', 1);
            }
            $form.data('mail_form_events_init', 1);
        }
    }
}

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if (/^.*_email_form_.*$/.test(e.$form.attr('id'))) {
            onMailFormLoaded(e.$form);
        }
    });
});