<?php
/**
 * This script acts as a bridge between BCA and Kaspay
 *
 * @todo  many hardcoded strings
 * @todo  magic numbers
 */
$config = require __DIR__.'/../config.php';

define('FEE', 1650);
define('STATUS_UNPAID', 99);
define('STATUS_EXPIRED', 98);
define('KASPAY_BASEURL', $config['kaspay_base_url']);

ini_set("soap.wsdl_cache_enabled", 0);
require_once '../common/lib/nusoap.php';
$server = new soap_server();
$server->configureWSDL('Klikbca', 'urn:klikbca', $config["klikbca_wsdl_endpoint"]);

//list transaction
$server->wsdl->addComplexType(
    'InputListTransaction',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'merchantCode'   => array('name' => 'merchantCode',   'type' => 'xsd:string'),
        'userId'         => array('name' => 'userId',         'type' => 'xsd:string'),
        'additionalData' => array('name' => 'additionalData', 'type' => 'xsd:string', 'nillable' => 'true'),
        )
    );

$server->wsdl->addComplexType(
    'Transaction',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'transactionNo'   => array('name' => 'transactionNo',   'type' => 'xsd:string'),
        'transactionDate' => array('name' => 'transactionDate', 'type' => 'xsd:string'),
        'amount'          => array('name' => 'amount',          'type' => 'xsd:string'),
        'description'     => array('name' => 'description',     'type' => 'xsd:string'),
        )
    );

$server->wsdl->addComplexType(
    'Transactions',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:Transaction[]', ),
        ),
    'tns:Transaction'
    );

$server->wsdl->addComplexType(
    'ListTransaction',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'merchantCode'    => array('name' => 'merchantCode',    'type' => 'xsd:string'),
        'userId'          => array('name' => 'userId',          'type' => 'xsd:string'),
        'additionalData'  => array('name' => 'additionalData',  'type' => 'xsd:string'),
        'OutputDetailB2C' => array('name' => 'OutputDetailB2C', 'type' => 'tns:Transactions'),
        )
    );

$server->register('InputListTransactionB2C',                             // method name
        array('InputListTransactionB2C'  => 'tns:InputListTransaction'), // input parameters
        array('OutputListTransactionB2C' => 'tns:ListTransaction'),      // output parameters
        'urn:klikbca',                                                   // namespace
        'urn:klikbca#InputListTransactionB2C',                           // soapaction
        'rpc',                                                           // style
        'encoded',                                                       // use
        'List Transaction'                                               // documentation
        );

//payment flag

$server->wsdl->addComplexType(
    'InputPayment',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'merchantCode'    => array('name' => 'merchantCode',    'type' => 'xsd:string'),
        'userId'          => array('name' => 'userId',          'type' => 'xsd:string'),
        'transactionNo'   => array('name' => 'transactionNo',   'type' => 'xsd:string'),
        'transactionDate' => array('name' => 'transactionDate', 'type' => 'xsd:string'),
        'amount'          => array('name' => 'amount',          'type' => 'xsd:string'),
        'type'            => array('name' => 'type',            'type' => 'xsd:string'),
        'additionalData'  => array('name' => 'additionalData',  'type' => 'xsd:string', 'nillable' => 'true'),
        )
    );

$server->wsdl->addComplexType(
    'OutputPayment',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'merchantCode'    => array('name' => 'merchantCode',    'type' => 'xsd:string'),
        'userId'          => array('name' => 'userId',          'type' => 'xsd:string'),
        'transactionNo'   => array('name' => 'transactionNo',   'type' => 'xsd:string'),
        'transactionDate' => array('name' => 'transactionDate', 'type' => 'xsd:string'),
        'additionalData'  => array('name' => 'additionalData',  'type' => 'xsd:string', 'nillable' => 'true'),
        'status'          => array('name' => 'status',          'type' => 'xsd:string'),
        'reason'          => array('name' => 'reason',          'type' => 'xsd:string'),
        )
    );

$server->register('InputPaymentB2C',                      // method name
        array('InputPaymentB2C' => 'tns:InputPayment'),   // input parameters
        array('OutputPaymentB2C' => 'tns:OutputPayment'), // output parameters
        'urn:klikbca',                                    // namespace
        'urn:klikbca#OutputPaymentB2C',                   // soapaction
        'rpc',                                            // style
        'encoded',                                        // use
        'Confirm Transaction'                             // documentation
        );

