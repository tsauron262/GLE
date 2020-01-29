function createNewPropal($button, id_sav) {
    BimpAjax('createPropal', {
        id_sav: id_sav
    }, null, {
        id_sav: id_sav,
        $button: $button,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
        }
    });
}

function generatePropal($button, id_sav) {
    BimpAjax('generatePropal', {
        id_sav: id_sav
    }, null, {
        id_sav: id_sav,
        $button: $button,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
        }
    });
}

function setNewSavStatus($button, id_sav, new_status, send_msg, extra_data) {
    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    if (typeof (send_msg) === 'undefined') {
        send_msg = true;
    }

    if (send_msg) {
        if (confirm('envoyer une notification au client ?')) {
            send_msg = 1;
        } else {
            send_msg = 0;
        }
    }

    extra_data['send_msg'] = send_msg;

    BimpAjax('setObjectNewStatus', {
        module: 'bimpsupport',
        object_name: 'BS_SAV',
        id_object: id_sav,
        new_status: new_status,
        extra_data: extra_data
    }, null, {
        id_sav: id_sav,
        $button: $button,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
        }
    });
}

function generatePDFFile($button, id_sav, file_type) {
    BimpAjax('generatePDFFile', {
        id_sav: id_sav,
        file_type: file_type
    }, null, {
        id_sav: id_sav,
        $button: $button,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
            if (typeof (result.file_url) !== 'undefined' && result.file_url) {
                window.open(result.file_url);
            }
        }
    });
}

function loadGSXView($button, id_sav) {
    var $gsxForm = $('#loadGSXForm');
    var $container = $('#gsxResultContainer');
    var serial = $gsxForm.find('#gsx_equipment_serial').val();
    if (/^S?[A-Z0-9]{11,12}$/.test(serial) || /^S?[0-9]{15}$/.test(serial)) {
        var params = {
            append_html: true,
            display_processing: true,
            processing_msg: 'Chargement en cours',
            $gsxForm: $gsxForm,
            $button: $button,
            success: function (result, bimpAjax) {
                bimpAjax.$gsxForm.slideUp(250);
                $('body').trigger($.Event('controllerTabLoaded', {
                    $container: bimpAjax.$resultContainer
                }));
            }
        };

        if (typeof (use_gsx_v2) !== 'undefined' && use_gsx_v2) {
            GsxAjax('gsxLoadSavGsxView', {
                serial: serial,
                id_sav: id_sav
            }, $container, params);
        } else {
            BimpAjax('loadGSXView', {
                serial: serial,
                id_sav: id_sav
            }, $container, params);
        }
    } else {
        $container.html('<p class="alert alert-danger">Numéro de série invalide</p>');
    }

}

function setSavEquipmentInputEvents($input) {
    if ($.isOk($input)) {
        if (!parseInt($input.data('sav_equipment_input_events_init'))) {
            $input.change(function () {
                var $container = $input.findParentByClass('inputContainer');
                if ($container.length) {
                    $container.find('div.equipmentAjaxInfos').remove();
                    var html = '<div class="equipmentAjaxInfos" style="display: none"></div>';
                    $container.append(html);

                    var params = {
                        $container: $container,
                        display_success: false,
                        display_errors: true,
                        display_warnings: true,
                        display_processing: true,
                        processing_padding: 0,
                        append_html: true,
                        processing_msg: ''
                    };

                    if (typeof (use_gsx_v2) !== 'undefined' && use_gsx_v2) {
                        GsxAjax('gsxGetEquipmentEligibility', {
                            id_equipment: $input.val()
                        }, $container.find('.equipmentAjaxInfos'), params);
                    } else {
                        params.url = dol_url_root + '/bimpequipment/index.php';
                        BimpAjax('getEquipmentGsxInfos', {
                            id_equipment: $input.val()
                        }, $container.find('.equipmentAjaxInfos'), params);
                    }
                }
            });
            $input.data('sav_equipment_input_events_init', 1);
        }
    }
}

function onSavFormLoaded($form) {
    var $input = $form.find('[name="id_equipment"]');
    if ($input.length) {
        setSavEquipmentInputEvents($input);
    }

    $('body').on('inputReloaded', function (e) {
        if ($.isOk(e.$form) && e.$form.hasClass('BS_SAV_form')) {
            if ($.isOk(e.$input) && e.input_name === 'id_equipment') {
                setSavEquipmentInputEvents(e.$input);
                e.$input.change();
            }
        }
    });
}

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('BS_SAV_form')) {
                onSavFormLoaded(e.$form);
            }
        }
    });
});