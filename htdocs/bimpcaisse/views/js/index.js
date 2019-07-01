var ticket_url = './ticket.php';

function BC_Vente() {
    var ptr = this;

    this.reset = function () {
        this.id_vente = 0;
        this.id_client = 0;
        this.nb_articles = 0;
        this.nb_returns = 0;
        this.total_ttc = 0;
        this.total_remises_vente = 0;
        this.total_remises_articles = 0;
        this.total_remises = 0;
        this.total_returns = 0;
        this.total_discounts = 0;
        this.toPay = 0;
        this.toReturn = 0;
        this.avoir = 0;
        this.remises = [];
        this.returns = [];
        this.paiement_differe = 0;
    };

    this.ajaxResult = function (result) {
        if (typeof (result.vente_data) === 'undefined') {
            return;
        }

        if (typeof (result.vente_data.id_vente) !== 'undefined') {
            this.id_vente = result.vente_data.id_vente;
        }

        if (typeof (result.vente_data.nb_articles) !== 'undefined') {
            this.nb_articles = result.vente_data.nb_articles;
            var html = this.nb_articles + ' article';
            if (this.nb_articles > 1) {
                html += 's';
            }
            $('#venteNbArticles').html(html);
        }

        if (typeof (result.vente_data.total_ttc) !== 'undefined') {
            this.total_ttc = result.vente_data.total_ttc;
        }

        if (typeof (result.vente_data.total_remises_vente) !== 'undefined') {
            this.total_remises_vente = result.vente_data.total_remises_vente;
        }

        if (typeof (result.vente_data.total_remises_articles) !== 'undefined') {
            this.total_remises_articles = result.vente_data.total_remises_articles;
        }

        if (typeof (result.vente_data.total_remises) !== 'undefined') {
            this.total_remises = result.vente_data.total_remises;
        }

        if (typeof (result.vente_data.remises) !== 'undefined') {
            this.remises = result.vente_data.remises;
        }

        if (typeof (result.vente_data.paiement_differe) !== 'undefined') {
            this.paiement_differe = parseInt(result.vente_data.paiement_differe);
        }

        if (typeof (result.vente_data.toPay) !== 'undefined') {
            this.toPay = result.vente_data.toPay;
            displayMoneyValue(this.toPay, $('#venteToPay').find('span'));
            $('#ventePaiementMontant').val(this.toPay);
        }

        if (typeof (result.vente_data.toReturn) !== 'undefined') {
            this.toReturn = result.vente_data.toReturn;
            displayMoneyValue(this.toReturn, $('#venteToReturn').find('span'));
        }

        if (typeof (result.vente_data.avoir) !== 'undefined') {
            this.avoir = result.vente_data.avoir;
            displayMoneyValue(this.avoir, $('#venteAvoir').find('span'));
        }

        if (typeof (result.vente_data.articles) !== 'undefined') {
            for (var i in result.vente_data.articles) {
                var article = result.vente_data.articles[i];
                var $line = $('#cart_article_' + article.id_article);
                if ($line.length) {
                    var $input = $line.find('.article_qty_input');
                    if (article.qty && $input.length) {
                        $input.val(article.qty);
                    }

                    if (article.total_remises > 0) {
                        displayMoneyValue((article.total_ttc), $line.find('.base_price'));
                        $line.find('.base_price').show();
                    } else {
                        $line.find('.base_price').html('').hide();
                    }

                    displayMoneyValue((article.total_ttc - article.total_remises), $line.find('.final_price'));
                }
            }
        }

        if (typeof (result.vente_data.nb_returns) !== 'undefined') {
            this.nb_returns = result.vente_data.nb_returns;
            var html = this.nb_returns + ' article';
            if (this.nb_returns > 1) {
                html += 's';
            }
            $('#curVenteRetours').find('.nbReturns').text(html);
        }

        if (typeof (result.vente_data.total_returns) !== 'undefined') {
            this.total_returns = result.vente_data.total_returns;
        }

        if (typeof (result.vente_data.total_discounts) !== 'undefined') {
            this.total_discounts = result.vente_data.total_discounts;
        }

        if (typeof (result.vente_data.returns) !== 'undefined') {
            this.returns = result.vente_data.returns;
        }

        if (parseInt($('#venteHt').find('[name="vente_ht"]').val())) {
            $('.cart_total_label').text('Total HT');
        } else {
            $('.cart_total_label').text('Total TTC');
        }

//        displayMoneyValue(this.total_remises_vente, $('#venteRemises').find('.total_remises_vente span'));
        displayMoneyValue(this.total_remises_articles, $('#venteRemises').find('.total_remises_articles span'));
        displayMoneyValue(this.total_remises, $('#venteRemises').find('.total_remises span'));
        displayMoneyValue((this.total_ttc - this.total_remises), $('#ventePanierTotal span.cart_total'));
        displayMoneyValue(this.total_discounts, $('#totalDiscounts span'));

        $('#venteRemises').hide().find('.remises_lines').html('');
        $('#ventePanierLines').find('.cartArticleLine').each(function () {
            $(this).find('.article_remises').hide().find('.content').html('');
        });

        if (this.remises.length) {
            for (var j in this.remises) {
                var html = '<div class="remise">' + this.remises[j].label + ': <span>' + this.remises[j].montant;
                html += '</span><i class="fa fa-trash" onclick="deleteRemise($(this), ' + this.remises[j].id_remise + ')"></i></div>';
                if (this.remises[j].id_article) {
                    $('#cart_article_' + this.remises[j].id_article).find('.article_remises').show().find('.content').append(html);
                } else {
                    $('#venteRemises').find('.remises_lines').append(html);
                }
            }
        }

        var returns_html = '';
        if (this.returns.length) {
            for (var k in this.returns) {
                returns_html += '<div id="cur_vente_return_' + this.returns[k].id_return + '" class="returnLine" data-id_return="' + this.returns[k].id_return + '">';
                returns_html += '<div class="product_title">' + this.returns[k].label;
                returns_html += '<span class="removeArticle" onclick="removeReturn($(this), ' + this.returns[k].id_return + ')">';
                returns_html += '<i class="fa fa-trash"></i></span></div>';
                returns_html += '<div class="product_info"><strong>Ref.: </strong> : ' + this.returns[k].ref + '</div>';
                if (this.returns[k].serial) {
                    returns_html += '<div class="product_info"><strong>N° de série: </strong> : ' + this.returns[k].serial + '</div>';
                }
                returns_html += '<div class="product_info"><strong>Prix unitaire TTC</strong> : ' + this.returns[k].unit_price + '</div>';
                returns_html += '<div class="product_info"><strong>Quantité</strong> : ' + this.returns[k].qty + '</div>';
                returns_html += '<div class="product_info"><strong>Défectueux</strong> : ';
                if (parseInt(this.returns[k].defective)) {
                    returns_html += 'OUI';
                } else {
                    returns_html += 'NON';
                }
                returns_html += '</div>';
                if (this.returns[k].infos) {
                    returns_html += '<div class="product_info"><strong>Informations: </strong><br/>';
                    returns_html += '<div style="padding: 3px 10px; font-style: italic; background-color: #FFFFE1">' + this.returns[k].infos + '</div>';
                    returns_html += '</div>';
                }
                if (this.returns[k].warnings.length) {
                    returns_html += '<div class="product_info">';
                    for (var h in this.returns[k].warnings) {
                        if (typeof (this.returns[k].warnings[h]) === 'string' && this.returns[k].warnings[h] !== '') {
                            returns_html += '<div class="alert alert-danger">';
                            returns_html += this.returns[k].warnings[h];
                            returns_html += '</div>';
                        }
                    }
                    returns_html += '</div>';
                }
                returns_html += '<div class="article_options">';
                returns_html += '<div class="article_qty">&nbsp;</div>';
                returns_html += '<div class="product_total_price">';
                returns_html += '<span class="final_price">' + this.returns[k].total_ttc + '</span>';
                returns_html += '</div>';
                returns_html += '</div>';
                returns_html += '</div>';
            }
        }
        $('#returnsLines').html(returns_html);

        if (this.total_remises_articles > 0) {
            $('#venteRemises').find('.total_remises_articles').show();
        } else {
            $('#venteRemises').find('.total_remises_articles').hide();
        }

        if (this.total_remises > 0) {
            $('#venteRemises').show();
        } else {
            $('#venteRemises').hide();
        }

        if (this.total_returns > 0) {
            displayMoneyValue(this.total_returns, $('#curVenteRetours span.totalReturns'));
            $('#curVenteRetours').slideDown(250);
        } else {
            $('#curVenteRetours').slideUp(250);
        }

        if (this.avoir > 0) {
            $('#venteAvoir').slideDown(250);
            $('#avoirRbtForm').slideDown(250);

            $('#venteToReturn').slideUp(250);
            $('#venteToPay').slideUp(250);

            $('#ventePaiementButtons').slideUp(250);
            $('#venteAddPaiementFormContainer').slideUp(250);
            $('#ventePaimentsLines').slideUp(250);
            $('#condReglement').slideUp(250);
        } else {
            $('#ventePaiementButtons').slideDown(250);
            $('#ventePaimentsLines').slideDown(250);
            $('#condReglement').slideDown(250);

            $('#avoirRbtForm').slideUp(250);

            if (this.toReturn > 0) {
                $('#venteAvoir').slideUp(250);
                $('#venteToReturn').slideDown(250);
                $('#venteToPay').slideUp(250);
            } else {
                $('#venteAvoir').slideUp(250);
                $('#venteToReturn').slideUp(250);
                $('#venteToPay').slideDown(250);
            }
        }

        if ((this.nb_articles > 0 || this.nb_returns > 0) && (this.paiement_differe || this.toPay <= 0)) {
            $('#validateCurrentVenteButton').removeClass('disabled');
//            $('#saveCurrentVenteButton').addClass('disabled');
        } else {
            $('#validateCurrentVenteButton').addClass('disabled');
//            $('#saveCurrentVenteButton').removeClass('disabled');
        }
    };

    this.reset();

    this.refresh = function () {
        if (ptr.id_vente) {
            BimpAjax('loadVenteData', {
                id_vente: ptr.id_vente
            }, null, {
                display_success: false,
                display_errors_in_popup_only: true,
                success: function (result, bimpAjax) {
                    ptr.ajaxResult(result);
                }
            });
        }
    };
}

