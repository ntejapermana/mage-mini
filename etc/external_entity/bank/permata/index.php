<?php
/**
 * This script acts as a bridge between Permata Bank and Kaspay
 * 
 * @todo  many hardcoded strings
 * @todo  magic numbers
 */
$config = require(__DIR__ . '/../config.php');

define('STATUS_UNPAID', '99');
define('STATUS_SUCCESS', '00');
define('KASPAY_BASEURL', $config['kaspay_base_url']);
define('DEFAULT_AMOUNT', 500000); // user can only topup 500k?
define('CCY', '360'); // IDR
define('ERROR_CODE_NONE', '00');
define('ERROR_CODE_GENERAL', '14');
define('ERROR_CODE_DUPLICATE', '01');
define('ERROR_CODE_REVERSED', '02');

ini_set("soap.wsdl_cache_enabled", 0); 
require_once('../common/lib/nusoap.php');
$db = mysql_connect($config['db']['hostname'], $config['db']['username'], $config['db']['password']);
mysql_select_db($config['db']['database'], $db);
$server = new soap_server();
$server->configureWSDL('Permata', 'urn:permata');	

/*if(isset($_GET['i']))
{
	for($i=1;$i<=50;$i++)
	{
		mysql_query("insert into kaspay_topup_permata values('','".(8910000090869650+$i)."','','','','99',".mktime().",'".($i*5000)."','Testing Aja ".$i."')");
		mysql_query("update kaspay_users set vanumber_permata = '".(8910000090869650+$i)."' where id = '".($i+30)."' ");
	}
}*/
##### Inquiry Service
$server->wsdl->addComplexType(
	'InquiryInput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'VANumber'  => array('name' => 'VANumber', 	'type' => 'xsd:string'),
		'TrnDate'	=> array('name' => 'TrnDate', 	'type' => 'xsd:string'),
		'TraceNO' 	=> array('name' => 'TraceNO', 	'type' => 'xsd:string'),
		)
	);


$server->wsdl->addComplexType(
	'InquiryOutput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'Nama'    			=> array('name' => 'Nama', 				'type' => 'xsd:string'),
		'NominalTagihan'	=> array('name' => 'NominalTagihan', 	'type' => 'xsd:string'),
		'BillDescription'  	=> array('name' => 'BillDescription', 	'type' => 'xsd:string'),
		'CCY' 				=> array('name' => 'CCY', 				'type' => 'xsd:string'),
		'ErrorCode'			=> array('name' => 'ErrorCode', 		'type' => 'xsd:string'),
		'ErrorMessage'		=> array('name' => 'ErrorMessage', 		'type' => 'xsd:string'),

		)
	);

##### payment Service
$server->wsdl->addComplexType(
	'PaymentInput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'VANumber'  => array('name' => 'merchantCode', 		'type' => 'xsd:string'),
		'Amount'	=> array('name' => 'userId', 			'type' => 'xsd:string'),
		'TrnDate'   => array('name' => 'transactionNo', 	'type' => 'xsd:string'),
		'TraceNO' 	=> array('name' => 'transactionDate', 	'type' => 'xsd:string'),
		)
	);

$server->wsdl->addComplexType(
	'PaymentOutput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'ErrorCode'    => array('name' => 'ErrorCode', 		'type' => 'xsd:string'),
		'ErrorMessage' => array('name' => 'ErrorMessage', 	'type' => 'xsd:string'),
		)
	);

##### Reversal Service
$server->wsdl->addComplexType(
	'ReversalInput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'VANumber'  => array('name' => 'VANumber', 	'type' => 'xsd:string'),
		'Amount'	=> array('name' => 'Amount', 	'type' => 'xsd:string'),
		'TrnDate'   => array('name' => 'TrnDate', 	'type' => 'xsd:string'),
		'TraceNO' 	=> array('name' => 'TraceNO', 	'type' => 'xsd:string'),
		)
	);

$server->wsdl->addComplexType(
	'ReversalOutput',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'ErrorCode'    => array('name' => 'ErrorCode', 		'type' => 'xsd:string'),
		'ErrorMessage' => array('name' => 'ErrorMessage', 	'type' => 'xsd:string'),
		)
	);


$server->register('Inquiry',                   					// method name
        array('Inquiry'  		=> 'tns:InquiryInput'),         // input parameters
        array('Response' 		=> 'tns:InquiryOutput'),        // output parameters
        'urn:permata',                                       	// namespace
        'urn:permata#Inquiry',              					// soapaction
        'rpc',                                                  // style
        'encoded',                                              // use
        'Inquiry Service'                                       // documentation
        );

