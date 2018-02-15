/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idCurrentEntrepot;
var idCurrentProd;
var cntEquip = 0;
var allSerialNumber = [];

/**
 * Contains equipment object
 *      serial
 *      id_entrepot
 *      id_product
 * @type Array
 */
var newEquipments = [];





/**
 * Ajax call
 */

function checkEquipment(serialNumber, currentEquipCnt) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
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
            var responseTd = 'tr#' + currentEquipCnt + ' td text.reponseServeur';
            if (allSerialNumber.includes(serialNumber)) {
                $(responseTd).text('Déjà scanné.');
                $(responseTd).css('color', 'red');
            } else if (outDec.code === 1) {
                $(responseTd).text('OK');
                $(responseTd).css('color', 'green');
                allSerialNumber.push(serialNumber);
                var newEquipment = {
                    serial: serialNumber,
                    id_entrepot: idCurrentEntrepot,
                    id_product: idCurrentProd
                };
                newEquipments.push(newEquipment);
            } else if (outDec.code === -1) {
                $(responseTd).text('Existe déjà.');
                $(responseTd).css('color', 'red');
                allSerialNumber.push(serialNumber);
            }
            $('input.custNote[cntEquip="' + currentEquipCnt + '"]').val(outDec.note);
        }
    });
}

function addEquipment() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            newEquipments: newEquipments,
            action: 'addEquipment'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            var outDec = JSON.parse(out);
            if (outDec.errors.length !== 0) {
                var errors_msg = "Certains équipement n'ont pas pû être enregistrés<br>";
                for (i = 0; i < outDec.errors.length; i++) {
                    errors_msg += "Erreur numéro " + i + " " + outDec.errors[i] + "<br>";
                }
                setMessage(errors_msg, 'errors');
            } else {
                if (outDec.nbNewEquipment === 1)
                    setMessage('alertMessage', outDec.nbNewEquipment + " équipement a été enregistré avec succès", 'mesgs');
                else if (outDec.nbNewEquipment > 1)
                    setMessage('alertMessage', outDec.nbNewEquipment + " équipements ont été enregistrés avec succès", 'mesgs');
            }
            newEquipments = [];
        }
    });
}



/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepot').select2();
    idCurrentEntrepot = $('#entrepot').val();
    idCurrentProd = productid.value;
    addFieldEquipment();
    initEvents();

});





/**
 * Functions
 */

function initEvents() {
    $('#search_productid').on('change', function () {
        idCurrentProd = productid.value;
        $('#hereEquipment tr').last().remove();
        cntEquip--;
        addFieldEquipment();
    });

    $('#entrepot').on('change', function () {
        idCurrentEntrepot = $(this).val();
    });

    $('#enregistrer').click(function () {
        addEquipment();
    });
}

/* Add a line in the table of equipments */
function addFieldEquipment() {

    $('#alertProd').empty();
    cntEquip++;

    var line = '<tr id="' + cntEquip + '" ><td>' + cntEquip + '</td>';      // Nombre de produits scannés
    line += '<td>'+productid.value+'</td><td>';      // Identifiant du produit
    line += '<input class="serialNumber" name="serial" cntEquip="' + cntEquip + '">'; // Numéro de série
    line += '</td><td><input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '"></td>';   // Note
    line += '<td><text class="reponseServeur"></text></td></tr>';   // Réponse serveur
    $(line).appendTo('#hereEquipment');

    $('input.serialNumber[cntEquip="' + cntEquip + '"]').first().focus();

    $(".serialNumber").keyup(function (e) {
        if (e.keyCode === 13 && $(this).attr('valider') !== 'true') { // code for "Enter"
            if (productid.value !== '') {
                $(this).attr('valider', 'true');
                var serialNumber = $("input[cntEquip='" + $(this).attr('cntEquip') + "']").val();
                $(this).blur(); // unfocus
                checkEquipment(serialNumber, $(this).attr('cntEquip'));
                addFieldEquipment();
            } else {
                $('#alertProd').text('Veuillez sélectionner un produit.');
            }
        }
    });
}

/**
 * 
 * @param String idElement id of the element to append the message in
 * @param String message the message you want to display
 * @param String type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';

    $('#' + idElement).hide().fadeIn(1000).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}