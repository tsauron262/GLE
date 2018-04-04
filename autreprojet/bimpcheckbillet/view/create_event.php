<?php

include_once '../param.inc.php';

print '<h4>Crée évènement</h4>';

print '<form action="' . URL_ROOT . 'interface.php" method="post">';

print '<label for="label">Libellé </label>';
print '<input name="label">';

print '<input name="action" style="display: none" value="create_event">';

print '<button type="submit">Créer</button>';
print '</form>';
