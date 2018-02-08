/**
 * Globals variable
 */

/* global DOL_URL_ROOT, productid */

var idEntrepotStart;
var idEntrepotEnd;
//var cntProduct = 0;

// list of product
// if is equipment, fields are:
//        is_equipment: false,
//        id_product: productId,
//        serial: serial
// else:
//        is_equipment: true,
//        id_product: productId,
//        qty: qty

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
            if (outParsed.error !== '') {
                setMessage('alertProd', outParsed.error, 'error');
                return;
            }
            if (outParsed.stock < 1) {
                setMessage('alertProd', 'L\'entrepot de départ ne possède pas ' + (outParsed.isEquipment ? 'cet equipement' : 'ce produit') + '.', 'error');
            } else if (outParsed.id === false) {
                setMessage('alertProd', 'Produit non renseigné dans la base de donnée.', 'error');
            } else if (outParsed.isEquipment) {
                if ($('table#productTable tr#' + outParsed.serial).length === 0)
                    addFieldEquipment(outParsed.id, outParsed.refUrl, outParsed.serial, outParsed.label);
                else
                    setMessage('alertProd', "Cet équipement vient d'être scanné.", 'error');
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
            var nb_product_in_entrepot = parseInt(outParsed.stock);
            if (nb_product_in_entrepot < 1) {
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

//    addFieldProduct(151, 2, 10, "test", '15554');
    initEvents();
    $('#entrepotStart').select2();
    $('#entrepotEnd').select2();
    $('#entrepotEnd option:selected').next().prop('selected', true);
    $('#entrepotEnd').trigger('change');

    idEntrepotStart = $('#entrepotStart').val();
    idEntrepotEnd = $('#entrepotEnd').val();
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
        if (products.length !== 0) {
            if (confirm('Vous etes sur le point d\'annuler tous les enregistrements, continuer ?')) {
                $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', false);
                idEntrepotStart = $(this).val();
                $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', true);
                products = [];
                $('table#productTable tr[id]').remove();
            }
        } else {
            $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', false);
            idEntrepotStart = $(this).val();
            $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', true);
        }
    });
    $('#entrepotEnd').on('change', function () {
        $('#entrepotStart option[value=' + idEntrepotEnd + ']').prop('disabled', false);
        idEntrepotEnd = $(this).val();
        $('#entrepotStart option[value=' + idEntrepotEnd + ']').prop('disabled', true);
    });

    $('#enregistrer').click(function () {
        if (idEntrepotStart === idEntrepotEnd) {
            setMessage('alertEnregistrer', 'L\'entrepot de départ doit être différent de celui d\'arrivé.', 'error');
        }
    });

    var element = $("input[name=refScan]");


    element.on('keyup', function (e) {
        if (e.keyCode === 13 || e.keyCode === 9 || e.key == "Enter") { // code for "Enter"
            prepareAjax($(this), e);
            e.preventDefault();
        }
    });

    element.focus();
}

//    element.on('keyup', function (e) {
//        if (e.keyCode === 13) { // code for "Enter"
//            prepareAjax($(this), e);
//        }
//    });
//
//    element.on('keydown', function (e) {
//        if (e.keyCode === 9) { // code for "Tab"
//            prepareAjax($(this), e);
//        }
//    });
//
//    element.focus();
//}

function prepareAjax(element) {
    var ref = element.val();
    if (ref !== '') {
        checkProductByRef(ref);
        element.val('');
    }
    element.focus();
}


/* Add a line in the table of equipments */
function addFieldEquipment(productId, refUrl, serial, label) {

//    cntProduct++;
    productId = parseInt(productId);
    var line = '<tr id="' + serial + '">';
    line += '<td>' + productId + '</td>';    // id
    line += '<td>' + refUrl + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td style="border-right:none"></td>';   // Quantité
    line += '<td style="border-left:none"></td>';   // Modifier
    line += '<td id="stock"></td>'; // prod restant
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove "></td></tr>'; // supprimer
    $(line).appendTo('#productTable');
    initRemoveLine(serial);
    var newEquipment = {
        is_equipment: true,
        id_product: productId,
        serial: serial
    };
    products.push(newEquipment);
    document.querySelector("#bipAudio2").play();
    console.log(products);
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
        is_equipment: false,
        id_product: productId,
        qty: qty
    };
    products.push(newProduct);
    document.querySelector("#bipAudio").play();
    console.log(products);
}

function initRemoveLine(idTr) {
    $('table#productTable tr#' + idTr + ' td img.remove').click(function () {
        $(this).parent().parent().remove();
        products = products.filter(function (obj) {
            if (obj.is_equipment)
                return obj.serial !== idTr;
            else
                return obj.id_product !== idTr;
        });
        console.log(products);
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
    if (newQty > $('table#productTable tr#' + idProduct + ' td input[name=modify]').attr('max'))
        $('table#productTable tr#' + idProduct + ' td input[name=modify]').val(oldQty);

    document.querySelector("#bipAudio").play();
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
    if (type === "error")
        document.querySelector("#bipError").play();

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}


var oldCode = "";
function traiteCode(code) {
    if (code != oldCode) {
        $(".custInput").val(code);
        prepareAjax($(".custInput"));
    }
    oldCode = code;
}

