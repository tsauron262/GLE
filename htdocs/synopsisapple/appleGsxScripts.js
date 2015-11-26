var maxProdQty = 99;
var importCart = false;
var partsGroup = {
    0: 'Général',
    1: 'Visuel',
    2: 'Affichage',
    3: 'Stockage',
    4: 'Périphériques d\'entrées',
    5: 'Cartes',
    6: 'Alimentation',
    7: 'Impression',
    8: 'Périphériques multi-fonctions',
    9: 'Périphériques de communication',
    'A': 'Partage',
    'B': 'iPhone',
    'E': 'iPod',
    'F': 'iPad'
};


var partDataType = {
    'eeeCode': 'eeeCode',
    'name': 'Nom',
    'num': 'Ref.',
    'type': 'Type',
    'price': 'Prix'
}

var extra = "";
if (typeof (chronoId) != 'undefined')
    extra = extra + "&chronoId=" + chronoId;
//else
//    extra = extra+ "&chronoId=12";

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
    this.appendCompTIACodesSelect = function(cart, $div, group) {
        if (!$div.length)
            return;

        if (!this.codes[group])
            return;

        var html = '<select class="compTIACodeSelect">';
        html += '<option value="0">Code symptôme</option>';
        for (code in this.codes[group]) {
            html += '<option value="' + code + '">' + code + ' - ' + this.codes[group][code] + '</option>';
        }
        html += '</select>';
        html += '<select class="compTIAModifierSelect">';
        html += '<option value="0">Modificateur</option>';
        for (mod in this.modifiers) {
            html += '<option value="' + mod + '">' + mod + ' - ' + this.modifiers[mod] + '</option>';
        }
        html += '</select>';
        $div.html(html);
        cart.setChangesEvents($div.parent('tr'));
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

function Part(name, num, type, price, eeeCode, newNum) {
    this.name = name;
    this.num = num;
    this.newNum = newNum;
    this.type = type;
    this.price = price;
    this.eeeCode = eeeCode;
}

function CartProduct(code, name, num, price) {
    this.listId = null;
    this.code = code;
    this.name = name;
    this.num = num;
    this.price = price;
    this.qty = null;
    this.comptiaCode = null;
    this.comptiaModifier = null;
    this.setValues = function($tr) {
        if (!$tr.length)
            return;
        if (this.qty) {
            $tr.find('input.prodQty').val(this.qty);
        }
        if (this.comptiaCode) {
            $tr.find('select.compTIACodeSelect').val(this.comptiaCode);
        }
        if (this.comptiaModifier) {
            $tr.find('select.compTIAModifierSelect').val(this.comptiaModifier);
        }
    };
}

function Cart(prodId, serial, PM) {
    var ptr = this;
    this.prodId = prodId;
    this.serial = serial;
    this.PM = PM;
    this.cartProds = [];
    this.nextCartProdId = 0;
    this.nbrProds = 0;
    this.$prod = $('#prod_' + prodId);
    this.changes = false;

    this.newProdContainer = function() {
        this.$prod = $('#prod_' + prodId);
    };
    this.setEvents = function() {
    };
    this.setChangesEvents = function($tr) {
        if (!$tr.length)
            return;
        $tr.find('input').change(function() {
            ptr.displayModifMsg();
        });
        $tr.find('select').change(function() {
            ptr.displayModifMsg();
        });
    }
    this.onComptiaLoadingStart = function() {
        var $cart = this.$prod.find('.cartContent');
        if ($cart.length) {
            displayRequestMsg('requestProcess', 'Liste des codes compTIA en cours de chargement', $cart.find('.cartRequestResults'));
            $cart.find('.cartRequestResults').show();
            $cart.find('.noProducts').hide();
            $cart.find('.cartProducts').hide();
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
                        var $td = $cart.find('tr.cartProd_' + id).find('td.compTIACodes');
                        if ($td.length)
                            CTIA.appendCompTIACodesSelect(this, $td, this.cartProds[id].code);
                        this.cartProds[id].setValues($cart.find('tr.cartProd_' + id));
                    }
                }
                $cart.find('.noProducts').hide();
                $cart.find('.cartProducts').show();
            } else {
                $cart.find('.noProducts').show();
                $cart.find('.cartProducts').hide();
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
        } else {
            $cart.find('.noProducts').show();
            $cart.find('.cartProducts').hide();
        }
    };

    this.add = function($span, qty, comptiaCode, comptiaModifier) {
        if (($span).hasClass('deactivated'))
            return;

        var curId = this.nextCartProdId;
        this.nextCartProdId++;
        var gpe = $span.attr('id').replace(/^add_(\d+)_(.*)_(.*)$/, '$2');
        var id = $span.attr('id').replace(/^add_(\d+)_(.*)_(.*)$/, '$3');
        $span.attr('class', 'addToCart deactivated');
        this.cartProds[curId] = new CartProduct(gpe, this.PM.parts[gpe][id].name, this.PM.parts[gpe][id].num, this.PM.parts[gpe][id].price);
        this.cartProds[curId].listId = id;

        if (qty)
            this.cartProds[curId].qty = qty;
        if (comptiaCode)
            this.cartProds[curId].comptiaCode = comptiaCode;
        if (comptiaModifier)
            this.cartProds[curId].comptiaModifier = comptiaModifier;

        this.nbrProds++;

        var html = '<tr class="cartProd_' + curId + '">';
        html += '<td>' + this.cartProds[curId].name + '</td>';
        html += '<td class="ref">' + this.cartProds[curId].num + '</td>';
        html += '<td class="price">' + this.cartProds[curId].price + '&nbsp;&euro;</td>';
        html += '<td><input type="text" value="1" class="prodQty" size="8" onchange="checkProdQty($(this))"/>';
        html += '<span class="button prodQtyDown redHover" onclick="prodQtyDown($(this))"></span>';
        html += '<span class="button prodQtyUp greenHover" onclick="prodQtyUp($(this))"></span></td>';
        html += '<td class="compTIACodes"></td>';
        html += '<td><span class="removeCartProduct" onclick="GSX.products[' + this.prodId + '].cart.remove($(this))"></span></td>';
        html += '</tr>';
        this.$prod.find('.cartProducts').find('tbody').append(html);
        this.$prod.find('.nbrCartProducts').html(ptr.nbrProds);
        this.setChangesEvents(this.$prod.find('tr.cartProd_' + curId));
        this.displayModifMsg();
        this.activateSave();
        if (CTIA.loadStatus == 'loaded') {
            CTIA.appendCompTIACodesSelect(this, this.$prod.find('tr.cartProd_' + curId).find('td.compTIACodes'), gpe);
            this.cartProds[curId].setValues(this.$prod.find('tr.cartProd_' + curId));
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
        $tr.fadeOut(250, function() {
            $(this).remove();
            if (!ptr.$prod.find('.cartProducts').find('tbody').find('tr').length) {
                ptr.$prod.find('.cartProducts').hide();
                ptr.$prod.find('.noProducts').slideDown(250);
            }
        });
        this.nbrProds--;
        this.$prod.find('.nbrCartProducts').html(ptr.nbrProds);
        var $addSpan = this.$prod.find('.partGroup_' + this.cartProds[id].code).find('tr.partRow_' + this.cartProds[id].listId).find('span.addToCart');
        if ($addSpan.length) {
            $addSpan.attr('class', 'addToCart activated').click(function() {
                ptr.add($(this));
            });
        }
        delete this.cartProds[id];
        this.cartProds[id] = null;
        this.displayModifMsg();
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
        this.$prod.find('.cartProducts').find('tbody').html('');
        this.$prod.find('span.addToCart.deactivated').each(function() {
            $(this).attr('class', 'addToCart activated');
        });
    };
    this.activateSave = function() {
        this.$prod.find('.cartSave').attr('class', 'button cartSave greenHover');
        this.$prod.find('.addToPropal').attr('class', 'button addToPropal');
    };
    this.deactivateSave = function() {
        this.$prod.find('.cartSave').attr('class', 'button cartSave greenHover deactivated');
        this.$prod.find('.addToPropal').attr('class', 'button addToPropal deactivated');
    };
    this.save = function(addToPropal) {
        if (typeof (addToPropal) == 'undefined')
            addToPropal = false;

        if (!this.cartProds.length)
            return;

        if (this.$prod.find('.cartSave').hasClass('deactivated'))
            if (!addToPropal)
                return;
        this.deactivateSave();

        var params = 'serial=' + this.serial;
        if (addToPropal)
            params += '&addToPropal=1';
        var i = 1;
        for (id in this.cartProds) {
            var $tr = this.$prod.find('tr.cartProd_' + id);
            if (this.cartProds[id]) {
                params += '&part_' + i + '_ref=' + this.cartProds[id].num;
                if ($tr.find('select.compTIACodeSelect'))
                    params += '&part_' + i + '_comptiaCode=' + $tr.find('select.compTIACodeSelect').val();
                if ($tr.find('select.compTIAModifierSelect'))
                    params += '&part_' + i + '_comptiaModifier=' + $tr.find('select.compTIAModifierSelect').val();
                if ($tr.find('input.prodQty'))
                    params += '&part_' + i + '_qty=' + $tr.find('input.prodQty').val();
                params += '&part_' + i + '_componentCode=' + this.cartProds[id].code;
                params += '&part_' + i + '_partDescription=' + encodeURIComponent(this.cartProds[id].name);
                params += '&part_' + i + '_stockPrice=' + this.cartProds[id].price;
                i++;
            }
        }
        this.removeModifMsg();
        this.$prod.find('.cartRequestResults').find('ok').remove();
        this.$prod.find('.cartRequestResults').stop().css('opacity', 1).append('<p class="requestProcess">Requête en cours de traitement</p>').slideDown(250);
        setRequest('POST', 'savePartsCart', this.prodId, params);
    };
    this.load = function() {
        if (this.$prod.find('.cartLoad').hasClass('deactivated'))
            return;

        if (this.cartProds.length) {
            if (!confirm("Toutes les éventuelles modifications du panier faites depuis votre dernier enregistrement seront perdues.\n\nContiner?"))
                return;
            this.deleteCart();
        }
        if (this.changes)
            this.removeModifMsg();
        this.$prod.find('.cartRequestResults').stop().css('opacity', 1).html('<p class="requestProcess">Requête en cours de traitement</p>').slideDown(250);
        setRequest('GET', 'loadPartsCart', this.prodId, '&serial=' + this.serial + '&prodId=' + this.prodId);
    };
    this.onPartLoad = function(code, name, num, comptiaCode, comptiaModifier, qty, price) {
        var curId = this.nextCartProdId;
        this.nextCartProdId++;

        var listId = this.PM.getPartListId(code, num);
        if (listId !== null) {
            var $span = this.$prod.find('#add_' + ptr.prodId + '_' + code + '_' + listId);
            if ($span.length)
                $span.attr('class', 'addToCart deactivated');
        }

        this.cartProds[curId] = new CartProduct(code, name, num, price);
        this.cartProds[curId].listId = listId;
        this.cartProds[curId].qty = qty;
        this.cartProds[curId].comptiaCode = comptiaCode;
        this.cartProds[curId].comptiaModifier = comptiaModifier;

        this.nbrProds++;

        var html = '<tr class="cartProd_' + curId + '">';
        html += '<td>' + name + '</td>';
        html += '<td class="ref">' + num + '</td>';
        html += '<td class="price">' + price + '&nbsp;&euro;</td>';
        html += '<td><input type="text" value="1" class="prodQty" size="8" onchange="checkProdQty($(this))"/>';
        html += '<span class="button prodQtyDown redHover" onclick="prodQtyDown($(this))"></span>';
        html += '<span class="button prodQtyUp greenHover" onclick="prodQtyUp($(this))"></span></td>';
        html += '<td class="compTIACodes"></td>';
        html += '<td><span class="removeCartProduct" onclick="GSX.products[' + this.prodId + '].cart.remove($(this))"></span></td>';
        html += '</tr>';
        this.$prod.find('.cartProducts').find('tbody').append(html);
        this.$prod.find('.nbrCartProducts').html(ptr.nbrProds);
        this.setChangesEvents(this.$prod.find('tr.cartProd_' + curId));
        this.activateSave();
        if (CTIA.loadStatus == 'loaded') {
            CTIA.appendCompTIACodesSelect(this, this.$prod.find('tr.cartProd_' + curId).find('td.compTIACodes'), code);
            this.cartProds[curId].setValues(this.$prod.find('tr.cartProd_' + curId));
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
    this.onPartsListLoad = function() {
        for (id in this.cartProds) {
            if (ptr.cartProds[id]) {
                var listId = ptr.PM.getPartListId(ptr.cartProds[id].code, ptr.cartProds[id].num);
                if (listId !== null) {
                    ptr.cartProds[id].listId = listId;
                    var $span = this.$prod.find('#add_' + ptr.prodId + '_' + ptr.cartProds[id].code + '_' + listId);
                    if ($span.length)
                        $span.attr('class', 'addToCart deactivated');
                }
            }
        }
    };
    this.addToPropal = function($span) {
        if ($span.hasClass('deactivated'))
            return;
        this.save(true);
    };
    this.displayModifMsg = function() {
        this.changes = true;
        var $div = this.$prod.find('.cartRequestResults2');
        if ($div.length) {
            var $p = $div.find('p.changesMsg');
            if ($p.length)
                return;
            $div.stop().css('opacity', 1).append('<p class="alert changesMsg">Votre panier a été modifié. Si vous souhaitez conserver ces modifications, pensez à sauvegarder le panier.</p>').slideDown(250);
        }
    }
    this.removeModifMsg = function() {
        this.changes = false;
        var $div = this.$prod.find('.cartRequestResults2');
        if ($div.length) {
            var $p = $div.find('p.changesMsg');
            if ($p.length)
                $p.remove();
        }
    }
}

function PartsManager(prodId, serial) {
    this.prodId = prodId;
    this.serial = serial;
    var ptr = this;
    this.parts = [];
    this.$prod = $('#prod_' + prodId);
    this.$parts = null;

    this.newProdContainer = function() {
        this.$prod = $('#prod_' + prodId);
    };

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
    this.addPart = function(group, name, num, type, price, eeeCode, newNum) {
        if (group === ' ')
            group = 0;
        if (!this.parts[group])
            this.parts[group] = [];
        this.parts[group].push(new Part(name, num, type, price, eeeCode, newNum));
    };
    this.loadParts = function() {
        displayRequestMsg('requestProcess', '', this.$prod.find('.partsRequestResult'));
        this.removeParts();
        setRequest('GET', 'loadParts', this.prodId, '&serial=' + this.serial + '&prodId=' + this.prodId);
    };
    this.displayParts = function() {
        this.$parts = this.$prod.find('.partsListContainer');
        var ths = '';
        ths += '<th></th>';
        ths += '<th style="min-width: 250px">Nom</th>';
        ths += '<th style="min-width: 80px">Ref.</th>';
        ths += '<th style="min-width: 80px">Nouvelle Ref.</th>';
        ths += '<th style="min-width: 80px">eeeCode</th>';
        ths += '<th style="min-width: 80px">Type</th>';
        ths += '<th style="min-width: 80px">Prix</th>';

        for (gpe in this.parts) {
            this.$prod.find('.typeFiltersContent').append('<input type="checkbox" checked id="typeFilter_' + ptr.prodId + '_' + gpe + '"/><label for="typeFilter_' + ptr.prodId + '_' + gpe + '">' + partsGroup[gpe] + '</label><br/>');
            var html = '<div class="partGroup_' + gpe + ' partGroup">';
            html += '<div class="partGroupName closed">' + partsGroup[gpe] + '<span class="partsNbr">(' + this.parts[gpe].length + ' produits)</span></div>';
            html += '<div class="partsList">';
            html += '<table><thead>' + ths + '</thead><tbody>';
            var odd = true;
            for (id in this.parts[gpe]) {
                html += '<tr class="partRow_' + id + ' partRow';
                if (odd)
                    html += '  oddRow';
                else
                    html += ' evenRow';
                html += '">';
                html += '<td><span id="add_' + this.prodId + '_' + gpe + '_' + id + '" class="addToCart activated" onclick="GSX.products[' + this.prodId + '].cart.add($(this))">Ajouter au panier</span></td>';
                html += '<td class="partName">' + this.parts[gpe][id].name + '</td>';
                html += '<td>' + this.parts[gpe][id].num + '</td>';
                html += '<td>' + this.parts[gpe][id].newNum + '</td>';
                html += '<td>' + this.parts[gpe][id].eeeCode + '</td>';
                html += '<td>' + this.parts[gpe][id].type + '</td>';
                html += '<td>' + this.parts[gpe][id].price + '&nbsp;&euro;</td>';
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
        ptr.$prod.find('.filterTitle').click(function() {
            ptr.showTypeFilters();
        });
        ptr.$prod.find('.filterTitle').mouseover(function() {
//            ptr.showTypeFilters();
        }).mouseout(function() {
            ptr.hideTypeFilters();
        });
        ptr.$prod.find('.typeFiltersContent').mouseover(function() {
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
                ptr.$prod.find('.partGroup_' + gpe).stop().css('display', 'none').slideDown(250);
            } else {
                ptr.$prod.find('.partGroup_' + gpe).slideUp(250);
            }
        });
    };
    this.getPartListId = function(gpe, num) {
        if (this.parts[gpe]) {
            for (idx in this.parts[gpe]) {
                if (this.parts[gpe][idx].num == num)
                    return idx;
            }
        }
        return null;
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
        $('#typeFilter_' + ptr.prodId + '_' + gpe).attr('checked', '');
        this.$prod.find('.partGroup_' + gpe).stop().css('display', 'none').slideDown(250);
    };
    this.hidePartsGroup = function(gpe) {
        $('#typeFilter_' + ptr.prodId + '_' + gpe).removeAttr('checked');
        this.$prod.find('.partGroup_' + gpe).stop().hide();
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
            this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').html('(' + this.parts[gpe].length + ' produits)').css('color', '#505050');
        }
    };

    // Gestion des filtres et des recherches:
    this.addKeywordFilter = function() {
        var kw = this.$prod.find('.keywordFilter').val();
        if (!kw) {
            this.$prod.find('.curKeywords').append('<p class="error">Veuillez entrer un mot-clé</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(2000, function() {
                $(this).remove();
            });
            return;
        }
        if (/ +/.test(kw)) {
            this.$prod.find('.curKeywords').append('<p class="error">Veuillez n\'entrer qu\'un seul mot à la fois</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(5000, function() {
                $(this).remove();
            });
            return;
        }
        if (!/^[a-zA-Z0-9\.,\-]+$/.test(kw)) {
            this.$prod.find('.curKeywords').append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques</p>');
            this.$prod.find('.curKeywords').find('p.error').fadeOut(5000, function() {
                $(this).remove();
            });
            return;
        }
        var kwType = this.$prod.find('select.keywordFilterType').val();
        this.$prod.find('.curKeywords').find('p.error').stop().remove();
        var html = '<div><span class="keyword">' + kw + '</span>';
        html += '<span class="kwType kwt_' + kwType + '">&nbsp;&nbsp;(' + partDataType[kwType] + ')</span>';
        html += '<span class="removeKeyWord" onclick="GSX.products[' + this.prodId + '].PM.removeKeywordFilter($(this))"></span></div>';
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
            $result.find('p.error').fadeOut(2000, function() {
                $(this).remove();
            });
            return;
        }
        if (!/^[a-zA-Z0-9\-\_ ]+$/.test(search)) {
            $result.append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
            $result.find('p.error').fadeOut(5000, function() {
                $(this).remove();
            });
            return;
        }
        this.unsetSearch();
        this.$prod.find('.searchResult').html('<div class="partSearchNum"><span class="searchNum">' + search + '</span><span class="removeSearch" onclick="GSX.products[' + this.prodId + '].PM.unsetSearch()"></span></div>');
        this.$prod.find('.curKeywords').find('div').each(function() {
            $(this).remove();
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        var n = 0;
        for (gpe in this.parts) {
            this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').html('');
            var check = false;
            for (id in this.parts[gpe]) {
                if ((this.parts[gpe][id].num == search) || (this.parts[gpe][id].newNum == search)) {
                    check = true;
                    n++;
                    this.$prod.find('.partGroup_' + gpe).find('tr.partRow_' + id).show();
                }
            }
            if (check) {
                this.showPartsGroup(gpe);
                this.openPartsGroup(this.$prod.find('.partGroup_' + gpe).find('div.partGroupName'));
            } else {
                this.hidePartsGroup(gpe);
                this.closePartsGroup(this.$prod.find('.partGroup_' + gpe).find('div.partGroupName'));
                this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').html('');
            }
        }
        if (!n) {
            this.$prod.find('.searchResult').append('<p class="error">Aucun composant compatible ne correspond à ce numéro</p>');
        }
    };
    this.filterByKeywords = function() {
        this.unsetSearch();
        var kw = [];
        this.$prod.find('.curKeywords').children('div').each(function() {
            var txt = $(this).find('span.keyword').text();
            var type = $(this).find('.kwType').attr('class').replace(/^kwType kwt_(.*)$/, '$1');
            kw.push({
                'txt': txt,
                'type': type
            });
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        for (gpe in ptr.parts) {
            var n = 0;
            for (id in ptr.parts[gpe]) {
                var display = false;
                for (i in kw) {
                    var str = null;
                    var regex = null;
                    switch (kw[i].type) {
                        case 'eeeCode':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = ptr.parts[gpe][id].eeeCode;
                            break;

                        case 'name':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = ptr.parts[gpe][id].name;
                            break;

                        case 'num':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = ptr.parts[gpe][id].num;
                            str += ' ' + ptr.parts[gpe][id].newNum;
                            break;

                        case 'price':
                            kw[i].txt = kw[i].txt.replace(/,/g, '.');
                            regex = new RegExp('^' + kw[i].txt + '\.*\d*$', 'i');
                            str = ptr.parts[gpe][id].price;
                            break;

                        case 'type':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = ptr.parts[gpe][id].type;
                            break;
                    }
                    if (str) {
                        if (regex.test(str)) {
                            display = true;
                        }
                    }
                }
                if (display) {
                    this.$prod.find('.partGroup_' + gpe).find('tr.partRow_' + id).show();
                    n++;
                }
            }
            this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').html('(' + n + ' produits)');
            if (n > 0)
                this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').css('color', '#00780A');
            else
                this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').css('color', '#960000');
        }
    };
    this.unsetKeywordsFilter = function() {
        this.$prod.find('.curKeywords').find('div').each(function() {
            $(this).remove();
        });
        this.$prod.find('.searchResult').html('');
        $('tr.partRow').each(function() {
            $(this).show();
        });
        for (gpe in this.parts) {
            this.$prod.find('.partGroup_' + gpe).find('span.partsNbr').html('(' + this.parts[gpe].length + ' produits)').css('color', '#505050');
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
    this.PM = new PartsManager(id, serial);
    this.cart = new Cart(id, serial, this.PM);

    this.loadDatas = function() {
        $('#requestsResponsesContainer').append('<div id="prod_' + this.id + '" class="productDatasContainer"></div>');
        this.PM.newProdContainer();
        this.cart.newProdContainer();
        setRequest('GET', 'loadProduct', this.id, '&prodId=' + this.id + '&serial=' + serial);
        displayRequestMsg('requestProcess', '');
    };
    this.loadParts = function() {
        this.PM.loadParts();
    };
}

function GSX() {
    this.products = [];
    this.nextProdId = 1;

    this.importPartsFromCartToRepair = function(repair) {
        var $form = $('#repairForm_' + repair);
        if ($form.length) {
            $form.find('.partsImportResults').hide().html('');
            var $container = $form.find('div.repairPartsContainer');
            var $template = $form.find('div.repairsPartsInputsTemplate');
            var prodId = $form.parent('div.repairFormContainer').parent('div.repairPopUp').find('input.prodId').val();
            if (prodId && $container.length && $template.length) {
                var $prod = $('#prod_' + prodId);
                if ($prod.length) {
                    var $cart = $prod.find('div.cartContent');
                    if (!$cart.length) {
                        $form.find('.partsImportResults').html('<p class="alert">Veuillez charger la liste des composants pour afficher le panier</p>').slideDown(250);
                        return;
                    }
                    var $partsRows = $cart.find('table.cartProducts').find('tbody').find('tr');
                    if (!$partsRows.length) {
                        $form.find('.partsImportResults').html('<p class="alert">Veuillez ajouter des éléments au panier depuis la liste des composants compatibles</p>').slideDown(250);
                        return;
                    }
                    $container.html('');
                    var i = 1;
                    $partsRows.each(function() {
                        var partName = $(this).find('td:first').text();
                        var html = '<div class="partDatasBlock">';
                        html += '<div class="partDatasBlockTitle open" onclick="togglePartDatasBlockDisplay($(this))">' + partName + '</div>';
                        html += '<div class="partDatasContent partDatasContent_' + i + '">';
                        html += $template.html();
                        html += '</div></div>';
                        $container.append(html);
                        var $div = $container.find('div.partDatasContent_' + i + '');
                        var $partRow = $(this);
                        if ($div.length) {
                            $div.find('div.dataBlock').each(function() {
                                var dataName = $(this).find('label').attr('for');
                                var select = null;
                                switch (dataName) {
                                    case 'partNumber':
                                        $(this).find('input').val($partRow.find('td.ref').text()).attr('id', 'partNumber_' + i).attr('name', 'partNumber_' + i);
                                        break;

                                    case 'comptiaCode':
                                        select = '<select id="comptiaCode_' + i + '" name="comptiaCode_' + i + '">';
                                        select += $partRow.find('select.compTIACodeSelect').html();
                                        select += '</select>';
                                        $(this).find('div.comptiaCodeContainer').html(select);
                                        $(this).find('div.comptiaCodeContainer').find('select').val($partRow.find('select.compTIACodeSelect').val());
                                        break;

                                    case 'comptiaModifier':
                                        select = '<select id="comptiaModifier_' + i + '" name="comptiaModifier_' + i + '">';
                                        select += $partRow.find('select.compTIAModifierSelect').html();
                                        select += '</select>';
                                        $(this).find('div.comptiaModifierContainer').html(select);
                                        $(this).find('div.comptiaModifierContainer').find('select').val($partRow.find('select.compTIAModifierSelect').val());
                                        break;

                                    default:
                                        $(this).find('#' + dataName).attr('id', dataName + '_' + i).attr('name', dataName + '_' + i);
                                        $(this).find('#' + dataName + '_yes').attr('id', dataName + '_yes_' + i).attr('name', dataName + '_' + i);
                                        $(this).find('#' + dataName + '_no').attr('id', dataName + '_no_' + i).attr('name', dataName + '_' + i);
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
    this.loadProduct = function(serial) {
        if (!serial) {
            displayRequestMsg('error', 'Veuillez entrer un numéro de série');
            return;
        }

        if (!/^[0-9A-Z]{11}[0-9A-Z]*$/.test(serial)) {
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
    this.onProductLoad = function(prodId) {
        this.products[prodId].cart.setEvents();
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
            displayRequestMsg('error', 'Erreur : produit non initialisé', $('#prod' + prodId).find('.partsRequestResult'));
        }
    };
    this.addPart = function(prodId, group, name, num, type, price, eeeCode, newNum) {
        this.products[prodId].PM.addPart(group, name, num, type, price, eeeCode, newNum);
    };
    this.displayParts = function(prodId) {
        this.products[prodId].PM.displayParts();
        this.products[prodId].cart.onPartsListLoad();
    };
    this.loadRepairForm = function($button) {
        var prodId = getProdId($button.parent('p').parent('div.repairPopUp'));
        if (!prodId) {
            alert('Erreur : ID produit absent');
            return;
        }
        var $prod = $('#prod_' + prodId);
        if (!$prod.length) {
            alert('Une erreur est survenue, opération impossible');
            return;
        }
        if (this.products[prodId]) {
            var requestType = $prod.find('.repairTypeSelect').val();
            setRequest('GET', 'loadRepairForm', prodId, '&requestType=' + requestType + '&prodId=' + prodId + '&serial=' + this.products[prodId].serial + '&symCode=' + $("#symptomesCodes").val());
            displayRequestMsg('requestProcess', '', $prod.find('div.repairFormContainer'));
        } else {
            displayRequestMsg('error', 'Erreur : produit non initialisé', $prod.find('.partsRequestResult'));
        }
    };
}

var GSX = new GSX();

function onCaptionClick($caption) {
    if (!$caption.length)
        return;

    var $container = $caption.parent('.container');
    if (!$container.length)
        return;

    var $content = $container.find('.blocContent').first();
    if (!$content.length)
        return;

    if ($content.css('display') == 'none') {
        $content.stop().slideDown();
        $caption.find('span.arrow').attr('class', 'arrow upArrow');
    } else {
        $content.stop().slideUp();
        $caption.find('span.arrow').attr('class', 'arrow downArrow');
    }
}
function displayRequestMsg(type, msg, $div) {
    if ((type == 'requestProcess') && (msg === ''))
        msg = 'Requête en cours de traitement';

    if (!$div)
        $div = $('#requestResult');

    var html = '<p class="' + type + '">' + msg + '</p>';

    $div.html(html).hide().slideDown(250);
}
function displayCartRequestResult(prodId, html) {
    var $cart = $('#prod_' + prodId).find('.cartRequestResults');
    $cart.find('p.requestProcess').first().slideUp(250, function() {
        $(this).remove();
        $cart.append(html);
        var $p = $cart.find('p').last();
        $p.fadeOut(5000, function() {
            $(this).remove();
        });
    });
}
function getProdId($obj) {
    if (!$obj.length)
        return 0;
    var $parent = $obj;
    while (1) {
        $parent = $parent.parent();
        if (!$parent.length)
            return 0;
        if ($parent.hasClass("productDatasContainer"))
            break;
    }
    var id = $parent.attr('id').replace(/^prod_(\d+)$/, '$1');
    if (!id)
        return 0;
    return id;
}
function checkProdQty($input) {
    var val = $input.val();
    if (!val) {
        $input.val(1);
    }
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
    if (val == 1)
        return;

    val--;
    if (val < 1)
        val = 1;
    $input.val(val);
    $input.trigger('change');
}
function prodQtyUp($button) {
    var $input = $button.parent().find('input.prodQty');
    var val = parseInt($input.val());
    if (val == maxProdQty)
        return;
    val++;
    if (val > maxProdQty)
        val = maxProdQty;
    $input.val(val);
    $input.trigger('change');
}
function onYesNoBlockMouseOver($div) {
    $div.parent('div.dataBlock').css({
        'padding': '4px 0',
        'border-bottom': '1px solid #B4B1AD',
        'border-top': '1px solid #B4B1AD'
    }).find('label.dataTitle').css({
        'color': '#1E1E1E'
    });

}
function onYesNoBlockMouseOut($div) {
    $div.parent('div.dataBlock').css({
        'padding': '5px 0',
        'border-bottom': 'none',
        'border-top': 'none'
    }).find('label.dataTitle').css({
        'color': '#505050'
    });
}
function onComptiaGroupSelect($select) {
    var val = $select.val();
    if (typeof (val) != 'undefined') {
        var $options = $select.parent('.dataBlock').parent('fieldset').find('#comptiaCode').find('option');
        var classe = 'comptiaGroup_' + val;
        var newVal = null;
        $options.each(function() {
            if ($(this).hasClass(classe)) {
                if (newVal == null) {
                    newVal = $(this).attr('value');
                    $(this).parent('select').val(newVal)
                }
                $(this).show();
            }
            else
                $(this).hide();
        });
    }
}
function switchUpdateSerialForm($span) {
    var $container = $span.parent('div.serialUpdateFormContainer');
    var val = $container.find('select.updateFormSelect').val();
    $container.find('.updateSerialFormBlock').each(function() {
        if ($(this).css('display') == 'none') {
            if ($(this).hasClass(val))
                $(this).stop().slideDown(250);
            else
                $(this).stop().hide();
        } else {
            if ($(this).hasClass(val))
                $(this).stop().show();
            else
                $(this).stop().slideUp(250);
        }
    });
}
function displaySoapMessage($span) {
    var $container = $span.parent('p').parent('div.repairInfos').find('.soapMessageContainer');
    if (!$container.length) {
        alert('ici');
        return;
    }

    if ($container.css('display') == 'none') {
        $container.stop().slideDown(250);
    } else {
        $container.stop().slideUp(250);
    }
}

function displayCreateRepairPopUp($button) {
    var prodId = getProdId($button);
    var $popUp = $('#prod_' + prodId).find('div.repairPopUp')
    if ($popUp.length) {
        $popUp.show();
        setNewScrollToAnchor($popUp);
    }
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
function openRepairImportForm(prodId) {
    var $form = $('#prod_' + prodId).find('div.importRepairForm');
    if (!$form.length) {
        alert('Erreur: impossible d\'afficher le formulaire');
        return;
    }
    $form.slideDown(250);
}
function closeRepairImportForm(prodId) {
    var $form = $('#prod_' + prodId).find('div.importRepairForm');
    if (!$form.length)
        return;
    $form.slideUp(250);
}
function closeRepairSubmit($span, repairId, checkRepair) {
    if ($span.hasClass('deactivated'))
        return;

    if (checkRepair)
        checkRepair = 1;
    else
        checkRepair = 0;
    if (confirm("Attention, La réparation va être indiquée comme complète auprès du service GSX d'Apple.\nVeuillez confirmer")) {
        var $container = $('#repair_' + repairId).find('.repairRequestsResults');
        if ($container.length) {
            $span.attr('class', 'button redHover closeRepair deactivated');
            displayRequestMsg('requestProcess', '', $container);
            setRequest('GET', 'closeRepair', repairId, '&repairRowId=' + repairId + '&checkRepair=' + checkRepair);
            return;
        }
        alert('Une erreur est survenue, opération impossible.');
    }
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
    var html = '<span class="' + type + '">' + msg + '</span>';
    var $checkSpan = $input.parent('div.dataBlock').find('span.dataCheck');
    if ($checkSpan.length) {
        $checkSpan.slideUp(250);
        $checkSpan.html(html).slideDown(250);
    }
}
function checkInput($input, type) {
    var val = $input.val();
    if (!val.length) {
        if ($input.attr('required') !== undefined) {
            assignInputCheckMsg($input, 'notOk', 'Information obligatoire');
            return false;
        }
        assignInputCheckMsg($input, '', '');
        return true;
    }
    switch (type) {
        case 'text':
            break;

        case 'alphanum':
            if (!/^[a-zA-Z0-9\-\._ ]+$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Caractères interdits');
                return false;
            }
            break;

        case 'phone':
            val = val.replace(/\./g, '');
            val = val.replace(/\-/g, '');
            val = val.replace(/\//g, '');
            val = val.replace(/ /g, '');
            $input.val(val);
            if (!/^[0-9]{10}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format invalide');
                return false;
            }
            break;

        case 'email':
            if (!/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z\p{L}0-9]+$/i.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de l\'adresse e-mail invalide');
                return false;
            }
            break;

        case 'zipCode':
            if (!/^[0-9]{5}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format du code postal invalide');
                return false;
            }
            break;

        case 'date':
            if (!/^[0-9]{2}\/[0-9]{2}\/[0-9]{2,4}$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de la date invalide. (Attendu: JJ/MM/AA)');
                return false;
            } else if (/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/.test(val))
                $input.val(val.replace(/^([0-9]{2}\/[0-9]{2}\/)[0-9]{2}([0-9]{2})$/, '$1$2'));
            break;

        case 'time':
            if (!/^[0-9]{2}:[0-9]{2}?$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format de l\'heure invalide. (Attendu: HH:MM)');
                return false;
            }
            var hours = parseInt(val.replace(/^([0-9]{2}):[0-9]{2}.*$/, '$1'));
            var mins = parseInt(val.replace(/^[0-9]{2}:([0-9]{2}).*$/, '$1'));
            if (mins > 59) {
                assignInputCheckMsg($input, 'notOk', 'Heure incorrecte');
                return false;
            }
            if (hours > 23) {
                assignInputCheckMsg($input, 'notOk', 'Heure incorrecte');
                return false;
            }
            break;

        case 'num':
            if (!/^[0-9]*$/.test(val)) {
                assignInputCheckMsg($input, 'notOk', 'Format invalide (Chiffres uniquement).');
                return false;
            }
            break;
    }
    assignInputCheckMsg($input, 'ok', '');
    return true;
}
function duplicateInput($span, inputName) {
    var $container = $span.parent('div.dataBlock');
    if ($container.length) {
        var $template = $container.find('div.dataInputTemplate');
        var $index = $container.find('#' + inputName + '_nextIdx');
        if ($template.length) {
            if ($index.length) {
                var html = $template.html();
                var index = parseInt($index.val());
                var regex = new RegExp(inputName, 'g');
                index++
                html = html.replace(regex, inputName + '_' + index);
                var $list = $container.find('div.inputsList');
                if ($list.length) {
                    $list.append(html);
                    $index.val(index);
                    return;
                }
            }
        }
    }
    alert('Une erreur est survenue, opération impossible');
}
function duplicateDatasGroup($span, inputName) {
    var $container = $span.parent('legend').parent('fieldset');
    if ($container.length) {
        var $template = $container.find('div.dataInputTemplate');
        var $index = $container.find('#' + inputName + '_nextIdx');
        if ($template.length) {
            if ($index.length) {
                var html = '<div class="subInputsList">';
                html += $template.html();
                html += '</div>';
                var index = parseInt($index.val());
                var regex = new RegExp('_idx', 'g');
                html = html.replace(regex, '_' + index);
                index++
                var $list = $container.find('div.inputsList');
                if ($list.length) {
                    $list.append(html);
                    $index.val(index);
                    return;
                }
            }
        }
    }
    alert('Une erreur est survenue, opération impossible');
}

function submitGsxRequestForm(prodId, request, repairRowId) {
    var $prod = $('#prod_' + prodId);
    var $form = null;
    var formElement = null;
    var $resultContainer = null;
    if (typeof (repairRowId) != 'undefined') {
        var $repairContainer = $prod.find('#repair_' + repairRowId);
        if ($repairContainer.length) {
            $form = $repairContainer.find('#repairForm_' + request);
            switch (request) {
                case 'UpdateSerialNumber':
                    $resultContainer = $repairContainer.find('.partsPendingSerialUpdateResults');
                    break;

                case 'KGBSerialNumberUpdate':
                    $resultContainer = $repairContainer.find('.kgbSerialUpdateResults');
                    break;
            }

        }
    } else {
        if (!$prod.length) {
            $form = $('#repairForm_' + request);
        } else {
            $form = $prod.find('#repairForm_' + request);
            $resultContainer = $prod.find('.repairFormResults');
        }
    }
    if (!$form.length) {
        alert('Erreur: échec d\'identification du formulaire. Abandon');
        return;
    }
    formElement = $form.get(0);
    var partCount = $form.find('div.partDatasBlock').length;

    var $template = $form.find('div.repairsPartsInputsTemplate');
    var templateHtml = '';
    if ($template.length) {
        templateHtml = $template.html();
        $template.html('');
    }

    var check = true;
    var $inputs = $form.find('input');
    var $areas = $form.find('textarea')
    if ($areas.length) {
        $inputs = $inputs.add($areas);
    }
    $inputs.each(function() {
        if ($(this).attr('onchange')) {
            var type = $(this).attr('onchange').replace(/^checkInput\(\$\(this\), '(.*)'\)$/, '$1');
            if (!checkInput($(this), type)) {
                check = false;
            }
        }
    });
    if ($template.length) {
        $template.html(templateHtml);
    }
    if (!check) {
        alert("Des erreurs ont été détectées.\nMerci de corriger ces dernières avant de valider le formulaire.");
        return;
    }

    $form.find('#partsCount').val(partCount);
    if ($resultContainer.length) {
        displayRequestMsg('requestProcess', '', $resultContainer);
    }
    $.ajax({
        type: "POST",
        url: $form.attr('action'),
        dataType: 'html',
        processData: false,
        contentType: false,
        data: new FormData(formElement), // Non compatible pour les IE < 10
        success: function(html) {
            traiteCommandeRetour(html, $resultContainer);
        },
        error: function(html) {
            alert("passage en erreur" + html);
            traiteCommandeRetour(html, $resultContainer);
            displayRequestMsg('error', 'Une erreur technique est survenue. Ajax', $resultContainer);
        }
    });
}

function traiteCommandeRetour(html, $resultContainer) {
    if ($resultContainer.length) {
        $resultContainer.html(html);
        if ($resultContainer.find("prix").size() > 0) {
            prix = $resultContainer.find("prix").html();
            $resultContainer.find("prix").html("");
            if (prix > 0)
                alert("Attention la réparation n'est pas prise sous garantie. Prix : " + prix + " €");

            window.location.replace(window.location.href.replace("card.php", "request.php") + "&actionEtat=commandeOK&sendSms=" + confirm("Envoyer SMS ?") + "&prix=" + prix);
        }
        if (html.indexOf('<formSus>OK</formSus>') !== -1) {
            htmlFormSus = html.replace("<formSus>OK</formSus>", "");
            $(".formSus").append(htmlFormSus);
            $resultContainer.html("Renseignez les Composants");
        }
        if (html.indexOf('<showavailableRepairStrategies>OK</showavailableRepairStrategies>') !== -1) {
            $(".showavailableRepairStrategies").show();
        }
        if (html.indexOf('<horsgarantie>OK</horsgarantie>') !== -1) {
            $resultContainer.html("La réparation est hors garantie. Veuillez vérifier.");
            if(confirm("La réparation est hors garantie, voulez vous continuer ?")){
                $("#checkIfOutOfWarrantyCoverage_no").attr("checked", "checked");
                $("#checkIfOutOfWarrantyCoverage_yes").removeAttr("checked");
                submitGsxRequestForm(1, 'CreateCarryInRepair');
            }
        }
    }

}

function importRepairSubmit(prodId) {
    var $prod = $('#prod_' + prodId);
    if ($prod.length) {
        var $input = $prod.find('#importNumber');
        if ($input.length) {
            var $resultContainer = $prod.find('div.importRepairResult');
            if ($resultContainer.length) {
                var number = $input.val();
                if (!number.length) {
                    displayRequestMsg('error', 'Veuillez indiquer un identifiant et sélectionner le type correspondant.', $resultContainer);
                    return;
                } else {
                    if (checkInput($input, 'text')) {
                        var params = '&importNumber=' + number + '&importNumberType=' + $prod.find('#importNumberType').val();
                        setRequest('GET', 'importRepair', prodId, params);
                        displayRequestMsg('requestProcess', '', $resultContainer);
                        return;
                    } else {
                        displayRequestMsg('error', 'L\'identifiant indiqué ne respecte pas le bon format', $resultContainer);
                        return;
                    }
                }
            }
        }
    }
    alert('Une erreur est survenue, opération impossible (ID produit absent)');
}
function getXMLHttpRequest() {
    var xhr = null;
    if (window.XMLHttpRequest || window.ActiveXObject) {
        if (window.ActiveXObject) {
            try {
                xhr = new ActiveXObject("msxml2.XMLHTTP");
            }
            catch (e) {
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
    var $span = null;
    if (xhr.responseText.indexOf('<ok>Reload</ok>') !== -1)
        location.reload();
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
            $div = $('#prod_' + prodId);
            if ($div.length) {
                $div.html(xhr.responseText).slideDown(250).show();
                GSX.onProductLoad(prodId);
            }
            else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;

        case 'loadRepairForm':
            $div = $('#prod_' + prodId).find('.repairFormContainer');
            if ($div.length) {
                $div.slideUp(250, function() {
                    $(this).html(xhr.responseText).show();
                    if (importCart) {
                        $(this).find('span.importParts').click();
                    }  
                    $("#requestReviewByApple_yes").click(function(){
                        $("#checkIfOutOfWarrantyCoverage_no").click();
                    });
                });
            } else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;

        case 'loadParts':
            $div = $('#prod_' + prodId).find('.partsRequestResult');
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
            if (xhr.responseText == 'ok') {
                $('#prod_' + prodId).find('.cartRequestResults').find('ok').remove();
                displayCartRequestResult(prodId, '<p class="confirmation">Ajout à la propal effectué</p>');
            } else
                displayCartRequestResult(prodId, xhr.responseText);
            GSX.products[prodId].cart.activateSave();
            break;

        case 'loadPartsCart':
            if (xhr.responseText == 'noSerial') {
                displayCartRequestResult(prodId, '<p class="error">Erreur: numéro de série absent</p>');
            } else if (xhr.responseText == 'noPart') {
                displayCartRequestResult(prodId, '<p class="alert">Aucun composant enregistré pour ce numéro de série</p>');
            } else if (xhr.responseText == 'noDb') {
                displayCartRequestResult(prodId, '<p class="error">Impossible d\'accéder à la base de données.</p>');
            } else {
                $('#prod_' + prodId).find('.cartRequestResults').html('').hide();
                eval(xhr.responseText);
            }
            break;

        case 'importRepair':
            var $prod = $('#prod_' + prodId);
            if ($prod.length) {
                $('#prod_' + prodId).find('.importRepairResult').slideUp(250, function() {
                    $(this).html(xhr.responseText).slideDown(250);
                })
            } else {
                displayRequestMsg('error', 'Erreur : container absent pour cet ID produit, impossible d\'afficher les données');
            }
            break;

        case 'closeRepair':
            var repairRowId = prodId;
            var $repair = $('#repair_' + repairRowId);
            if ($repair.length) {
                var $repairResult = $repair.find('div.repairRequestsResults');
                $span = $repair.find('span.closeRepair');
                if ($repairResult.length) {
                    if (xhr.responseText == 'ok') {
                        displayRequestMsg('confirmation', 'La réparation a été fermée avec succés.<ok>Reload</ok>', $repairResult);
                        $span.hide();
                        return;
                    }
                    $span.attr('class', 'button redHover closeRepair');
                    $repairResult.show().html(xhr.responseText);
                    return;
                }
            } else {
                alert(xhr.responseText);
            }
            break;

        case 'addCartToPropal':
            alert('kkkkkkk');
            if (xhr.responseText == 'ok') {
                $span = $('#prod_' + prodId).find('span.addToPropal');
                $span.attr('class', 'button addToPropal');
                $('#prod_' + prodId).find('.cartRequestResults').find('ok').remove();
                displayCartRequestResult(prodId, '<p class="confirmation">Ajout à la propal effectué</p>');
            } else
                displayCartRequestResult(prodId, xhr.responseText);
            break;
    }
}
function setRequest(method, requestType, prodId, requestParams) {
    var xhr = getXMLHttpRequest();

    xhr.onreadystatechange = function() {
        //alert('state: ' + xhr.readyState + ', status: ' +xhr.status);
        var RT = requestType;
        var ID = prodId;
        if ((xhr.readyState == 4) && ((xhr.status == 200) || (xhr.status == 0))) {
            onRequestResponse(xhr, RT, ID);
        }
    }
    switch (method) {
        case 'GET':
            xhr.open(method, DOL_URL_ROOT + '/synopsisapple/ajax/requestProcess.php?action=' + requestType + requestParams + extra);
            xhr.send();
            break;

        case 'POST':
            xhr.open(method, DOL_URL_ROOT + '/synopsisapple/ajax/requestProcess.php?action=' + requestType + extra);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send(requestParams);
            break;
    }
}

var $curAnchor = null;
var count = 0;
function scrollToAnchor() {
    // Ne pas appellet directement, passer par setNewScrollToAnchor()
    if (!$curAnchor)
        return;

    if (!$curAnchor.length) {
        $curAnchor = null;
        return;
    }

    var start = $(window).scrollTop();
    var end = $curAnchor.offset().top;
    var distance = end - start;
    distance /= 2;

    var newScroll = 0;
    if ((distance > 0) && (distance <= 2))
        newScroll = end;
    else if ((distance < 0) && (distance >= -2))
        newScroll = end;
    else {
        newScroll = start + distance;
        if (newScroll < 0)
            newScroll = 0;
        var maxScroll = $('body').height() - $(window).height();
        if ((newScroll > maxScroll) && (maxScroll > 0))
            newScroll = maxScroll;
    }

    $(window).scrollTop(newScroll);
    count++;
    if (count < 20) {
        if (newScroll != end) {
            setTimeout(function() {
                scrollToAnchor();
            }, 100)
            return;
        }
    }
    $curAnchor = null;
}

function setNewScrollToAnchor($anchor) {
    count = 0;
    $curAnchor = $anchor;
    scrollToAnchor();
}