var Vente = new BC_Vente();

// Caisse: 

function openCaisse($button, confirm_fonds) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $form = $('#openCaisseForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        return;
    }

    var id_caisse = parseInt($form.find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Veuillez sélectionner une caisse', 'warning', null, true);
        return;
    }

    var fonds = parseFloat($form.find('[name="fonds"]').val());
    if (!fonds) {
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning', null, true);
        return;
    }

    BimpAjax('openCaisse', {
        id_caisse: id_caisse,
        fonds: fonds,
        confirm_fonds: confirm_fonds
    }, $('#openCaisseForm').find('.freeFormAjaxResult'), {
        display_success: false,
        append_html: true,
        $button: $button,
        success: function (result, bimpAjax) {
            if (typeof (result.need_confirm_fonds) !== 'undefined' && result.need_confirm_fonds) {
                bimpAjax.$button.html('<i class="fa fa-check iconLeft"></i>Confirmer');
                bimpAjax.$button.attr('onclick', 'openCaisse($(this), 1);');
            } else {
                window.location = document.location.href.replace(document.location.search, "");
            }
        }
    });
}

function closeCaisse($button, confirm_fonds) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $form = $('#closeCaisseForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        return;
    }

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Erreur: aucune caisse active', 'danger');
        return;
    }

    var fonds = parseFloat($form.find('[name="fonds"]').val());
    if (!fonds) {
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning', null, true);
        return;
    }

    BimpAjax('closeCaisse', {
        id_caisse: id_caisse,
        fonds: fonds,
        confirm_fonds: confirm_fonds
    }, $form.find('.freeFormAjaxResult'), {
        display_success: false,
        append_html: true,
        $button: $button,
        success: function (result, bimpAjax) {
            if (typeof (result.need_confirm_fonds) !== 'undefined' && result.need_confirm_fonds) {
                bimpAjax.$button.html('<i class="fa fa-check iconLeft"></i>Confirmer');
                bimpAjax.$button.attr('onclick', 'closeCaisse($(this), 1);');
            } else {
                if (typeof (result.recap_url) !== 'undefined' && result.recap_url) {
                    var recap_width = 827;
                    if (typeof (result.recap_width) !== 'undefined') {
                        recap_width = result.recap_width;
                    }
                    window.open(result.recap_url, 'Récapitulatif session de caisse', "menubar=no, status=no, width=" + recap_width + ", height=900");
                }
                window.location = document.location.href.replace(document.location.search, "") + '?id_entrepot=' + result.id_entrepot;
            }
        }
    });
}

