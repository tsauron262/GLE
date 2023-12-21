
/**
 * Utilisation des notification:
 * 
 * 1 - ajout d'une entrée dans la tale llx_bimp_notification
 * 2 - créer la function php qui renvoie un tableau du style
 *          $demandes['content'][] = array(  // content est obligatoire et doit être définit ainsi
 *              'id'  => $d->id,             // content est obligatoire dans chaque content
 *                                           // il permet  de récupéré le max id
 *              'ce_que_je_veux_1' => ce_que_je_veux_1(),  // ajout spécifique à l'utilisation 1
 *              'ce_que_je_veux_2' => ce_que_je_veux_2(),  // ajout spécifique à l'utilisation 2
 *              etc...
 *          );
 * 3 - créer le fichier JS avec le même nom (.js) et nom de classe
 *     que le champ "nom" renseigné à l'étape 1 et ajouter les fonctions spécifiques
 *     (displayNotification, formatElement, etc...)
 *     
 *     
 */

class AbstractNotification {
    /**
     * Méthode doit être appelée avec super()
     */
    constructor(id_notification) {
        if (this.constructor == AbstractNotification) {
            throw new Error('La classe abstraite "AbstractNotification" ne peut être instanciée.');
            return;
        }
        
        this.id_max = 0;
        this.id_notification = id_notification;
        this.content = [];
//        this.nom = nom;
        this.ptr = 'notificationActive[' + this.id_notification + '].obj';
        this.dropdown_id = 'dropdown_' + this.id_notification;
        // Aussi dans BimpNotification
//        this.parent_selector = 'div.dropdown.modifDropdown:last';
        this.parent_selector = 'div.login_block_other';
//        this.display_notification = true;
//        if (bimp_storage.get(this.id_notification) === null)
//            bimp_storage.set(this.id_notification, this.id_max);
        
        this.init();
    }

    init(an) {
        var instance = this;
        // Animation ouverture des notifs
        $('#' + an.dropdown_id).click(function (e) {
            an.expand();
            e.stopPropagation();

        });

        // Fermeture des dropdown lors de cliques à côté
        $(document).click(function (e) {
            if (!$('#page_modal').hasClass('in') && $(e.target).attr('id') != 'page_modal') {
                var $target = $(e.target);
                if (!$target.closest(instance.parent_selector).length)
                    instance.collapse();
            }
        });

        // Click recharger la notif
        $('span[name="reload_notif"][dropdown_id="' + instance.dropdown_id + '"]').click(function () {
            instance.reloadNotif();
        });
    }

    notificationAction(action, id, data, success) {
        data['actionNotif'] = action;
        data['id_notification'] = this.id_notification;
        data['id'] = id;

        BimpAjax('notificationAction', data, null, {
            display_success: false,
            success: function (result, bimpAjax) {
                success(result, bimpAjax);
            }
        });
    }

    setAsViewed(key, id) {
        var data = {
        };

        var thisClass = this;
        var success = function (result, bimpAjax) {
            thisClass.isViewed(key);
        };

        this.notificationAction('setAsViewed', id, data, success);
    }

    isViewed(key) {
        $('div.list_part[key="' + key + '"] span.nonLu').removeClass('nonLu');
        this.elementRemoved(1);
    }

