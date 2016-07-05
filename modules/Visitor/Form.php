<?php
namespace Visitor;

class Form extends \Springboard\Controller\Form {
  protected $purifier;
  public $xsrfprotect = false;
  public $checkboxitemlayout = '
    <div class="checkboxwrap indentlevel-%level%">
      <div class="checkboxindent indentlevel-%level%">%indent%</div>
      <div class="checkboxitem">%checkbox%</div>
      <div class="checkboxlabel" title="%valuehtmlescape%">%label%</div>
    </div>
  ';
  public $radioitemlayout = '
    <div class="radiowrap">
      <div class="radioitem">%radio%</div>
      <div class="radiolabel">%label%</div>
    </div>
  ';
  public $singlecolumnlayout = '
    <tr>
      <td class="labelcolumn singlecolumn"><label for="%id%">%displayname%</label></td>
    </tr>
    <tr %errorstyle%>
      <td class="elementcolumn singlecolumn">%prefix%%element%%postfix%%errordiv%</td>
    </tr>
  ';

  public function redirectToMainDomain() {}

  public function postGetForm() {

    $this->form->errorstyle = ' class="formerror"';
    $this->form->jsalert = false;
    $this->form->jspath =
      $this->controller->toSmarty['STATIC_URI'] . 'js/clonefish.js'
    ;

    $this->form->messagecontainerlayout =
      '<div class="formerrors"><ul>%s</ul></div>'
    ;

    $this->form->messageprefix = '';
    $this->form->layouts['tabular']['container']  =
      "<table cellpadding=\"0\" cellspacing=\"0\" class=\"formtable\">\n%s\n</table>\n"
    ;

    $this->form->layouts['tabular']['element'] = '
      <tr%errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ';

    $this->form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow"><td colspan="2">%s</td></tr>'
    ;

    $this->form->layouts['tabular']['button'] =
      '<input type="submit" value="%s" class="submitbutton" />'
    ;

    $this->form->layouts['tabular']['errordiv'] = '
      <div id="%divid%" class="formerrordiv"></div>
      <div class="clear"></div>
    ';

  }

  public function handleAccesstypeForModel( $model, &$values, $shouldclear = true ) {

    $model->clearAccess();
    switch( $values['accesstype'] ) {

      case 'public':
      case 'registrations':
        // kiuritettuk mar elobb az `access`-t az adott recordinghoz
        // itt nincs tobb dolgunk
        break;

      case 'departments':

        if ( isset( $_REQUEST['departments'] ) and !empty( $values['departments'] ) )
          $model->restrictDepartments( $values['departments'] );

        break;

      case 'groups':

        if ( isset( $_REQUEST['groups'] ) and !empty( $values['groups'] ) )
          $model->restrictGroups( $values['groups'] );

        break;

      case 'departmentsorgroups':

        if ( isset( $_REQUEST['departments'] ) and !empty( $values['departments'] ) )
          $model->restrictDepartments( $values['departments'] );

        if ( isset( $_REQUEST['groups'] ) and !empty( $values['groups'] ) )
          $model->restrictGroups( $values['groups'] );

        break;

      default:
        throw new \Exception('Unhandled accesstype: ' . $values['accesstype'] );
        break;

    }

  }

  public function sanitizeHTML( $html ) {

    if ( !$this->purifier ) {
      require_once(
        $this->bootstrap->config['libpath'] .
        'HTMLPurifier/HTMLPurifier.includes.php'
      );

      $config = \HTMLPurifier_Config::createDefault();
      // engedjuk amit a tinymce-be hagytunk editalni
      $config->set('HTML.Allowed', 'p[style],b,a[href|target|title],i,ul,li,span[style]');
      $config->set('Attr.AllowedFrameTargets', array('_blank' => true, '_self' => true, ) );
      $config->set('Cache.SerializerPath', $this->bootstrap->config['cachepath'] . 'application/' );
      $this->purifier = new \HTMLPurifier( $config );

    }

    return $this->purifier->purify( $html );

  }

  public function handleTemplate( $userModel, &$values ) {
    $l              = $this->bootstrap->getLocalization();
    $subject        = trim( $values['templatesubject'] );
    $defaultsubject = trim( $l('users', 'templatesubject_default') );
    $title          = trim( $values['templatetitle'] );
    $defaulttitle   = trim( $l('users', 'templatetitle_default') );
    $prefix         = $this->sanitizeHTML( $values['templateprefix'] );
    $defaultprefix  = $l('users', 'templateprefix_default');
    $postfix        = $this->sanitizeHTML( $values['templatepostfix'] );
    $defaultpostfix = $l('users', 'templatepostfix_default');

    if ( $subject == $defaultsubject )
      $subject = '';

    if ( $title == $defaulttitle )
      $title = '';

    if ( $prefix == $defaultprefix )
      $prefix = '';

    if ( $postfix == $defaultpostfix )
      $postfix = '';

    $template = array(
      'id'             => $values['templateid'],
      'subject'        => $subject,
      'title'          => $title,
      'prefix'         => $prefix,
      'postfix'        => $postfix,
      'timestamp'      => date('Y-m-d H:i:s'),
      'organizationid' => $this->controller->organization['id'],
    );

    return $userModel->maybeInsertTemplate( $template );
  }

}
