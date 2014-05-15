var maxProdQty = 99;

var partsGroup = {
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
};
var partDataType = {
    'name': 'Nom',
    'num': 'Ref.',
    'type': 'Type',
    'price': 'Prix'
}

function CompTIACodes() {
    this.loadStatus = 'unloaded';
    this.codes = [];
    this.modifiers = [];

    this.addCode = function(grp, code, desc) {
        if (!this.codes[grp]) {
            this.codes[grp] = [];
        }
        this.codes[grp][code] = desc;
    };
    this.addModifier = function(modifier, desc) {
        this.modifiers[modifier] = desc;
    };
    this.endInit = function() {
        if ((this.loadStatus == 'loading') || (this.loadStatus == 'newLoadingTry')) {
            for (id in GSX.products) {
                if (GSX.products[id].cart) {
                    GSX.products[id].cart.onComptiaLoadingEnd();
                }
            }
            this.loadStatus = 'loaded';
        }
    };
    this.load = function() {
        if (this.loadStatus != 'unloaded')
            return;
        this.loadStatus = 'loading';
        for (id in GSX.products) {
            if (GSX.products[id].cart) {
                GSX.products[id].cart.onComptiaLoadingStart();
            }
        }
        setRequest('GET', 'loadCompTIACodes', 0, '');
    };
    this.appendCompTIACodesSelect = function($div, group) {
        if (!$div.length)
            return;

        if (!this.codes[group])
            return;

        var html = '<select class="compTIACodeSelect">';
        html += '<option value="0">Code symptôme</option>';
        for (code in this.codes[group]) {
            html += '<option value="'+code+'">'+code+' - '+this.codes[group][code]+'</option>';
        }
        html += '</select>';
        html += '<select class="compTIAModifierSelect">';
        html += '<option value="0">Modificateur</option>';
        for (mod in this.modifiers) {
            html += '<option value="'+mod+'">'+mod+' - '+this.modifiers[mod]+'</option>';
        }
        html += '</select>';
        $div.html(html);
    };
    this.onLoadFail = function() {
        if (this.loadStatus == 'loading') {
            this.loadStatus = 'newLoadingTry';
            setRequest('GET', 'loadCompTIACodes', 0, '');
        } else if (this.loadStatus == 'newLoadingTry') {
            this.loadStatus = 'fail';
            for (id in GSX.products) {
                if (GSX.products[id].cart) {
                    GSX.products[id].cart.onComptiaLoadingFail();
                }
            }
        }
    };
}

var CTIA = new CompTIACodes();

function Part(name, num, type, price) {
    this.name = name;
    this.num = num;
    this.type = type;
    this.price = price;
}

function CartProduct(gpe, id) {
    this.gpe = gpe;
    this.id = id;
    this.qty = 1;
}