function loadCaisseMvtForm($button) {
    var $params = $('#current_params');
    var id_entrepot = parseInt($params.find('[name="id_entrepot"]').val());
    var id_caisse = parseInt($params.find('[name="id_caisse"]').val());
    var caisse_name = $params.find('[name="caisse_name"]').val();

    if (!id_entrepot || !id_caisse) {
        bimp_msg('Aucune caisse ouverte. Opération impossible', 'danger');
        return;
    }

    var title = 'Mouvement de fonds pour la caisse "' + caisse_name + '"';

    var values = '{"fields": {"id_entrepot": ' + id_entrepot + ', "id_caisse": ' + id_caisse + '}}';
    loadModalForm($button, {
        module: 'bimpcaisse',
        object_name: 'BC_CaisseMvt',
        form_name: 'light',
        full_panel: 0,
        param_values: values
    }, title);
}

// Vente: 

function loadNewVente(id_client) {
    if (typeof (id_client) === 'undefined') {
        id_client = 0;
    }
    var $button = $('#newVenteButton');
    if (!$button.length || $button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    $('#venteTicketContainer').html('');

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());

    if (!id_caisse) {
        bimp_msg('Erreur: identifiant de la caisse absent', 'danger');
        return;
    }

    var $container = $('#currentVenteContainer');
    var $content = $('#currenVenteContent');
    var $listContainer = $('#listVentesContainer');

    $listContainer.stop().slideUp(250);
    $content.hide();
    $container.find('.footer_buttons').stop().hide();
    $container.stop().slideDown();
    $('#validateCurrentVenteButton').addClass('disabled');

    Vente.reset();

    BimpAjax('loadNewVente', {
        id_caisse: id_caisse,
        id_client: id_client
    }, $content, {
        $listContainer: $listContainer,
        $content: $content,
        $container: $container,
        display_success: false,
        display_processing: true,
        append_html: true,
        processing_msg: 'Chargement',
        error_msg: 'Echec du chargement',
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.footer_buttons').stop().slideDown(250);

            var $view = $('#BC_Vente_creation_view');
            if ($view.length) {
                onViewLoaded($view);
            }
            Vente.ajaxResult(result);

            onVenteLoaded();
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
            bimpAjax.$listContainer.stop().slideDown(250);
            bimpAjax.$content.html('').hide();
            bimpAjax.$container.hide();
        }
    });
}

function loadVente($button, id_vente) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());

    if (!id_caisse) {
        bimp_msg('Erreur: identifiant de la caisse absent', 'danger');
        return;
    }

    var $container = $('#currentVenteContainer');
    var $content = $('#currenVenteContent');
    var $listContainer = $('#listVentesContainer');

    $listContainer.stop().slideUp(250);
    $content.hide();
    $container.find('.footer_buttons').stop().hide();
    $container.stop().slideDown();
    $('#validateCurrentVenteButton').addClass('disabled');
    $('#newVenteButton').addClass('disabled');

    Vente.reset();

    BimpAjax('loadVente', {
        id_caisse: id_caisse,
        id_vente: id_vente
    }, $content, {
        $button: $button,
        $listContainer: $listContainer,
        $content: $content,
        $container: $container,
        display_success: false,
        display_processing: true,
        append_html: true,
        processing_msg: 'Chargement',
        error_msg: 'Echec du chargement',
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.footer_buttons').stop().slideDown(250);

            var $view = $('#BC_Vente_creation_view');
            if ($view.length) {
                onViewLoaded($view);
            }
            Vente.ajaxResult(result);

            onVenteLoaded();
        },
        error: function (result, bimpAjax) {
            $('#newVenteButton').removeClass('disabled');
            bimpAjax.$listContainer.stop().slideDown(250);
            bimpAjax.$content.html('').hide();
            bimpAjax.$container.hide();
        }
    });
}

