notifNote = null;

class bimp_note extends AbstractNotification {
    constructor(id, storage_key) {
        super('bimp_note', id, storage_key);
        notifNote = this;

        this.init();
    }

    init() {
        if ($('a#' + this.dropdown_id).length == 0) {
            if (theme != 'BimpTheme') {
                var notif_white = 'notif_white';
            } else {
                var notif_white = '';
            }

            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="fas fa5-comments atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<h4 class="header">';
            html += 'Messages' + this.getBoutonReload(this.dropdown_id);
            html += '<a style="float:right" href="' + dol_url_root + '/bimpmsg/index.php?fc=bal"><i class="fas fa5-envelope-open-text iconLeft"></i>Ma messagerie</a>';
            html += '</h4>';
            html += '<div class="notifications-wrap list_notification bimp_note">';
            html += '</div>';
            html += '</div>';

            var $container = $(this.parent_selector);
            if ($container.length) {
                $container.prepend(html);
                super.init();
            } else {
                console.error('Notes : container notifs absent');
            }
        }
    }

    avatarIcone(text, color, classSup) {
        return '<span  style="background-color:' + color + '!important" class="badge badge-md badge-circle badge-floating badge-danger border-white' + classSup + '">' + text + '</span>';
    }

    formatElement(element, key) {
        var html = '';

        element.is_new = this.isNew(element);

        var style = 'petit';

        // Initiales
        var author_initiales = this.getInitiales(element.author.nom);

        var dest_initiales = '';
        if (element.is_dest_user_or_grp) {
            dest_initiales = this.getInitiales(element.dest.nom);
        }

        // Client
        if (element.obj_link) {
            html += element.obj_link;
        } else {
            html += "Pièce supprimée";
        }

        html += '<div class="d-flex justify-content-' + (element.is_user_dest ? "start" : (element.is_user_author ? "end" : "")) + (style == "petit" ? ' petit' : '') + ' mb-4">';

        // Author
        html += '<span data-toggle="tooltip" data-placement="top" title="' + element.author.nom + '" class="chat-img pull-left">';
        html += getBadge(author_initiales, (style === "petit" ? '25' : '45'), (element.is_user_author ? 'warning' : 'info'));
        html += '</span>';

        // Content
        html += '<div class="msg_cotainer">' + element.content + '</div>';

        if (!element.is_user_author && element.author.id) {
            var js_repondre = "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpNote', id_object: '" + element.id + "'}";
            js_repondre += ", 'repondre', {type_dest: 1, fk_user_dest: " + element.author.id + ", content: '', id: ''}";
            js_repondre += ", null, null, {form_name: 'rep', modal_title: 'Répondre'})";
            html += '<span class="rowButton bs-popover" onclick="' + js_repondre + '"><i class="fas fa-share link"></i></span>';
        }

        // Dest
        if (dest_initiales) {
            html += '<span data-toggle="tooltip" data-placement="top" title="' + element.dest.nom + '"';
            html += ' class="chat-img pull-left ' + (element.is_viewed ? "" : "nonLu") + (element.is_user_dest ? " my" : "") + '">';
            html += getBadge(dest_initiales, (style === "petit" ? '25' : '45'), (element.is_user_dest ? 'warning' : 'info'));
            html += '</span>';
        }

        html += '</div>';
        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';

        // Marquer comme lu
        if (element.is_viewed) {
            html += '<span style="font-size: 11px" class="success"><i class="fas fa5-check iconLeft"></i>Lu</span>';
        } else {
            if (!element.is_user_dest) {
                html += '<span style="font-size: 11px" class="danger"><i class="fas fa5-times iconLeft"></i>Non lu</span>';
            } else {
                var onclick = 'notifNote.setAsViewed("' + key + '","' + element.id + '")';
                html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Marquer Lu" ';
                html += 'data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" ';
                html += 'onclick=\'' + onclick + ';\'>';
                html += '<i class="far fa5-envelope-open"></i>';
                html += '</span>';
            }
        }

        if (parseInt(element.id_obj)) {
            html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Voir toute la conversation"';
            html += ' data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}"';
            html += ' onclick=\'loadModalObjectNotes($(this), "' + element.obj_module + '", "' + element.obj_name + '", "' + element.id_obj + '", "chat", true);\'>';
            html += '<i class="fas fa5-eye"></i></span>';
        }

        return html;
    }

    isNew(element) {
        if (!element) {
            return 0;
        }

        if (typeof element.author === undefined || id_user === parseInt(element.author.id) || parseInt(element.is_viewed) === 1) {
            return 0;
        }

        if (!element.is_user_dest) {
            return 0;
        }

        return 1;
    }

    getKey(element) {
        if (!element) {
            return '';
        }

        return element.obj_type + '_' + element.obj_module + '_' + element.obj_name + '_' + element.id_obj;
    }

    sendBrowserNotification(elements) {
        if (!elements.length) {
            return;
        }

        var bn = this;
        var title = '';
        var content = '';
        var onclick = null;

        if (elements.length > 1) {
            title = 'Vous avec reçu ' + elements.length + ' nouveaux messages';
            onclick = function (x) {
                window.focus(); //this.close();
                if (parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1) {
                    $('#' + bn.dropdown_id).trigger('click');
                }
            };
        } else {
            title = "Nouveau message de " + elements[0].author.nom;
            content = elements[0].content;
            onclick = function (x) {
                window.focus();
                window.open(elements[0].obj_url);
                this.close();
            };
        }

        BimpBrowserNotification(title, content, onclick);
    }

    getInitiales(nom) {
        var full_name = nom.split(' ');
        var initials = '';
        for (var i = 0; i < full_name.length; i++) {
            initials += full_name[i].charAt(0);
        }
        return initials.toUpperCase();
    }

    getLabel() {
        return 'Notes';
    }

}

$(document).ready(function () {
    $('body').on('objectChange', function (e) {
        if (e.module === 'bimpcore' && e.object_name === 'BimpNote') {
            if (typeof (notifNote) !== 'undefined' && notifNote !== null) {
                notifNote.refreshElements();
            }
        }
    });
});