function Cart(prodId, serial, PM) {
    var ptr = this;
    this.prodId = prodId;
    this.serial = serial;
    this.PM = PM;
    this.cartProds = [];
    this.nextCartProdId = 0;
    this.nbrProds = 0;
    this.$prod = $('#prod_'+prodId);

    this.onComptiaLoadingStart = function() {
        var $cart = this.$prod.find('.cartContent');
        if ($cart.length) {
            displayRequestMsg('requestProcess', 'Liste des codes compTIA en cours de chargement', $cart.find('.cartRequestResults'));
            $cart.find('.cartRequestResults').show();
            $cart.find('.noProducts').hide();
            $cart.find('.cartProducts').hide();
            $cart.find('.cartSubmitContainer').hide();
        }
    };
    this.onComptiaLoadingEnd = function() {
        var $cart = this.$prod.find('.cartContent');
        if ($cart.length) {
            $cart.find('th.comptiaCodeTitle').show();
            $cart.find('.cartRequestResults').html('').hide();
            if (this.nbrProds) {
                for (id in this.cartProds) {
                    if (this.cartProds[id]) {
                        var $td = $cart.find('tr.cartProd_'+id).find('td.compTIACodes');
                        if ($td.length)
                            CTIA.appendCompTIACodesSelect($td, this.cartProds[id].gpe);
                    }
                }
                $cart.find('.noProducts').hide();
                $cart.find('.cartProducts').show();
                $cart.find('.cartSubmitContainer').show();
            } else {
                $cart.find('.noProducts').show();
                $cart.find('.cartProducts').hide();
                $cart.find('.cartSubmitContainer').hide();
            }
        }
    };
    this.onComptiaLoadingFail = function() {
        var $cart = this.$prod.find('.cartContent');
        $cart.find('.cartRequestResults').html('').hide();
        $cart.find('.cartSubmit').attr('class', 'cartSubmit deactivated');
        var html = '<div style="margin: 20px">';
        html += '<p class="error" style="font-size: 11px">';
        html += 'Le chargement des codes compTIA a échoué. <br/>';
        html += 'Le service Apple GSX est probablement temporairement indisponible.<br />';
        html += 'Veuillez essayer de recharger la page ultérieurement<br />';
        html += 'L\'envoi des commandes de composants est désactivée pour le moment</p>';
        html += '</div>';
        $cart.append(html);
        if (this.nbrProds) {
            $cart.find('.noProducts').hide();
            $cart.find('.cartProducts').show();
            $cart.find('.cartSubmitContainer').show();
        } else {
            $cart.find('.noProducts').show();
            $cart.find('.cartProducts').hide();
            $cart.find('.cartSubmitContainer').hide();
        }
    };

    this.add = function($span) {
        if (($span).hasClass('deactivated'))
            return;

        var curId = this.nextCartProdId;
        this.nextCartProdId++;
        var gpe = $span.attr('id').replace(/^add_(\d+)_(.*)_(.*)$/, '$2');
        var id = $span.attr('id').replace(/^add_(\d+)_(.*)_(.*)$/, '$3');
        $span.attr('class', 'addToCart deactivated');
        this.cartProds[curId] = new CartProduct(gpe, id);
        this.nbrProds++;

        var html = '<tr class="cartProd_'+curId+'">';
        html += '<td>'+this.PM.parts[gpe][id].name+'</td>';
        html += '<td class="ref">'+this.PM.parts[gpe][id].num+'</td>';
        html += '<td class="price">'+this.PM.parts[gpe][id].price+'&nbsp;&euro;</td>';
        html += '<td><input type="text" value="1" class="prodQty" size="8" onchange="checkProdQty($(this))"/>';
        html += '<button class="prodQtyDown redHover" onclick="prodQtyDown($(this))"></button>';
        html += '<button class="prodQtyUp greenHover" onclick="prodQtyUp($(this))"></button></td>';
        html += '<td class="compTIACodes"></td>';
        html += '<td><span class="removeCartProduct" onclick="GSX.products['+this.prodId+'].cart.remove($(this))"></span></td>';
        html += '</tr>';
        this.$prod.find('.cartProducts').find('tbody').append(html);
        this.$prod.find('.nbrCartProducts').html(ptr.nbrProds);
        this.activateSave();
        if (CTIA.loadStatus == 'loaded') {
            CTIA.appendCompTIACodesSelect(this.$prod.find('tr.cartProd_'+curId).find('td.compTIACodes'), gpe);
            this.$prod.find('.noProducts').hide();
            this.$prod.find('.cartProducts').show();
            this.$prod.find('.cartSubmitContainer').show();
        } else if (CTIA.loadStatus == 'fail') {
            this.$prod.find('.noProducts').hide();
            this.$prod.find('.cartProducts').show();
            this.$prod.find('.cartSubmitContainer').show();
        } else
            CTIA.load();
    };
    this.remove = function($span) {
        if ($span.hasClass('deactivated'))
            return;

        $span.attr('class', 'removeCartProduct deactivated');
        var $tr = $span.parent('td').parent('tr');
        var id = $tr.attr('class').replace(/^cartProd_(\d+)$/, '$1');
        $tr.fadeOut(250, function(){
            $(this).remove();
            if (!ptr.$prod.find('.cartProducts').find('tbody').find('tr').length) {
                ptr.$prod.find('.cartProducts').hide();
                ptr.$prod.find('.cartSubmitContainer').slideUp(250);
                ptr.$prod.find('.noProducts').slideDown(250);
            }
        });
        this.nbrProds--;
        this.$prod.find('.nbrCartProducts').html(ptr.nbrProds);
        var $addSpan = this.$prod.find('.partGroup_'+this.cartProds[id].gpe).find('tr.partRow_'+this.cartProds[id].id).find('span.addToCart');
        $addSpan.attr('class', 'addToCart activated').click(function() {
            ptr.add($(this));
        });
        delete this.cartProds[id];
        this.cartProds[id] = null;
        this.activateSave();
    };
    this.deleteCart = function() {
        this.nextCartProdId = 0;
        this.nbrProds = 0;
        for (id in this.cartProds) {
            delete this.cartProds[id];
        }
        delete this.cartProds;
        this.cartProds = [];
    };
    this.activateSave = function() {
        this.$prod.find('.cartSave').attr('class', 'cartSave blueHover');
    };
    this.deactivateSave = function() {
        this.$prod.find('.cartSave').attr('class', 'cartSave blueHover deactivated');
    };
    this.save = function() {
        if (!this.cartProds.length)
            return;

        if (this.$prod.find('.cartSave').hasClass('deactivated'))
            return;
        this.deactivateSave();

        var params = 'serial='+this.serial;
        var i = 1;
        for (id in this.cartProds) {
            if (this.cartProds[id]) {
                params += '&part_'+i+'_ref='+this.PM.parts[this.cartProds[id].gpe][this.cartProds[id].id].num;
                params += '&part_'+i+'_qty='+this.cartProds[id].qty;
                i++;
            }
        }
        this.$prod.find('.cartRequestResults').stop().css('opacity', 1).html('<p class="requestProcess">Requête en cours de traitement</p>').slideDown(250);
        setRequest('POST', 'savePartsCart', this.prodId, params);
    };
    this.submit = function() {
        if (!this.cartProds.length)
            return;
        if (this.$prod.find('.cartSubmit').hasClass('deactivated'))
            return;
    };
}

