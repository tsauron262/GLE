/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idEntrepotStart;
var idEntrepotEnd;
var cntEquip = 0;
var allSerialNumber = [];

var newEquipments = [];

var newProducts = [];



/**
 * Ajax call
 */

function checkStockForProduct(idProduct, qty) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/transfertequipment/interface.php",
        data: {
            idProduct: idProduct,
            idEntrepotStart: idEntrepotStart,
            action: 'checkStockForProduct'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            var nb_product_in_entrepot = parseInt(outParsed.nb_product);
            if (outParsed.nb_product === 'no_row') {
                setMessage('alertProduct', 'L\'entrepot de départ ne possède pas ce produit.', 'error');
            } else if (nb_product_in_entrepot < qty) {
                setMessage('alertProduct', 'Il n\'y a que ' + nb_product_in_entrepot + ' produit(s) dans cet entrepot et vous souhaitez en transférer ' + qty + '.', 'error');
            } else {
                addFieldProduct(idProduct, qty, nb_product_in_entrepot);
            }
        }
    });
}

function checkStockForEquipment(serial, baliseSerial) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/transfertequipment/interface.php",
        data: {
            serial: serial,
            idEntrepotStart: idEntrepotStart,
            action: 'checkStockEquipment'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            if (outParsed === 'serial_unknown') {
                setMessage('alertEquipment', 'Cet équipement n\'est pas renseigné dans la base de donné.', 'error');
            } else if (outParsed === 'no_entrepot_for_equipment') {
                setMessage('alertEquipment', 'L\'entrepot de départ ne possède pas cet équipement.', 'error');
            } else {
                initDeleteEquipment(serial);
                baliseSerial.parent().parent().attr('id', serial);
                baliseSerial.attr('valider', 'true');
                baliseSerial.blur(); // unfocus
                newEquipments.push(serial);
                $('tr#' + serial + ' td[name=reponseServeur]').text(outParsed);
                addFieldEquipment();
            }
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepotStart').select2();
    $('#entrepotEnd').select2();
    idEntrepotStart = $('#entrepotStart').val();
    idEntrepotEnd = $('#entrepotEnd').val();
    addFieldEquipment();
    initEvents();
});





/**
 * Functions
 */

function initEvents() {
    $('#addProduct').on('click', function () {
        var productId = productid.value;
        var qty = $('#qty').val();
        if (productId !== '') {
            if (newProducts.find(obj => obj.id_product === productId) !== undefined) {
                setMessage('alertProduct', 'Le produit est déjà dans le tableau avec l\'identifiant ' + productId + '. Pour changer la quantité de ce produit, veuillez supprimer la ligne et la recréer', 'error');
            } else {
                checkStockForProduct(productId, qty)
            }
        } else {
            setMessage('alertProduct', 'Veuillez sélectionner un produit pour l\'ajouter au tableau.', 'error');
        }
    });

    $('#entrepot').on('change', function () {
//        idCurrentEntrepot = $(this).val();
    });


    $('#enregistrer').click(function () {
        if (idEntrepotStart === idEntrepotEnd) {
            setMessage('alertEnregistrer', 'L\'entrepot de départ doit être différent de celui d\'arrivé.', 'error');
        }
    });
}

/* Add a line in the table of equipments */
function addFieldEquipment() {

//    $('#alertProd').empty();
//    cntEquip++;

    var line = '<tr><td></td>';
    line += '<td></td>';      // Identifiant du produit
    line += '<td><input class="serialNumber" name="serial"></td>'; // Numéro de série
    line += '</td><td><input class="custInput" type="text" name="note"></td>';   // Note
    line += '<td name="reponseServeur"></td>';   // Réponse serveur
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable removeEquipment"></td></tr>'; // supprimer
    $(line).appendTo('#equipmentTable');

//    $('input.serialNumber[cntEquip="' + cntEquip + '"]').first().focus();

    $(".serialNumber").keyup(function (e) {

        if (e.keyCode === 13 && $(this).attr('valider') !== 'true') { // code for "Enter"
            var serial = $(this).val();
            checkStockForEquipment(serial, $(this));
        }
    });
}

function initDeleteEquipment(serial) {
    $('.removeEquipment').click(function () {
        $(this).parent().parent().remove();
        var index = newEquipments.indexOf(serial);
        if (index !== -1) {
            newEquipments.splice(index, 1);
        }
    });
}

/* Add a line in the table of product */
function addFieldProduct(productId, qty, nb_prod_in_stock) {

    var diff = nb_prod_in_stock - qty;
    var line = '<tr id="' + productId + '">';
    line += '<td>' + productId + '</td>';
    line += '<td id="quantity" style="border-right:none">' + qty + '</td>';
    line += '<td style="border-left:none"><input type="number" class="custInput" style="width: 40px" value=0 min=-' + qty + ' max=' + diff + '> <img src="css/ok.ico" class="clickable modify"></td>';
    line += '<td id="prodRestant">' + diff + '</td>';
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove"></td></tr>';
    $(line).appendTo('#productsTable');
    $('.remove').click(function () {
        $(this).parent().parent().remove();
        newProducts = newProducts.filter(function (obj) {
            return obj.id_product !== productId;
        });
    });
    $('.modify').click(modifyQuantity);

    var newProduct = {
        id_product: productId,
    };
    newProducts.push(newProduct);
}

function modifyQuantity() {
    var idLine = $(this).parent().parent().attr('id');
    var modifyValue = parseInt($('table#productsTable tr#' + idLine + ' td input').val());
    var oldQty = parseInt($('table#productsTable tr#' + idLine + ' td#quantity').text());
    var oldStock = parseInt($('table#productsTable tr#' + idLine + ' td#prodRestant').text());
    var newQty = oldQty + modifyValue;
    var newStock = oldStock - modifyValue;
    $('table#productsTable tr#' + idLine + ' td#quantity').text(newQty);
    $('table#productsTable tr#' + idLine + ' td#prodRestant').text(newStock);
    $('table#productsTable tr#' + idLine + ' td input').attr('min', -(newQty));
    $('table#productsTable tr#' + idLine + ' td input').attr('max', newStock);
    $('table#productsTable tr#' + idLine + ' td input').val('');
    $.each(newProducts, function () {
        if (this.id_product === idLine) {
            this.qty = newQty;
        }
    });
}


/**
 * 
 * @param String idElement id of the element to append the message in
 * @param String message the message you want to displ  ay
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