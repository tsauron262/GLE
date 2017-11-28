<?php

class BimpCard
{

    public $object;
    public $config;
    public $config_path;

    public function __construct($object, BimpConfig $config, $config_path)
    {
        $this->object = $object;
        $this->config = $config;
        $this->config_path = $config_path;
    }

    public function render()
    {
        if (!isset($this->object->id) || !$this->object->id) {
            if (is_a($this->object, 'BimpObject')) {
                $msg = 'Aucun' . ($this->object->isLabelFemale() ? 'e' : '') . ' ' . $this->object->getLabel();
            } else {
                $msg = 'Aucun';
            }
            return self::renderAlerts($msg, 'warning');
        }

        $params = $this->config->get($this->config_path, null, true, 'any');
        if (is_string($params)) {
            switch ($params) {
                case 'societe_card':
                    return $this->renderSocieteCard();

                case 'contact_card':
                    return $this->renderContactCard();

                case 'user_card':
                    return $this->renderUserCard();

                default:
                    if ($field && $this->config->isDefined('fields/' . $field . '/object')) {
                        $object_name = $this->config->get('fields/' . $field . '/object', '', false, 'any');
                        if ($object_name && is_string($object_name)) {
                            if ($this->config->isDefined('objects/' . $object_name . '/cards/' . $card)) {
                                $this->config->setCurrentPath('objects/' . $object_name . '/cards/' . $card);
                                $html .= BimpRender::renderObjectCard($instance, $this->config);
                            }
                        }
                    } elseif (is_a($instance, 'BimpObject')) {
                        if ($instance->config->isDefined('cards/' . $card)) {
                            $instance->config->setCurrentPath('cards/' . $card);
                            $html .= BimpRender::renderObjectCard($instance, $instance->config);
                        }
                    }
            }
        }

        if (is_a($this->object, 'BimpObject')) {
            $object_name = $this->object->object_name;
        } else {
            $object_name = get_class($this->object);
        }

        $card_identifier = $object_name . '_' . $this->object->id . '_card';

        $title = $this->config->get($this->config_path . '/title', null, false, 'any');
        $image_url = $this->config->get($this->config_path . '/image', null);
        $view_btn = $this->config->get($this->config_path . '/view_btn', false, false, 'bool');

        if (is_array($title)) {
            if (isset($title['object_prop'])) {
                if (property_exists($this->object, $title['object_prop'])) {
                    $title = $this->object->{$title['object_prop']};
                }
            }
        }

        if (is_string($title) && ($title === 'nom')) {
            $title = BimpObject::getInstanceNom($this->object);
        }

        $fields = array();
        foreach ($this->config->get($this->config_path . '/fields', array(), false, 'array') as $idx => $params) {
            $label = $this->config->get($this->config_path . '/fields/' . $idx . '/label', '');
            $field = $this->config->get($this->config_path . '/fields/' . $idx . '/field', '');
            $prop = $this->config->get($this->config_path . '/fields/' . $idx . '/prop', '');
            $value = $this->config->get($this->config_path . '/fields/' . $idx . '/value', null);
            $icon = $this->config->get($this->config_path . '/fields/' . $idx . '/icon', '');
            $display = $this->config->get($this->config_path . '/fields/' . $idx . '/display', 'default', false, 'any');

            if ($prop) {
                if (property_exists($this->object, $prop)) {
                    $value = $this->object->{$prop};
                } else {
                    $value = BimpRender::renderAlerts('Erreur: la propriété "' . $prop . '" n\existe pas');
                }
            } else {
                if (is_a($this->object, 'BimpObject')) {
                    if ($field) {
                        $value = $this->object->displayData($field, $display, $value);
                    }
                }
            }

            if (!is_null($value)) {
                $fields[] = array(
                    'label' => $label,
                    'value' => $value,
                    'icon'  => $icon
                );
            }
        }

        return $this->renderCard($title, $image_url, $fields, $view_btn);
    }