function willUseKaspayApi($merchantCode)
{
    return in_array($merchantCode, array(
        "KASPAY",
        "KASAD",
        "DONATUR",
    ));
}

function connectDatabase()
{
    global $config;
    $db = mysql_connect($config['db']['hostname'], $config['db']['username'], $config['db']['password']);
    mysql_select_db($config['db']['database'], $db);
}

function callKaspayApi($params, $method, $suffix = "")
{
    global $config;
    $oauth = $config["oauth"];
    $consumerKey = $oauth["key"];
    $consumerSecret  = $oauth["secret"];

    $apiUrl = $config["kaspay_api_url"];
    $resourceUrl = "{$apiUrl}/payment{$suffix}";

    try {
        $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauthClient->enableDebug();
        $oauthClient->fetch($resourceUrl, json_encode($params), $method, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ));

        return json_decode($oauthClient->getLastResponse(), true);
    } catch (OAuthException $e) {
        return json_decode($e->lastResponse, true);
    }
}

function getErrorResponse($response)
{
    if (isset($response["messages"]) && isset($response["messages"]["error"])) {
        return $response["messages"]["error"];
    } else {
        return null;
    }
}

function InputListTransactionB2C($param)
{
    // Some warning occured. Set error reporting to zero to make smooth running.
    error_reporting(0);

    $merchantCode = mysql_escape_string($param['merchantCode']);

    if (willUseKaspayApi($merchantCode)) {
        $accountId = mysql_escape_string($param['userId']);
        $response = callKaspayApi(null, "GET", "/?state=pending&payment_method=klikbca&user_id={$accountId}&merchant_code={$merchantCode}");
        $error = getErrorResponse($response);

        if (! $error) { // no error
            foreach ($response as $payment) {
                $data['OutputDetailB2C'][] = array(
                    'transactionNo'   => $payment['reference_no'],
                    'transactionDate' => $payment['create_time'],
                    'amount'          => 'IDR'.'10000'.'.00', // get from transactions
                    'description'     => 'Pembayaran Kaskus Ref No. '.$payment['reference_no'],
                    );
            }
        } else { // error
            $data['OutputDetailB2C'] = array();
        }

        $output = array(
            'merchantCode'   => $merchantCode,
            'userId'         => $accountId,
            'additionalData' => null,
        );
        $output = array_merge($output, $data);
    } else {
        connectDatabase();

        $fee = FEE;
        $query = mysql_query("SELECT * FROM kaspay_topup_klikbca WHERE userid= '".mysql_real_escape_string($param['userId'])."' AND status = ".STATUS_UNPAID);//99 belum dibayar
        $data  = array();
        while ($row = mysql_fetch_array($query, MYSQL_ASSOC)) {
            $data['OutputDetailB2C'][] = array(
                'transactionNo'   => $row['transactionno'],
                'transactionDate' => $row['transactiondate'],
                'amount'          => 'IDR'.($row['amount'] + $fee).'.00',
                'description'     => $row['description'],
                );
        }

        $output = array(
            'merchantCode'   => mysql_real_escape_string($param['merchantCode']),
            'userId'         => mysql_real_escape_string($param['userId']),
            'additionalData' => null,

            );
        $output = array_merge($output, $data);
    }

    return $output;
}

function build_InputPaymentB2C_output($merchantCode, $userId, $transactionNo, $transactionDate, $amount, $additionalData, $status, $reason)
{
    return array(
        'merchantCode'    => mysql_real_escape_string($merchantCode),
        'userId'          => $userId,
        'transactionNo'   => $transactionNo,
        'transactionDate' => $transactionDate,
        'amount'          => $amount,
        'additionalData'  => $additionalData,
        'status'          => $status,
        'reason'          => $reason,
    );
}