    expand() {
        var instance = this;
        $(this.parent_selector).find('.bimp_notification_dropdown').each(function () {
            // Définition de l'attribut is_open
            if (typeof $(this).attr('is_open') === typeof undefined)
                $(this).attr('is_open', 0);

            var was_open = parseInt($(this).attr('is_open'));

            // Fermeture
            if (was_open === 1) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 0);
            }

            // Ouverture de la dropdown cliquée si elle n'était pas déjà ouverte
            if ($(this).attr('aria-labelledby') === instance.dropdown_id && was_open === 0) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 1);
            }

        });
    }

    collapse() {
        $(this.parent_selector).find('.bimp_notification_dropdown').each(function () {

            // Définition de l'attribut is_open
            if (typeof $(this).attr('is_open') === typeof undefined)
                $(this).attr('is_open', 0);

            // Fermeture
            if (parseInt($(this).attr('is_open')) === 1) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 0);
            }
        });
    }

    /**
     * Méthode appelé lors du retour ajax, doit être appelée avec super(element)
     */
    addElement(element) {
//        console.log(element);
//            console.log(typeof element.content, this.id_notification + "_content", element.content.length);
        if (typeof element.content === "object" && (element.content.length > 0 || Object.keys(element.content).length > 0)) {

            //this.content = this.content.concat(element.content);
            
            if(this.id_max == 0)
                var add = 0;
            else
                var add = 1;
            bimp_storage.set(this.id_notification + "_content", element.content, add);
            this.traiteStorage();
//            this.traiteElement(element.content);
        }

    }
    
    traiteStorage(){
        var content = bimp_storage.get(this.id_notification + "_content");
        if (content !== null){
            this.content = [];
            this.id_max = 0;
            this.emptyNotifs();
            this.traiteElement(content);
        }
    }
    
    traiteElement(content){
        content = Object.keys(content).map(function(cle) {
            return content[cle];
        });
        var notif = this;
        
        
        content = content.sort(function compare(a, b) {
            if (notif.isNew(a) < notif.isNew(b))
               return -1;
            if (notif.isNew(a) > notif.isNew(b))
               return 1;
            if (a.id < b.id)
               return -1;
            if (a.id > b.id )
               return 1;
            return 0;
        });
        
//        content = Object.fromEntries(content);
        
        var nb_unread = 0;
        var id_max_changed = 0;
        var to_display = '';

//        if (content !== null && this.isMultiple(content))
//            var is_multiple = true;
//        else
//            var is_multiple = false;

        for (var i in content) {

            // Redéfinition de id max
            if (typeof content[i].tms !== 'undefined') {
                if (parseInt(content[i].tms) > this.id_max) {
                    this.id_max = parseInt(content[i].tms);
                    id_max_changed = 1;
                }
            }
            else{
                if (parseInt(content[i].id) > this.id_max) {
                    this.id_max = parseInt(content[i].id);
                    id_max_changed = 1;
                }
            }

            // Si la fonction n'est pas implémenter dans la classe fille: is_new = 1
            var is_new = this.isNew(content[i]);

            // Clé utilisé pour identifier les "groupes de message" (ex: conversation)
            var key = this.getKey(content[i]);

            // Affichage dans le topmenu
            to_display = this.formatElement(content[i], key);
            this.addInList(to_display, content[i].url, content[i], key, content[i].append);

            // Augmentation du nombre dans le span rouge
            if (is_new === 1) {

                // Affichage dans la notification
//                if (!is_multiple && id_max_changed) {
//                    var global_id_max = parseInt(bimp_storage.get(this.id_notification));
//                    if (global_id_max < this.id_max) {
//                        bimp_storage.set(this.id_notification, this.id_max);
//                        this.displayNotification(content[i]);
//                    }
//                }

                nb_unread++;
            }
        }

//        if (is_multiple && id_max_changed) {
//            var global_id_max = parseInt(bimp_storage.get(this.id_notification));
//            if (global_id_max < this.id_max) {
//                bimp_storage.set(this.id_notification, this.id_max);
//                this.displayMultipleNotification(content);
//            }
//        }


        this.elementAdded(nb_unread);
    }

    addInList(to_display, url, element, key, to_append = false) {

        if (!element.class)
            element.class = '';

        var html = '';

        html += '<div class="list_part ' + (url ? 'notif_link ' : '') + element.class + '" key="' + key + '">';

        var header = '';

        if (element.date_create) {
            header += '<span class="date_notif">' + this.formatDate(element.date_create) + '</span>';
        }

        var header_buttons = this.getElementHeaderButtons(element, key);

        if (url) {
            header_buttons += '<span class="rowButton" onclick="window.open(\'' + url + '\')">';
            header_buttons += '<i class="fas fa5-external-link-alt"></i></span>';
        }

        if (header_buttons) {
            header += '<div style="display: inline-block; float: right">';
            header += header_buttons;
            header += '</div>';
        }

        if (header) {
            html += '<div class="part_list_header">' + header + '</div>';
        }

        html += to_display;

        html += '</div>';

        var $container = null;

        if (to_append === false) {
            $container = $('div[aria-labelledby="' + this.dropdown_id + '"] div.notifications-wrap ');
        } else {
            $container = $(to_append);
        }

        if ($.isOk($container)) {
            $container.prepend(html);

            $container.find('.bs-popover').each(function () {
                if (!parseInt($(this).data('bs_popover_event_init'))) {
                    $(this).popover();
                    $(this).click(function () {
                        $(this).popover('hide');
                    });
                    $(this).data('bs_popover_event_init', 1);
                }
            });

            $container.find('.cardPopoverIcon').each(function () {
                if (!parseInt($(this).data('bs_popover_click_event_init'))) {
                    $(this).click(function (e) {
                        displayObjectLinkCardPopover($(this));
                        e.stopPropagation();
                    });
                    $(this).data('bs_popover_click_event_init', 1);
                }
            });
    }
    }

    isMultiple(elements)
    {
        return elements.length >= 7;
    }

    formatDate(input) {
        input = input.replace('-', '/').replace('-', '/');
        var m = new Date(input);
        return  ("0" + m.getDate()).slice(-2) + "/" +
                ("0" + (m.getMonth() + 1)).slice(-2) + "/" +
                m.getFullYear() + " " +
                ("0" + m.getHours()).slice(-2) + ":" +
                ("0" + m.getMinutes()).slice(-2);
        // Pas besoin des secondes
//                + ":" +
//                ("0" + m.getSeconds()).slice(-2);
    }

    elementAdded(nb_add) {
        // Aucun nouvel élément
        if (nb_add === 0)
            return;

        var span_red = $('a#' + this.dropdown_id + ' > span.badge.bg-danger');


        // Le span existe, on le met à jour
        if (0 < parseInt(span_red.length)) {
            var nb_old = parseInt($('a#' + this.dropdown_id + ' > span.badge.bg-danger').html());
            span_red.html(nb_add + nb_old);
        }

        // Le span n'existe pas, il faut le créer
        else {
            $('a#' + this.dropdown_id).append('<span class="badge bg-danger">' + nb_add + '</span>');
        }
    }

    elementRemoved(nb_rm) {

        // Aucun élément à supprimer
        if (nb_rm === 0)
            return;
        var span_red = $('a#' + this.dropdown_id + ' > span.badge.bg-danger');

        // Le span existe, on le met à jour
        if (0 < parseInt(span_red.length)) {

            var nb_old = parseInt(span_red.html());
            var nb_new = nb_old - nb_rm;
            if (nb_new <= 0)
                span_red.remove();
            else
                span_red.html(nb_new);

        }
    }

    isNew() {
        return 1;
    }

    getKey(element) {
        return element.id;
    }

    getBoutonReload() {
        return '<span dropdown_id="' + this.dropdown_id + '" name="reload_notif" class="objectIcon"><i class="fas fa5-redo-alt"></i></span>';
    }

    reloadNotif() {
        this.content = [];
        this.id_max = 0;

        this.emptyNotifs();

        // TODO check la suite
//        this.display_notification = false;
        bimp_notification.reload(false, this.id_notification);
    }

    emptyNotifs(display_loading_spin) {
        var nb_rm = $('div[aria-labelledby="' + this.dropdown_id + '"] div.notifications-wrap > div').length;

        $('div[aria-labelledby="' + this.dropdown_id + '"] div.notifications-wrap').empty();

        this.elementRemoved(nb_rm, this.dropdown_id);
    }

}