function PartsManager(prodId, serial) {
    this.prodId = prodId;
    this.serial = serial;
    var ptr = this;
    this.parts = [];
    this.$prod = $('#prod_'+prodId);
    this.$parts = null;

    //Gestion de la liste des Parts:
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
    this.addPart = function(group, name, num, type, price) {
        if (group === ' ')
            group = 0;
        if (!this.parts[group])
            this.parts[group] = [];
        this.parts[group].push(new Part(name, num, type, price));
    };
    this.loadParts = function () {
        this.removeParts();
        setRequest('GET', 'loadParts', this.prodId, '&serial='+this.serial+'&prodId='+this.prodId);
        displayRequestMsg('requestProcess', '', this.$prod.find('.partsRequestResult'));
    };
    this.displayParts = function() {
        this.$parts = this.$prod.find('.partsListContainer');
        var ths = '<th style="min-width: 350px">Nom</th>';
        ths += '<th style="min-width: 100px">Ref.</th>';
        ths += '<th style="min-width: 100px">Type</th>';
        ths += '<th style="min-width: 100px">Prix</th>';
        ths += '<th></th>';

        for (gpe in this.parts) {
            this.$prod.find('.typeFiltersContent').append('<input type="checkbox" checked id="typeFilter_'+ptr.prodId+'_'+gpe+'"/><label for="typeFilter_'+ptr.prodId+'_'+gpe+'">'+partsGroup[gpe]+'</label><br/>');
            var html = '<div class="partGroup_'+gpe+' partGroup">';
            html += '<div class="partGroupName closed">'+partsGroup[gpe]+'<span class="partsNbr">('+this.parts[gpe].length+' produits)</span></div>';
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
                html += '<td>'+this.parts[gpe][id].price+'&nbsp;&euro;</td>';
                html += '<td><span id="add_'+this.prodId+'_'+gpe+'_'+id+'" class="addToCart activated" onclick="GSX.products['+this.prodId+'].cart.add($(this))">Ajouter au panier</span></td>';
                html += '</tr>';
                odd = !odd;
            }
            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
            this.$parts.append(html);
        }
        this.setEvents();
    };
    this.setEvents = function() {
        ptr.$prod.find('.cartTitle').mouseover(function() {
            if (ptr.$prod.find('.cartContent').css('display') == 'none')
                ptr.$prod.find('.cartContent').fadeIn(250);
            else {
                ptr.$prod.find('.cartContent').stop().css('opacity', 1);
            }
        }).mouseleave(function() {
            ptr.$prod.find('.cartContent').fadeOut(250);
        });
        ptr.$prod.find('.cartContent').mouseover(function() {
            ptr.$prod.find('.cartContent').stop().css('opacity', 1);
        }).mouseout(function() {
            ptr.$prod.find('.cartContent').fadeOut(250);
        });
        ptr.$prod.find('div.partGroupName.closed').click(function() {
            ptr.openPartsGroup($(this));
        });
        ptr.$prod.find('div.partGroupName.opened').click(function() {
            ptr.closePartsGroup($(this));
        });
        ptr.$prod.find('tr.partRow').mouseover(function() {
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
        ptr.$prod.find('.filterTitle').mouseover(function(){
            ptr.showTypeFilters();
        }).mouseout(function() {
            ptr.hideTypeFilters();
        });
        ptr.$prod.find('.typeFiltersContent').mouseover(function(){
            ptr.showTypeFilters();
        }).mouseout(function() {
            ptr.hideTypeFilters();
        });
        ptr.$prod.find('.filterCheckAll').click(function() {
            ptr.showAllPartsGroup();
        });
        ptr.$prod.find('.filterHideAll').click(function() {
            ptr.hideAllPartsGroup();
        });
        ptr.$prod.find('.typeFiltersContent input').change(function() {
            var gpe = $(this).attr('id').replace(/^typeFilter_(\d+)_(.+)$/, '$2');
            if ($(this).prop('checked')) {
                ptr.$prod.find('.partGroup_'+gpe).stop().css('display', 'none').slideDown(250);
            } else {
                ptr.$prod.find('.partGroup_'+gpe).slideUp(250);
            }
        });
    };

    // Gestion de l'affichage:
    this.showTypeFilters = function() {
        this.$prod.find('.typeFiltersContent').stop().css('display', 'none').slideDown(250);
    }
    this.hideTypeFilters = function() {
        this.$prod.find('.typeFiltersContent').stop().slideUp(250);
    }
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
        $('#typeFilter_'+ptr.prodId+'_'+gpe).attr('checked', '');
        this.$prod.find('.partGroup_'+gpe).stop().css('display', 'none').slideDown(250);
    };
    this.hidePartsGroup = function(gpe) {
        $('#typeFilter_'+ptr.prodId+'_'+gpe).removeAttr('checked');
        this.$prod.find('.partGroup_'+gpe).stop().hide();
    };
    this.showAllPartsGroup = function() {
        this.$prod.find('.typeFiltersContent').find('input').each(function() {
            $(this).attr('checked', '');
        });
        this.$prod.find('.partGroup').each(function() {
            $(this).stop().show();
        });
    };
    this.hideAllPartsGroup = function() {
        this.$prod.find('.typeFiltersContent').find('input').each(function() {
            $(this).removeAttr('checked');
        });
        this.$prod.find('.partGroup').each(function() {
            $(this).slideUp(250);
        });
    };
    this.resetPartsDisplay = function() {
        this.showAllPartsGroup();
        this.$prod.find('tr.partRow').each(function() {
            $(this).show();
        });
        for (gpe in this.parts) {
            this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').html('('+this.parts[gpe].length+' produits)').css('color', '#505050');
        }
    };

    // Gestion des filtres et des recherches:
    this.addKeywordFilter = function() {
        var kw = this.$prod.find('.keywordFilter').val();
        if (!kw) {
            this.$prod.find('.curKeywords').append('<p class="error">Veuillez entrer un mot-clé</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(2000, function(){
                $(this).remove();
            });
            return;
        }
        if (/ +/.test(kw)) {
            this.$prod.find('.curKeywords').append('<p class="error">Veuillez n\'entrer qu\'un seul mot à la fois</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(5000, function(){
                $(this).remove();
            });
            return;
        }
        if (!/^[a-zA-Z0-9\.,\-]+$/.test(kw)) {
            this.$prod.find('.curKeywords').append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(5000, function(){
                $(this).remove();
            });
            return;
        }
        var kwType = this.$prod.find('select.keywordFilterType').val();
        this.$prod.find('.curKeywords').find('p.error').stop().remove();
        var html = '<div><span class="keyword">'+kw+'</span>';
        html += '<span class="kwType kwt_'+kwType+'">&nbsp;&nbsp;('+partDataType[kwType]+')</span>';
        html += '<span class="removeKeyWord" onclick="GSX.products['+this.prodId+'].PM.removeKeywordFilter($(this))"></span></div>';
        this.$prod.find('.curKeywords').append(html);
        this.$prod.find('.keywordFilter').val('');
        this.filterByKeywords();
    };
    this.removeKeywordFilter = function($span) {
        $span.parent('div').remove();
        if (this.$prod.find('.curKeywords').find('div').length)
            this.filterByKeywords('name');
        else
            this.unsetKeywordsFilter();
    };
    this.searchPartByNum = function() {
        var $result = this.$prod.find('.searchResult');
        var search = this.$prod.find('.searchPartInput').val();
        if (!search) {
            $result.append('<p class="error">Veuillez entrer un code produit</p>');
            $result.find('p.error').fadeOut(2000, function(){
                $(this).remove();
            });
            return;
        }
        if (!/^[a-zA-Z0-9\-\_ ]+$/.test(search)) {
            $result.append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
            $result.find('p.error').fadeOut(5000, function(){
                $(this).remove();
            });
            return;
        }
        this.unsetSearch();
        this.$prod.find('.searchResult').html('<div class="partSearchNum"><span class="searchNum">'+search+'</span><span class="removeSearch" onclick="GSX.products['+this.prodId+'].PM.unsetSearch()"></span></div>');
        this.$prod.find('.curKeywords').find('div').each(function() {
            $(this).remove();
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        var n = 0;
        for (gpe in this.parts) {
            this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').html('');
            var check = false;
            for (id in this.parts[gpe]) {
                if (this.parts[gpe][id].num == search) {
                    check = true;
                    n++;
                    this.$prod.find('.partGroup_'+gpe).find('tr.partRow_'+id).show();
                }
            }
            if (check) {
                this.showPartsGroup(gpe);
                this.openPartsGroup(this.$prod.find('.partGroup_'+gpe).find('div.partGroupName'));
            } else {
                this.hidePartsGroup(gpe);
                this.closePartsGroup(this.$prod.find('.partGroup_'+gpe).find('div.partGroupName'));
                this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').html('');
            }
        }
        if (!n) {
            this.$prod.find('.searchResult').append('<p class="error">Aucun composant compatible ne correspond à ce numéro</p>');
        }
    };
    this.filterByKeywords = function() {
        this.unsetSearch();
        var kw = [];
        this.$prod.find('.curKeywords').children('div').each(function(){
            var txt = $(this).find('span.keyword').text();
            var type = $(this).find('.kwType').attr('class').replace(/^kwType kwt_(.*)$/, '$1');
            kw.push({
                'txt' : txt,
                'type' : type
            });
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
                    var regex = null;
                    switch (kw[i].type) {
                        case 'name':
                            regex = new RegExp('^(.*)'+kw[i].txt+'(.*)$', 'i');
                            str = ptr.parts[gpe][id].name;
                            break;

                        case 'num':
                            regex = new RegExp('^(.*)'+kw[i].txt+'(.*)$', 'i');
                            str = ptr.parts[gpe][id].num;
                            break;

                        case 'price':
                            kw[i].txt = kw[i].txt.replace(/,/g, '.');
                            regex = new RegExp('^'+kw[i].txt+'\.*\d*$', 'i');
                            str = ptr.parts[gpe][id].price;
                            break;

                        case 'type':
                            regex = new RegExp('^(.*)'+kw[i].txt+'(.*)$', 'i');
                            str = ptr.parts[gpe][id].type;
                            break;
                    }
                    if (str) {
                        if (!regex.test(str)) {
                            display = false;
                        }
                    }
                }
                if (display) {
                    this.$prod.find('.partGroup_'+gpe).find('tr.partRow_'+id).show();
                    n++;
                }
            }
            this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').html('('+n+' produits)');
            if (n > 0)
                this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').css('color', '#00780A');
            else
                this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').css('color', '#960000');
        }
    };
    this.unsetKeywordsFilter = function() {
        this.$prod.find('.curKeywords').find('div').each(function() {
            $(this).remove();
        });
        this.$prod.find('.searchResult').html('');
        $('tr.partRow').each(function(){
            $(this).show();
        });
        for (gpe in this.parts) {
            this.$prod.find('.partGroup_'+gpe).find('span.partsNbr').html('('+this.parts[gpe].length+' produits)').css('color', '#505050');
        }
    };
    this.unsetSearch = function() {
        if (this.$prod.find('.partSearchNum').length) {
            this.$prod.find('.searchResult').html('');
            this.resetPartsDisplay();
        }
    };
}

