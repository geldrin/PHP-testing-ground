<?php
namespace Visitor\Telemetry;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'exception' => 'public',
  );

  public $forms = array(
  );

  public $paging = array(
  );

  private function stackTraceValid( $trace ) {
    // TODO $trace[url] ellenorzes es general well-formedness
    return true;
  }

  public function exceptionAction() {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
      http_response_code(404);
      die('Not found');
    }

    $body = file_get_contents('php://input');
    if ( !$body or $body[0] !== '{' )
      die('Invalid');

    $trace = json_decode( $body, true );
    if ( !$trace or !$this->stackTraceValid( $trace ) )
      die('Invalid json');

    $telemetryModel = $this->bootstrap->getModel('telemetry');
    $telemetryModel->insertTrace( $trace );
    die('OK');
  }
}
