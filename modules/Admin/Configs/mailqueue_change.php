<?php

$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'changemultiple'
  ),
  
  'fs0' => Array(
    'type'   => 'fieldset',
    'legend' => 'Leállítás/újraküldés',
    'submit' => true,
  ),
  
  'from' => Array(
    'displayname' => 'Első törlendő üzenet feladásának időpontja',
    'type'        => 'inputText',
    'help'        => 'ÉÉÉÉ-HH-NN ÓÓ:PP:MP formátumban adja meg az időpontot!',
    'html'        => 'size="19" maxlength="19"',
    'value'       => date("Y-m-d 0:00:00"),
    'validation'  => Array(
      Array( 
        'type' => 'date', 
        'format' => "YYYY-M-D h:m:s",
      )
    )
  ),

  'until' => Array(
    'displayname' => 'Utolsó törlendő üzenet feladásának időpontja',
    'type'        => 'inputText',
    'help'        => 'ÉÉÉÉ-HH-NN ÓÓ:PP:MP formátumban adja meg az időpontot!',
    'html'        => 'size="19" maxlength="19"',
    'value'       => date("Y-m-d H:i:s"),
    'validation'  => Array(
      Array( 
        'type' => 'date', 
        'format' => "YYYY-M-D h:m:s",
      )
    )
  ),

  'status_from' => Array(
    'displayname' => 'Mely állapotú üzenetek módosuljanak?',
    'type'        => 'select',
    'values'      => $l->getLov('mailqueueerrors_plain'),
  ),

  'status_to' => Array(
    'displayname' => 'Mi legyen az új állapot?',
    'type'        => 'select',
    'value'       => 'cancelled',
    'values'      => $l->getLov('mailqueueerrors_plain'),
  ),

  'limit' => Array(
    'displayname' => 'Maximum hány üzenet módosuljon?',
    'type'        => 'inputText',
    'value' => 99999,
    'validation' => Array(
      Array( 'type' => 'number' )
    ),
  ),

);
