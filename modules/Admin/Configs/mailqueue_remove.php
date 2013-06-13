<?php

$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'removemultiple'
  ),
  
  'fs0' => Array(
    'type'   => 'fieldset',
    'legend' => 'Törlés',
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

  'status' => Array(
    'displayname' => 'Mely állapotú üzenetek kerüljenek törlésre?',
    'type'        => 'select',
    'values'      => $l->getLov('mailqueueerrors_plain'),
  ),

  'limit' => Array(
    'displayname' => 'Maximum hány üzenet törlődjön?',
    'type'        => 'inputText',
    'value' => 99999,
    'validation' => Array(
      Array( 'type' => 'number' )
    ),
  ),

);
