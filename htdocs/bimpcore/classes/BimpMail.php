<?php

class BimpMail extends BimpMailCore
{

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

        $html .= '<div style="position: relative; z-index: 1000;background-color: #' . $this->primary . '; padding: 15px 10px;">';
        $html .= '<table style="width: 100%">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td style="width: 50%; text-align: left">';

        if ($this->title) {
            $html .= '<span style="font-size: 28px; font-weight: bold; color: #FFFFFF">';
            $this->title;
            $html .= '</span>';
        }

        if ($this->subtitle) {
            $html .= '  <span style="font-size: 16px; font-style: italic; color: #FFCF9A">';
            $html .= $this->subtitle;
            $html .= '</span>';
        }
        $html .= '</td>';
        $html .= '<td style="width: 50%; text-align: right">';
        if (isset($mysoc->url) && $mysoc->url) {
            $html .= '<a href="' . $mysoc->url . '" style="font-size: 16px; color: #FFFFFF; font-weight: bold">www.bimp.fr</a>';
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div style="position: relative; z-index: 1; background-color: #F2F2F2; text-align: center;">';
        $html .= '<div style="position: relative; z-index: 2; background-color: #FFFFFF; display: block; width: 800px; margin: auto; text-align: left; padding: 80px 40px; box-shadow: 0 0 100px 0 #CCCCCC">';

        $html .= '<div style="font-family: \'Roboto\',\'Helvetica Neue\',Arial,sans-serif; font-size; 14px; color: #737373">';

        return $html;
    }

    function getFooter()
    {
        $html = '</div>';
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
            $html .= '<div style="font-size: 12px;">';
            $html .= $address;
            $html .= '</div>';
        }

        $url = (isset($mysoc->url) ? $mysoc->url : '');

        if ($url) {
            $html .= '<div style="font-size: 11px;">';
            $html .= '<a href="' . $url . '">' . $url . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