    protected function renderSocieteCard()
    {
        if (!is_a($this->object, 'Societe')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas une société');
        }
        
        $img_url = null; // todo...
        $fields = array();

        if (isset($this->object->nom) && isset($this->object->nom)) {
            $title = $this->object->nom;
            $name = '';
            if (isset($this->object->firstname) && $this->object->firstname) {
                $name .= BimpTools::ucfirst(strtolower($this->object->firstname)) . ' ';
            }
            if (isset($this->object->lastname) && $this->object->lastname) {
                $name .= strtoupper($this->object->lastname);
            }
            if ($name) {
                $fields[] = array(
                    'icon'  => 'user-circle',
                    'value' => $name
                );
            }
        } else {
            $title = strtoupper($this->object->lastname) . ' ' . BimpTools::ucfirst(strtolower($this->object->firstname));
        }

        if (isset($this->object->code_client) && $this->object->code_client) {
            $fields[] = array(
                'label' => 'Code client',
                'value' => $this->object->code_client
            );
        }

        if (isset($this->object->code_fournisseur) && $this->object->code_fournisseur) {
            $fields[] = array(
                'label' => 'Code fournisseur',
                'value' => $this->object->code_fournisseur
            );
        }

        $address = '';
        if (isset($this->object->address) && $this->object->address) {
            $address .= $this->object->address . '<br/>';
        }

        if (isset($this->object->zip) && $this->object->zip) {
            $address .= $this->object->zip . ' ';
        }

        if (isset($this->object->town) && $this->object->town) {
            $address .= $this->object->town . '<br/>';
        }

        if (isset($this->object->pays) && $this->object->pays) {
            $address .= $this->object->pays;
        } elseif (isset($this->object->country) && $this->object->country) {
            $address .= $this->object->country;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->object->phone) && $this->object->phone) {
            $fields[] = array(
                'icon'  => 'phone',
                'value' => $this->object->phone
            );
        }

        if (isset($this->object->fax) && $this->object->fax) {
            $fields[] = array(
                'icon'  => 'fax',
                'value' => $this->object->fax
            );
        }

