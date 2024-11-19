
var notifValidations = null;

class notif_validations extends AbstractNotification {
    constructor(id, storage_key) {
        super('notif_validations', id, storage_key);
        notifValidations = this;

        this.nb_user_demandes = 0;
        this.nb_other_demandes = 0;

        this.user_demandes_id = 'notifs_user_demandes_validation';
        this.other_demandes_id = 'notifs_other_demandes_validation';

        this.init();
    }

    init() {
        if (theme != 'BimpTheme') {
            var notif_white = 'notif_white';
        } else {
            var notif_white = '';
        }

        if (!$('a#' + this.dropdown_id).length) {
            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="far fa5-check-circle atoplogin"></i>';
            html += '</a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<div class="notifications-wrap list_notification ' + '' /*this.nom*/ + '">';
            html += '</div>';

            html += '<div class="header" style="padding: 10px 15px">';

            html += '<table style="width: 100%; font-size: 15px;">';
            html += '<tr>';
            html += '<td style="width: 30%">';
            html += 'Demandes de validation';
            html += this.getBoutonReload(this.dropdown_id);
            html += '</td>';
            html += '<td style="width: 70%; text-align: right">';
            html += '<a href="' + DOL_URL_ROOT + '/bimpvalidation/index.php?fc=user"><i class="far fa5-check-circle iconLeft"></i>Toutes mes demandes de validation</a>';
            html += '</td>';
            html += '</tr>';
            html += '</table>';

            html += '</div>';

            html += '<div class="tabs-animated">';

            // Nav tabs 
            html += '<ul id="nav_bv_demandes" class="nav nav-tabs" role="tablist">';
            html += '<li role="presentation" class="active"><a href="#' + this.user_demandes_id + '" aria-controls="user_demandes" role="tab" data-toggle="tab">Mes demandes assignées à valider&nbsp;&nbsp;<span class="badge badge-default nb_elements">0</span></a></li>';
            html += '<li role="presentation"><a href="#' + this.other_demandes_id + '" aria-controls="other_demandes" role="tab" data-toggle="tab">Autres demandes validables&nbsp;&nbsp;<span class="badge badge-default nb_elements">0</span></a></li>';
            html += '</ul>';

            // Tab panels 
            html += '<div class="tab-content bv_demandes_panel">';
            html += '<div role="tabpanel" class="list_notification tab-pane fade in active" id="' + this.user_demandes_id + '"></div>';
            html += '<div role="tabpanel" class="list_notification tab-pane fade" id="' + this.other_demandes_id + '"></div>';
            html += '</div>';

            html += '</div>';
            html += '</div>';

            var $container = $(this.parent_selector);
            if ($container.length) {
                $container.prepend(html);

                // Animations slides sur le côté: 
                $('ul#nav_bv_demandes > li > a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
                    var $old_tab = $($(e.target).attr("href"));
                    var $new_tab = $($(e.relatedTarget).attr("href"));

                    if ($new_tab.index() < $old_tab.index()) {
                        $old_tab.css('position', 'relative').css("right", "0").show();
                        $old_tab.animate({"right": "-100%"}, 300, function () {
                            $old_tab.css("right", 0).removeAttr("style");
                        });
                    } else {
                        $old_tab.css('position', 'relative').css("left", "0").show();
                        $old_tab.animate({"left": "-100%"}, 300, function () {
                            $old_tab.css("left", 0).removeAttr("style");
                        });
                    }
                });

                $('ul#nav_bv_demandes > li > a[data-toggle="tab"]').on('show.bs.tab', function (e) {
                    var $new_tab = $($(e.target).attr("href"));
                    var $old_tab = $($(e.relatedTarget).attr("href"));

                    if ($new_tab.index() > $old_tab.index()) {
                        $new_tab.css('position', 'relative').css("right", "-2500px");
                        $new_tab.animate({"right": "0"}, 500);
                    } else {
                        $new_tab.css('position', 'relative').css("left", "-2500px");
                        $new_tab.animate({"left": "0"}, 500);
                    }
                });

                super.init();
            } else {
                console.error('Demandes de validation : container notifs absent');
            }
        }
    }

    isNew(element) {
        if (element.tab_type === 'user_demandes') {
            return 1;
        }

        return 0;
    }

    formatElement(element, key) {
        var html = '';

        html += '<div class="bv_demande_content">';
        html += '<b>Validation ' + element.type + '</b><br/>';

        html += '<div class="user_demande">' + element.user_demande + '</div>';
        if (typeof element.obj_link !== 'undefined') {
            html += '<b>Pièce : </b>' + element.obj_link + '<br/>';
        }
        if (typeof element.client !== 'undefined') {
            html += '<b>Client : </b>' + element.client + '<br/>';
        }
        if (typeof element.val !== 'undefined') {
            html += element.val + '<br/>';
        }
        html += '</div>';

        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';

        if (element.id) {
            if (element.can_process) {
                var onclick = 'setObjectAction($(this), {module: \'bimpvalidation\', object_name: \'BV_Demande\', id_object: ' + element.id + '}, \'accept\', {no_page_reload: 1})';
                html += '<span class="btn btn-default btn-small" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>Accepter</span>';

                var onclick = 'setObjectAction($(this), {module: \'bimpvalidation\', object_name: \'BV_Demande\', id_object: ' + element.id + '}';
                onclick += ', \'refuse\', {no_page_reload: 1}, null, null, {form_name: \'refuse\'})';
                html += '<button class="btn btn-default btn-small" onclick="' + onclick + '"><i class="fas fa5-times iconLeft"></i>Refuser</button>';
            }

            if (element.can_view) {
                html += '<span class="rowButton" onclick="loadModalView(\'bimpvalidation\', \'BV_Demande\', ' + element.id + ', \'default\', $(this))">';
                html += '<i class="fas fa5-eye"></i></span>';
            }
        }

        return html;
    }

    emptyContent() {
        this.nb_user_demandes = 0;
        this.nb_other_demandes = 0;

        $('#' + this.user_demandes_id).html('');
        $('#' + this.other_demandes_id).html('');
    }

    appendElement(element, key, html) {
        if (element.tab_type === 'user_demandes') {
            this.nb_user_demandes++;
            $('#' + this.user_demandes_id).append(html);
        } else {
            this.nb_other_demandes++;
            $('#' + this.other_demandes_id).append(html);
        }
    }

    renderElements() {
        super.renderElements();
        this.updateNavs();
    }

    updateNavs() {
        var $span = $('#nav_bv_demandes a[href="#' + this.user_demandes_id + '"] span.nb_elements');
        $span.text(this.nb_user_demandes);
        if (this.nb_user_demandes > 0) {
            $span.removeClass('badge-danger').addClass('badge-info');
        } else {
            $span.removeClass('badge-info').addClass('badge-danger');
        }

        var $span = $('#nav_bv_demandes a[href="#' + this.other_demandes_id + '"] span.nb_elements');
        $span.text(this.nb_other_demandes);
        if (this.nb_other_demandes > 0) {
            $span.removeClass('badge-danger').addClass('badge-info');
        } else {
            $span.removeClass('badge-info').addClass('badge-danger');
        }
    }

    sendBrowserNotification(elements) {
        if (!elements.length) {
            return;
        }

        var bn = this;
        var title = '';

        if (elements.length > 1) {
            title = 'Vous avec reçu ' + elements.length + ' nouvelles demandes de validation';
        } else {
            title = "Nouvelle demande de validation " + elements[0].type;
        }

        BimpBrowserNotification(title, '', function () {
            window.parent.parent.focus();
            if (parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1) {
                $('#' + bn.dropdown_id).trigger('click');
            }
        });
    }

    getLabel() {
        return 'Demandes de validation';
    }
}

$(document).ready(function () {
    $('body').on('objectChange', function (e) {
        if (e.module === 'bimpvalidation' && e.object_name === 'BV_Demande') {
            if (typeof (notifValidations) !== 'undefined' && notifValidations !== null) {
                notifValidations.refreshElements();
            }
        }
    });
});