function refreshVente() {
    Vente.refresh();
}

function saveCurrentVente($button, status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (status === 0) {
        if (Vente.nb_articles > 0) {
            if (!confirm('Etes-vous sûr de vouloir abandonner cette vente?')) {
                return;
            }
        }
    }

    var data = {
        id_vente: Vente.id_vente,
        status: status
    };

    if (status === 2) {
        if (Vente.avoir > 0) {
            data.avoir_rbt_mode = $('#avoirRbtMode').find('[name="avoir_rbt_mode"]').val();
            if (data.avoir_rbt_mode === 'rbt') {
                data.avoir_rbt_paiement = $('#avoirRbtModePaiement').find('[name="avoir_rbt_paiement"]').val();
            }
        }
    }

    BimpAjax('saveVenteStatus', data, null, {
        $button: $button,
        id_vente: Vente.id_vente,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {

            var $container = $('#currentVenteContainer');
            var $content = $('#currenVenteContent');
            var $listContainer = $('#listVentesContainer');

            if (status === 2) {
                if (typeof (result.validate_errors) !== 'undefined' && result.validate_errors.length) {
                    var html = '<div class="alert alert-danger alert-dismissible">';
                    html += '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>';
                    html += 'Des erreurs sont survenues lors de la validation de la vente n°' + bimpAjax.id_vente + '<br/>';
                    for (var i in result.validate_errors) {
                        html += ' - ' + result.validate_errors[i] + '<br/>';
                    }
                    html += '</div>';
                    if (result.validate) {
                        $('#venteErrors').append(html).slideDown(250);
                    } else {
                        $('#currentVenteErrors').html(html).slideDown(250);
                        return;
                    }
                }
            }

            $content.slideUp(250, function () {
                $(this).html('');
            });

            $container.find('.footer_buttons').hide();
            $container.stop().slideUp(250);
            $listContainer.slideDown(250);
            $('#newVenteButton').removeClass('disabled');
            Vente.reset();

            if ((status === 2) && result.validate) {
                var url = ticket_url + '?id_vente=' + bimpAjax.id_vente;
                window.open(url, 'Ticket de caisse', "menubar=no, status=no, width=370, height=600");
            }

            $('body').trigger($.Event('objectChange', {
                module: 'bimpcaisse',
                object_name: 'BC_Vente',
                id_object: bimpAjax.id_vente
            }));
        },
        error: function (result, bimpAjax) {
            if (status === 2) {
                if (typeof (result.validate_errors) !== 'undefined' && result.validate_errors.length) {
                    var html = '<div class="alert alert-danger alert-dismissible">';
                    html += '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>';
                    html += 'Des erreurs sont survenues lors de la validation de la vente n°' + bimpAjax.id_vente + '<br/>';
                    for (var i in result.validate_errors) {
                        html += ' - ' + result.validate_errors[i] + '<br/>';
                    }
                    html += '</div>';
                    if (result.validate) {
                        $('#venteErrors').append(html).slideDown(250);
                    } else {
                        $('#currentVenteErrors').html(html).slideDown(250);
                        return;
                    }
                }
            }
        }
    });
}

function setVenteStatus($button, id_vente, status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (status === 0) {
        if (!confirm('Etes-vous sûr de vouloir abandonner cette vente?')) {
            return;
        }
    }

    var data = {
        id_vente: id_vente,
        status: status
    };

    BimpAjax('saveVenteStatus', data, null, {
        $button: $button,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            reloadObjectList('BC_Vente_default_list_table');
        }
    });
}

// Client vente: 

function loadNewClientForm($button) {
    loadModalForm($button, {
        module: 'bimpcore',
        object_name: 'Bimp_Client',
        form_name: 'light'
    }, 'Ajout d\'un nouveau client', function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent', 'danger');
            return;
        }
        var $button = bimpModal.$footer.find('.save_object_button.modal_' + modal_idx);
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        bimpModal.removeContent(modal_idx);
                        bimpModal.hide();
                        $('#venteClientFormContainer').find('[name="id_client"]').val(result.id_object);
                        $('#venteClientFormContainer').find('[name="id_client_contact"]').val(0);
                        saveClient();
                    });
                }
            });
        }
    });
}

function loadNewContactForm($button, id_client) {
    loadModalForm($button, {
        module: 'bimpcore',
        object_name: 'Bimp_Contact',
        form_name: 'default',
        id_parent: id_client
    }, 'Ajout d\'un nouveau contact', function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent', 'danger');
            return;
        }
        var $button = bimpModal.$footer.find('.save_object_button.modal_' + modal_idx);
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        bimpModal.removeContent(modal_idx);
                        bimpModal.hide();
                        BimpAjax('saveContact', {
                            id_vente: Vente.id_vente,
                            id_contact: result.id_object
                        }, $('#contactViewContainer'), {
                            display_success: false,
                            display_errors_in_popup_only: true,
                            append_html: true
                        });
                    });
                }
            });
        }
    });
}

function editClient($button, id_client, client_name) {
    var title = 'Edition du client "' + client_name + '"';
    loadModalForm($button, {
        'module': 'bimpcore',
        'object_name': 'Bimp_Client',
        'id_object': id_client,
        'form_name': 'default'
    }, title, function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent', 'danger');
            return;
        }
        var $button = bimpModal.$footer.find('.save_object_button.modal_' + modal_idx);
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        bimpModal.removeContent(modal_idx);
                        bimpModal.hide();
                        $('#venteClientFormContainer').find('[name="id_client"]').val(result.id_object);
                        saveClient();
                    });
                }
            });
        }
    });
}

