/**
 * Globals variable
 */

/* global DOL_URL_ROOT, productid */

var idEntrepotStart;
var idEntrepotEnd;
var cntProduct = 0;

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
    var qtyToAdd = parseInt($('input#qty').val());
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            ref: ref,
            idEntrepotStart: idEntrepotStart,
            action: 'checkProductByRef'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5316.', 'error');
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            if (outParsed.error.length !== 0) {
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
                addQuantity(outParsed.id, qtyToAdd);
            } else {
                addFieldProduct(outParsed.id, qtyToAdd, outParsed.stock, outParsed.label, outParsed.refUrl);
            }
        }
    });
}


function checkStockForProduct(idProduct, qty) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            idProduct: idProduct,
            idEntrepotStart: idEntrepotStart,
            action: 'checkStockForProduct'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5315.', 'error');
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            if (outParsed.error.length !== 0) {
                setMessage('alertProd', outParsed.error, 'error');
                return;
            }
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

function saveproducts(localCntProduct) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            products: products,
            idEntrepotStart: idEntrepotStart,
            idEntrepotEnd: idEntrepotEnd,
            action: 'transfertAll'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5314.', 'error');
        },
        success: function (out) {
            var outParsed = JSON.parse(out);
            if (outParsed.errors.length !== 0) {
                setMessage('alertEnregistrer', outParsed.errors, 'error');
            } else if (1 < localCntProduct) {
                setMessage('alertEnregistrer', localCntProduct + ' Groupes de produit ont été enregistré avec succès.', 'mesgs');
            } else {
                setMessage('alertEnregistrer', localCntProduct + ' Groupe de produit a été enregistré avec succès.', 'mesgs');
            }
        }
    });
}


/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepotStart').select2({placeholder: 'Rechercher ...'});
    $('#entrepotEnd').select2({placeholder: 'Rechercher ...'});
    $('#entrepotStart option:selected').prop('selected', true);
    $('#entrepotStart').trigger('change');
    $('#entrepotEnd option:selected').prop('selected', true);
    $('#entrepotEnd').trigger('change');
    initEvents();
    initIE('input[name=refScan]', 'checkProductByRef', 'input#qty');

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
                setMessage('alertProd', 'Le produit est déjà dans le tableau avec l\'identifiant ' + productId + '. Pour changer la quantité de ce produit, veuillez supprimer la ligne et la recréer', 'error');
            } else {
                checkStockForProduct(productId, qty);
            }
        } else {
            setMessage('alertProd', 'Veuillez sélectionner un produit pour l\'ajouter au tableau.', 'error');
        }
    });

    $('#entrepotStart').on('change', function () {
        if ($(this).prop('preventOnClickEvent')) {
            $(this).prop('preventOnClickEvent', false);
        } else if (idEntrepotStart === undefined) {
            idEntrepotStart = $(this).val();
            $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', true);
            $('#divEntrepotEnd').css('visibility', 'visible');
            $('#divEntrepotEnd').addClass('fade-in');
        } else if (products.length !== 0) {
            var confirmed = confirm('Vous etes sur le point d\'annuler tous les enregistrements, continuer ?');
            if (confirmed) {
                $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', false);
                idEntrepotStart = $(this).val();
                $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', true);
                $('table#productTable tr[id]').remove();
                products = [];
                cntProduct = 0;
            } else {
                $('#entrepotStart').prop('preventOnClickEvent', true);
                $('#entrepotStart').select2('val', idEntrepotStart, true);
            }
        } else {
            $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', false);
            idEntrepotStart = $(this).val();
            $('#entrepotEnd option[value=' + idEntrepotStart + ']').prop('disabled', true);
        }
    });

    $('#entrepotEnd').on('change', function () {
        if (idEntrepotEnd === undefined) {
            idEntrepotEnd = $(this).val();
            $('#allTheFiche').css('visibility', 'visible');
            $('#allTheFiche').addClass('fade-in');
        }
        if (idEntrepotEnd !== undefined) {
            $('#entrepotStart option[value=' + idEntrepotEnd + ']').prop('disabled', false);
        }
        idEntrepotEnd = $(this).val();
        $('#entrepotStart option[value=' + idEntrepotEnd + ']').prop('disabled', true);
    });

    $('#enregistrer').click(function () {
        if (idEntrepotStart === idEntrepotEnd) {
            setMessage('alertEnregistrer', 'L\'entrepot de départ doit être différent de celui d\'arrivé.', 'error');
        } else if (cntProduct !== 0 && confirm('Etes-vous sur de vouloir transférer ' + cntProduct + ' groupes de produit ?')) {
            saveproducts(cntProduct);
            $('table#productTable tr[id]').remove();
            products = [];
            cntProduct = 0;
        } else {
            setMessage('alertEnregistrer', 'Vous devez ajouter des produits avant de les transférer.', 'error');
        }
    });
}


/* Add a line in the table of equipments */
function addFieldEquipment(productId, refUrl, serial, label) {

    cntProduct++;
    productId = parseInt(productId);
    var line = '<tr id="' + serial + '">';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + productId + '</td>';    // id
    line += '<td>' + refUrl + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td style="border-right:none"></td>';   // Quantité
    line += '<td style="border-left:none"></td>';   // Modifier
    line += '<td id="stock"></td>'; // prod restant
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove "></td></tr>'; // supprimer
    $(line).appendTo('#productTable tbody');
    initRemoveLine(serial);
    var newEquipment = {
        is_equipment: true,
        id_product: productId,
        serial: serial
    };
    products.push(newEquipment);
    document.querySelector("#bipAudio2").play();
}

/* Add a line in the table of product */
function addFieldProduct(productId, qty, nb_prod_in_stock, label, refUrl) {

    cntProduct++;

    productId = parseInt(productId);
    qty = parseInt(qty);
    nb_prod_in_stock = parseInt(nb_prod_in_stock);

    var diff = nb_prod_in_stock - qty;
    var line = '<tr id=' + productId + '>';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + productId + '</td>';
    line += '<td>' + refUrl + '</td>';
    line += '<td></td>';
    line += '<td>' + label + '</td>';
    line += '<td name="quantity" style="border-right:none">' + qty + '</td>';
    line += '<td style="border-left:none"><input name="modify" type="number" class="custInput" style="width: 40px" value=1 min=1 max=' + nb_prod_in_stock + '> <img src="css/ok.ico" class="clickable modify"></td>';
    line += '<td name="stock">' + diff + '</td>';
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove"></td></tr>';
    $(line).appendTo('#productTable tbody');
    initRemoveLine(productId);

    $('table#productTable tr#' + productId + ' .modify').click(modifyQuantity);

    var newProduct = {
        is_equipment: false,
        id_product: productId,
        qty: qty
    };
    products.push(newProduct);
    document.querySelector("#bipAudio").play();
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
        cntProduct = 1;
        $('table#productTable td[name=cnt]').each(function () {
            $(this).text(cntProduct);
            cntProduct++;
        })
        cntProduct--;
    });
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
        products.forEach(function (prod) {
            if (prod.id_product === parseInt(idLine)) {
                prod.qty = modifyValue;
            }
        });
    } else {
        setMessage('alertProd', "Les stockes de l'entrepot de départ ne sont pas suffisant.", 'error');
    }
}

function addQuantity(idProduct, qty) {
    console.log("add quant " +qty);
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

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px; color:black">' + message + '</div>');
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

