

function getProduct(input_name) {
    var number = getNbOfProduct(input_name);
    BimpAjax('addProductInput', {
        number: number,
        input_name: input_name
    }, null, {
        processing_msg: 'Insertion en cours',
        success: function (result, bimpAjax) {
            $('div[input_name="' + input_name + '"] div[name=div_products]').append(result.data);
            addEventForUnitPorduct(number, input_name);
            refreshNames(input_name);
        }, error: function(result, bimpAjax) {
            alert('error voir cons log');
            console.log(result);
        }
    });
}

function setProductUrl(div, id_prod, input_name) {
    
    if(0 < +id_prod) {
        BimpAjax('getProductUrl', {
            id_prod: id_prod,
        }, null, {
            success: function (result, bimpAjax) {
                var div_url = div.find('div[url_prod]');
                console.log(div_url);
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

function getNbOfProduct(input_name) {
//    return +$('div[name=div_products] > div[is_product]').length + 1;
    var last = $('div[input_name="' + input_name + '"] div[is_product]').last().attr('name');
    if(last == undefined)
        return 1;
    
    return parseInt(last.replace( /^\D+/g, '')) + 1;
}

function deleteProduct(input_name) {
    $('div[input_name="' + input_name + '"] div[name=div_products]').empty();
}

function refreshNames(input_name) {
    var cnt = 1;

    $('div[input_name="' + input_name + '"] div[name=div_products] > div[is_product]').each(function(){
        $(this).find('strong').text('Produit n°' + cnt);
        cnt++;
    });
}

function deleteUnitProduct(item, input_name) {
    item.parent().remove();
    refreshNames(input_name);
}

function addEventForUnitPorduct(number, input_name) {
    $('div[input_name="' + input_name + '"] div[name=div_products] > div[name=cnt_prod' + number + '] > input').change(function(){
        var id_prod = parseInt($(this).val());
        if(id_prod > 0)
            setProductUrl($(this).parent(), $(this).val());
    });
}
