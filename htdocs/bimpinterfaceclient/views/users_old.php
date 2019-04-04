<?php
if (isset($_REQUEST['modif'])) {
    $email_user = $_REQUEST['modif'];
    if ($userClient->in_my_soc($email_user)) {
        $modifUser = new User_For_Interface($db);
        $modifUser->fetch($email_user);
        ?>
        <form action="?page=users&action=update" method="POST">
            <input type="text" name="email" value="<?= $email_user ?>" style="display:none" id="email" required>
            <div class="content">
                <div class="card">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="header">
                                <h4 class="title">Modification du compte <?= $modifUser->email ?></h4>
                                <p class="category">Pour la societé <?= $userClient->attached_societe->nom ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="content" >
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="header">
                                        <h4 class="title">Contrats attribués</h4>
                                    </div>
                                    <div class="content">
                                        <input class="list_contrat" name="attached_contrat" style="display:none"/>
                                        <div class="table-full-width">
                                            <table class="table">
                                                <tbody>
                                                    <?php
                                                    $my_attached_contrat = $modifUser->get_attached_contrat($couverture);
                                                    foreach ($couverture as $ref) {
                                                        if (in_array($ref, $my_attached_contrat)) {
                                                            echo '<tr>';
                                                            echo '<td>';
                                                            echo '<div class="checkbox">';
                                                            echo '<input type="checkbox" class="verif_check" id="' . $ref . '" checked="checked">';
                                                            echo '<label for="' . $ref . '"></label>';
                                                            echo '</div>';
                                                            echo '</td>';
                                                            echo '<td>' . $ref . '</td>';
                                                            echo '</tr>';
                                                        } else {
                                                            echo '<tr>';
                                                            echo '<td>';
                                                            echo '<div class="checkbox">';
                                                            echo '<input type="checkbox" class="verif_check" id="' . $ref . '">';
                                                            echo '<label for="' . $ref . '"></label>';
                                                            echo '</div>';
                                                            echo '</td>';
                                                            echo '<td>' . $ref . '</td>';
                                                            echo '</tr>';
                                                        }
                                                    }
                                                    ?>
                                                <?php
                                                ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="header">
                                        <h4 class="title">Status du compte</h4>
                                    </div>
                                    <div class="content">
                                        <div class="table-full-width">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>Role du compte :
                                                            <select name="role_compte" >
                                                                <option value="0" <?= ($modifUser->user()) ? 'selected' : "" ?> >Utilisateur</option>
                                                                <option value="1" <?= ($modifUser->admin()) ? 'selected' : "" ?> >Administrateur</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Status du compte :
                                                            <select name="status_compte" >
                                                                <option value="1" <?= ($modifUser->status == 1) ? 'selected' : "" ?> >Compte actif (L'utilisateur peut ce connecter à l'espace client)</option>
                                                                <option value="2" <?= ($modifUser->status == 2) ? 'selected' : "" ?> >Compte inactif (L'utilisateur ne peut pas ce connecter à l'espace client)</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mot de passe du compte :
                                                            <input type="password" class="form-control" name="password" placeholder="Laisser vide pour garder l'ancien mot de passe">
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info btn-fill pull-right">Modifier le compte utilisateur</button>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <script>
            var attached_contrat = Array();
            $(document).ready(function () {
                var ma_class = $('.verif_check');
                ma_class.each(function () {
                    if ($(this).attr('checked') == 'checked') {
                        attached_contrat.push($(this).attr('id'));
                        $('.list_contrat').val(attached_contrat);
                    }
                });
                console.log('Array() à l\'entré sur la page : ' + $('.list_contrat').val());
            });

            $('.verif_check').on('click', function () {
                if (!attached_contrat.includes($(this).attr('id'))) {
                    attached_contrat.push($(this).attr('id'));
                } else {
                    attached_contrat.splice(attached_contrat.indexOf($(this).attr('id')), 1);
                }
                $('.list_contrat').val(attached_contrat);
                console.log('Array() après modification : ' + $('.list_contrat').val());
            });

        </script>

        <?php
    } else {
        echo BimpRender::renderAlerts("Vous ne pouvez pas modifier un utilisateur qui n'est pas dans votre entreprise", 'danger', false);
    }
} else {
    ?>
    <form action="?page=users&action=create" method="POST">
        <div class="col-md-12">
            <div class="card">
                <div class="header">
                    <h4 class="title">Créer un utilisateur</h4>
                </div>
                <div class="content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Adresse mail</label>
                                <input type="email" class="form-control" placeholder="Email" name="email">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-multiselect form-group">
                                <label for="exampleInputEmail1">Attribuer un contrat</label>
                                <div class="selectBox form-control" onclick="showCheckboxes()">
                                    <select>
                                        <option>Liste contrat</option>
                                    </select>
                                    <div class="selectWrapper"></div>
                                    <input class="list_contrat" name="attached_contrat" style="display:none"/>
                                </div>
                                <div id="selectOptions" style="z-index:9999">
    <?php
    foreach ($couverture as $contrat => $ref) {
        echo '<label class="singleOption" contrat="' . $ref . '">';
        echo '<b class="singleOption_bold">' . $ref . '</b>';
        echo '</label>';
    }
    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 custom-multiselect form-group">
                            <label for="exampleInputEmail1">Type de compte</label><br />
                            <div class="selectBox form-control">
                                <select name="role_compte" >
                                    <option value="0">Utilisateur</option>
                                    <option value="1">Administrateur</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <button type="submit" class="btn btn-info btn-fill pull-right">Créer l'utilisateur</button>
                <div class="clearfix"></div>
            </div>
        </div>
    </form>

    <div class="col-md-12">
        <div class="content-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="header">
                            <h4 class="title">Liste des utilisateurs</h4>
                            <p class="category">Pour la societé <?= $userClient->attached_societe->nom ?></p>
                        </div>
                        <div class="content table-responsive table-full-width">
                            <table class="table table-hover table-striped">
                                <thead>
                                <th>ID</th>
                                <th>Adresse Mail</th>
                                <th>Contrats</th>
                                <th>Status</th>
                                <th>Actions</th>
                                </thead>
                                <tbody>
    <?php
    $role = Array(0 => '(Utilisateur)', 1 => '(Administrateur)');
    $status = Array(1 => '<i class="fa fa-check"></i> Compte actif', 2 => '<i class="fa fa-times"></i> Compte inactif');
    foreach ($userClient->liste_compte() as $compte) {
        echo '<tr>';
        echo '<td><b>#' . $compte->id . '</b></td>';
        echo '<td>' . $compte->email . ' ' . $role[$compte->role] . '</td>';
        echo '<td>';
        foreach (json_decode($compte->attached_contrat) as $ref) {
            echo '<div class="css-tooltip left">' . $ref . '<span class="tt-content">' . $actions->get_infos_contrat($ref) . '</span></div>, ';
        }
        echo '</td>';
        echo '<td>' . $status[$compte->status] . '</td>';
        echo '<td><a href="?page=users&modif=' . $compte->email . '"><i class="fa fa-edit" ></i></a></td>';
        echo '</tr>';
    }
    ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var expanded = false;
        var attached_contrat = Array();
        function showCheckboxes() {
            var options = document.getElementById("selectOptions");
            if (!expanded) {
                options.style.display = "block";
                expanded = true;
            } else {
                options.style.display = "none";
                expanded = false;
            }
        }

        $('.singleOption').on('click', function () {
            if (!attached_contrat.includes($(this).attr('contrat'))) {
                attached_contrat.push($(this).attr('contrat'));
                $(this).css('color', '#EF7D00');
            } else {
                attached_contrat.splice(attached_contrat.indexOf($(this).attr('contrat')), 1);
                $(this).css('color', 'grey');
            }
            $('.list_contrat').val(attached_contrat);
            //alert($('.list_contrat').text());
        });


    </script>
    <style>
        .custom-multiselect {
            width: 200px;
        }

        .selectBox {
            position: relative;
        }

        .selectBox select {
            width: 100%;
        }

        .selectWrapper {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
        }

        #selectOptions {
            display: none;
            position: fixed;
            width: 250px;
            border: 1px #dddddd solid;
            background-color: white;
            line-height: 225%;
            box-shadow: 0 0 12px 0 rgba(0,0,0,.12);
        }

        #selectOptions b {
            margin-left:20px;
        }

        #selectOptions label {
            display: block;
        }

        #selectOptions label:hover {
            color: #EF7D00;
        }

        input[type="checkbox"] {
            position: absolute;
            right: 0;
        }

        .singleOption{
            position: relative;
        }
    </style>
    <?php
}
if (isset($_REQUEST['action']) == 'create') {
    switch ($_REQUEST['action']) {
        case 'create' :
            $list = explode(',', $_POST['attached_contrat']);
            $user_for_create = new User_For_Interface($db);
            $user_for_create->email = $_POST['email'];
            $user_for_create->attached_societe = $userClient->attached_societe->id;
            $user_for_create->role = $_POST['role_compte'];
            if ($user_for_create->admin()) {
                $list = $couverture;
            }

            $user_for_create->status = 1;
            $user_for_create->attached_contrat = json_encode($list);
            $user_for_create->date_creation = Date('Y-m-d');
            $user_for_create->user_creation = $userClient->id;
            $user_for_create->create();
            $user_for_create = null;
            break;
        case 'update' :
            $list = explode(',', $_POST['attached_contrat']);
            $user_for_modif = new User_For_Interface($db);
            $user_for_modif->email = $_POST['email'];
            $user_for_modif->role = $_POST['role_compte'];
            $user_for_modif->attached_societe = $userClient->attached_societe->id;
            $user_for_modif->attached_contrat = json_encode($list);
            $user_for_modif->status = $_POST['status_compte'];
            $user_for_modif->date_modification = Date('Y-m-d');
            $user_for_modif->user_modification = $userClient->id;
            $user_for_modif->password = (empty($_POST['password'])) ? $user_for_modif->get_password() : hash('sha256', $_POST['password']);
            $user_for_modif->update();
            $user_for_modif = null;
            break;
    }
}
?>

   