function editContact($button, id_contact, contact_name) {
    var title = 'Edition du contact "' + contact_name + '"';
    loadModalForm($button, {
        'module': 'bimpcore',
        'object_name': 'Bimp_Contact',
        'id_object': id_contact,
        'form_name': 'default'
    }, title, function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent', 'danger');
            return;
        }
        var $button = $('#page_modal').find('.modal-footer').find('.save_object_button');
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        bimpModal.removeContent(modal_idx);
                        bimpModal.hide();
                        BimpAjax('saveContact', {
                            id_vente: Vente.id_vente,
                            id_contact: result.id_object
                        }, $('#contactViewContainer'), {
                            display_success: false,
                            display_errors_in_popup_only: true,
                            append_html: true
                        });
                    });
                }
            });
        }
    });
}

function changeContact($select) {
    var id_contact = parseInt($select.val());

    BimpAjax('saveContact', {
        id_vente: Vente.id_vente,
        id_contact: id_contact
    }, $('#contactViewContainer'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true
    });
}

function saveClient() {
    var $container = $('#venteClientFormContainer');
    var $button = $container.find('#saveClientButton');
    var $resultContainer = $container.find('#saveClientResult');

    if ($button.hasClass('disabled')) {
        return;
    }

    if (!Vente.id_vente) {
        bimp_msg('Erreur opération impossible (ID de la vente absent)', 'danger');
        return;
    }

    var id_client = parseInt($container.find('[name="id_client"]').val());

    BimpAjax('saveClient', {
        id_vente: Vente.id_vente,
        id_client: id_client
    }, $resultContainer, {
        $container: $container,
        $button: $button,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                $('#venteClientViewContainer').html(result.html).slideDown(250);
                bimpAjax.$container.slideUp(250);
            }
            if (typeof (result.discounts_html) !== 'undefined') {
                $('#customerDiscounts').html(result.discounts_html);
                checkDiscounts();
            }
            refreshVente();
        }
    });
}

function selectClientFromList($button) {
    var $row = $button.findParentByClass('Bimp_Client_row');

    if (!$row.length) {
        bimp_msg('Erreur: ID client absent', 'danger');
        return;
    }

    var id_client = parseInt($row.data('id_object'));

    if (!id_client) {
        bimp_msg('Erreur: ID client absent', 'danger');
        return;
    }

    var $container = $('#venteClientFormContainer');
    if (!$container.length) {
        loadNewVente(id_client);
    } else {
        $container.find('[name="id_client"]').val(id_client);
        $container.find('[name="id_client_contact"]').val(0);

        saveClient();
    }

    $('#bc_main_container').find('a[href="#ventes"]').click();
}

// Données vente: 

function saveCondReglement() {
    if (!Vente.id_vente) {
        bimp_msg('Erreur opération impossible (ID de la vente absent)', 'danger');
        return;
    }

    BimpAjax('saveCondReglement', {
        id_cond: $('#condReglementSelect').val(),
        id_vente: Vente.id_vente
    }, null, {
        success: function (result, bimpAjax) {
            Vente.ajaxResult(result);
        }
    });
}

function saveVenteHt() {
    if (!Vente.id_vente) {
        bimp_msg('Erreur opération impossible (ID de la vente absent)', 'danger');
        return;
    }

    BimpAjax('saveVenteHt', {
        vente_ht: $('#venteHt').find('[name="vente_ht"]').val(),
        id_vente: Vente.id_vente
    }, null, {
        success: function (result, bimpAjax) {
            Vente.ajaxResult(result);
        }
    });
}

function saveCommercial() {
    BimpAjax('saveCommercial', {
        id_user_resp: parseInt($('#id_user_resp').val()),
        id_vente: Vente.id_vente
    }, null, {
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true
    });
}

function saveNotePlus() {
    BimpAjax('saveNotePlus', {
        note_plus: $('#note_plus').val(),
        id_vente: Vente.id_vente
    }, null, {
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true
    });
}

// Articles vente: 

function findProduct($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $input = $('#venteSearchProduct');
    var search = $input.val();

    if (typeof (search) === 'undefined' || !search) {
        bimp_msg('Veuillez saisir un code-barres ou un numéro de série', 'warning', null, true);
        return;
    }

    var $resultContainer = $('#findProductResult');

    BimpAjax('findProduct', {
        id_vente: Vente.id_vente,
        search: search
    }, $resultContainer, {
        display_success: false,
        display_processing: true,
        processing_padding: 0,
        processing_msg: 'Recherche en cours',
        $button: $button,
        $input: $input,
        success: function (result, bimpAjax) {
            if (typeof (result.result_html) !== 'undefined' && result.result_html) {
                $resultContainer.html(result.result_html).slideDown(250).css('height', 'auto');
            } else if (typeof (result.cart_html) !== 'undefined' && result.cart_html) {
                $('#ventePanierLines').prepend(result.cart_html);
                var $line = $('#ventePanierLines').find('.cartArticleLine:first');
                setCartLineEvents($line);
            }
            Vente.ajaxResult(result);
            bimpAjax.$input.val('').focus();
        },
        error: function (result, bimpAjax) {
            bimpAjax.$input.focus().select();
        }
    });
}

