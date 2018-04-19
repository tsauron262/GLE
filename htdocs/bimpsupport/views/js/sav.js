function createNewPropal($button, id_sav) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

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
            bimpAjax.$button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function setNewSavStatus($button, id_sav, new_status, send_msg, extra_data) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

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
            bimpAjax.$button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function generatePDFFile($button, id_sav, file_type) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');
    
    BimpAjax('generatePDFFile', {
        id_sav: id_sav,
        file_type: file_type
    }, null, {
        id_sav: id_sav,
        $button: $button,
        success: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SAV',
                id_object: bimpAjax.id_sav
            }));
            if (typeof (result.file_url) !== undefined && result.file_url) {
                window.open(result.file_url);
            }
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

$(document).ready(function () {
    var $gsxForm = $('#loadGSXForm');

    if ($.isOk($gsxForm)) {
        $gsxForm.find('#loadGSXButton').click(function () {
            var $container = $('#gsxContainer');

            var serial = $gsxForm.find('#gsx_equipment_serial').val();
            serial = 'C02QL8SEFVH5';
            var ok = false;
            if (/^[A-Z0-9]{11,12}$/.test(serial)) {
                GSX.loadProduct(serial, 'gsxResultContainer');
//                        setRequest('newSerial', '&serial=' + serial);
                ok = true;
            }
            if (/^[0-9]{15}$/.test(serial)) {
                GSX.loadProduct(serial, 'gsxResultContainer');
//                        setRequest('newSerial', '&serial=' + serial);
                ok = true;
            }
            if (!ok) {
                $('#gsxResultContainer').html('<p class="alert alert-danger">Pas de numéro de série correct</p>');
            }
        });
    }
});
//
// if ($("textarea#Descr").length) {
//        textarea2 = $("textarea#Descr");
//        tabAccess = Array("Rayure", "Écran cassé", "Liquide");
//        textarea2.parent().append(' <select name="sometext" multiple="multiple" class="grand" id="sometext2">    <option>' + tabAccess.join('</option><option>') + '</option></select>');
//        $("#sometext2").click(function () {
//            textarea2.val(textarea2.val() + $(this).val() + ', ');
//        });
//    }
//    
//
//    if ($("textarea#Symptomes").length) {
//        textarea3 = $("textarea#Symptomes");
//        tabAccess = Array("Renouvellement anti virus et maintenance annuelle","Anti virus expiré","Virus ? Eradication? Nettoyage?", "Machine lente","Formatage","Réinstallation système.");
//        textarea3.parent().append(' <select name="sometext" multiple="multiple" class="grand" id="sometext3">    <option>' + tabAccess.join('</option><option>') + '</option></select>');
//        $("#sometext3").click(function () {
//            textarea3.val(textarea3.val() + $(this).val() + ', ');
//        });
//    }
//
//
//    if ($("textarea.choixAccess").length) {
//        textarea = $("textarea.choixAccess");
//        tabAccess = Array("Housse", "Alim", "Carton", "Clavier", "Souris", "Dvd", "Batterie", "Boite complet");
//        textarea.parent().append(' <select name="sometext" style="height: '+(20 * tabAccess.length)+'px;" multiple="multiple" class="grand" id="sometext">    <option>' + tabAccess.join('</option><option>') + '</option></select>');
//        $("#sometext").click(function () {
//            textarea.val(textarea.val() + $(this).val() + ', ');
//        });
//    }