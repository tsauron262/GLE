<?php

class BimpHistory extends BimpObject
{

    public function add(BimpObject $object, $field, $value)
    {
        $this->reset();
        $errors = array();
        $baseErrorMsg = 'Echec de la mise à jour de l\'historique pour le champ "' . $field . '"';

        $baseErrorMsg .= ' ' . $object->getLabel('of_the');
        if (is_null($object->id) || !$object->id) {
            $errors[] = ' - aucun ID';
        } elseif (!$field || !$object->config->isDefined('fields/' . $field)) {
            $errors[] = ' - champ invalide';
        }

        $baseErrorMsg .= ' ' . $object->id;

        if (!count($errors)) {
            global $user;
            if (isset($user->id) && $user->id) {
                $id_user = $user->id;
            } else {
                $id_user = 0;
            }

            $data = array(
                'module'    => $object->module,
                'object'    => $object->object_name,
                'id_object' => $object->id,
                'field'     => $field,
                'value'     => $this->db->db->escape($value),
                'date'      => date('Y-m-d H:i:s'),
                'id_user'   => (int) $id_user
            );

            $errors = $this->validateArray($data);

            if (!count($errors)) {
                $errors = $this->create();
            }
        }

        return $errors;
    }

    public function deleteByObject(BimpObject $object, $id_object)
    {
        $errors = array();
        if (!is_null($id_object) && $id_object) {
            $where = '`module` = \'' . $object->module . '\'';
            $where .= ' AND `object` = \'' . $object->object_name . '\'';
            $where .= ' AND `id_object` = ' . (int) $id_object;

            if ($this->db->delete($this->getTable(), $where) <= 0) {
                $errors[] = 'Echec de la suppression de l\'historique des champs pour ' . $object->getLabel('the') . ' ' . $object->id;
            }
        }

        return $errors;
    }

    public function getHistory(BimpObject $object, $field)
    {
        if (!isset($object->id) || !$object->id) {
            return array();
        }

        return $this->getList(array(
                    'module'    => $object->module,
                    'object'    => $object->object_name,
                    'id_object' => (int) $object->id,
                    'field'     => $field
                        ), null, null, 'id', 'desc', 'array', array(
                    'id', 'id_user', 'date', 'value'
        ));
    }

    public function renderCard(BimpObject $object, $field, $limit = 15, $display_user = true, $display_title = true)
    {
        if (!$object->field_exists($field)) {
            return '';
        }

        $list = $this->getHistory($object, $field);

        $html = '';

        $html .= '<div class="objectFieldHistoryCard"';
        $html .= ' data-module="' . $object->module . '"';
        $html .= ' data-object="' . $object->object_name . '"';
        $html .= ' data-id_object="' . $object->id . '"';
        $html .= ' data-field="' . $field . '"';
        $html .= '>';

        if ($display_title) {
            $title = 'Historique pour ' . BimpTools::ucfirst($object->getLabel()) . ' ' . $object->id . ': ' . $field;
            $html .= '<h5>' . $title . '</h5>';
        }

        $values = $object->getConf('fields/' . $field . '/values', array(), false, 'array');

        $n = 1;
        if (count($list)) {
            global $db;
            $user = new User($db);
            $users = array();

            $html .= '<table class="objectFieldHistoryTable">';
            $html .= '<tbody>';
            foreach ($list as $item) {
                $DT = new DateTime($item['date']);
                $html .= '<tr>';
                $html .= '<td>Le <span class="date">' . $DT->format('d / m / Y') . '</span>';
                $html .= ' à <span class="time">' . $DT->format('H:i:s') . '</span></td>';

                if ($display_user) {
                    $html .= '<td style="padding-left: 5px">';
                    if (!is_null($item['id_user']) && $item['id_user']) {
                        if (!array_key_exists((int) $item['id_user'], $users)) {
                            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $item['id_user']);
                            $users[(int) $item['id_user']] = $user->getName();
                        }
                        $html .= $users[(int) $item['id_user']];
                    }
                    $html .= '</td>';
                }

                $html .= '<td style="padding-left: 5px">';
                if (isset($values[$item['value']])) {
                    $html .= '<span class="' . (isset($values[$item['value']]['classes']) ? implode(' ', $values[$item['value']]['classes']) : 'bold') . '">';
                    if (isset($values[$item['value']]['icon'])) {
                        $html .= BimpRender::renderIcon($values[$item['value']]['icon'], 'iconLeft');
                    }
                    if (is_string($values[$item['value']])) {
                        $html .= $values[$item['value']];
                    } elseif (isset($values[$item['value']]['label'])) {
                        $html .= $values[$item['value']]['label'];
                    } else {
                        $html .= $item['value'];
                    }
                    $html .= '</span>';
                } else {
                    $html .= '<span class="badge">' . $item['value'] . '</span>';
                }

                $html .= '</td>';
                $html .= '</tr>';
                unset($DT);
                $n++;
                if (!is_null($limit) && $n > $limit) {
                    break;
                }
            }
            $html .= '</tbody>';
            $html .= '</table>';

            unset($user);
        } else {
            $html .= BimpRender::renderAlerts('Aucune entrée dans l\'historique enregistrée pour ce champ', 'warning');
        }
        $html .= '</div>';

        return $html;
    }
}