function selectArticle($button, id_object, object_name) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $('.selectArticleLine').each(function () {
        $(this).find('button').addClass('disabled');
    });

    $button.removeClass('disabled');

    $button.removeClass('disabled');
    BimpAjax('selectArticle', {
        id_vente: Vente.id_vente,
        id_object: id_object,
        object_name: object_name
    }, null, {
        $button: $button,
        display_success: false,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('#findProductResult').slideUp(250, function () {
                $(this).html('');
            });
            if (typeof (result.html) !== 'undefined' && result.html) {
                $('#ventePanierLines').prepend(result.html);
                var $line = $('#ventePanierLines').find('.cartArticleLine:first');
                setCartLineEvents($line);
            } else {
                $('.selectArticleLine').each(function () {
                    $(this).find('button').removeClass('disabled');
                });
            }
            Vente.ajaxResult(result);
            $('#venteSearchProduct').focus();
        },
        error: function (result, bimpAjax) {
            $('.selectArticleLine').each(function () {
                $(this).find('button').removeClass('disabled');
            });
        }
    });
}

function removeArticle($button, id_article) {
    if ($button.hasClass('disabled')) {
        return;
    }

    BimpAjax('removeArticle', {
        id_vente: Vente.id_vente,
        id_article: id_article
    }, null, {
        $button: $button,
        display_success: false,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('#cart_article_' + id_article).fadeOut(250, function () {
                $(this).remove();
            });
            Vente.ajaxResult(result);
        }
    });
}

function changeArticleQty($button, id_article, movement) {
    var $input = $('#article_' + id_article + '_qty');
    if (!$input.length) {
        bimp_msg('Une erreur est survenue. Impossible de changer les quantités de l\'article', 'danger');
        return;
    }

    var val = parseInt($input.val());

    switch (movement) {
        case 'up':
            val++;
            break;

        case 'down':
            if (val <= 1) {
                val = 1;
            } else {
                val--;
            }
            break;
    }

    $input.val(val).change();
}

function saveArticleQty(id_article) {
    var $input = $('#article_' + id_article + '_qty');
    if (!$input.length) {
        bimp_msg('Une erreur est survenue. Impossible d\'enregistrer les quantités de l\'article', 'danger');
        return;
    }
    var val = parseInt($input.val());

    if (val) {
        BimpAjax('saveArticleQty', {
            id_vente: Vente.id_vente,
            id_article: id_article,
            qty: val
        }, null, {
            qty: val,
            id_article: id_article,
            display_success: false,
            display_errors_in_popup_only: true,
            error_msg: 'Echec de l\'enregistrement de la quantité',
            success: function (result, bimpAjax) {
                Vente.ajaxResult(result);

//                if (result.total_ttc) {
//                    displayMoneyValue(result.total_ttc, $('#cart_article_' + bimpAjax.id_article).find('.product_total_price'));
//                }

                var $stockAlert = $('#cart_article_' + bimpAjax.id_article).find('.stockAlert');
                if ($stockAlert.length) {
                    $stockAlert.find('span.stock').text(result.stock);
                    if (result.stock < bimpAjax.qty) {
                        $stockAlert.slideDown(250);
                    } else {
                        $stockAlert.slideUp(250);
                    }
                }
            }
        });
    }
}

function saveArticleRemiseCrt($input) {
    if ($.isOk($input)) {
        var $container = $input.findParentByClass('cartArticleLine');
        if (!$.isOk($container)) {
            bimp_msg('Une erreur est survenue. L\'option n\'a pas pu être enregistrée', 'danger');
            return;
        }

        var id_article = parseInt($container.data('id_article'));
        var remise_crt = parseInt($input.val());

        saveObjectField('bimpcaisse', 'BC_VenteArticle', id_article, 'remise_crt', remise_crt);
    }
}

// Retours vente: 

function loadReturnForm($button) {
    loadModalForm($button, {
        'module': 'bimpcaisse',
        'object_name': 'BC_VenteReturn',
        'id_parent': Vente.id_vente
    }, 'Ajout d\'un retour produit');
}

function searchReturnedEquipment($button) {
    var $inputContainer = $button.findParentByClass('search_equipment_inputContainer');
    var $form = $inputContainer.findParentByClass('BC_VenteReturn_form');

    if (!$inputContainer.length || !$form.length) {
        bimp_msg('Une erreur est survenue', 'danger');
        return;
    }

    var serial = $inputContainer.find('[name="search_equipment"]').val();

    if (!serial) {
        bimp_msg('Aucun numéro de série spécifié', 'danger', null, true);
        return;
    }

    $inputContainer.find('[name="search_equipment"]').val('');

    BimpAjax('searchEquipmentToReturn', {
        serial: serial,
        id_vente: Vente.id_vente
    }, null, {
        $button: $button,
        $inputContainer: $inputContainer,
        $form: $form,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.equipments) === 'object') {
                var html = '<select name="id_equipment">';
                var first = true;
                for (var i in result.equipments) {
                    html += '<option value="' + result.equipments[i].id + '"';
                    html += ' data-id_client="' + result.equipments[i].id_client + '"';
                    if (first) {
                        html += ' selected="selected"';
                    }
                    html += '>' + result.equipments[i].label + '</option>';
                }
                html += '</select>';

                bimpAjax.$form.find('.id_equipment_inputContainer').html(html);
                var $parent = bimpAjax.$form.find('.id_equipment_inputContainer').parent();
                var $input = bimpAjax.$form.find('[name=id_equipment]');
                var initial_value = $input.find('option').first().attr('value');
                bimpAjax.$form.find('.id_equipment_inputContainer').data('initial_value', initial_value);
                setCommonEvents($parent);
                setInputsEvents($parent);
                setInputEvents(bimpAjax.$form.findParentByClass('BC_VenteReturn_form'), $input);
                bimpAjax.$form.find('[name="id_product"]').val(0).findParentByClass('formRow').slideUp(250);
                bimpAjax.$form.find('[name="show_equipment"]').val(1).change();
                bimpAjax.$form.find('[name="id_equipment"]').change(function () {
                    var $container = $(this).findParentByClass('inputContainer');
                    var id_equipment = parseInt($(this).val());
                    var check = false;
                    if (!isNaN(id_equipment) && id_equipment) {
                        for (var i in result.equipments) {
                            if (result.equipments[i].id == id_equipment) {
                                if (result.equipments[i].warnings.length) {
                                    $container.find('.equipmentWarnings').remove();
                                    var has_warnings = false;
                                    var wHtml = '<div class="alert alert-warning equipmentWarnings"><ul>';
                                    for (var j in result.equipments[i].warnings) {
                                        if (typeof (result.equipments[i].warnings[j]) === 'string' && result.equipments[i].warnings[j] !== '') {
                                            has_warnings = true;
                                            wHtml += '<li>' + result.equipments[i].warnings[j] + '</li>';
                                        }
                                    }
                                    wHtml += '</ul></div>';

                                    if (has_warnings) {
                                        check = true;
                                        $container.append(wHtml);
                                    }
                                }
                            }
                        }
                    }

                    if (!check) {
                        $container.find('.equipmentWarnings').remove();
                    }
                }).change();
            }
        }
    });
}

