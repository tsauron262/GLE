/* global DOL_URL_ROOT */

var idCurentEntrepot;
var idCurentProd;
var nameCurrentProd;
var cntEquip = 0;

function addEquipment(serialNumber, currentEquip) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/addequipment/interface.php",
        data: {
            idCurentEntrepot: idCurentEntrepot,
            idCurentProd: idCurentProd,
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

    idCurentProd = $('#type').val();
    nameCurrentProd = $('#type option:selected').text();
    idCurentEntrepot = $('#entrepot').val();
    addFieldEquipment();

    $('#type').on('change', function () {
        idCurentProd = $(this).val();
        nameCurrentProd = $('#type option:selected').text();
        console.log('nameCurrentProd' + nameCurrentProd);
        addFieldEquipment();
    });

    $('#entrepot').on('change', function () {
        idCurentEntrepot = $(this).val();
    });

});

function addFieldEquipment() {
    var line = '<text>Numéro de série<text>';
    for (i = 0; i < 3; i++) {
        line += '<input class="subSerialNumber" name="serial" cntEquip="' + cntEquip + '" maxlength="4">';
    }
    line += '<text>Produit:' + nameCurrentProd + '</text>&nbsp;&nbsp;&nbsp;&nbsp;<text>Note: <text>';
    line += '<input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '"><br/><br/>';
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