        if (isset($this->object->email) && $this->object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->object->email . '" style="text-overflow: ellipsis;">' . $this->object->email . '</a>'
            );
        }

        if (isset($this->object->skype) && $this->object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->object->skype
            );
        }

        if (isset($this->object->url) && $this->object->url) {
            $fields[] = array(
                'icon'  => 'home',
                'value' => '<a href="http://' . $this->object->url . '" title="Site web" target="_blank">' . $this->object->url . '</a>'
            );
        }

        return $this->renderCard($title, $img_url, $fields, true);
    }

    protected function renderContactCard()
    {
        if (!is_a($this->object, 'Contact')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas un contact');
        }
        $img_url = null; // todo...
        $fields = array();

        $title = '';

        if (isset($this->object->lastname) && $this->object->lastname) {
            $title .= strtoupper($this->object->lastname) . ' ';
        }
        if (isset($this->object->firstname) && $this->object->firstname) {
            $title .= BimpTools::ucfirst(strtolower($this->object->firstname));
        }


        $address = '';
        if (isset($this->object->address) && $this->object->address) {
            $address .= $this->object->address . '<br/>';
        }

        if (isset($this->object->zip) && $this->object->zip) {
            $address .= $this->object->zip . ' ';
        }

        if (isset($this->object->town) && $this->object->town) {
            $address .= $this->object->town;
        }

        if (isset($this->object->state) && $this->object->state) {
            $address .= ' - ' . $this->object->state;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->object->phone_pro) && $this->object->phone_pro) {
            $fields[] = array(
                'label' => 'Pro',
                'icon'  => 'phone',
                'value' => $this->object->phone_pro
            );
        }

        if (isset($this->object->phone_perso) && $this->object->phone_perso) {
            $fields[] = array(
                'label' => 'Perso',
                'icon'  => 'phone',
                'value' => $this->object->phone_perso
            );
        }

        if (isset($this->object->phone_mobile) && $this->object->phone_mobile) {
            $fields[] = array(
                'label' => 'Mobile',
                'icon'  => 'mobile',
                'value' => $this->object->phone_mobile
            );
        }

        if (isset($this->object->fax) && $this->object->fax) {
            $fields[] = array(
                'icon'  => 'fax',
                'value' => $this->object->fax
            );
        }

        if (isset($this->object->email) && $this->object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->object->email . '" style="text-overflow: ellipsis;">' . $this->object->email . '</a>'
            );
        }

        if (isset($this->object->skype) && $this->object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->object->skype
            );
        }

        return $this->renderCard($title, $img_url, $fields, true);
    }

    protected function renderUserCard()
    {
        if (!is_a($this->object, 'User')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas un utilisateur');
        }
        $img_url = null; // todo...
        $fields = array();

        $title = '';

        if (isset($this->object->name) && $this->object->name) {
            $title .= $this->object->name;
        } else {
            if (isset($this->object->lastname) && $this->object->lastname) {
                $title .= strtoupper($this->object->lastname) . ' ';
            }
            if (isset($this->object->firstname) && $this->object->firstname) {
                $title .= BimpTools::ucfirst(strtolower($this->object->firstname));
            }
        }

        if (isset($this->object->job) && $this->object->job) {
            $fields[] = array(
                'label' => 'Fonction',
                'value' => $this->object->Job
            );
        }

        $address = '';
        if (isset($this->object->address) && $this->object->address) {
            $address .= $this->object->address . '<br/>';
        }

        if (isset($this->object->zip) && $this->object->zip) {
            $address .= $this->object->zip . ' ';
        }

        if (isset($this->object->town) && $this->object->town) {
            $address .= $this->object->town;
        }

        if (isset($this->object->state) && $this->object->state) {
            $address .= ' - ' . $this->object->state;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->object->office_phone) && $this->object->office_phone) {
            $fields[] = array(
                'label' => 'Bureau',
                'icon'  => 'phone',
                'value' => $this->object->phone_pro
            );
        }
        
        if (isset($this->object->office_fax) && $this->object->office_fax) {
            $fields[] = array(
                'label' => 'Bureau',
                'icon'  => 'fax',
                'value' => $this->object->fax
            );
        }

        if (isset($this->object->user_mobile) && $this->object->user_mobile) {
            $fields[] = array(
                'label' => 'Mobile',
                'icon'  => 'mobile',
                'value' => $this->object->user_mobile
            );
        }

        if (isset($this->object->email) && $this->object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->object->email . '" style="text-overflow: ellipsis;">' . $this->object->email . '</a>'
            );
        }

        if (isset($this->object->skype) && $this->object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->object->skype
            );
        }

        return $this->renderCard($title, $img_url, $fields, true);
    }

    protected function renderCard($title, $img_url = '', $fields = array(), $view_btn = false)
    {
        $html = '';
        $html .= '<div class="media object_card">';
        if (!is_null($img_url) && $img_url) {
            $html .= '<div class="media-left">';
            $html .= '<img src="' . $img_url . '" alt=""/>';
            $html .= '</div>';
        }
        $html .= '<div class="media-body">';
        if (!is_null($title) && $title) {
            $html .= '<h4 class="media-heading">' . $title . '</h4>';
        }
        $html .= '<table class="object_card_table">';
        $html .= '<thead></thead>';
        $html .= '<tbody>';
        foreach ($fields as $field) {
            if (isset($field['value']) && $field['value']) {
                $html .= '<tr>';
                $html .= '<th>';
                if (isset($field['icon']) && $field['icon']) {
                    $html .= '<i class="fa fa-' . $field['icon'] . ' iconLeft"></i>';
                }
                if (isset($field['label']) && $field['label']) {
                    $html .= $field['label'] . ':';
                }
                $html .= '</th>';

                $html .= '<td>' . $field['value'] . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        if ($view_btn) {
            $url = BimpObject::getInstanceUrl($this->object);
            $file = '';
            $params = array();
            if (is_a($this->object, 'BimpObject')) {
                $file = $this->object->module . '/index.php';
                $params['fc'] = $this->object->controller;
                $params['id'] = $this->object->id;
            } else {
                $file = strtolower(get_class($this->object)) . '/card.php';
                if (is_a($this->object, 'Societe')) {
                    $params['socid'] = $this->object->id;
                } else {
                    $params['id'] = $this->object->id;
                }
            }
            if (!file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
                $file = '';
            }

            if ($url || $file) {
                $html .= '<div style="text-align: right; margin-top: 15px">';
                $html .= '<div class="btn-group">';
                if ($file) {
                    $html .= '<button type="button" class="btn btn-default" ';
                    $html .= 'onclick="loadModalObjectPage($(this), \'' . $url . '\', \'page_modal\', \'' . htmlentities(addslashes($title)) . '\')">';
                    $html .= '<i class="fa fa-file-o iconLeft"></i>';
                    $html .= 'Afficher</button>';
                }
                if ($url) {
                    $html .= '<a href="' . $url . '" class="btn btn-default" target="_blank" title="Afficher dans une nouvel onglet">';
                    $html .= '<i class="fa fa-external-link"></i>';
                    if (!$file) {
                        $html .= '&nbsp;&nbsp;Afficher';
                    }
                    $html .= '</a>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
