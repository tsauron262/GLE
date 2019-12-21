var equipment_url = dol_url_root + '/bimpequipment/index.php';

function onEquipmentFormLoaded($form) {
    $form.find('[name="serial"]').removeClass('disabled');
    $form.find('[name="product_label"]').removeClass('disabled');
    $form.find('[name="imei"]').removeClass('disabled');
    $form.find('[name="imei2"]').removeClass('disabled');
    $form.find('[name="meid"]').removeClass('disabled');
    $form.find('[name="date_purchase_picker"]').removeClass('disabled');
    $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
    $form.find('[name="warranty_type"]').removeClass('disabled');


    if ($.isOk($form)) {

        $form.find('[name="serial"]').change(function () {
            var identifier = $(this).val();
            if (identifier) {
                fetchEquipmentInfos($form, identifier);
            }
        });
        $form.find('[name="imei"]').change(function () {
            var identifier = $(this).val();
            if (identifier) {
                fetchEquipmentInfos($form, identifier);
            }
        });
        $form.find('[name="imei2"]').change(function () {
            var identifier = $(this).val();
            if (identifier) {
                fetchEquipmentInfos($form, identifier);
            }
        });
        $form.find('[name="meid"]').change(function () {
            var identifier = $(this).val();
            if (identifier) {
                fetchEquipmentInfos($form, identifier);
            }
        });

        var $sav_form = $form.findParentByClass('BS_SAV_form');
        if ($.isOk($sav_form)) {
            var serial = $sav_form.find('form.BS_SAV_form').find('[name="id_equipment_search"]').val();
            if (serial) {
                $form.find('[name="serial"]').val(serial).change();
            }
        }
    }
}

function fetchEquipmentInfos($form, identifier) {
    var $container = $form.find($('#' + $form.attr('id') + '_result'));

    if (identifier) {
        $form.find('[name="id_product_search"]').addClass('disabled');
        $form.find('[name="product_label"]').addClass('disabled');
        $form.find('[name="serial"]').addClass('disabled');
        $form.find('[name="imei"]').addClass('disabled');
        $form.find('[name="imei2"]').addClass('disabled');
        $form.find('[name="meid"]').addClass('disabled');
        $form.find('[name="date_purchase_picker"]').addClass('disabled');
        $form.find('[name="date_warranty_end_picker"]').addClass('disabled');
        $form.find('[name="warranty_type"]').addClass('disabled');
        var params = {
            $form: $form,
            display_processing: true,
            display_success: false,
            processing_msg: 'Chargement des données GSX en cours',
            success: function (result, bimpAjax) {
                $form.find('[name="id_product_search"]').removeClass('disabled');
                $form.find('[name="product_label"]').removeClass('disabled');
                $form.find('[name="serial"]').removeClass('disabled');
                $form.find('[name="imei"]').removeClass('disabled');
                $form.find('[name="imei2"]').removeClass('disabled');
                $form.find('[name="meid"]').removeClass('disabled');
                $form.find('[name="date_purchase_picker"]').removeClass('disabled');
                $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
                $form.find('[name="warranty_type"]').removeClass('disabled');

                if (typeof (result.data.product_label) === 'string' && result.data.product_label) {
                    bimpAjax.$form.find('[name="product_label"]').val(result.data.product_label);
                }
                if (typeof (result.data.serial) === 'string' && result.data.serial) {
                    bimpAjax.$form.find('[name="serial"]').val(result.data.serial);
                }
                if (typeof (result.data.imei) === 'string' && result.data.imei) {
                    bimpAjax.$form.find('[name="imei"]').val(result.data.imei);
                }
                if (typeof (result.data.imei2) === 'string' && result.data.imei2) {
                    bimpAjax.$form.find('[name="imei2"]').val(result.data.imei2);
                }
                if (typeof (result.data.meid) === 'string' && result.data.meid) {
                    bimpAjax.$form.find('[name="meid"]').val(result.data.meid);
                }
                if (typeof (result.data.date_purchase) === 'string' && result.data.date_purchase) {
                    bimpAjax.$form.find('[name="date_purchase_picker"]').data("DateTimePicker").date(moment(result.data.date_purchase));
                    bimpAjax.$form.find('[name="date_purchase_picker"]').change();
                }
                if (typeof (result.data.date_warranty_end) === 'string' && result.data.date_warranty_end) {
                    bimpAjax.$form.find('[name="date_warranty_end_picker"]').data("DateTimePicker").date(moment(result.data.date_warranty_end));
                    bimpAjax.$form.find('[name="date_warranty_end_picker"]').change();
                }
                if (typeof (result.data.warranty_type) === 'string' && result.data.warranty_type) {
                    bimpAjax.$form.find('[name="warranty_type"]').val(result.data.warranty_type).change();
                }
                if (typeof (result.data.warning) === 'string' && result.data.warning) {
                    bimpAjax.$form.find('[name="serial"]').parent().append(result.data.warning);
                }
                if (typeof (result.data.id_product) !== 'undefined' && parseInt(result.data.id_product)) {
                    var $input = bimpAjax.$form.find('[name="id_product"]');
                    if ($input.length) {
                        if (parseInt($input.val()) !== parseInt(result.data.id_product)) {
                            $input.val(result.data.id_product);
                            reloadObjectInput($form.data('identifier'), 'id_product', {}, true);
                            bimpAjax.$form.find('[name="product_label"]').val('');
                        }
                    }
                }
            },
            error: function (result, bimpAjax) {
                $form.find('[name="id_product_search"]').removeClass('disabled');
                $form.find('[name="product_label"]').removeClass('disabled');
                $form.find('[name="serial"]').removeClass('disabled');
                $form.find('[name="imei"]').removeClass('disabled');
                $form.find('[name="imei2"]').removeClass('disabled');
                $form.find('[name="meid"]').removeClass('disabled');
                $form.find('[name="date_purchase_picker"]').removeClass('disabled');
                $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
                $form.find('[name="warranty_type"]').removeClass('disabled');
                $form.find('[name="note"]').removeClass('disabled');
            }
        };

        if (typeof (use_gsx_v2) !== 'undefined' && use_gsx_v2) {
            GsxAjax('gsxGetEquipmentInfos', {
                serial: identifier
            }, $container, params);
        } else {
            params.url = equipment_url;
            BimpAjax('equipmentGgxLookup', {
                serial: identifier
            }, $container, params);
        }
    }
}

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('Equipment_form')) {
                onEquipmentFormLoaded(e.$form);
            }
        }
    });
});