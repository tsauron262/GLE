/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idCurrentEntrepot;
var idCurrentProd;
var nameCurrentProd;
var cntEquip = 0;
var allSerialNumber = [];




/**
 * Ajax call
 */

function checkEquipment(serialNumber, currentEquip) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/addequipment/interface.php",
        data: {
            idCurrentEntrepot: idCurrentEntrepot,
            idCurrentProd: idCurrentProd,
            serialNumber: serialNumber,
            action: 'checkEquipment'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            var outDec = JSON.parse(out);
            var responseTd = 'tr#' + currentEquip + ' td text.reponseServeur';
            if (allSerialNumber.includes(serialNumber)) {
                $(responseTd).text('A déjà scanné.');
                $(responseTd).css('color', 'red');
            } else if (outDec.code === -1) {
                $(responseTd).text('Existe déjà.');
                $(responseTd).css('color', 'red');
                allSerialNumber.push(serialNumber);
            } else if (outDec.code === 1) {
                $(responseTd).text('OK');
                $(responseTd).css('color', 'green');
                allSerialNumber.push(serialNumber);
            }
            $('input.custNote[cntEquip="' + currentEquip + '"]').val('Du texte ... ' + currentEquip);
        }
    });
}





/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepot').select2();

    idCurrentEntrepot = $('#entrepot').val();
    addFieldEquipment();

    $('#search_productid').on('change', function () {
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

    cntEquip++;

    var line = '<tr id="' + cntEquip + '"><td>' + cntEquip + '</td>';
    line += '<td>' + productid.value + '</td><td>';
    line += '<input class="serialNumber" name="serial" cntEquip="' + cntEquip + '">';
    line += '</td><td><input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '"></td>';
    line += '<td><text class="reponseServeur"></text></td></tr>';
    $(line).appendTo('#hereEquipment');

    $('input.serialNumber[cntEquip="' + cntEquip + '"]').first().focus();


    $(".serialNumber").keyup(function (e) {
        if (e.keyCode === 13 && $(this).attr('valider') !== 'true') { // code for "Enter"
            if (productid.value !== '') {
                $('#alertProd').empty();
                $('#search_productid').css('border', 'none');
                $(this).attr('valider', 'true');
                var serialNumber = $("input[cntEquip='" + $(this).attr('cntEquip') + "']").val();
                $(this).blur();
                checkEquipment(serialNumber, $(this).attr('cntEquip'));
                addFieldEquipment();
            } else {
                $('#alertProd').text('Veuillez sélectionner un produit.');
            }
        }
    });
}