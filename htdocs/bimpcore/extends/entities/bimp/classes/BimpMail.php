<?php

class BimpMail extends BimpMailCore
{

    public $title = 'BIMP';
    public $subtitle = 'Groupe LDLC';
    public $from_name = 'BIMP';
    public $url = 'www.ldlc.com';

    function __construct($parent, $subject, $to, $from, $msg = '', $reply_to = '', $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $errors_to = '')
    {
        $type = '';

        if ($from === 'ldlc') {
            $type = 'ldlc';
        } elseif (is_object($parent) && method_exists($parent, 'getEmailClientFromType')) {
            $type = $parent->getEmailClientFromType();
        }

        $this->setFromType($type);

        parent::__construct($parent, $subject, $to, $from, $msg, $reply_to, $addr_cc, $addr_bcc, $deliveryreceipt, $errors_to);
    }

    public function setFromType($type)
    {
        $this->type = $type;

        if ($type == 'ldlc') {
            $this->title = 'LDLC';
            $this->subtitle = 'Apple';
            if ($this->from_name) {
                $this->from_name = 'LDLC Apple';
            }
        } else {
            $this->title = 'BIMP';
            $this->subtitle = 'Groupe LDLC';
            if ($this->from_name) {
                $this->from_name = 'BIMP';
            }
        }
    }

    function getHeader()
    {
        global $mysoc;

        if (!$this->title) {
            if (isset($mysoc->name) && $mysoc->name) {
                $this->title = $mysoc->name;
            } elseif (isset($mysoc->nom) && $mysoc->nom) {
                $this->title = $mysoc->nom;
            }
        }
        $html = '';

        $html .= '<div style="position: relative; z-index: 1000;background-color: #' . $this->primary . '; padding: 15px 20px">';
        $html .= '<table style="width: 100%">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td style="width: 50%; text-align: left">';

        if ($this->title) {
            $html .= '<span style="font-size: 28px; font-weight: bold; color: #FFFFFF">';
            $html .= $this->title;
            $html .= '</span>';
        }

        if ($this->subtitle) {
            $html .= '  <span style="font-size: 16px; font-style: italic; color: #FFCF9A">';
            $html .= $this->subtitle;
            $html .= '</span>';
        }
        $html .= '</td>';
        $html .= '<td style="width: 50%; text-align: right">';
        if (isset($this->url) && $this->url) {
            $html .= '<a href="' . $this->url . '" style="font-size: 16px; color: #FFFFFF; font-weight: bold">' . $this->url . '</a>';
        } elseif (isset($mysoc->url) && $mysoc->url) {
            $html .= '<a href="' . $mysoc->url . '" style="font-size: 16px; color: #FFFFFF; font-weight: bold">' . $mysoc->url . '</a>';
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div style="position: relative; z-index: 1; background-color: #F2F2F2; text-align: center;">';
        $html .= '<div style="position: relative; z-index: 2; background-color: #FFFFFF; display: block; width: 800px; margin: auto; text-align: left; padding: 60px 40px; box-shadow: 0 0 100px 0 #CCCCCC">';

        $html .= '<div style="font-family: \'Roboto\',\'Helvetica Neue\',Arial,sans-serif; font-size; 14px; color: #737373">';

        return $html;
    }

    function getFooter()
    {
        $html = '';
        if ($this->type === 'ldlc') {
            $html .= '<div style="margin-top: 50px">';
            $html .= '<a href="https://www.ldlc.com/aide/50-la-garantie-ldlc/">';
            $html .= '<img width="150px" height="auto" src="https://media.ldlc.com/cms/LDLC_com/bloc_droit_home/garantie.gif"/>';
            $html .= '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div style="position: relative; z-index: 10; background-color: #FFFFFF; border-top: 2px solid #' . $this->primary . '; text-align: center; padding: 20px; color: #737373">';
        global $mysoc;

        $address = (isset($mysoc->nom) ? $mysoc->nom : '');

        if (isset($mysoc->address) && $mysoc->address) {
            $address .= ($address ? ' - ' : '') . $mysoc->address;

            if (isset($mysoc->zip)) {
                $address .= ' ' . $mysoc->zip;
            }

            if (isset($mysoc->town) && $mysoc->town) {
                $address .= ' ' . $mysoc->town;
            }

            if (isset($mysoc->country) && $mysoc->country) {
                $address .= ' ' . $mysoc->country;
            }
        }

        if ($address) {
            $html .= '<div style="font-size: 14px;">';
            $html .= $address;
            $html .= '</div>';
        }

        $url = ((isset($this->url) && $this->url) ? $this->url : (isset($mysoc->url) ? $mysoc->url : ''));

        if ($url) {
            $html .= '<div style="font-size: 12px;">';
            $html .= '<a href="' . $url . '">' . $url . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