function removeReturn($button, id_return) {
    if ($button.hasClass('disabled')) {
        return;
    }

    BimpAjax('removeReturn', {
        id_vente: Vente.id_vente,
        id_return: id_return
    }, null, {
        $button: $button,
        id_return: id_return,
        display_success: false,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('#cur_vente_return_' + bimpAjax.id_return).fadeOut(250, function () {
                $(this).remove();
            });
            Vente.ajaxResult(result);
        }
    });
}

// Remises vente: 

function loadRemiseForm($button) {
    loadModalForm($button, {
        'module': 'bimpcaisse',
        'object_name': 'BC_VenteRemise',
        'id_parent': Vente.id_vente
    }, 'Ajout d\'une remise');
}

function deleteRemise($button, id_remise) {
    if ($button.hasClass('disabled')) {
        return;
    }

    BimpAjax('deleteRemise', {
        id_vente: Vente.id_vente,
        id_remise: id_remise
    }, null, {
        $button: $button,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success_msg: 'Remise supprimée avec succès',
        success: function (result, bimpAjax) {
            Vente.ajaxResult(result);
        }
    });
}

// Paiements vente: 

function displayNewPaiementForm($button) {
    if ($button.hasClass('selected')) {
        return;
    }

    $button.addClass('selected');

    $('#ventePaiementButtons').find('.ventePaiementButton').each(function () {
        $(this).removeClass('selected').removeClass('btn-primary').addClass('btn-default');
    });

    $button.addClass('selected').removeClass('btn-default').addClass('btn-primary');

    var code = $button.data('code');
    $('#venteAddPaiementFormContainer').find('#ventePaiementCode').val(code);

    $('#venteAddPaiementFormContainer').slideDown(250, function () {
        $('#ventePaiementMontant').focus().select();
    });
}

function addVentePaiement($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var montant = parseFloat($('#ventePaiementMontant').val());
    if (!montant) {
        bimp_msg('Veuillez saisir un montant', 'warning', null, true);
        $button.removeClass('disabled');
        return;
    }

    BimpAjax('addPaiement', {
        id_vente: Vente.id_vente,
        code: $('#ventePaiementCode').val(),
        montant: montant
    }, $('#ventePaimentsLines'), {
        $button: $button,
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        success: function (result, bimpAjax) {
            $('#ventePaiementCode').val('');
            $('#ventePaiementMontant').val('');
            $('#venteAddPaiementFormContainer').slideUp(250);
            $('#ventePaiementButtons').find('.ventePaiementButton').each(function () {
                $(this).removeClass('selected').removeClass('btn-primary').addClass('btn-default');
            });
            Vente.ajaxResult(result);
        }
    });
}

function deletePaiement($button, id_paiement) {
    if ($button.hasClass('disabled')) {
        return;
    }

    BimpAjax('deletePaiement', {
        id_vente: Vente.id_vente,
        id_paiement: id_paiement
    }, $('#ventePaimentsLines'), {
        $button: $button,
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        success: function (result, bimpAjax) {
            Vente.ajaxResult(result);
        }
    });
}

function checkDiscounts() {
    var $container = $('#customerDiscountsContainer');

    if (!$container.find('select[name="discounts_add_value"]').find('option').length) {
        $container.hide();
    } else {
        $container.show();
    }
}

// Evénements: 

function setCartLineEvents($line) {
    if ($line.length) {
        if (!$line.data('event_init')) {
            setInputsEvents($line);
            var $qtyInput = $line.find('.article_qty_input');
            if ($qtyInput.length) {
                $qtyInput.keyup(function (e) {
                    if (e.key === 'Enter') {
                        $qtyInput.change();
                    }
                });
                $qtyInput.change(function () {
                    if (!parseInt($(this).val())) {
                        $(this).val(1);
                    }
                    saveArticleQty(parseInt($(this).data('id_article')));
                });
            }

            $line.find('[name="article_remise_crt"]').change(function () {
                saveArticleRemiseCrt($(this));
            });

            $line.data('event_init', 1);
        }
    }
}

