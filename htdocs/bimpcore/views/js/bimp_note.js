notifNote = null;
class bimp_note extends AbstractNotification {

    /**
     * Overrides
     */

    constructor(nom, id_notif) {
        super(nom, id_notif);
        notifNote = this;
    }

    init() {
        if (typeof object_labels['BimpNote'] === 'undefined')
            object_labels['BimpNote'] = 'Note';

        if ($('a#' + this.dropdown_id).length == 0) {

            if (theme != 'BimpTheme')
                var notif_white = 'notif_white';
            else
                var notif_white = '';

            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="fas fa5-comments atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<h4 class="header">';
            html += 'Messages' + this.getBoutonReload(this.dropdown_id);
            html += '<a style="float:right" href="' + dol_url_root + '/bimpmsg/index.php?fc=bal"><i class="fas fa5-envelope-open-text iconLeft"></i>Ma messagerie</a>';
            html += '</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';

            $(this.parent_selector).prepend(html);

            super.init(this);
        }
    }

    avatarIcone(text, color, classSup) {
        return '<span  style="background-color:' + color + '!important" class="badge badge-md badge-circle badge-floating badge-danger border-white' + classSup + '">' + text + '</span>';
    }

    formatElement(element, key) {
        if ($('div.list_part[key="' + key + '"]').length) {
            $('div.list_part[key="' + key + '"]').remove();
            AbstractNotification.prototype.elementRemoved(1, this.dropdown_id);
        }

        var html = '';

        // User courant est l'auteur
        if (typeof element.author !== undefined && id_user == element.author.id)
            var is_user_author = 1;
        else
            var is_user_author = 0;

//        if(is_user_author === 1 || element.viewed === 1)
        element.is_new = this.isNew(element);

        var style = 'petit';

        // Initiales
        var author_initiale = this.getInitiales(element.author.nom);
        if (element.is_user_or_grp)
            var dest_initiale = this.getInitiales(element.dest.nom);

        // Client
        if (element.obj == undefined || element.obj.nom_url == undefined) {
            html += "Pièce supprimée, aucun URL n'est disponible";
        } else {
            html += element.obj.nom_url;
        }

        html += '<div class="d-flex justify-content-' + (element.is_user_dest ? "start" : (is_user_author ? "end" : "")) + (style == "petit" ? ' petit' : '') + ' mb-4">';

        // Author
        html += '<span data-toggle="tooltip" data-placement="top" title="' + element.author.nom + '" class="chat-img pull-left">';
//        html += '<img src="https://placehold.it/' + (style === "petit" ? '35' : '55') + '/' + (element.type_author ? '55C1E7' : '5500E7') + '/fff&amp;text=' + author_initiale + '" alt="User Avatar" class="img-circle">';
        html += getBadge(author_initiale, (style === "petit" ? '25' : '45'), (element.is_user_author ? 'warning' : 'info'));
        html += '</span>';

        // Content
        html += '<div class="msg_cotainer">' + element.content + '</div>';

        if (element.author.id !== id_user && 0 < parseInt(element.author.id)) {// TODO rep à autre que 1224
            var js_repondre = "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpNote', id_object: '" + element.id + "'}";
            js_repondre += ", 'repondre', {type_dest: 1, fk_user_dest: " + element.author.id + ", content: '', id: ''}";
            js_repondre += ", null, null, {form_name: 'rep', modal_title: 'Répondre'})";
            html += '<span class="rowButton bs-popover" onclick="' + js_repondre + '"><i class="fas fa-share link"></i></span>';
        }

        // Dest
        if (element.is_user_or_grp) {
            html += '<span data-toggle="tooltip" data-placement="top" title="' + element.dest.nom + '"';
            html += ' class="chat-img pull-left ' + (element.is_viewed ? "" : "nonLu") + (element.is_user_dest ? " my" : "") + '">';
//            html += '<img src="https://placehold.it/' + (style == "petit" ? '28' : '45') + '/' + (element.is_user ? '55C1E7' : '5500E7') + '/fff&amp;text=' + dest_initiale + '" alt="User Avatar" class="img-circle">';
            html += getBadge(dest_initiale, (style === "petit" ? '25' : '45'), (element.is_user_dest ? 'warning' : 'info'));
            html += '</span>';
        }

        html += '</div>';
        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';

        var callback_set_as_viewed = this.ptr + '.setAsViewed("' + key + '","' + element.id + '")';

        // Marquer comme lu
        if (element.is_viewed) {
            html += '<span style="font-size: 11px" class="success"><i class="fas fa5-check iconLeft"></i>Lu</span>';
        } else {
            if (id_user == element.author.id) {
                html += '<span style="font-size: 11px" class="danger"><i class="fas fa5-times iconLeft"></i>Non lu</span>';
            } else {
                html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Marquer Lu" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" ';
                html += 'onclick=\'' + callback_set_as_viewed + ';\' data-original-title="" title="Marquer comme lu"><i class="far fa5-envelope-open"></i></span>'
            }
        }

        html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Voir toute la conversation"';
        html += ' data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}"';
        html += ' onclick=\'loadModalObjectNotes($(this), "' + element.obj_module + '", "' + element.obj_name + '", "' + element.id_obj + '", "chat", true);\'>';
        html += '<i class="fas fa5-eye"></i></span>';

        return html;
    }

    isNew(element) {

        if (typeof element.author === undefined || id_user === parseInt(element.author.id) || parseInt(element.is_viewed) === 1)
            return 0;

        return 1;
    }

    getKey(element) {
        return element.obj_type + '_' + element.obj_module + '_' + element.obj_name + '_' + element.id_obj;
    }

    displayNotification(element) {
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {

            var titre = "Nouveau message de " + element.author.nom;
            var content = element.content.replace(/(<([^>]+)>)/gi, "");
            var n = new Notification(titre, {
                body: content,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });

            n.onclick = function () {
                if (parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + bn.dropdown_id).trigger('click');
            }
        }

    }

    displayMultipleNotification(elements) {
        var nb_valid = elements.length;
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {

            var n = new Notification("Vous avez reçu " + nb_valid + " messages.", {
                body: '',
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });

            n.onclick = function () {
                $('#' + bn.dropdown_id).trigger('click');
            }
        }
    }

    /**
     * Fonctions spécifique à la classe
     */



    getInitiales(nom) {
        var full_name = nom.split(' ');
        var initials = '';
        for (var i = 0; i < full_name.length; i++) {
            initials += full_name[i].charAt(0);
        }
        return initials.toUpperCase();
    }

}
