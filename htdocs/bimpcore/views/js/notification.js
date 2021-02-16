
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
 *     (displayNotification et formatElement)
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
        this.parent_selector = '.login_block_other';
        this.init();
    }
    
    init(an) {

        // Animation
        $('#' + an.dropdown_id).click(function(e) {

            $(an.parent_selector).find('.bimp_notification_dropdown').each(function() {
                
                if(typeof $(this).attr('is_open') === typeof undefined)
                    $(this).attr('is_open', 0);
                
                var was_open = parseInt($(this).attr('is_open'));
                
                if(was_open === 1) {
                    $(this).slideToggle(200);
                    $(this).attr('is_open', 0);
                }
                
                if($(this).attr('aria-labelledby') === an.dropdown_id && was_open === 0) {
                    $(this).slideToggle(200);
                    $(this).attr('is_open', 1);
                }
                
            });
            
            e.stopPropagation();
            
        });
    }
    
    /**
     * Méthode appelé lors du retour ajax, doit être appelée avec super(element)
     */
    addElement(element) {
                
        var to_display = '';
        if(typeof element.content === "object") {

            this.content = this.content.concat(element.content);
            var nb_unread = 0;
                        
            if(element.content !== null && element.content.length >= 3)
                var is_multiple = true;
            else
                var is_multiple = false;

            for(var i in element.content) {


                // Redéfinition de id max
                if(parseInt(element.content[i].id) > this.id_max)
                    this.id_max = parseInt(element.content[i].id);
                
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
                    if(!is_multiple)
                        this.displayNotification(element.content[i]);
                    
                    nb_unread++;
                }
            }
            
            if(is_multiple) {
                this.displayMultipleNotification(element.content);
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
            $('div.list_part[key="' + key + '"] > span.objectIcon').on('mousedown', function(e) {
                window.open(url);
                e.stopPropagation();
            });
            
            // Clique sur la notification entière
            $('div.list_part[key="' + key + '"]').on('mousedown', function(e) {
                // Clique gauche
                if(e.button === 0)
                    document.location.href=url;
                // Clique molette
                else if(e.button === 1)
                    window.open(url);
            });
        }


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
//        else {
//            console.log($('a#' + dropdown_id + ' > span.badge.bg-danger'));
//            console.log('Illogisme à elementRemoved, nb_rm: ' + nb_rm + ', dropdown_id: ' + dropdown_id);
//        }

    }
    
    isNew() {
        return 1;
    }
    
    getKey(element) {
        return element.id;
    }
    
}

function BimpNotification() {
    this.id = getRandomInt(9999999999999);
    this.active = true;
    this.hold = false;
    this.processing = false;
    this.storage_key = 'id_bn_actif';
    this.delay = 0;
    this.is_first_iteration = true;
    this.$loading = $();
    this.$refreshBtn = $();
    this.notificationActive = {};

    var bn = this;
    

    this.reload = function (status = '') {

        if (!bn.active || bn.processing) {
            return;
        }

        if (bn.hold) {
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
                        
                        bn.delay = 0;
                        
                    }
                    
                    bn.iterate();
                },
                error: function () {
                    bn.processing = false;
                    bn.$loading.hide();
                    bn.$refreshBtn.show();
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
                        alert("Vous n'etes pas notifié (pas recommandé), merci d'autoriser les notifications pour ce site");
                    }
                });
            }

            // Si l'utilisateur refuse d'être notifié
            else {
                alert("Vous ne serez pas notifié (pas recommandé)");
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
                console.log(bn.id);
                if(document.hidden) {
                        bn.active = false;

                        setTimeout(function() {
                            // Aucun autre onglet a prit le lead
                            if(parseInt(bs.get(bn.storage_key)) === bn.id)
//                                bn.upDateStorage;
//                            else 
                            {
                                bn.active = true;

                            }
                            
                        }, 1000);

                    } else {
                        bn.active = true;
                        bn.upDateStorage();
                        bn.iterate();
                    }
            });

            $(window).data('focus_bimp_notification_event_init', 1);
        }

        $('body').data('bimp_notification_events_init', 1);
    }
    
    
//    this.upDateStorage = function () {
//        var tabs = bs.get(this.storage_key);
//
////console.log('ancien tab:');
////console.log(tabs);
//        if(tabs == null) {
//            bs.set(this.storage_key, {[this.id]: this.active});
//        } else {
//            tabs[this.id] = this.active;
//            bs.set(this.storage_key, tabs);
//        }
//
////console.log('nouveau tab:');
//console.log(bs.get(this.storage_key));
//        
//    }

    this.upDateStorage = function () {
        bs.set(this.storage_key, this.id);
    }
    

}

function BimpStorage() {
    
    this.get = function (key) {
        var value = localStorage.getItem(key);
        var obj = JSON.parse(value);
        
        // Is an object
        if(typeof obj === 'object' && obj !== null)
            return obj;
    
        return value;
    }
    
    this.set = function (key, value) {
        
        // Est un object
        if(typeof value === 'object' && value !== null)
            return localStorage.setItem(key, JSON.stringify(value));
        
        return localStorage.setItem(key, value);
    }
    
    this.remove = function (key) {
        localStorage.removeItem(key);
    }
    
    this.clear = function () {
        return localStorage.clear();
    }
}


var bn = new BimpNotification();
var bs = new BimpStorage();

$(document).ready(function () {
    bn.upDateStorage();
    bn.onWindowLoaded();
});


