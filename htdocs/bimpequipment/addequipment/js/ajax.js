/* global DOL_URL_ROOT */

var idCurrentEntrepot;
var idCurrentProd;
var nameCurrentProd;
var cntEquip = 0;

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
        success: function (note) {
//            $("input.custNote[cntEquip='" + currentEquip + "']").val(note);
            $("input.custNote[cntEquip='" + currentEquip + "']").val('Du texte ... ' + currentEquip);
            addFieldEquipment();
        }
    });
}




$(document).ready(function () {

    $('.select2').select2();
    console.log("ready");

    idCurrentProd = $('#type').val();
    nameCurrentProd = $('#type option:selected').text();
    idCurrentEntrepot = $('#entrepot').val();
    addFieldEquipment();

    $('#type').on('change', function () {
        idCurrentProd = $(this).val();
        nameCurrentProd = $('#type option:selected').text();
        console.log('nameCurrentProd' + nameCurrentProd);
        $('#hereEquipment tr').last().remove();
        cntEquip--;
        addFieldEquipment();
    });

    $('#entrepot').on('change', function () {
        idCurrentEntrepot = $(this).val();
    });

});

function addFieldEquipment() {
    var line = '<tr id="' + idCurrentProd +'"><td>' + nameCurrentProd + '</td><td>';
    for (i = 0; i < 3; i++) {
        line += '<input class="subSerialNumber" name="serial" cntEquip="' + cntEquip + '" maxlength="4">';
    }
    line += '</td><td><input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '"></td></tr>';
    $(line).appendTo('#hereEquipment');

    $('input.serial[cntEquip="' + cntEquip + '"]').first().focus();

    cntEquip++;

    $(".subSerialNumber").keyup(function () {
        if (this.value.length === this.maxLength) {
            $(this).next('.subSerialNumber').focus();
            if (!$(this).next('.subSerialNumber').length && $(this).attr('valider') !== 'true') {
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