function BimpNotification() {
    this.id = getRandomInt(9999999999999);
    this.active = true;
    this.hold = false;
    this.processing = false;
    this.delay = 0;
    this.is_first_iteration = true;
    this.$loading = $();
    this.$refreshBtn = $();
    this.notificationActive = {};

    var bn = this;

    // Ajout du panneau rouge si notifications non activée
    if (Notification.permission !== "granted") {
//        $('div.dropdown.modifDropdown:last').prepend();
    }

    this.reload = function (reiterate = true, id_notification = 0) {

        if (!bn.active || bn.processing) {
            bn.iterate();
            return;
        }

        if (bn.hold) {
            alert("HOLD EN COURS"); // TODO check encore utile ?
            bn.delay = 0;
            bn.iterate();
        } else {
            bn.processing = true;
            bn.$loading.show();
            bn.$refreshBtn.hide();
            var data = {
                randomId: bn.id,
                notificationActive: []
            };


            for (var i in this.notificationActive) {
                if (id_notification == 0 || id_notification == this.notificationActive[i].id_notification) {
                    var notif = {
                        id_notification: this.notificationActive[i].id_notification,
                        id_max: this.notificationActive[i].obj.id_max
                    };
                    data.notificationActive.push(notif);
                }
            }

            data.date_start = date_start;
           

            BimpAjax('getNotification', data, null, {
                display_success: false,
                display_errors: false,
                display_warnings: false,
                display_debug_content: false,
                success: function (result, bimpAjax) {
                    bn.processing = false;
                    bn.$loading.hide();
                    bn.$refreshBtn.show();

                    if (result.notifications) {
//                        console.log(result.notifications);
                        for (const [id, value] of Object.entries(result.notifications)) {
                            bn.notificationActive[id].obj.addElement(value);
                        }
//                        bn.delay = 0;
                    }

                    if (reiterate)
                        bn.iterate();
                },
                error: function () {
                    bn.processing = false;
                    bn.$loading.hide();
                    bn.$refreshBtn.show();
                    if (reiterate)
                        bn.iterate();
                }
            });
        }
    };

    this.iterate = function () {
        if (bn.delay < 20000) {
            bn.delay += 2000;
//            bn.delay += 2000; // valeur d'origine
        }

        if (bn.delay > 0) {
            setTimeout(function () {
                bn.reload();
            }, bn.delay);
        } else {
            bn.reload();
        }
    };

    this.addButtonAllowNotification = function () {
        if ($('#allow_notif').length === 0) {
            var button = '<span id="allow_notif" class="headerIconButton header-icon bs-popover nav-link" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Autoriser les notifications">';
            button += '<i class="fas fa5-exclamation-triangle"></i></span>';
            $('div.login_block_other').prepend(button);
            $('#allow_notif').popover();
            $(this).click(function () {
                $('#allow_notif').popover('hide');
            });
        }
        $('#allow_notif').click(function () {
            Notification.requestPermission();
        });
    };

    this.removeButtonAllowNotification = function () {
        $('#allow_notif').remove();
    };

    this.onWindowLoaded = function () {

        var bn = this;

        if (typeof navigator.permissions === "object") {
            navigator.permissions.query({name: 'notifications'})
                    .then(function (permission_status) {

                        permission_status.onchange = function () {
                            if (Notification.permission !== "granted")
                                bn.addButtonAllowNotification();
                            else
                                bn.removeButtonAllowNotification();
                        };
                    });
        }


        var now = new Date();
        date_start = now.getFullYear() + "-" +
                ("0" + (now.getMonth() + 1)).slice(-2) + "-" +
                ("0" + now.getDate()).slice(-2) + " " +
                ("0" + now.getHours()).slice(-2) + ":" +
                ("0" + now.getMinutes()).slice(-2) + ":" +
                ("0" + now.getSeconds()).slice(-2);


        // Premièrement, vérifions que nous avons la permission de publier des notifications. Si ce n'est pas le cas, demandons la
        if (window.Notification && Notification.permission !== "granted") {

            this.addButtonAllowNotification();

            // Si l'utilisateur accepte d'être notifié
            if (window.Notification && Notification.permission === "granted") {
                var n = new Notification("Titre", {
                    body: 'body'
                });
            }


            // Si l'utilisateur n'a pas choisi s'il accepte d'être notifié
            // Note: à cause de Chrome, nous ne sommes pas certains que la propriété permission soit définie, par conséquent il n'est pas sûr de vérifier la valeur par défaut.
            else if (window.Notification && Notification.permission !== "denied") {
//                    
//                    if (Notification.permission !== status) {
//                        Notification.permission = status;
//                    }
//
//                    // Si l'utilisateur est OK
//                    if (status === "granted") {
//                        var n = new Notification("Titre", {
//                            body: 'body2'
//                        });
//                    }
//
//                    // Sinon, revenons en à un mode d'alerte classique
//                    else {
//                        $('div.dropdown.modifDropdown:last').prepend('<span class="headerIconButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Veuillez autoriser les notifications" data-html="false" data-original-title="" title=""><i style="color: #A00000" class="fas fa-warning"></i></span>');
////                        alert("Vous n'etes pas notifié (pas recommandé), merci d'autoriser les notifications pour ce site");
//                    }
//                });
            }

            // Si l'utilisateur refuse d'être notifié
            else {
                this.addButtonAllowNotification();
//                alert("Vous ne serez pas notifié (pas recommandé)");
            }
        }
        // Variable définie coté PHP (actions_bimpcore.class.php)
        this.notificationActive = notificationActive;

        var localStorageOk = false;
        for (const [id_notification, value] of Object.entries(this.notificationActive)) {
            var notification = this;
            $.getScript(dol_url_root + '/' + value.module + '/views/js/' + value.nom + '.js', function () {
                eval('notification.notificationActive[' + id_notification + '].obj = new ' + value.nom + '(' + value.id_notification + ');');
                notification.notificationActive[id_notification].obj.traiteStorage();
                if(notification.notificationActive[id_notification].obj.id_max > 0) 
                    localStorageOk = true;
            });

        }

        setTimeout(function(){
            if(localStorageOk){
//                console.log('storage ok');
                setTimeout(function(){
                    bn.iterate();
                }, 15000);
            }
            else{
//                console.log('storage off');
                bn.iterate();
            }
        }, 4000);

        if (!parseInt($(window).data('focus_bimp_notification_event_init'))) {

            document.addEventListener('visibilitychange', function () {

                if (document.hidden) {
                    bn.active = false;

                    setTimeout(function () {
                        // Aucun autre onglet a prit le lead
                        if (parseInt(bimp_storage.get('id_bn_actif')) === bn.id)
//                                bn.updateStorage;
//                            else 
                        {
                            bn.active = true;

                        }

                    }, 1000);

                } else {
                    bn.active = true;
                    bn.updateStorage();
                    for (const [id_notification, notif] of Object.entries(bn.notificationActive)) {
                        var notification = this;
                        notif.obj.traiteStorage();
                    }
//                        bn.iterate();
                }
            });

            $(window).data('focus_bimp_notification_event_init', 1);
        }

        $('body').data('bimp_notification_events_init', 1);
    };

    this.updateStorage = function () {
        bimp_storage.set('id_bn_actif', this.id);
    };
}

