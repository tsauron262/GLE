/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idEntrepotStart;
var idEntrepotEnd;
//var cntProduct = 0;

var products = [];



/**
 * Ajax call
 */

function checkProductByRef(ref) {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/transfertequipment/interface.php",
        data: {
            ref: ref,
            idEntrepotStart: idEntrepotStart,
            action: 'checkProductByRef'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            if (outParsed.error === 'unknown_product') {
                return;
            }
            if (outParsed.stock === 'no_row') {
                setMessage('alertProd', 'L\'entrepot de départ ne possède pas ce produit.', 'error');
            } else if (outParsed.id === false) {
                setMessage('alertProd', 'Produit non renseigné dans la base de donnée.', 'error');
            } else if (outParsed.isEquipment) {
                addFieldEquipment(outParsed.id, outParsed.refUrl, outParsed.serial, outParsed.label);
            } else if ($('table#productTable tr#' + outParsed.id).length !== 0) {
                addQuantity(outParsed.id, 1);
            } else {
                addFieldProduct(outParsed.id, 1, outParsed.stock, outParsed.label, outParsed.refUrl);
            }
        }
    });
}


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
                setMessage('alertProd', 'L\'entrepot de départ ne possède pas ce produit.', 'error');
            } else if (nb_product_in_entrepot < qty) {
                setMessage('alertProd', 'Il n\'y a que ' + nb_product_in_entrepot + ' produit(s) dans cet entrepot et vous souhaitez en transférer ' + qty + '.', 'error');
            } else if ($('table#productTable tr#' + idProduct).length !== 0) {
                addQuantity(idProduct, qty);
            } else {
                addFieldProduct(idProduct, qty, nb_product_in_entrepot, outParsed.label, outParsed.refUrl);
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
//    addFieldProduct(151, 2, 10, "test", '15554');
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
            if (products.find(obj => obj.id_product === productId) !== undefined) {
                setMessage('alertProduct', 'Le produit est déjà dans le tableau avec l\'identifiant ' + productId + '. Pour changer la quantité de ce produit, veuillez supprimer la ligne et la recréer', 'error');
            } else {
                checkStockForProduct(productId, qty);
            }
        } else {
            setMessage('alertProduct', 'Veuillez sélectionner un produit pour l\'ajouter au tableau.', 'error');
        }
    });

    $('#entrepotStart').on('change', function () {
        idEntrepotStart = $(this).val();
    });
    $('#entrepotEnd').on('change', function () {
        idEntrepotEnd = $(this).val();
    });

    $('#enregistrer').click(function () {
        if (idEntrepotStart === idEntrepotEnd) {
            setMessage('alertEnregistrer', 'L\'entrepot de départ doit être différent de celui d\'arrivé.', 'error');
        }
    });

    $("input[name=refScan]").on('keyup', function (e) {
        if (e.keyCode === 13) { // code for "Enter"
            prepareAjax($(this), e);
        }
    });

    $("input[name=refScan]").on('keydown', function (e) {
        if (e.keyCode === 9) { // code for "Tab"
            prepareAjax($(this), e);
        }
    });
}

function prepareAjax(element, event) {
    var ref = element.val();
    if (ref !== '') {
        checkProductByRef(ref);
        element.val('');
    }
    element.focus();
    event.preventDefault();
}


/* Add a line in the table of equipments */
function addFieldEquipment(id, refUrl, serial, label) {

//    $('#alertProd').empty();
//    cntProduct++;

    var line = '<tr id="' + serial + '">';
    line += '<td>' + id + '</td>';    // id
    line += '<td>' + refUrl + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td style="border-right:none"></td>';   // Quantité
    line += '<td style="border-left:none"></td>';   // Modifier
    line += '<td id="stock"></td>'; // prod restant
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove "></td></tr>'; // supprimer
    $(line).appendTo('#productTable');
}

/* Add a line in the table of product */
function addFieldProduct(productId, qty, nb_prod_in_stock, label, refUrl) {

//    cntProduct++;
    productId = parseInt(productId);
    qty = parseInt(qty);
    nb_prod_in_stock = parseInt(nb_prod_in_stock);

    var diff = nb_prod_in_stock - qty;
    var line = '<tr id=' + productId + '>';
    line += '<td>' + productId + '</td>';
    line += '<td>' + refUrl + '</td>';
    line += '<td></td>';
    line += '<td>' + label + '</td>';
    line += '<td name="quantity" style="border-right:none">' + qty + '</td>';
    line += '<td style="border-left:none"><input name="modify" type="number" class="custInput" style="width: 40px" value=1 min=1 max=' + nb_prod_in_stock + '><img src="css/ok.ico" class="clickable modify"></td>';
    line += '<td name="stock">' + diff + '</td>';
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove"></td></tr>';
    $(line).appendTo('#productTable');
    initRemoveLine(productId);

    $('table#productTable tr#' + productId + ' .modify').click(modifyQuantity);

    var newProduct = {
        id_product: productId,
        qty: qty
    };
    products.push(newProduct);
}

function initRemoveLine(idTr) {
    $('.remove').click(function () {
        if ($(this).parent().parent().attr('id') !== undefined) {
            $(this).parent().parent().remove();
            products = products.filter(function (obj) {
                return obj.id_product !== idTr;
            });
        }
    });
//    cntProduct--;
}

function modifyQuantity() {
    var idLine = $(this).parent().parent().attr('id');
    var selectoTr = 'table#productTable tr#' + idLine;
    var modifyValue = parseInt($(selectoTr + ' td input[name=modify]').val());
    var oldQty = parseInt($(selectoTr + ' td[name=quantity]').text());
    var oldStock = parseInt($(selectoTr + ' td[name=stock]').text());
    var newStock = oldStock + oldQty - modifyValue;
    if (newStock >= 0) {
        $(selectoTr + ' td[name=quantity]').text(modifyValue);
        $(selectoTr + ' td[name=stock]').text(newStock);
        $.each(products, function () {
            if (this.id_product === idLine) {
                this.qty = modifyValue;
            }
        });
    } else {
        setMessage('alertProd', "Les stockes de l'entrepot de départ ne sont pas suffisant.", 'error');
    }
    $(selectoTr + ' td input[name=modify]').val(1);
}

function addQuantity(idProduct, qty) {
    var selectorQuantity = 'table#productTable tr#' + idProduct + ' td[name=quantity]';
    var oldQty = parseInt($(selectorQuantity).text());
    var newQty = parseInt(qty) + oldQty;
    $('table#productTable tr#' + idProduct + ' td input[name=modify]').val(newQty);
    $('table#productTable tr#' + idProduct + ' img.modify').click();
    $.each(products, function () {
        if (this.id_product === idProduct) {
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