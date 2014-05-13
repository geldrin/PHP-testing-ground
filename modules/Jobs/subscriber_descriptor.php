<?php

// Contract data: should be retrived from DB later, when contract description database is implemented.

// !!!
if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) {

	$org_contracts = array();

} else {

	$org_contracts = array(
		0	=> array(
				'orgid' 					=> 200,
				'name'						=> "Conforg",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		1	=> array(
				'orgid' 					=> 222,
				'name'						=> "Infoszféra",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		2	=> array(
				'orgid' 					=> 282,
				'name'						=> "IIR",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			)
	);

}

?>