<?php

class BRH_Period extends BimpObject
{

    public function getFraisFilters()
    {
        if ($this->isLoaded()) {
            return array(
                'date'    => array(
                    'min' => $this->getData('date_from'),
                    'max' => $this->getData('date_to')
                ),
                'id_user' => (int) $this->getData('id_user')
            );
        }
        return array(
            'id' => 0
        );
    }

    public function getFraisNumber($status_filter = null)
    {
        $filters = array();

        if (!is_null($status_filter)) {
            $filters['status'] = $status_filter;
        }
        $frais = $this->getChildrenObjects('frais', $filters);
        return count($frais);
    }

    public function getTotalAmount()
    {
        $total = 0;
        $frais_list = $this->getChildrenObjects('frais');
        foreach ($frais_list as $frais) {
            if ((int) $frais->getData('status') !== BRH_Frais::NOTE_FRAIS_REFUSEE) {
                $total += (float) $frais->getTotalAmount();
            }
        }
        return $total;
    }

    // Affichages: 

    public function displayTotalAmount()
    {
        return BimpTools::displayMoneyValue((float) $this->getTotalAmount(), 'EUR');
    }

    public function displayFraisNumber($status_filter = null)
    {
        $number = (int) $this->getFraisNumber($status_filter);
        $class = '';
        if (!is_null($status_filter) && preg_match('/^\d+$/', (string) $status_filter)) {
            if ($number > 0) {
                if (in_array((int) $status_filter, array(1, 2, 4))) {
                    $class = 'danger';
                } else {
                    $class = 'success';
                }
            } else {
                if (in_array((int) $status_filter, array(1, 2, 4))) {
                    $class = 'success';
                } else {
                    $class = 'danger';
                }
            }
        }

        return '<span class="badge' . ($class ? ' badge-' . $class : '') . '">&nbsp;' . $number . '&nbsp;</span>';
    }

    // Traitements:

    public function checkUserPeriods($id_user, $date = null)
    {
        if (!(int) $id_user) {
            return array('ID de l\'utilisateur absent');
        }

        if (is_null($date)) {
            $date = date('Y-m-d');
        }

        $DT = new DateTime($date);

        $day = $DT->format('d');
        $month = $DT->format('m');
        $year = $DT->format('Y');

        $from = $year . '-' . $month . '-';
        $to = $year . '-' . $month . '-';

        if ($day < 15) {
            $from .= '01';
            $to .= '14';
        } else {
            $from .= '15';
            $to .= cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
        }

        $where = '`id_user` = ' . $id_user . ' AND `date_from` = \'' . $from . '\'AND `date_to` = \'' . $to . '\'';
        $id_period = $this->db->getValue($this->getTable(), 'id', $where);
        $errors = array();
        if (is_null($id_period) || !(int) $id_period) {
            $period = BimpObject::getInstance($this->module, $this->object_name);
            $errors = $period->validateArray(array(
                'id_user'   => (int) $id_user,
                'date_from' => $from,
                'date_to'   => $to
            ));
            if (!count($errors)) {
                $errors = $period->create();
            }
        }
        return $errors;
    }
}