function InputPaymentB2C($param)
{
    $merchantCode = mysql_escape_string($param['merchantCode']);
    $referenceNo = mysql_escape_string($param['transactionNo']);

    if (willUseKaspayApi($merchantCode)) {
        $accountId = mysql_escape_string($param['userId']);

        $response = callKaspayApi(array("action" => "pay"), "PUT", "/{$referenceNo}");
        $error = getErrorResponse($response);

        if (! $error) { // no error
            $output = build_InputPaymentB2C_output(
                $merchantCode,
                $accountId, // take from response if exists
                $response['reference_no'],
                $response['create_time'],
                'IDR'.$param['amount'].'.00', // take from response if exists
                '',
                '00',
                'Success'
            );
        } else { // error
            $output = build_InputPaymentB2C_output(
                $merchantCode,
                $accountId,
                $referenceNo,
                $param['transactionDate'], // take from response if exists
                'IDR'.$param['amount'].'.00', // take from response if exists
                '',
                '01',
                'Error'
            );
        }
    } else {
        connectDatabase();

        $query = mysql_query("SELECT * FROM kaspay_topup_klikbca WHERE userid= '".mysql_real_escape_string($param['userId'])."' AND transactionno='".mysql_real_escape_string($param['transactionNo'])."'");
        //status 99= belum dibayar, 98 = transaksi expired
        $fee = FEE;

        if (mysql_num_rows($query) > 0) {
            // if it's only one row, don't use while loop
            while ($row = mysql_fetch_array($query, MYSQL_ASSOC)) {
                if ($row['status'] == STATUS_UNPAID) {
                    $topup = file_get_contents(KASPAY_BASEURL."/klikbca/topup/?trxid=".$row['trxid']."&account=".$row['uaccount']."&amount=".$row['amount']);
                    //$topup = 'gagal';
                    if (trim($topup) == 'success') {
                        $output = build_InputPaymentB2C_output(
                            $param['merchantCode'],
                            $row['userid'],
                            $row['transactionno'],
                            $row['transactiondate'],
                            'IDR'.($row['amount'] + $fee).'.00',
                            '',
                            '00',
                            'Success'
                        );

                        mysql_query("UPDATE kaspay_topup_klikbca SET status='00',reason='Success' WHERE transactionno = '".mysql_real_escape_string($param['transactionNo'])."'");
                    } elseif (trim($topup) == 'double') {
                        $output = build_InputPaymentB2C_output(
                            $param['merchantCode'],
                            $row['userid'],
                            $row['transactionno'],
                            $row['transactiondate'],
                            'IDR'.($row['amount'] + $fee).'.00',
                            '',
                            '01',
                            'KasPay Top Up has been paid'
                        );
                    } elseif (trim($topup) == 'max') {
                        $output = build_InputPaymentB2C_output(
                            $param['merchantCode'],
                            $row['userid'],
                            $row['transactionno'],
                            $row['transactiondate'],
                            'IDR'.($row['amount'] + $fee).'.00',
                            '',
                            '01',
                            'Over Limit Max Saldo'
                        );
                    } else {
                        $output = build_InputPaymentB2C_output(
                            $param['merchantCode'],
                            $param['userId'],
                            $param['transactionNo'],
                            $row['transactiondate'],
                            'IDR'.($row['amount'] + $fee).'.00',
                            '',
                            '01',
                            'KasPay Top Up cannot be processed'
                        );
                    }
                } elseif ($row['status'] == STATUS_EXPIRED) {
                    $output = build_InputPaymentB2C_output(
                        $param['merchantCode'],
                        $row['userid'],
                        $row['transactionno'],
                        $row['transactiondate'],
                        'IDR'.($row['amount'] + $fee).'.00',
                        '',
                        '01',
                        'KasPay Top Up has expired'
                    );
                } else {
                    $output = build_InputPaymentB2C_output(
                        $param['merchantCode'],
                        $row['userid'],
                        $row['transactionno'],
                        $row['transactiondate'],
                        'IDR'.($row['amount'] + $fee).'.00',
                        '',
                        '01',
                        'KasPay Top Up has been paid'
                    );
                }
            }
        } else {
            $output = build_InputPaymentB2C_output(
                $param['merchantCode'],
                $param['userId'],
                $param['transactionNo'],
                '',
                'IDR0.00',
                '',
                '01',
                'Data not found'
            );
        }
    }

    return $output;
}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
