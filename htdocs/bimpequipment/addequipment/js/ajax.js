/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idCurrentEntrepot;
var idCurrentProd;
var nameCurrentProd;
var cntEquip = 0;




/**
 * Ajax call
 */

function addEquipment(serialNumber, currentEquip) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/addequipment/interface.php",
        data: {
            idCurrentEntrepot: idCurrentEntrepot,
            idCurrentProd: idCurrentProd,
            serialNumber: serialNumber,
            action: 'addEquipment'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
//            $('input.custNote[cntEquip="' + currentEquip + '"]').val(out.note);

//            if (out.code)
            if (out.code === -1) {
                $('tr#' + currentEquip + ' td text.reponseServeur').text("L'insertion à échouer, veuillez réessayer");  // (erreur base de donnée)
            } else if (out.code === -2) {
                $('tr#' + currentEquip + ' td text.reponseServeur').text("Le numéro de série existe déjà.");
            }
//            $('tr#' + currentEquip + ' td text.reponseServeur').text(out);

            $('input.custNote[cntEquip="' + currentEquip + '"]').val('Du texte ... ' + currentEquip);
            $('tr#' + currentEquip + ' td text.reponseServeur').text('OK');
            addFieldEquipment();
        }
    });
}





/**
 * Ready
 */

$(document).ready(function () {

    $('.select2').select2();

    idCurrentProd = $('#type').val();
    nameCurrentProd = $('#type option:selected').text();
    idCurrentEntrepot = $('#entrepot').val();
    addFieldEquipment();

    $('#type').on('change', function () {
        idCurrentProd = $(this).val();
        nameCurrentProd = $('#type option:selected').text();
        $('#hereEquipment tr').last().remove();
        cntEquip--;
        addFieldEquipment();
    });

    $('#entrepot').on('change', function () {
        idCurrentEntrepot = $(this).val();
    });

});




/**
 * Functions
 */

function addFieldEquipment() {
    var line = '<tr id="' + cntEquip + '"><td>' + cntEquip + '</td>';
    line += '<td>' + nameCurrentProd + '</td><td>';
    for (i = 0; i < 3; i++) {
        line += '<input class="subSerialNumber" name="serial" cntEquip="' + cntEquip + '" maxlength="4">';
    }
    line += '</td><td><input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '"></td>';
    line += '<td><text class="reponseServeur"></text></td></tr>';
    $(line).appendTo('#hereEquipment');

    $('input.subSerialNumber[cntEquip="' + cntEquip + '"]').first().focus();

    cntEquip++;

    $(".subSerialNumber").keyup(function () {
        if (this.value.length === this.maxLength) {
            $(this).next('.subSerialNumber').focus();
            if (!$(this).next('.subSerialNumber').length && $(this).attr('valider') !== 'true'/* &&
             $(this).prev().value.length === $(this).prev().maxLength &&
             $(this).prev().prev().value.length === $(this).prev().prev().maxLength*/) {
                $(this).attr('valider', 'true');
                var serialNumber = '';
                $("input[cntEquip='" + $(this).attr('cntEquip') + "']").each(function () {
                    serialNumber += $(this).val();
                });
                $(this).blur();
                addEquipment(serialNumber, $(this).attr('cntEquip'));
            }
        }
    });
}