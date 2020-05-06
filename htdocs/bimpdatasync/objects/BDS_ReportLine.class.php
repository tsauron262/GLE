<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpReportLine.class.php';

class BDS_ReportLine extends BimpReportLine
{

    public function displayObjLink()
    {
        $module = $this->getData('obj_module');
        $name = $this->getData('obj_name');
        $id_object = $this->getData('id_obj');

        if ($module && $name) {
            $instance = BimpCache::getBimpObjectInstance($module, $name, $id_object);
            if (BimpObject::objectLoaded($instance)) {
                return $instance->getLink();
            } elseif (is_a($instance, 'BimpObject')) {
                if ($id_object) {
                    return '<span class="danger">' . BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe plus</span>';
                } else {
                    return BimpTools::ucfirst($instance->getLabel());
                }
            } else {
                return 'Objet "' . $name . '"' . ($id_object ? ' #' . $id_object : '');
            }
        }

        return '';
    }

    public function renderBeforeListContent($bc_list)
    {
        $html = '';

        if (is_a($bc_list, 'BC_ListTable')) {
            $filters = $bc_list->initial_filters;
            $joins = $bc_list->initial_joins;
            
//            $html .= 'FILTERS: <pre>';
//            $html .= print_r($filters, 1);
//            $html .= '</pre>';
            
            if (!empty($filters)) {
                $filters['type'] = 'success';
                $nSuccess = (int) $this->getListCount($filters, $joins);
                
                $filters['type'] = 'info';
                $nInfos = (int) $this->getListCount($filters, $joins);
                
                $filters['type'] = 'warning';
                $nWarnings = (int) $this->getListCount($filters, $joins);
                
                $filters['type'] = 'danger';
                $nErrors = (int) $this->getListCount($filters, $joins);

                $html .= BimpRender::renderInfoCard('Infos', $nInfos, array(
                            'icon'   => 'fas_info-circle',
                            'class'  => 'info',
                            'format' => 'small'
                ));
                $html .= BimpRender::renderInfoCard('Succès', $nSuccess, array(
                            'icon'   => 'fas_check',
                            'class'  => 'success',
                            'format' => 'small'
                ));
                $html .= BimpRender::renderInfoCard('Alertes', $nWarnings, array(
                            'icon'   => 'fas_exclamation-triangle',
                            'class'  => 'warning',
                            'format' => 'small'
                ));
                $html .= BimpRender::renderInfoCard('Erreurs', $nErrors, array(
                            'icon'   => 'fas_exclamation-circle',
                            'class'  => 'danger',
                            'format' => 'small'
                ));
            }
        }

        return $html;
    }
}
