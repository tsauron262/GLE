var ticket_url = './ticket.php';

function BC_Vente() {

    this.reset = function () {
        this.id_vente = 0;
        this.id_client = 0;
        this.nb_articles = 0;
        this.total_ttc = 0;
        this.total_remises_vente = 0;
        this.total_remises_articles = 0;
        this.total_remises = 0;
        this.toPay = 0;
        this.toReturn = 0;
        this.remises = [];
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

//        displayMoneyValue(this.total_remises_vente, $('#venteRemises').find('.total_remises_vente span'));
        displayMoneyValue(this.total_remises_articles, $('#venteRemises').find('.total_remises_articles span'));
        displayMoneyValue(this.total_remises, $('#venteRemises').find('.total_remises span'));
        displayMoneyValue((this.total_ttc - this.total_remises), $('#ventePanierTotal span'));

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

        if (this.toReturn > 0) {
            $('#venteToReturn').slideDown(250);
            $('#venteToPay').slideUp(250);
        } else {
            $('#venteToReturn').slideUp(250);
            $('#venteToPay').slideDown(250);
        }

        if (this.nb_articles > 0 && (this.paiement_differe || this.toPay <= 0)) {
            $('#validateCurrentVenteButton').removeClass('disabled');
            $('#saveCurrentVenteButton').addClass('disabled');
        } else {
            $('#validateCurrentVenteButton').addClass('disabled');
            $('#saveCurrentVenteButton').removeClass('disabled');
        }
    };

    this.reset();
}

var Vente = new BC_Vente();

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
        bimp_msg('Veuillez sélectionner une caisse', 'warning');
        return;
    }

    var fonds = parseFloat($form.find('[name="fonds"]').val());
    if (!fonds) {
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning');
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
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning');
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
                window.location = document.location.href.replace(document.location.search, "") + '?id_entrepot=' + result.id_entrepot;
            }
        }
    });
}

function changeUser($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $form = $('#changeUserForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        return;
    }

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Erreur: aucune caisse active', 'danger');
        return;
    }

    var id_new_user = parseInt($form.find('[name="id_new_user"]').val());
    if (!id_new_user) {
        bimp_msg('Aucun nouvel utilisateur sélectionné', 'warning');
        return;
    }

    var id_user = parseInt($('#current_params').find('[name="id_user"]').val());
    if (!id_user) {
        bimp_msg('Erreur: ID de l\'utilisateur actuel absent', 'danger');
        return;
    }

    if (id_user === id_new_user) {
        bimp_msg('L\'utilisateur sélectionné est déjà assigné à cette caisse', 'warning');
        return;
    }

    var user_name = $form.find('[name="id_new_user"]').find('option[value="' + id_new_user + '"]').text();
    var caisse_name = $('#current_params').find('[name="caisse_name"]').val();

    var msg = 'Etes-vous sûr de vouloir assigné l\'utilisateur "' + user_name + '"';
    msg += ' à la caisse "' + caisse_name + '"?' + "\n\n";
    msg += 'Cet utilisateur devra se connecter pour accéder à cette caisse';

    if (confirm(msg)) {
        BimpAjax('changeUser', {
            id_caisse: id_caisse,
            id_new_user: id_new_user,
            logout: $form.find('[name="logout"]').val()
        }, $form.find('.freeFormAjaxResult'), {
            $button: $button,
            success: function (result, bimpAjax) {
                window.location = document.location.href.replace(document.location.search, "");
            },
            error: function (result, bimpAjax) {
                if (!result || typeof (result.errors) === 'undefined') {
                    window.location = document.location.href.replace(document.location.search, "");
                }
            }
        });
    }
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
    if (Vente.id_vente) {
        BimpAjax('loadVenteData', {
            id_vente: Vente.id_vente
        }, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            success: function (result, bimpAjax) {
                Vente.ajaxResult(result);
            }
        });
    }
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

    BimpAjax('saveVenteStatus', {
        id_vente: Vente.id_vente,
        status: status
    }, null, {
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

            reloadObjectList('BC_Vente_default_list_table');
            if ((status === 2) && result.validate) {
                var url = ticket_url + '?id_vente=' + bimpAjax.id_vente;
                window.open(url, 'Ticket de caisse', "menubar=no, status=no, width=370, height=600");
            }
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

function loadNewClientForm($button) {
    loadModalForm($button, {
        module: 'bimpcore',
        object_name: 'Bimp_Societe',
        form_name: 'client_light'
    }, 'Ajout d\'un nouveau client', function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent');
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
            bimp_msg('Erreur technique: index de la modale absent');
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

function selectClientFromList($button) {
    var $row = $button.findParentByClass('Bimp_Societe_row');

    if (!$row.length) {
        bimp_msg('Erreur: ID client absent');
        return;
    }

    var id_client = parseInt($row.data('id_object'));

    if (!id_client) {
        bimp_msg('Erreur: ID client absent');
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
        }
    });
}

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

function editClient($button, id_client, client_name) {
    var title = 'Edition du client "' + client_name + '"';
    loadModalForm($button, {
        'module': 'bimpcore',
        'object_name': 'Bimp_Societe',
        'id_object': id_client,
        'form_name': 'default'
    }, title, function ($form) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (!modal_idx) {
            bimp_msg('Erreur technique: index de la modale absent');
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
            bimp_msg('Erreur technique: index de la modale absent');
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

function findProduct($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $input = $('#venteSearchProduct');
    var search = $input.val();

    if (typeof (search) === 'undefined' || !search) {
        bimp_msg('Veuillez saisir un code-barres ou un numéro de série', 'warning');
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

function setVenteStatus($button, id_vente, status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (status === 0) {
        if (!confirm('Etes-vous sûr de vouloir abandonner cette vente?')) {
            return;
        }
    }

    BimpAjax('saveVenteStatus', {
        id_vente: id_vente,
        status: status
    }, null, {
        $button: $button,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            reloadObjectList('BC_Vente_default_list_table');
        }
    });
}

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

function addPaiement() {

}

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
        bimp_msg('Veuillez saisir un montant');
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

            $line.data('event_init', 1);
        }
    }
}

function onVenteLoaded() {
    var $input = $('#venteSearchProduct');

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
            module: 'bimpcore',
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
            }
            else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            }
            else if (document.webkitCancelFullScreen) {
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
            }
            else if (docElm.mozRequestFullScreen) {
                docElm.mozRequestFullScreen();
            }
            else if (docElm.webkitRequestFullScreen) {
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
