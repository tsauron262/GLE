function Part(name, num, type) {
    this.name = name;
    this.num = num;
    this.type = type;
}

function CartProduct(gpe, id) {
    this.gpe = gpe;
    this.id = id;
    this.qty = 1;
}

function PartsManager() {
    var ptr = this;
    this.groups = {
        0 : 'Général',
        1 :'Visuel',
        2 :'Affichage',
        3 :'Stockage',
        4 :'Périphériques d\'entrées',
        5 :'Cartes',
        6 :'Alimentation',
        7 :'Impression',
        8 :'Périphériques multi-fonctions',
        9 :'Périphériques de communication',
        'A' :'Partage',
        'B' :'iPhone',
        'E' :'iPod',
        'F' :'iPad'
    }
    this.parts = [];
    this.cartProds = [];
    this.nextCartProdId = 0;
    this.nbrProds = 0;

    this.removeParts = function() {
        for (gpe in this.parts) {
            for (id in this.parts[gpe]) {
                delete this.parts[gpe][id];
            }
            delete this.parts[gpe];
        }
        delete this.parts;
        this.parts = [];
    };
    this.addPart = function(group, name, num, type) {
        if (group === ' ')
            group = 0;
        if (!this.parts[group])
            this.parts[group] = [];
        this.parts[group].push(new Part(name, num, type));
    };
    this.displayParts = function() {
        var $container = $('#partsListContainer');
        var ths = '<th style="min-width: 350px">Nom</th>';
        ths += '<th style="min-width: 100px">Ref.</th>';
        ths += '<th style="min-width: 100px">Type</th>';
        ths += '<th></th>';

        for (gpe in this.parts) {
            $('#typeFiltersContent').append('<input type="checkbox" checked id="typeFilter_'+gpe+'"/><label for="typeFilter_'+gpe+'">'+this.groups[gpe]+'</label><br/>');
            var html = '<div id="partGroup_'+gpe+'" class="partGroup">';
            html += '<div class="partGroupName closed">'+this.groups[gpe]+'<span class="partsNbr">('+this.parts[gpe].length+' produits)</span></div>';
            html += '<div class="partsList">';
            html += '<table><thead>'+ths+'</thead><tbody>';
            var odd = true;
            for (id in this.parts[gpe]){
                html += '<tr class="partRow_'+id+' partRow';
                if (odd)
                    html += '  oddRow';
                else
                    html += ' evenRow';
                html += '">';
                html += '<td class="partName">'+this.parts[gpe][id].name+'</td>';
                html += '<td>'+this.parts[gpe][id].num+'</td>';
                html += '<td>'+this.parts[gpe][id].type+'</td>';
                html += '<td><span id="add_'+gpe+'_'+id+'" class="addToOrder activated" onclick="PM.addToCart($(this))">Commander</span></td>';
                html += '</tr>';
                odd = !odd;
            }
            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
            $container.append(html);
        }
        this.setEvents();
    };
    this.setEvents = function() {
        $('#cartTitle').mouseover(function() {
            if ($('#cartContent').css('display') == 'none')
                $('#cartContent').fadeIn(250);
            else {
                $('#cartContent').stop().css('opacity', 1);
            }
        }).mouseleave(function() {
            $('#cartContent').fadeOut(250);
        });
        $('#cartContent').mouseover(function() {
            $('#cartContent').stop().css('opacity', 1);
        }).mouseout(function() {
            $('#cartContent').fadeOut(250);
        });
        $('div.partGroupName.closed').click(function() {
            ptr.openPartsGroup($(this));
        });
        $('div.partGroupName.opened').click(function() {
            ptr.closePartsGroup($(this));
        });
        $('tr.partRow').mouseover(function() {
            $(this).find('td').css({
                'border-top': '1px solid #22A022',
                'border-bottom': '1px solid #22A022',
                'color': '#000'
            });
        }).mouseout(function() {
            $(this).find('td').css({
                'border-top': 'none',
                'border-bottom': '1px solid #A0A0A0',
                'color': '#464646'
            });
        });
        $('#filterTitle').mouseover(function(){
            showTypeFilters();
        }).mouseout(function() {
            hideTypeFilters();
        });
        $('#typeFiltersContent').mouseover(function(){
            showTypeFilters();
        }).mouseout(function() {
            hideTypeFilters();
        });
        $('#filterCheckAll').click(function() {
            ptr.showAllPartsGroup();
        });
        $('#filterHideAll').click(function() {
            ptr.hideAllPartsGroup();
        });
        $('#typeFiltersContent input').change(function() {
            var gpe = $(this).attr('id').replace(/^typeFilter_(.+)$/, '$1');
            if ($(this).prop('checked')) {
                $('#partGroup_'+gpe).stop().css('display', 'none').slideDown(250);
            } else {
                $('#partGroup_'+gpe).slideUp(250);
            }
        });
    };
    this.openPartsGroup = function($gpe) {
        $gpe.parent().children('div.partsList').slideDown(250);
        $gpe.attr('class', 'partGroupName opened').off('click').click(function() {
            ptr.closePartsGroup($gpe);
        });
    };
    this.closePartsGroup = function($gpe) {
        $gpe.parent().children('div.partsList').slideUp(250);
        $gpe.attr('class', 'partGroupName closed').off('click').click(function() {
            ptr.openPartsGroup($gpe);
        });
    };
    this.showPartsGroup = function(gpe) {
        $('#typeFilter_'+gpe).attr('checked', '');
        $('#partGroup_'+gpe).stop().css('display', 'none').slideDown(250);
    };
    this.hidePartsGroup = function(gpe) {
        $('#typeFilter_'+gpe).removeAttr('checked');
        $('#partGroup_'+gpe).stop().hide();
    };
    this.showAllPartsGroup = function() {
        $('#typeFiltersContent').find('input').each(function() {
            $(this).attr('checked', '');
        });
        $('.partGroup').each(function() {
            $(this).stop().show();
        });
    };
    this.hideAllPartsGroup = function() {
        $('#typeFiltersContent').find('input').each(function() {
            $(this).removeAttr('checked');
        });
        $('.partGroup').each(function() {
            $(this).slideUp(250);
        });
    };
    this.resetPartsDisplay = function() {
        this.showAllPartsGroup();
        $('tr.partRow').each(function() {
            $(this).show();
        });
        for (gpe in this.parts) {
            $('#partGroup_'+gpe).find('span.partsNbr').html('('+this.parts[gpe].length+' produits)').css('color', '#505050');
        }
    };

    this.filterByKeywords = function(dataToCheck) {
        this.unsetSearch();
        var kw = [];
        $('#curKeywords').children('div').each(function(){
            kw.push($(this).find('span.keyword').text());
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        for (gpe in ptr.parts) {
            var n = 0;
            for (id in ptr.parts[gpe]) {
                var display = true;
                for (i in kw) {
                    var str = null;
                    switch (dataToCheck) {
                        case 'name':
                            str = ptr.parts[gpe][id].name;
                            break;

                        case 'num':
                            str = ptr.parts[gpe][id].num;
                            break;
                    }
                    if (str) {
                        if (str.search(kw[i]) < 0) {
                            if (str.search(kw[i].toLowerCase()) < 0) {
                                if (str.search(kw[i].toUpperCase()) < 0) {
                                    display = false;
                                }
                            }
                        }
                    }
                }
                if (display) {
                    $('#partGroup_'+gpe).find('tr.partRow_'+id).show();
                    n++;
                }
            }
            $('#partGroup_'+gpe).find('span.partsNbr').html('('+n+' produits)');
            if (n > 0)
                $('#partGroup_'+gpe).find('span.partsNbr').css('color', '#00780A');
            else
                $('#partGroup_'+gpe).find('span.partsNbr').css('color', '#960000');
        }
    };
    this.unsetKeywordsFilter = function() {
        $('#curKeywords').find('div').each(function() {
            $(this).remove();
        });
        $('#searchResult').html('');
        $('tr.partRow').each(function(){
            $(this).show();
        });
        for (gpe in this.parts) {
            $('#partGroup_'+gpe).find('span.partsNbr').html('('+this.parts[gpe].length+' produits)').css('color', '#505050');
        }
    };
    this.searchPartByNum = function(num) {
        this.unsetSearch();
        $('#searchResult').html('<div id="partSearchNum"><span class="searchNum">'+num+'</span><span class="removeSearch" onclick="PM.unsetSearch()"></span></div>');
        $('#curKeywords').find('div').each(function() {
            $(this).remove();
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        var n = 0;
        for (gpe in this.parts) {
            $('#partGroup_'+gpe).find('span.partsNbr').html('');
            var check = false;
            for (id in this.parts[gpe]) {
                if (this.parts[gpe][id].num == num) {
                    check = true;
                    n++;
                    $('#partGroup_'+gpe).find('tr.partRow_'+id).show();
                }
            }
            if (check) {
                this.showPartsGroup(gpe);
                this.openPartsGroup($('#partGroup_'+gpe).find('div.partGroupName'));
            } else {
                this.hidePartsGroup(gpe);
                this.closePartsGroup($('#partGroup_'+gpe).find('div.partGroupName'));
                $('#partGroup_'+gpe).find('span.partsNbr').html('');
            }
        }
        if (!n) {
            $('#searchResult').append('<p class="error">Aucun composant compatible ne correspond à ce numéro</p>');
        }
    };
    this.unsetSearch = function() {
        if ($('#partSearchNum').length) {
            $('#searchResult').html('');
            this.resetPartsDisplay();
        }
    };

    this.addToCart = function($span) {
        if (($span).hasClass('deactivated'))
            return;

        var gpe = $span.attr('id').replace(/^add_(.*)_(.*)$/, '$1');
        var id = $span.attr('id').replace(/^add_(.*)_(.*)$/, '$2');
        $span.attr('class', 'addToOrder deactivated');
        this.cartProds[this.nextCartProdId] = new CartProduct(gpe, id);
        this.nbrProds++;
        $('#noProducts').hide();
        $('#orderSubmitContainer').show();
        var html = '<tr id="cartProd_'+this.nextCartProdId+'">';
        html += '<td>'+this.parts[gpe][id].name+'</td>';
        html += '<td class="ref">'+this.parts[gpe][id].num+'</td>';
        html += '<td><input type="number" value="1" class="prodQty" size="8"/></td>';
        html += '<td><span class="removeCartProduct" onclick="PM.removeFromCart($(this))"></span></td>';
        html += '</tr>';
        $('#cartProducts').show().find('tbody').append(html);
        $('#nbrCartProducts').html(ptr.nbrProds);
        this.nextCartProdId++;
    };
    this.removeFromCart = function($span) {
        $span.off('click');
        var $tr = $span.parent('td').parent('tr');
        var id = $tr.attr('id').replace(/^cartProd_(\d+)$/, '$1');
        $tr.fadeOut(250, function(){
            $(this).remove();
            if (!$('#cartProducts').find('tbody').find('tr').length) {
                $('#cartProducts').hide();
                $('#orderSubmitContainer').slideUp(250);
                $('#noProducts').slideDown(250);
            }
        });
        this.nbrProds--;
        $('#nbrCartProducts').html(ptr.nbrProds);
        var $addSpan = $('#partGroup_'+this.cartProds[id].gpe).find('tr.partRow_'+this.cartProds[id].id).find('span.addToOrder');
        $addSpan.attr('class', 'addToOrder activated').click(function() {
            ptr.addToCart($(this));
        });
        delete this.cartProds[id];
        this.cartProds[id] = null;
    };
}

var PM = new PartsManager();

function showTypeFilters() {
    $('#typeFiltersContent').stop().css('display', 'none').slideDown(250);
}
function hideTypeFilters() {
    $('#typeFiltersContent').stop().slideUp(250);
}

function addKeywordFilter() {
    var kw = $('#keywordFilter').val();
    if (!kw) {
        $('#curKeywords').append('<p class="error">Veuillez entrer un mot-clé</p>');
        $('#curKeywords').find('p.error').fadeOut(2000, function(){
            $(this).remove();
        });
        return;
    }
    if (!/^[a-zA-Z0-9\-\_ ]+$/.test(kw)) {
        $('#curKeywords').append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
        $('#curKeywords').find('p.error').fadeOut(5000, function(){
            $(this).remove();
        });
        return;
    }
    $('#curKeywords').find('p.error').stop().remove();
    $('#curKeywords').append('<div><span class="keyword">'+kw+'</span><span class="removeKeyWord" onclick="removeKeywordFilter($(this))"></span></div>');
    $('#keywordFilter').val('');
    PM.filterByKeywords('name');
}
function removeKeywordFilter($span) {
    $span.parent('div').remove();
    if ($('#curKeywords').find('div').length)
        PM.filterByKeywords('name');
    else
        PM.unsetKeywordsFilter();
}
function searchPartByNum() {
    var search = $('#searchPartInput').val();
    if (!search) {
        $('#searchResult').append('<p class="error">Veuillez entrer un code produit</p>');
        $('#searchResult').find('p.error').fadeOut(2000, function(){
            $(this).remove();
        });
        return;
    }
    if (!/^[a-zA-Z0-9\-\_ ]+$/.test(search)) {
        $('#searchResult').append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
        $('#searchResult').find('p.error').fadeOut(5000, function(){
            $(this).remove();
        });
        return;
    }
    $('#searchPartInput').val('');
    PM.searchPartByNum(search);
}

function getXMLHttpRequest() {
    var xhr = null;
    if (window.XMLHttpRequest || window.ActiveXObject) {
        if (window.ActiveXObject) {
            try {
                xhr = new ActiveXObject("msxml2.XMLHTTP");
            }
            catch(e) {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
        }
        else {
            xhr = new XMLHttpRequest();
        }
    }
    return xhr;
}
function onRequestResponse(xhr, requestType) {
    switch (requestType) {
        case 'newSerial':
            $('#serialResult').slideUp(250);
            $('#productInfos').html(xhr.responseText);
            PM.displayParts();
            $('#productInfos').slideDown(250);
            break;
    }
}
function setGetRequest(requestType, requestParams) {
    var xhr = getXMLHttpRequest();
    xhr.onreadystatechange = function(){
        //alert('state: ' + xhr.readyState + ', status: ' +xhr.status);
        var RT = requestType;
        if((xhr.readyState == 4) && ((xhr.status == 200) || (xhr.status == 0))) {
            onRequestResponse(xhr, RT);
        }
    }
    xhr.open("GET", DOL_URL_ROOT+'/apple/requestProcess.php?action='+requestType+requestParams);
    xhr.send();
}

$(document).ready(function() {
    $('#serialSubmit').click(function() {
        $('#serialResult').slideUp(250);
        $('#productInfos').slideUp(250);
        PM.removeParts();
        if ($('#partsListContainer').length)
            $('#partsListContainer').html('');
        var serial = $('#serialInput').val();
        if (!serial) {
            $('#serialResult').html('<p class="error">Veuillez entrer un numéro de série</p>');
        } else if (!/^[0-9a-zA-Z]+$/.test(serial)) {
            $('#serialResult').html('<p class="error">Le format du numéro de série est incorrect</p>');
        } else {
            setGetRequest('newSerial', '&serial='+serial);
            $('#serialResult').html('<p>Requête en cours de traitement...</p>');
        }
        $('#serialResult').slideDown(250);
    });
});
