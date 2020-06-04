<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";
class BMP_MontantDetailValue extends Abstract_margeprod
{

  public function canDelete()
  {
      global $user;

      if ($user->admin) {
          return 1;
      }

      return 0;
  }



  public function canView()
      {
          global $user;

          if ($user->admin) {
              return 1;
          }

          return 1;
      }

    public function getTypes_montantsArray()
    {
        $cache_key = 'bmp_types_montants_with_details';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
            $list = $instance->getList(array(
                'has_details' => 1
            ));

            foreach ($list as $item) {
                self::$cache[$cache_key][(int) $item['id']] = $item['name'];
            }
        }

        return self::$cache[$cache_key];
    }

    public function showQtyInput()
    {
        if ((int) $this->getData('use_groupe_number')) {
            return 0;
        }

        return 1;
    }
}
