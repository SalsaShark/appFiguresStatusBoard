<?php
/* Retrieves sales and iAd data from appFigures and writes JSON files readble as Status Board graphs */

////CONFIGURATION START

$iadDays = 21; //Number of iAd days to report
$salesDays = 14; //Number of sales days to report
$user = ""; //appFigures e-mail address
$pass = ""; //appFigures password
$enddate = date("Y-m-d"); //Ending date, defaults to today
$productid = 000000; //appFigures product ID. Can be found at https://api.appfigures.com/v1.1/products/mine
$appName = NULL; //App name for chart names (optional)

/* File locations for iAd revenue and app sales. Make sure they're writable and accessible. */
$iadFile = ""; // iAd revenue file
$salesFile = ""; // Sales file

/*
   If $revenueColors is TRUE, revenue graph will have bars colored according to value.
   These are the colors to be used.
 */
$revenueColors=TRUE;
$level1Color="red";
$level2Color="orange";
$level3Color="yellow";
$level4Color="green";

////CONFIGURATION END


$iadrequest = "https://api.appfigures.com/v1.1/iads/dates/".date("Y-m-d", time() - 86400 * $iadDays)."/".$enddate;
$salesrequest = "https://api.appfigures.com/v1.1/sales/dates/".date("Y-m-d", time() - 86400 * $salesDays)."/".$enddate."/?data_source=daily&products=".$productid;

// Abbreviations to use for bar labels
$monthname['01']="Jan";
$monthname['02']="Feb";
$monthname['03']="Mar";
$monthname['04']="Apr";
$monthname['05']="May";
$monthname['06']="Jun";
$monthname['07']="Jul";
$monthname['08']="Aug";
$monthname['09']="Sep";
$monthname['10']="Oct";
$monthname['11']="Nov";
$monthname['12']="Dec";

$process = curl_init($iadrequest);
curl_setopt($process, CURLOPT_HEADER, false);
curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
curl_setopt($process, CURLOPT_USERPWD, $user . ":" . $pass);
$data = json_decode(curl_exec($process), true);
curl_close($process);

if ($revenueColors) {
	//Get the max value for sales
	$max = 0;
	foreach ($data AS $value) {
		if ($value['revenue'] > $max)
			$max = $value['revenue'];
	}
	//Set the break points for each color
	$level1 = $max * 0.25;
	$level2 = $max * 0.5;
	$level3 = $max * 0.75;
}

foreach ($data AS $date => $value) {
	$dateparts=explode("-", $date);
	$daystr=$monthname[$dateparts[1]]." ".(int)$dateparts[2];
	$revenue=$value['revenue'];
	
/* Additional data not being used
	$impressions=$value['impressions'];
	$ecpm=$value['ecpm'];
	$clicks=$value['clicks'];
*/
	
	$thisrevenuedata = array(
		"title" => $daystr,
		"datapoints" => array(
			array(
				"title" => "Revenue",
				"value" => $revenue
			)
		)
	);

	if ($revenueColors) {
		if ($revenue < $level1) {
			$thiscolor=$level1Color;
		} elseif ($revenue < $level2) {
			$thiscolor=$level2Color;
		} elseif ($revenue < $level3) {
			$thiscolor=$level3Color;
		} else {
			$thiscolor=$level4Color;
		}
		
		$thisrevenuedata['color'] = $thiscolor;
	}
		
	$revenuedata[] = $thisrevenuedata;
}

$revenuegraph = array(
	"graph" => array(
		"title" => ($appName?$appName." ":"")."iAd Revenue",
		"yAxis" => array (
			"units" => array (
				"prefix" => "$"
			)
		),
		"total" => true,
		"type" => "bar",
		"datasequences" => $revenuedata
	)
);


$process = curl_init($salesrequest);
curl_setopt($process, CURLOPT_HEADER, false);
curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
curl_setopt($process, CURLOPT_USERPWD, $user . ":" . $pass);
$data = json_decode(curl_exec($process), true);
curl_close($process);

foreach ($data AS $date => $value) {
	$dateparts=explode("-", $date);
	$daystr=$monthname[$dateparts[1]]." ".(int)$dateparts[2];
	$downloads=$value['downloads'];
	$updates=$value['updates'];
	$salesdata[] = array(
		"title" => $daystr,
		"datapoints" => array(
			array(
				"title" => "Downloads",
				"value" => $downloads
			),
			array(
				"title" => "Updates",
				"value" => $updates
			)
		)
	);
}

$salesgraph = array(
	"graph" => array(
		"title" => ($appName?$appName." ":"")."App Sales",
		"total" => false,
		"type" => "bar",
		"datasequences" => $salesdata
	)
);

$fh=fopen($iadFile,"w") or die("Can't open $iadFile");
fwrite($fh, json_encode($revenuegraph));
fclose($fh);

$fh=fopen($salesFile,"w") or die("Can't open $salesFile");
fwrite($fh, json_encode($salesgraph));
fclose($fh);

?>