function GSX_Product(id, serial) {
    this.id = id;
    this.serial = serial;
    this.PM = null;
    this.cart = null;
    this.repairPartsDatasDefs = [];

    this.loadDatas = function() {
        $('#requestsResponsesContainer').append('<div id="prod_'+this.id+'" class="productDatasContainer"></div>');
        setRequest('GET', 'loadProduct', this.id, '&prodId='+this.id+'&serial='+serial);
        displayRequestMsg('requestProcess', '');
    };
    this.loadParts = function() {
        this.PM = new PartsManager(this.id, this.serial);
        this.cart = new Cart(this.id, this.serial, this.PM);
        this.PM.loadParts();
    }
}

function GSX() {
    this.products = [];
    this.nextProdId = 1;

    this.importPartsFromCartToRepair = function(repair) {
        var $form = $('#repairForm_'+repair);
        if ($form.length) {
            var $container = $form.find('div.repairPartsContainer');
            var $template = $form.find('div.repairsPartsInputsTemplate');
            var prodId = $form.parent('div.repairFormContainer').parent('div.repairPopUp').find('input.prodId').val();
            if (prodId && $container.length && $template.length) {
                var $prod = $('#prod_'+prodId);
                if ($prod.length) {
                    var $cart = $prod.find('div.cartContent');
                    if (!$cart.length) {
                        $container.html('<p class="alert">Veuillez charger la liste des composants pour afficher le panier</p>').slideDown(250);
                        return;
                    }
                    var $partsRows = $cart.find('table.cartProducts').find('tbody').find('tr');
                    if (!$partsRows.length) {
                        $container.html('<p class="alert">Veuillez ajouter des éléments au panier depuis la liste des composants compatibles</p>').slideDown(250);
                        return;
                    }
                    $container.html('');
                    var i = 1;
                    $partsRows.each(function() {
                        var partName = $(this).find('td:first').text();
                        var html = '<div class="partDatasBlock">';
                        html += '<div class="partDatasBlockTitle closed" onclick="togglePartDatasBlockDisplay($(this))">'+partName+'</div>';
                        html += '<div class="partDatasContent partDatasContent_'+i+'">';
                        html += $template.html();
                        html += '</div>';
                        $container.append(html);
                        var $div = $container.find('div.partDatasContent_'+i+'');
                        var $partRow = $(this);
                        if ($div.length) {
                            $div.find('div.dataBlock').each(function() {
                                var dataName = $(this).find('label').attr('for');
                                var select = null;
                                switch (dataName) {
                                    case 'partNumber':
                                        $(this).find('input').val($partRow.find('td.ref').text()).attr('id', 'partNumber_'+i).attr('name', 'partNumber_'+i);
                                        break;

                                    case 'comptiaCode':
                                        select = '<select id="comptiaCode_'+i+'" name="comptiaCode_'+i+'">';
                                        select += $partRow.find('select.compTIACodeSelect').html();
                                        select += '</select>';
                                        $(this).find('div.comptiaCodeContainer').html(select);
                                        $(this).find('div.comptiaCodeContainer').find('select').val($partRow.find('select.compTIACodeSelect').val());
                                        break;

                                    case 'comptiaModifier':
                                        select = '<select id="comptiaModifier_'+i+'" name="comptiaModifier_'+i+'">';
                                        select += $partRow.find('select.compTIAModifierSelect').html();
                                        select += '</select>';
                                        $(this).find('div.comptiaModifierContainer').html(select);
                                        $(this).find('div.comptiaModifierContainer').find('select').val($partRow.find('select.compTIAModifierSelect').val());
                                        break;

                                    default:
                                        $(this).find('#'+dataName).attr('id', dataName+'_'+i).attr('name', dataName+'_'+i);
                                        break;
                                }
                            });
                        }
                        i++;
                    });
                    $container.slideDown(250);
                    return;
                }
            }
        }
        alert('Une erreur est survenue');
    };
    this.loadProduct = function (serial) {
        if (!serial) {
            displayRequestMsg('error', 'Veuillez entrer un numéro de série');
            return;
        }

        if (!/^[0-9A-Z]{11,12}$/.test(serial)) {
            displayRequestMsg('error', 'Le format du numéro de série est incorrect');
            return;
        }

        for (id in this.products) {
            if (this.products[id].serial == serial) {
                displayRequestMsg('error', 'Un produit possédant ce numéro de série a déjà été chargé');
                return;
            }
        }
        this.products[this.nextProdId] = new GSX_Product(this.nextProdId, serial);
        this.products[this.nextProdId].loadDatas();
        this.nextProdId++;
    };
    this.loadProductParts = function($button) {
        var prodId = getProdId($button);
        if (!prodId) {
            alert('Erreur : ID produit absent');
            return;
        }
        if (this.products[prodId]) {
            $button.slideUp(250);
            this.products[prodId].loadParts();
        } else {
            displayRequestMsg('error', 'Erreur : produit non initialisé', $('#prod'+prodId).find('.partsRequestResult'));
        }
    };
    this.addPart = function(prodId, group, name, num, type, price) {
        this.products[prodId].PM.addPart(group, name, num, type, price);
    }
    this.displayParts = function(prodId) {
        this.products[prodId].PM.displayParts();
    };
    this.loadRepairForm = function($button) {
        var prodId = getProdId($button.parent('p').parent('div.repairPopUp'));
        if (!prodId) {
            alert('Erreur : ID produit absent');
            return;
        }
        var $prod = $('#prod_'+prodId);
        if (!$prod.length) {
            alert('Une erreur est survenue, opération impossible');
            return;
        }
        if (this.products[prodId]) {
            var requestType = $prod.find('.repairTypeSelect').val();
            setRequest('GET', 'loadRepairForm', prodId, '&requestType='+requestType+'&serial='+this.products[prodId].serial);
            displayRequestMsg('requestProcess', '', $prod.find('div.repairFormContainer'));
        } else {
            displayRequestMsg('error', 'Erreur : produit non initialisé', $prod.find('.partsRequestResult'));
        }
    };
}

