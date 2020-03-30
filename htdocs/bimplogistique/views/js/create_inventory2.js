

function getProduct() {
    var number = getNbOfProduct();
    BimpAjax('addProductInput', {
        number: number,
    }, null, {
        processing_msg: 'Insertion en cours',
        success: function (result, bimpAjax) {
            $('div[name=div_products]').append(result.data);
            addEventForUnitPorduct(number);
            refreshNames();
        }, error: function(result, bimpAjax) {
            alert('error voir cons log');
            console.log(result);
        }
    });
}

function setProductUrl(div, id_prod) {
    
    if(0 < +id_prod) {
        BimpAjax('getProductUrl', {
            id_prod: id_prod,
        }, null, {
            success: function (result, bimpAjax) {
                var div_url = div.find('div[url_prod]');
//                div.append('<input type="test" name="prod1" value="44"/>');
//                $('div.date_mouvement_inputContainer').append('<input type="test" name="prod1" value="44"/>');
                div_url.empty();
                div_url.append(result.url);

            }, error: function(result, bimpAjax) {
                alert('error voir cons log');
                console.log(result);
            }
        });
    }
}

function getNbOfProduct() {
//    return +$('div[name=div_products] > div[is_product]').length + 1;
    var last = $('div[is_product]').last().attr('name');
    if(last == undefined)
        return 1;
    
    return parseInt(last.replace( /^\D+/g, '')) + 1;
}

function deleteProduct() {
    $('div[name=div_products]').empty();
}

function refreshNames() {
    var cnt = 1;

    $('div[name=div_products] > div[is_product]').each(function(){
        $(this).find('strong').text('Produit n°' + cnt);
        cnt++;
    });
}

function deleteUnitProduct(item) {
    item.parent().remove();
    refreshNames();
}

function addEventForUnitPorduct(number) {
    $('div[name=div_products] > div[name=cnt_prod' + number + '] > input').change(function(){
        var id_prod = parseInt($(this).val());
        if(id_prod > 0)
            setProductUrl($(this).parent(), $(this).val());
    });
}
