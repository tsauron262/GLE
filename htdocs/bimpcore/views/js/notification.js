
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
    constructor (nom) {
        
        if (this.constructor == AbstractNotification) {
            throw new Error('La classe abstraite "AbstractNotification" ne peut être instanciée.');
            return;
        }
        this.id_max = 0;
        this.content = [];
        this.nom = nom;
        this.dropdown_id = 'dropdown_' + this.nom;
        // Aussi dans BimpNotification
        this.parent_selector = 'div.dropdown.modifDropdown:last';
//        this.display_notification = true;
        if(bimp_storage.get(this.nom) === null)
            bimp_storage.set(this.nom, this.id_max);
        this.init();
    }
    
    init(an) {
        var instance = this;

        // Animation ouverture des notifs
        $('#' + an.dropdown_id).click(function(e) {
            instance.expand(instance.dropdown_id);
            e.stopPropagation();
            
        });
        
        
        // Fermeture des dropdown lors de cliques à côté
        $(document).click(function(e) {
            
            if(!$('#page_modal').hasClass('in') && $(e.target).attr('id') != 'page_modal') {
                var $target = $(e.target);
                if(!$target.closest('.modifDropdown').length)
                    instance.collapse();
            }
        });
                
        
        // Click recharger la notif
        $('span[name="reload_notif"][dropdown_id="' + instance.dropdown_id + '"]').click(function() {
            instance.reloadNotif();
        });


        
    }
    
    expand(dropdown_id) {
        $(this.parent_selector).find('.bimp_notification_dropdown').each(function() {

            // Définition de l'attribut is_open
            if(typeof $(this).attr('is_open') === typeof undefined)
                $(this).attr('is_open', 0);

            var was_open = parseInt($(this).attr('is_open'));

            // Fermeture
            if(was_open === 1) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 0);
            }

            // Ouverture de la dropdown cliquée si elle n'était pas déjà ouverte
            if($(this).attr('aria-labelledby') === dropdown_id && was_open === 0) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 1);
            }

        });
    }
    
    collapse() {
        $(this.parent_selector).find('.bimp_notification_dropdown').each(function() {

            // Définition de l'attribut is_open
            if(typeof $(this).attr('is_open') === typeof undefined)
                $(this).attr('is_open', 0);

            // Fermeture
            if(parseInt($(this).attr('is_open')) === 1) {
                $(this).slideToggle(200);
                $(this).attr('is_open', 0);
            }
        });
    }
    
    /**
     * Méthode appelé lors du retour ajax, doit être appelée avec super(element)
     */
    addElement(element) {
        var id_max_changed = 0;
        var to_display = '';
//        console.log(this.content);
        if(typeof element.content === "object" && element.content.length > 0) {
            
            this.content = this.content.concat(element.content);
            var nb_unread = 0;
                        
            if(element.content !== null && this.isMultiple(element.content))
                var is_multiple = true;
            else
                var is_multiple = false;

            for(var i in element.content) {

                // Redéfinition de id max
                if(parseInt(element.content[i].id) > this.id_max) {
                    this.id_max = parseInt(element.content[i].id);
                    id_max_changed = 1;
                }
                
                // Si la fonction n'est pas implémenter dans la classe fille: is_new = 1
                var is_new = this.isNew(element.content[i]);
                
                // Clé utilisé pour identifier les "groupes de message" (ex: conversation)
                var key = this.getKey(element.content[i]);
                
                // Affichage dans le topmenu
                to_display = this.formatElement(element.content[i], key);
                this.addInList(to_display, element.content[i].url, element.content[i], key, element.content[i].append);
                
                // Augmentation du nombre dans le span rouge
                if(is_new === 1) {
                    
                    // Affichage dans la notification
                    if(!is_multiple && id_max_changed) {
                        var global_id_max = parseInt(bimp_storage.get(this.nom));
                        if(global_id_max < this.id_max) {
                            bimp_storage.set(this.nom, this.id_max);
                            this.displayNotification(element.content[i]);
                        }
                    }
                    
                    nb_unread++;
                }
            }
            
            if (is_multiple && id_max_changed) {
                    var global_id_max = parseInt(bimp_storage.get(this.nom));
                    if(global_id_max < this.id_max) {
                        bimp_storage.set(this.nom, this.id_max);
                        this.displayMultipleNotification(element.content);
                    }
            }
            
            
            this.elementAdded(nb_unread, this.dropdown_id);
            
        }
                
    }
    
    addInList(to_display, url, element, key, to_append = false) {
        
        if(!element.class)
            element.class = '';

        if(url !== undefined) {
            var html = '<div class="list_part notif_link ' + element.class + '" key="' + key + '">';            
            html += to_display + '<span class="objectIcon"><i class="fas fa5-external-link-alt"></i></span>';
        } else
            var html = '<div class="list_part ' + element.class + '" key="' + key + '">' + to_display;
        
        if(element.date_create)
            html += '<div class="date_notif">' + this.formatDate(element.date_create)  + '</div>';
        html += '</div>';

        if(to_append === false)
            $('div[aria-labelledby="' + this.dropdown_id + '"] div.notifications-wrap ').prepend(html);
        else
            $(to_append).prepend(html);
        
        if(url !== undefined) {
            
            // Clique icon "ouvrir dans un nouvel onglet"
            $('div.list_part[key="' + key + '"] > span.objectIcon').on('click', function(e) {
                window.open(url);
                e.stopPropagation();
            });
            
            // Clique sur la notification entière - clique gauche
            $('div.list_part[key="' + key + '"]').on('click', function(e) {
                if (e.target !== this)
                    return;
                
                if(e.button === 0)
                    document.location.href=url;
            });
            
//            // Clique sur la notification entière - clique molette
//            $('div.list_part[key="' + key + '"]').on('mouseup', function(e) {
//                // Clique molette
//                if(e.button === 1)
//                    window.open(url);
//            });
        }


    }
    
    isMultiple(elements) {
        return elements.length >= 7;
    }
    
    formatDate(input) {
        var m = new Date(input);
        return  ("0" + m.getDate()).slice(-2) + "/" +
                ("0" + (m.getMonth()+1)).slice(-2) + "/" +
                m.getFullYear() + " " +
                ("0" + m.getHours()).slice(-2) + ":" +
                ("0" + m.getMinutes()).slice(-2) + ":" +
                ("0" + m.getSeconds()).slice(-2);
    }
    
    elementAdded(nb_add, dropdown_id) {

        // Aucun nouvel élément
        if(nb_add === 0)
            return;
        
        var span_red = $('a#' + dropdown_id + ' > span.badge.bg-danger');
        
        
        // Le span existe, on le met à jour
        if(0 < parseInt(span_red.length)) {
            var nb_old = parseInt($('a#' + dropdown_id + ' > span.badge.bg-danger').html());
            span_red.html(nb_add + nb_old);
        }
        
        // Le span n'existe pas, il faut le créer
        else {
            $('a#' + dropdown_id).append('<span class="badge bg-danger">' + nb_add + '</span>');
        }
    }
    
    elementRemoved(nb_rm, dropdown_id) {
        
        // Aucun élément à supprimer
        if(nb_rm === 0)
            return;
                
        var span_red = $('a#' + dropdown_id + ' > span.badge.bg-danger');
        
        // Le span existe, on le met à jour
        if(0 < parseInt(span_red.length)) {

            var nb_old = parseInt(span_red.html());
            var nb_new = nb_old - nb_rm;
            if(nb_new <= 0)
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
    
    getBoutonReload(dropdown_id) {
        return '<span dropdown_id="' + dropdown_id +'" name="reload_notif" class="objectIcon"><i class="fas fa5-redo-alt"></i></span>';
    }
    
    reloadNotif() {
        
        bimp_notification.notificationActive[this.nom].obj.content = [];
        bimp_notification.notificationActive[this.nom].obj.id_max = 0;
        
        this.emptyNotifs();
        
        // TODO check la suite
//        this.display_notification = false;
        bimp_notification.reload(false);
    }
    
    emptyNotifs() {
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

    

    this.reload = function (reiterate = true) {
        
        if (!bn.active || bn.processing) {
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
            
            
            for(var i in this.notificationActive) {
                var notif = {
                    nom: this.notificationActive[i].obj.nom,
                    module: this.notificationActive[i].module,
                    class:  this.notificationActive[i].class,
                    method: this.notificationActive[i].method,
                    id_max: this.notificationActive[i].obj.id_max
                };
                data.notificationActive.push(notif);
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
                        
                        for (const [nom, value] of Object.entries(result.notifications)) {
                            eval('bn.notificationActive.' + nom + '.obj.addElement(value);');
                        }                     

//                        bn.delay = 0;
                        
                    }
                    
                    if(reiterate)
                        bn.iterate();
                },
                error: function () {
                    bn.processing = false;
                    bn.$loading.hide();
                    bn.$refreshBtn.show();
                    if(reiterate)
                        bn.iterate();
                }

            });
        }
    };

    this.iterate = function () {
        if (bn.delay < 10000) {
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

    this.onWindowLoaded = function () {
       
        
        var now = new Date();
        date_start = now.getFullYear() + "-" +
                ("0" + (now.getMonth()+1)).slice(-2) + "-" +
                ("0" + now.getDate()).slice(-2) + " " +
                ("0" + now.getHours()).slice(-2) + ":" +
                ("0" + now.getMinutes()).slice(-2) + ":" +
                ("0" + now.getSeconds()).slice(-2);


        // Premièrement, vérifions que nous avons la permission de publier des notifications. Si ce n'est pas le cas, demandons la
        if (window.Notification && Notification.permission !== "granted") {
            Notification.requestPermission(function (status) {
                if (Notification.permission !== status) {
                    Notification.permission = status;
                }
            });

            // Si l'utilisateur accepte d'être notifié
            if (window.Notification && Notification.permission === "granted") {
                var n = new Notification("Titre", {
                    body: 'body'
                });
            }


            // Si l'utilisateur n'a pas choisi s'il accepte d'être notifié
            // Note: à cause de Chrome, nous ne sommes pas certains que la propriété permission soit définie, par conséquent il n'est pas sûr de vérifier la valeur par défaut.
            else if (window.Notification && Notification.permission !== "denied") {
                Notification.requestPermission(function (status) {
                    if (Notification.permission !== status) {
                        Notification.permission = status;
                    }

                    // Si l'utilisateur est OK
                    if (status === "granted") {
                        var n = new Notification("Titre", {
                            body: 'body2'
                        });
                    }

                    // Sinon, revenons en à un mode d'alerte classique
                    else {
                        $('div.dropdown.modifDropdown:last').prepend('<span class="headerIconButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Veuillez autoriser les notifications" data-html="false" data-original-title="" title=""><i style="color: #A00000" class="fas fa-warning"></i></span>');
//                        alert("Vous n'etes pas notifié (pas recommandé), merci d'autoriser les notifications pour ce site");
                    }
                });
            }

            // Si l'utilisateur refuse d'être notifié
            else {
                $('div.dropdown.modifDropdown:last').prepend('<span class="headerIconButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Veuillez autoriser les notifications" data-html="false" data-original-title="" title=""><i style="color: #A00000" class="fas fa-warning"></i></span>');
//                alert("Vous ne serez pas notifié (pas recommandé)");
            }
        }
        // Variable définie coté PHP (actions_bimpcore.class.php)
        this.notificationActive = notificationActive;

        for (const [nom, value] of Object.entries(this.notificationActive)) {
            var notification = this;
            $.getScript(DOL_URL_ROOT + '/' + value.module + '/views/js/' + nom + '.js', function() {
                eval('notification.notificationActive.' + nom + '.obj = new ' + nom + '("' + nom + '");');
            });
            
        }
        
        bn.iterate();

        if (!parseInt($(window).data('focus_bimp_notification_event_init'))) {
            
            document.addEventListener('visibilitychange', function() {

                if(document.hidden) {
                        bn.active = false;

                        setTimeout(function() {
                            // Aucun autre onglet a prit le lead
                            if(parseInt(bimp_storage.get('id_bn_actif')) === bn.id)
//                                bn.updateStorage;
//                            else 
                            {
                                bn.active = true;

                            }
                            
                        }, 1000);

                    } else {
                        bn.active = true;
                        bn.updateStorage();
                        bn.iterate();
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
        return '_' + key;
    }

    get(key) {
//        console.log(this.getFullKey(key));
        var value = localStorage.getItem(this.getFullKey(key));
        var obj = JSON.parse(value);
        
        // Is an object
        if(typeof obj === 'object' && obj !== null)
            return obj;
    
        return value;
    };
    
    set(key, value) {
//        console.log(this.getFullKey(key));
        // Est un object
        if(typeof value === 'object' && value !== null)
            return localStorage.setItem(this.getFullKey(key), JSON.stringify(value));
        
        return localStorage.setItem(this.getFullKey(key), value);
    };
    
    remove(key) {
        localStorage.removeItem(this.getFullKey(key));
    };

}

var bimp_notification = new BimpNotification();
var bimp_storage = new BimpStorage();

$(document).ready(function () {
    $('a#notiDropdown').hide();
    bimp_notification.updateStorage();
    bimp_notification.onWindowLoaded();
});


