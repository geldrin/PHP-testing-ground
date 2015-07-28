<?php
namespace Visitor\Analytics;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'accreditedrecordings' => 'clientadmin',
    'statistics' => 'clientadmin',
  );

  public $forms = array(
    'accreditedrecordings' => 'Visitor\\Analytics\\Form\\Accreditedrecordings',
    'statistics' => 'Visitor\\Analytics\\Form\\Statistics',
  );

  public $paging = array(
  );

}
