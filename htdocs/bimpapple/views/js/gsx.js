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
    'F': 'iPad',
    'W': 'Watch'
};

var partDataType = {
    'eeeCode': 'eeeCode',
    'name': 'Nom',
    'num': 'Ref.',
    'type': 'Type',
    'price': 'Prix'
};

function PartsManager() {
    var ptr = this;
    this.$container = $('#partsListContainer');

    this.getPartListId = function (gpe, num) {
        if (this.parts[gpe]) {
            for (idx in this.parts[gpe]) {
                if (this.parts[gpe][idx].num == num)
                    return idx;
            }
        }
        return null;
    };

    // Gestion de l'affichage:
    this.resetPartsDisplay = function () {
        this.$container.find('tr.partRow').each(function () {
            $(this).show();
        });
        this.$container.find('.parts_group_panel').each(function () {
            $(this).find('.partsNbr').removeClass('badge-danger').text($(this).find('.partRow').length);
        });
    };

    // Gestion des filtres et des recherches:
    this.addKeywordFilter = function () {
        var kw = this.$container.find('.keywordFilter').val();
        if (!kw) {
            bimp_msg('Veuillez entrer un mot-clé', 'danger');
            return;
        }
        if (/ +/.test(kw)) {

            return;
        }
        if (!/^[a-zA-Z0-9\.,\-]+$/.test(kw)) {
            bimp_msg('Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques', 'danger');
            return;
        }
        var kwType = this.$container.find('select.keywordFilterType').val();

        var html = '<div class="curKeyword"><span class="keyword">' + kw + '</span>';
        html += '<span class="kwType kwt_' + kwType + '">&nbsp;&nbsp;(' + partDataType[kwType] + ')</span>';
        html += '<span class="removeKeyWord" onclick="PM.removeKeywordFilter($(this))"><i class="fa fa-trash"></i></span></div>';
        this.$container.find('.curKeywords').append(html);
        this.$container.find('.keywordFilter').val('');
        this.filterByKeywords();
    };

    this.removeKeywordFilter = function ($span) {
        $span.parent('div.curKeyword').remove();
        if (this.$container.find('.curKeywords').find('div').length)
            this.filterByKeywords();
        else
            this.unsetKeywordsFilter();
    };

    this.searchPartByNum = function () {
        var $result = this.$container.find('.searchResult');
        var search = this.$container.find('.searchPartInput').val();
        if (!search) {
            $result.append('<p class="alert alert-danger">Veuillez entrer un code produit</p>');
            $result.find('p.alert-danger').fadeOut(2000, function () {
                $(this).remove();
            });
            return;
        }
        if (!/^[a-zA-Z0-9\-\_ ]+$/.test(search)) {
            $result.append('<p class="alert alert-danger">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
            $result.find('p.alert-danger').fadeOut(5000, function () {
                $(this).remove();
            });
            return;
        }
        this.unsetSearch();
        this.$container.find('.searchResult').html('<div class="partSearchNum"><span class="searchNum">' + search + '</span><span class="removeSearch" onclick="GSX.products[' + this.prodId + '].PM.unsetSearch()"></span></div>');
        this.$container.find('.curKeywords').find('div').each(function () {
            $(this).remove();
        });
        $('tr.partRow').each(function () {
            $(this).hide();
        });
        var n = 0;
        for (gpe in this.parts) {
            this.$container.find('.partGroup_' + gpe).find('span.partsNbr').html('');
            var check = false;
            for (id in this.parts[gpe]) {
                if ((this.parts[gpe][id].num == search) || (this.parts[gpe][id].newNum == search)) {
                    check = true;
                    n++;
                    this.$container.find('.partGroup_' + gpe).find('tr.partRow_' + id).show();
                }
            }
            if (check) {
                this.showPartsGroup(gpe);
                this.openPartsGroup(this.$container.find('.partGroup_' + gpe).find('div.partGroupName'));
            } else {
                this.hidePartsGroup(gpe);
                this.closePartsGroup(this.$container.find('.partGroup_' + gpe).find('div.partGroupName'));
                this.$container.find('.partGroup_' + gpe).find('span.partsNbr').html('');
            }
        }
        if (!n) {
            this.$container.find('.searchResult').append('<p class="alert alert-danger">Aucun composant compatible ne correspond à ce numéro</p>');
        }
    };
    this.filterByKeywords = function () {
        this.unsetSearch();
        var kw = [];
        this.$container.find('.curKeywords').children('div.curKeyword').each(function () {
            var txt = $(this).find('span.keyword').text();
            var type = $(this).find('.kwType').attr('class').replace(/^kwType kwt_(.*)$/, '$1');
            kw.push({
                'txt': txt,
                'type': type
            });
        });

        this.$container.find('.parts_group_panel').each(function () {
            var n = 0;

            $(this).find('tr.partRow').each(function () {
                var display = false;
                for (i in kw) {
                    var str = null;
                    var regex = null;
                    switch (kw[i].type) {
                        case 'eeeCode':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('eeeCode');
                            break;

                        case 'name':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('name');
                            break;

                        case 'num':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('num') + ' ' + $(this).data('newNum');
                            break;

                        case 'price':
                            kw[i].txt = kw[i].txt.replace(/,/g, '.');
                            regex = new RegExp('^' + kw[i].txt + '\.*\d*$', 'i');
                            str = $(this).data('price');
                            break;

                        case 'type':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('type');
                            break;
                    }
                    if (str) {
                        if (regex.test(str)) {
                            display = true;
                        }
                    }
                }
                if (display) {
                    $(this).show();
                    n++;
                } else {
                    $(this).hide();
                }
            });

            $(this).find('.partsNbr').text(n);
            if (n > 0) {
                $(this).find('.partsNbr').removeClass('badge-danger');
            } else {
                $(this).find('.partsNbr').addClass('badge-danger');
            }
        });
    };
    this.unsetKeywordsFilter = function () {
        this.$container.find('.curKeywords').find('div').each(function () {
            $(this).remove();
        });
        this.$container.find('.searchResult').html('');
        this.resetPartsDisplay();
    };
    this.unsetSearch = function () {
        if (this.$container.find('.partSearchNum').length) {
            this.$container.find('.searchResult').html('');
            this.resetPartsDisplay();
        }
    };
}

function reloadRepairsViews(id_sav) {
    BimpAjax('loadRepairs', {
        id_sav: id_sav
    }, $('#sav_repairs').children('.panel-body'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true
    });
}

function loadPartsList(serial, id_sav) {
    BimpAjax('loadPartsList', {
        serial: serial,
        id_sav: id_sav
    }, $('#partsListContainer'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
//            $('#loadPartsButtonContainer').slideUp(250);
        }
    });
}

function addPartToCart($button, id_sav) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $row = $button.findParentByClass('partRow');
    if ($.isOk($row)) {
        var data = {
            module: 'bimpsupport',
            object_name: 'BS_ApplePart',
            id_object: 0,
            id_sav: id_sav,
            label: $row.data('name'),
            part_number: $row.data('num'),
            component_code: $row.data('code'),
            stock_price: $row.data('price')
        };

        BimpAjax('saveObject', data, null, {
            $button: $button,
            success: function (result, bimpAjax) {
                $('body').trigger($.Event('objectChange', {
                    module: 'bimpsupport',
                    object_name: 'BS_ApplePart',
                    id_object: result.id_object
                }));
            },
            error: function (result, bimpAjax) {
                bimpAjax.$button.removeClass('disabled');
            }
        });
    } else {
        bimp_msg('Une errur est survenue. Opération impossible', 'danger', null, function () {

        });
    }
}