$server->register('Payment',                   					// method name
        array('Payment' 		 => 'tns:PaymentInput'),        // input parameters
        array('Response'  		 => 'tns:PaymentOutput'),       // output parameters
        'urn:permata',                                       	// namespace
        'urn:permata#Payment',              					// soapaction
        'rpc',                                                  // style
        'encoded',                                              // use
        'Payment Service'	                                    // documentation
        );

$server->register('Reversal',                   				// method name
        array('Reversal' 		 => 'tns:ReversalInput'),       // input parameters
        array('Response' 		 => 'tns:ReversalOutput'),      // output parameters
        'urn:permata',                                       	// namespace
        'urn:permata#Reversal',              					// soapaction
        'rpc',                                                  // style
        'encoded',                                              // use
        'Reversal Service'	                                    // documentation
        );


function Inquiry($param)
{

	$query = mysql_query("
		SELECT * FROM kaspay_users
		WHERE vanumber_permata = '".mysql_real_escape_string($param['VANumber'])."' 
		");

	$default_amount = DEFAULT_AMOUNT;
	
	if (mysql_num_rows($query) > 0)
	{
		// don't use loop if only one row
		while ($row = mysql_fetch_array($query, MYSQL_ASSOC))
		{
			$output = array(
				'Nama'                  => $row['namalengkap'],
				'NominalTagihan'        => $default_amount.'00',
				'BillDescription'       => 'TopUp KasPay',
				'CCY'                   => '360',
				'ErrorCode'             => '00',
				'ErrorMessage'          => 'Success',
			);

			$check_trx = mysql_query("
				SELECT * FROM kaspay_topup_permata 
				WHERE VANumber = '".mysql_real_escape_string($param['VANumber'])."' AND 
				TraceNo = '".mysql_real_escape_string($param['TraceNO'])."'
			");

			if (mysql_num_rows($check_trx) == 0)
			{
				$StatusTrx = STATUS_UNPAID; //belum terbayar
				// @todo trxid collision detection
				$trxid     = 'PB'.strtoupper(substr(uniqid(),0,11)); //for trxid kaspay
				mysql_query("INSERT INTO kaspay_topup_permata  
					(VANumber, TrnDate, TraceNo, Status, Dateline, Amount, Nama, InquiryData, trxid)
					VALUES
					(
						'".mysql_real_escape_string($param['VANumber'])."',
						'".mysql_real_escape_string($param['TrnDate'])."',
						'".mysql_real_escape_string($param['TraceNO'])."',
						'".$StatusTrx."',
						'".time()."',
						'".$default_amount."',
						'".mysql_real_escape_string($row['namalengkap'])."',
						'".mysql_real_escape_string(serialize($param))."',
						'".$trxid."'
					)");
			}
		}

	}
	else
	{

		$output = array(
			'Nama'                  => '',
			'NominalTagihan'        => '00',
			'BillDescription'       => 'TopUp KasPay',
			'CCY'                   => '360',
			'ErrorCode'             => '14',
			'ErrorMessage'          => 'KasPay Top Up is not found',
		);


	}

	return $output;

}

function build_Payment_output($code, $message)
{
	return array(
		'ErrorCode'    => $code,
		'ErrorMessage' => $message,
	);
}

function Payment($param)
{

	$member = mysql_query("
		SELECT * FROM kaspay_users
		WHERE vanumber_permata = '".mysql_real_escape_string($param['VANumber'])."'
		");

	if(mysql_num_rows($member) == 0)
	{
		return build_Payment_output(ERROR_CODE_GENERAL, 'KasPay Top Up is not found');
	}

	$user = array();	
	while ($val = mysql_fetch_array($member, MYSQL_ASSOC))
	{
		$user = $val;
	}

	$query = mysql_query("
		SELECT * FROM kaspay_topup_permata 
		WHERE VANumber = '".mysql_real_escape_string($param['VANumber'])."' AND 
		TraceNo = '".mysql_real_escape_string($param['TraceNO'])."'
	");

	if(mysql_num_rows($query) > 0)
	{

		while ($row = mysql_fetch_array($query, MYSQL_ASSOC))
		{

			switch($row['Status'])
			{

				case STATUS_UNPAID:

					$StatusTrx = STATUS_SUCCESS;//Transaction Success

					mysql_query("UPDATE kaspay_topup_permata SET 
						Status 		= '".$StatusTrx."',
						Amount 		= '".mysql_real_escape_string($param['Amount'])."',
						PaymentData = '".mysql_real_escape_string(serialize($param))."',
						TrnDate 	= '".mysql_real_escape_string($param['TrnDate'])."'

						WHERE TraceNo 	= '".mysql_real_escape_string($param['TraceNO'])."' 
						");

					$result = file_get_contents(KASPAY_BASEURL."/permata/topup/?trxid=".$row['trxid']."&account=".$user['uaccount']."&amount=".$param['Amount']);
					
					if ($result == 'success')
					{
						return build_Payment_output(ERROR_CODE_NONE, $result);
					}
					elseif ($result == 'max')
					{	
						return build_Payment_output(ERROR_CODE_GENERAL, 'Melebihi batas maksimum saldo');
					}
					else
					{
						return build_Payment_output(ERROR_CODE_GENERAL, 'Failed');
					}

					break;

				case STATUS_SUCCESS:
					return build_Payment_output(ERROR_CODE_DUPLICATE, 'KasPay Top Up has been paid');

					break;

			}

		}

	}
	else
	{

		$StatusTrx = STATUS_SUCCESS;//Transaction Success
		$trxid     = 'PB'.strtoupper(substr(uniqid(),0,11)); //for trxid kaspay

		mysql_query("INSERT INTO kaspay_topup_permata  
			(VANumber, TrnDate, TraceNo, Status, Dateline, Amount, Nama, PaymentData, trxid)
			VALUES
			(
				'".mysql_real_escape_string($param['VANumber'])."',
				'".mysql_real_escape_string($param['TrnDate'])."',
				'".mysql_real_escape_string($param['TraceNO'])."',
				'".$StatusTrx."',
				'".mktime()."',
				'".mysql_real_escape_string($param['Amount'])."',
				'".mysql_real_escape_string($user['namalengkap'])."',
				'".mysql_real_escape_string(serialize($param))."',
				'".$trxid."'
			)");

		$result = file_get_contents(KASPAY_BASEURL."/permata/topup/?trxid=".$trxid."&account=".$user['uaccount']."&amount=".$param['Amount']);

		if ($result == 'success')
		{
			return build_Payment_output(ERROR_CODE_NONE, $result);
		}
		elseif ($result == 'max')
		{	
			return build_Payment_output(ERROR_CODE_GENERAL, 'Melebihi batas maksimum saldo');
		}
		else
		{
			return build_Payment_output(ERROR_CODE_GENERAL, 'Failed');
		}
	}
	
}


function build_Reversal_output($code, $message)
{
	return array(
		'ErrorCode'    => $code,
		'ErrorMessage' => $message,
	);
}

function Reversal($param)
{

	$member = mysql_query("
		SELECT * FROM kaspay_users
		WHERE vanumber_permata = '".mysql_real_escape_string($param['VANumber'])."'
	");
	
	if (mysql_num_rows($member) == 0)
	{
		return build_Reversal_output(ERROR_CODE_GENERAL, 'KasPay Top Up is not found');
	}

	$user = array();	
	while ($val = mysql_fetch_array($member, MYSQL_ASSOC))
	{
		$user = $val;
	}

	$query = mysql_query("
		SELECT * FROM kaspay_topup_permata 
		WHERE VANumber = '".mysql_real_escape_string($param['VANumber'])."' AND 
		TraceNo = '".mysql_real_escape_string($param['TraceNO'])."'
	");
	
	if (mysql_num_rows($query) > 0)
	{
		// don't use loop if only intended for one row
		while ($row = mysql_fetch_array($query, MYSQL_ASSOC))
		{
			if ($row['Reversal'] == ERROR_CODE_NONE)
			{
				$output = build_Reversal_output(ERROR_CODE_REVERSED, 'KasPay Top Up has been reversed');
			}
			else
			{
				mysql_query("
					UPDATE kaspay_topup_permata SET 
					Reversal = '".ERROR_CODE_NONE."',
					ReversalDate = ".mktime().",
					ReversalData = '".mysql_real_escape_string(serialize($param))."'
					WHERE TraceNo = '".mysql_real_escape_string($param['TraceNO'])."' 
				");
				$result = file_get_contents(KASPAY_BASEURL."/permata/reversal/?trxid=".$row['trxid']."&account=".$user['uaccount']."&amount=".$param['Amount']);

				if ($result == 'success')
				{
					$output = build_Reversal_output(ERROR_CODE_NONE, 'Success');
				}
				else
				{

					mysql_query("
						UPDATE kaspay_topup_permata SET 
						Reversal = '".ERROR_CODE_GENERAL."',
						ReversalData = concat(ReversalData, '$result')
						WHERE TraceNo = '".mysql_real_escape_string($param['TraceNO'])."' 
					");

					return build_Reversal_output(ERROR_CODE_GENERAL, 'Failed');
				}

			}
		}
	}
	else
	{
		$output = build_Reversal_output(ERROR_CODE_GENERAL, 'KasPay Top Up is not found');
	}
	
	return $output;
}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