function onVenteLoaded() {
    var $input = $('#id_user_resp');

    if ($input.length && !parseInt($input.data('event_init'))) {
        $input.change(function () {
            saveCommercial();
        });
        $input.data('event_init', 1);
    }

    $input = $('#note_plus');

    if ($input.length && !parseInt($input.data('event_init'))) {
        $input.change(function () {
            saveNotePlus();
        });
        $input.data('event_init', 1);
    }

    $input = $('#venteSearchProduct');

    if ($input.length && !$input.data('event_init')) {
        $input.keyup(function (e) {
            if (e.key === 'Enter') {
                $('#findProductButton').click();
            }
        });
        $input.keydown(function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                e.stopPropagation();
                $('#findProductButton').click();
            }
        });
        $input.data('event_init', 1);
    }

    $input = $('#ventePaiementMontant');

    if ($input.length && !$input.data('event_init')) {
        $input.keyup(function (e) {
            if (e.key === 'Enter') {
                $('#venteAddPaiementButton').click();
            } else {
                checkTextualInput($(this));
            }
        });
        $input.data('event_init', 1);
    }

    $('#ventePanierLines').find('.cartArticleLine').each(function () {
        setCartLineEvents($(this));
    });

    $('#venteClientFormContainer').find('input[name="id_client"]').change(function () {
        saveClient();
    });

    $('#condReglementSelect').change(function () {
        saveCondReglement();
    });

    $('#venteHt').find('[name="vente_ht"]').change(function () {
        saveVenteHt();
    });

    $('#avoirRbtMode').find('[name="avoir_rbt_mode"]').change(function () {
        if ($(this).val() === 'rbt') {
            $('#avoirRbtModePaiement').slideDown(250);
        } else {
            $('#avoirRbtModePaiement').slideUp(250);
        }
    });

    checkMultipleValues();
    checkDiscounts();
}

$(document).ready(function () {

    var $mainContainer = $('#bc_main_container');
    setCommonEvents($mainContainer);
    setInputsEvents($mainContainer);

    $('#openCaisseForm').find('[name="id_entrepot"]').change(function () {
        var id_entrepot = parseInt($(this).val());
        if (id_entrepot) {
            BimpAjax('loadCaisseSelect', {
                'id_entrepot': id_entrepot
            }, $('#caisseSelectContainer'), {
                display_success: false,
                display_errors_in_popup_only: true,
                append_html: true,
                success: function () {
                    $('#openCaisseForm').find('input[name="fonds"]').keyup(function () {
                        checkTextualInput($(this));
                    });
                    if ($('#openCaisseForm').find('#id_caisse').find('option').length) {
                        $('#openCaisseForm').find('#openCaisseButton').removeClass('disabled');
                    }
                }
            });
        }
    });

    $('#closeCaisseButton').click(function () {
        $('closeCaisseForm').find('[name="fonds"]').val('');
        $('#venteToolbar').slideUp(250);
        $('#closeCaisseForm').slideDown(250);
    });

    $('#cancelCloseCaisseButton').click(function () {
        $('#closeCaisseForm').slideUp(250);
        $('#venteToolbar').slideDown(250);
    });

    $('#caisseMvtButton').click(function () {
        loadCaisseMvtForm($(this));
    });

    $('#changeUserButton').click(function () {
        $('#venteToolbar').slideUp(250);
        $('#changeUserForm').slideDown(250);
    });

    $('#cancelChangeUserButton').click(function () {
        $('#changeUserForm').slideUp(250);
        $('#venteToolbar').slideDown(250);
    });

    $('#closeCaisseForm').find('[name="fonds"]').keyup(function () {
        checkTextualInput($(this));
    });

    $('#newVenteButton').click(function () {
        loadNewVente();
    });

    $('#newPaymentButton').click(function () {
        loadModalForm($(this), {
            module: 'bimpcommercial',
            object_name: 'Bimp_Paiement',
            form_name: 'default'
        }, 'Paiement facture');
    });

    $('.windowMaximiseButton').click(function () {
        if ($mainContainer.hasClass('fullScreen')) {
            $('#id-left').show();
            $mainContainer.removeClass('fullScreen');
            $(this).attr('data-content', 'Agrandir').find('i').attr('class', 'fa fa-window-maximize');
            $(this).popover('destroy');
            $(this).popover();
            $('.fullScreenButton').show();
            $('body').removeClass('has_fullscreen');
        } else {
            $('#id-left').hide();
            $mainContainer.addClass('fullScreen');
            $(this).attr('data-content', 'Quitter le plein écran').find('i').attr('class', 'fa fa-times');
            $(this).popover('destroy');
            $(this).popover();
            $('.fullScreenButton').hide();
            $('body').addClass('has_fullscreen');
        }
    });

    $('.fullScreenButton').click(function () {
        if ($mainContainer.hasClass('fullScreen')) {
            $('#id-left').show();
            $mainContainer.removeClass('fullScreen');
            $(this).attr('data-content', 'Plein écran').find('i').attr('class', 'fas fa5-expand-arrows-alt');
            $(this).popover('destroy');
            $(this).popover();
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitCancelFullScreen) {
                document.webkitCancelFullScreen();
            }
            $('.windowMaximiseButton').show();
            $('body').removeClass('has_fullscreen');
        } else {
            $('#id-left').hide();
            $mainContainer.addClass('fullScreen');
            $(this).attr('data-content', 'Quitter le plein écran').find('i').attr('class', 'fa fa-times');
            $(this).popover('destroy');
            $(this).popover();

            var docElm = document.documentElement;
            if (docElm.requestFullscreen) {
                docElm.requestFullscreen();
            } else if (docElm.mozRequestFullScreen) {
                docElm.mozRequestFullScreen();
            } else if (docElm.webkitRequestFullScreen) {
                docElm.webkitRequestFullScreen();
            }
            $('.windowMaximiseButton').hide();
            $('body').addClass('has_fullscreen');
        }
    });

    $('body').on('objectChange', function (e) {
        refreshVente();
    });
});