var GSX = new GSX();

function displayRequestMsg(type, msg, $div) {
    if ((type == 'requestProcess') && (msg === ''))
        msg = 'Requête en cours de traitement';

    if (!$div)
        $div = $('#requestResult');

    var html = '<p class="'+type+'">'+msg+'</p>';

    if ($div.css('display') != 'none') {
        $div.slideUp(250, function() {
            $(this).html(html).slideDown(250);
        });
    } else {
        $div.html(html);
        $div.slideDown(250);
    }
}

function getProdId($obj) {
    if (!$obj.length)
        return 0;
    var $prod = $obj.parent('.productDatasContainer');
    if (!$prod.length) return 0;
    var id = $prod.attr('id').replace(/^prod_(\d+)$/, '$1');
    if (!id) return 0;
    return id;
}
function checkProdQty($input) {
    var val = $input.val();
    if (!val)
        $input.val(1);
    else if (!/^[0-9]+$/.test(val)) {
        $input.val(1);
    } else {
        val = parseInt(val);
        if (val < 1)
            $input.val(1);
        if (val > maxProdQty)
            $input.val(maxProdQty);
    }
}
function prodQtyDown($button) {
    var $input = $button.parent().find('input.prodQty');
    var val = parseInt($input.val());
    val--;
    if (val < 1)
        val = 1;
    $input.val(val);
}
function prodQtyUp($button) {
    var $input = $button.parent().find('input.prodQty');
    var val = parseInt($input.val());
    val++;
    if (val > maxProdQty)
        val = maxProdQty;
    $input.val(val);
}

