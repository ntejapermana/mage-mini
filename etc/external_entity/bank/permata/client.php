<?php
ini_set("soap.wsdl_cache_enabled", 0); 
//phpinfo();
require_once('../common/lib/nusoap.php');
$client = new nusoap_client('http://112.78.131.3/permata/index.php?wsdl',true);
//echo "<pre>";print_r($client);echo "</pre>";

$err = $client->getError();
$id=1;
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}

	$param = array(
        'VANumber'  => '1',
		'TrnDate'	=> '3',
        'TraceNO' 	=> '4'
    );
	$result = $client->call('Inquiry',array($param));
	
echo '<pre>' ; print_r($result); echo '</pre>';

if (!empty($result)) {
	//echo "merchantCode: ".$result['merchantCode']."<br>";
	//echo "userID: ".$result['userId']."<br>";
	/*echo "<table border=1>";
	echo "<tr bgcolor='#cccccc'>";
	echo "<th>transactionNo</th>";
	echo "<th>transactionDate</th>";
	echo "<th>amount</th>";
	echo "<th>description</th>";
	echo "</tr>";
	/*foreach ($result['OutputDetailB2C'] as $item) {
		echo "<tr>";
		echo "<td>".$item['transactionNo']."</td>";
		echo "<td>".$item['transactionDate']."</td>";
		echo "<td>".$item['amount']."</td>";
		echo "<td>".$item['description']."</td>";
		echo "</tr>";
	}
	echo "</table>";*/
	
}
// Display the request and response
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2>';
echo '<pre>' . print_r(htmlspecialchars($client->response, ENT_QUOTES)) . '</pre>';
// Display the debug messages
echo '<h2>Debug</h2>';
echo '<pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';

?>

