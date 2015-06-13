<?php
namespace Visitor\Analytics;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'accreditedrecordings' => 'clientadmin',
  );

  public $forms = array(
    'accreditedrecordings' => 'Visitor\\Analytics\\Form\\Accreditedrecordings',
  );

  public $paging = array(
  );

}