class BimpStorage {

    getFullKey(key) {
        return dol_url_root+'_' + key;
    }

    get(key) {
//        console.log(this.getFullKey(key));
        var value = localStorage.getItem(this.getFullKey(key));
        var obj = JSON.parse(value);

        // Is an object
        if (typeof obj === 'object' && obj !== null)
            return obj;

        return value;
    }
    ;
            set(key, value, add = false) {
        // Est un object
        if(add){
            var oldValue = this.get(key);
            if(typeof oldValue === 'object' && typeof value === 'object'){
//                console.log('concat response', oldValue, value);
                value = {
                    ...oldValue,
                    ...value
                };
//                console.log('result', value);
            }
            else
                console.log('oups concat impossible');
                
        }
        
        
        if (typeof value === 'object' && value !== null)
            return localStorage.setItem(this.getFullKey(key), JSON.stringify(value));

        return localStorage.setItem(this.getFullKey(key), value);
    }
    ;
            remove(key) {
        localStorage.removeItem(this.getFullKey(key));
    }
    ;
}

var bimp_notification = new BimpNotification();
var bimp_storage = new BimpStorage();

$(document).ready(function () {
    $('a#notiDropdown').hide();
    bimp_notification.updateStorage();
    bimp_notification.onWindowLoaded();
});