function displayCreateRepairPopUp($button) {
    var prodId = getProdId($button);
    $('#prod_'+prodId).find('div.repairPopUp').show();
}
function hideCreateRepairPopUp($button) {
    $button.parent('.repairPopUp').hide();
}
function displayLabelInfos($span) {
    $span.find('div.labelInfos').stop().css('opacity', 1).show();
}
function hideLabelInfos($span) {
    $span.find('div.labelInfos').fadeOut(250);
}
function togglePartDatasBlockDisplay($div) {
    if ($div.hasClass('closed')) {
        $div.parent('div.partDatasBlock').find('div.partDatasContent').slideDown(250);
        $div.attr('class', 'partDatasBlockTitle open');
    } else {
        $div.parent('div.partDatasBlock').find('div.partDatasContent').slideUp(250);
        $div.attr('class', 'partDatasBlockTitle closed');
    }
}
function assignInputCheckMsg($input, type, msg) {
    var html = '<span class="'+type+'">'+msg+'</span>';
    var $checkSpan = $input.parent('div.dataBlock').find('span.dataCheck');
    if ($checkSpan.length) {
        $checkSpan.slideUp(250);
        $checkSpan.html(html).slideDown(250);
    }
}
function checkInput($input, type) {
    var val = $input.val();
    if (!val.length) {
        if ($input.attr('required') !== undefined){
            assignInputCheckMsg($input, 'notOk', 'Information obligatoire');
            return;
        }
        assignInputCheckMsg($input, '', '');
        return;
    }
    switch (type) {
        case 'text':
            if (!/^.+$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', '');
                return;
            }
            break;

        case 'alphanum':
            if (!/^[a-zA-Z0-9\-\._ ]+$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Caractères interdits');
                return;
            }
            break;

        case 'phone':
            val = val.replace(/\./g, '');
            val = val.replace(/\-/g, '');
            val = val.repalce(/\//g, '');
            val = val.replace(/ /g, '');
            $input.val(val);
            if (!/^[0-9]{10}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format invalide');
                return;
            }
            break;

        case 'email':
            if (!/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z\p{L}0-9]+$/i.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de l\'adresse e-mail invalide');
                return;
            }
            break;

        case 'zipCode':
            if (!/^[0-9]{5}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format du code postal invalide');
                return;
            }
            break;

        case 'date':
            if (!/^[0-9]{2}\/[0-9]{2}\/[0-9]{2}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de la date invalide. (Attendu: JJ/MM/AA)');
                return;
            }
            break;

        case 'time':
            if (!/^[0-9]{2}:[0-9]{2}( [AP]M)?$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de l\'heure invalide. (Attendu: HH:MM AM/PM)');
                return;
            }
            var hours = parseInt(val.replace(/^([0-9]{2}):[0-9]{2}.*$/, '$1'));
            var mins = parseInt(val.replace(/^[0-9]{2}:([0-9]{2}).*$/, '$1'));
            if (mins > 59) {
                assignInputCheckMsg($input, 'notOk', 'Heure incorrecte');
                return;
            }
            if (hours > 12) {
                if (hours < 24) {
                    hours -= 12;
                    $input.val(hours+':'+mins+' PM');
                } else {
                    assignInputCheckMsg($input, 'notOk', 'Heure incorrecte');
                    return;
                }
            } else {
                if (!/^[0-9]{2}:[0-9]{2} [AP]M$/.test(val)) {
                    $input.val(hours+':'+mins+' AM');
                }
            }
            break;

        case 'num':
            if (!/^[0-9]*$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format invalide (Chiffres uniquement).');
                return;
            }
            break;
    }
    assignInputCheckMsg($input, 'ok', '');
}
function submitGsxRequestForm($span, request) {
    $('#repairForm_'+request).submit();
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
function onRequestResponse(xhr, requestType, prodId) {
    var $div = null;
    switch (requestType) {
        case 'loadCompTIACodes':
            if (xhr.responseText == 'fail') {
                CTIA.onLoadFail();
            } else {
                eval(xhr.responseText);
                CTIA.endInit();
            }
            break;

        case 'loadProduct':
            $('#requestResult').slideUp(250);
            $div = $('#prod_'+prodId);
            if ($div.length)
                $div.html(xhr.responseText).slideDown(250).show();
            else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;
        case 'loadRepairForm':
            $div = $('#prod_'+prodId).find('.repairFormContainer');
            if ($div.length) {
                $div.slideUp(250, function(){
                    $(this).html(xhr.responseText).show();
                });
            } else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;

        case 'loadParts':
            $div = $('#prod_'+prodId).find('.partsRequestResult');
            if ($div.length) {
                $div.slideUp(250, function() {
                    $(this).html(xhr.responseText);
                    GSX.displayParts(prodId);
                    $(this).slideDown(250);
                });
            } else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;

        case 'savePartsCart':
            $('#prod_'+prodId).find('.cartRequestResults').animate({
                'opacity': 0.1
            }, {
                'duration' : 250,
                'complete' : function() {
                    GSX.products[prodId].cart.activateSave();
                    $(this).html(xhr.responseText).animate({
                        'opacity': 1
                    }, {
                        'duration': 250,
                        'complete' : function() {
                            $(this).fadeOut(5000, function() {
                                $(this).slideUp(250, function() {
                                    $(this).html('');
                                });
                            })
                        }
                    });
                }
            });
            break;
    }
}
function setRequest(method, requestType, prodId, requestParams) {
    var xhr = getXMLHttpRequest();

    xhr.onreadystatechange = function(){
        //alert('state: ' + xhr.readyState + ', status: ' +xhr.status);
        var RT = requestType;
        var ID = prodId;
        if((xhr.readyState == 4) && ((xhr.status == 200) || (xhr.status == 0))) {
            onRequestResponse(xhr, RT, ID);
        }
    }
    switch (method) {
        case 'GET':
            xhr.open(method, DOL_URL_ROOT+'/apple/requestProcess.php?action='+requestType+requestParams);
            xhr.send();
            break;

        case 'POST':
            xhr.open(method, DOL_URL_ROOT+'/apple/requestProcess.php?action='+requestType);
            xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            xhr.send(requestParams);
            break;
    }
}
