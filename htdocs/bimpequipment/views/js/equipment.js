var equipment_url = dol_url_root + '/bimpequipment/index.php';

function onEquipmentFormLoaded($form) {
    $form.find('[name="product_label"]').removeClass('disabled');
    $form.find('[name="date_purchase_picker"]').removeClass('disabled');
    $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
    $form.find('[name="warranty_type"]').removeClass('disabled');
//    $form.find('[name="note"]').removeClass('disabled');
    if ($.isOk($form)) {
        $form.find('[name="serial"]').change(function () {
            var $container = $form.find($('#' + $form.attr('id') + '_result'));
            var serial = $(this).val();
            if (serial) {
                $form.find('[name="product_label"]').addClass('disabled');
                $form.find('[name="date_purchase_picker"]').addClass('disabled');
                $form.find('[name="date_warranty_end_picker"]').addClass('disabled');
                $form.find('[name="warranty_type"]').addClass('disabled');
//                $form.find('[name="note"]').addClass('disabled');
                BimpAjax('equipmentGgxLookup', {
                    serial: serial
                }, $container, {
                    url: equipment_url,
                    $form: $form,
                    display_processing: true,
                    display_success: false,
                    processing_msg: 'Chargement des données GSX en cours',
                    success: function (result, bimpAjax) {
                        $form.find('[name="product_label"]').removeClass('disabled');
                        $form.find('[name="date_purchase_picker"]').removeClass('disabled');
                        $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
                        $form.find('[name="warranty_type"]').removeClass('disabled');
//                        $form.find('[name="note"]').removeClass('disabled');
                        if (typeof (result.data.product_label) === 'string' && result.data.product_label) {
                            bimpAjax.$form.find('[name="product_label"]').val(result.data.product_label).change();
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
//                        if (typeof (result.data.note) === 'string' && result.data.note) {
//                            bimpAjax.$form.find('[name="note"]').val(result.data.note).change();
//                        }
                    },
                    error: function (result, bimpAjax) {
                        $form.find('[name="product_label"]').removeClass('disabled');
                        $form.find('[name="date_purchase_picker"]').removeClass('disabled');
                        $form.find('[name="date_warranty_end_picker"]').removeClass('disabled');
                        $form.find('[name="warranty_type"]').removeClass('disabled');
                        $form.find('[name="note"]').removeClass('disabled');
                    }
                });
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

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('Equipment_form')) {
                onEquipmentFormLoaded(e.$form);
            }
        }
    });
});