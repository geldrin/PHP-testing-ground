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
			),
		3	=> array(
				'orgid' 					=> 304,
				'name'						=> "Kompkonzult Kft.",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		4	=> array(
				'orgid' 					=> 305,
				'name'						=> "OMI Kft.",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		5	=> array(
				'orgid' 					=> 307,
				'name'						=> "CEDH Hungária Kft.",
				'price_peruser'				=> 0,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		6	=> array(
				'orgid' 					=> 308,
				'name'						=> "Menedzsment Fórum",
				'price_peruser'				=> 0,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		7	=> array(
				'orgid' 					=> 309,
				'name'						=> "Hessyn",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		8	=> array(
				'orgid' 					=> 311,
				'name'						=> "Vezinfó",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
		9	=> array(
				'orgid' 					=> 312,
				'name'						=> "Penta",
				'price_peruser'				=> 2000,
				'currency'					=> "HUF",
				'listfromdate'				=> null,
				'generateduservaliditydays'	=> 30,
				'disableuseraftervalidity'	=> true,
				'promouservaliditydays'		=> 7
			),
	);

}

?>