<?php

// Chee Wee's PHP webhook for Dialogflow

function startsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    return substr( $haystack, 0, $length ) === $needle;
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) { // if (!$length)
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

function processMessage($update)
{
    try {
        $action = $update['queryResult']['action'];
        $params = $update['queryResult']['parameters'];

        if ($action == 'GetSum') {
            $num1 = $params['number'];
            $num2 = $params['number2'];
            $sum = $num1 + $num2;
            sendMessage([
                'fulfillmentText' => "The sum of the numbers is $sum."
            ]);
        } elseif ($action == 'GetMinus') {
            $num1 = $params['number'];
            $num2 = $params['number2'];
            $MinusResult = $num2 - $num1;
            sendMessage([
                'fulfillmentText' => "$num2 - $num1 is $MinusResult."
            ]);
        } elseif ($action == 'GetMultiply') {
            $num1 = $params['number'];
            $num2 = $params['number2'];
            $multiplyResult = $num1 * $num2;
            sendMessage([
                'fulfillmentText' => "The multiplication of $num1 and $num2 is $multiplyResult."
            ]);
        } elseif ($action == 'GetDivision') {
            $num1 = $params['number'];
            $num2 = $params['number2'];
            $divisionResult = $num1 / $num2;
            sendMessage([
                'fulfillmentText' => "$num1 / $num2 is $divisionResult."
            ]);
        } elseif ($action == 'getJoke') {
            $response = file_get_contents('http://api.icndb.com/jokes/random');
            $decoded = json_decode($response, true);
            $joke = $decoded['value']['joke'];
            sendMessage([
                'fulfillmentText' => $joke
            ]);
        } elseif ($action == 'getTrivia') {
            $date = $params['date'];
            $timestamp = strtotime($date);
            $year = date('Y', $timestamp);
            $month = date('m', $timestamp);
            $day = date('d', $timestamp);
            $apiUrl = 'http://numbersapi.com/' . $month . '/' . $day . '/date';
            $response = file_get_contents($apiUrl);
            sendMessage([
                'fulfillmentText' => $response,
                'debug_info-y-m-d' => "$year-$month-$day",
                "debug-url" => $apiUrl
            ]);
        } elseif ($action == 'getPlanetAttribute') {
            $planet = $params['planet'];
            $attribute = $params['attribute'];
            $restdbioUrl = "https://planets-eaac.restdb.io//rest/planets?q={\"Planet\":\"$planet\",\"Attribute\":\"$attribute\"}";
            $curl_h = curl_init($restdbioUrl);
            $apikey = 'XXXXXXXXXXX'; // replace API AUTH KEY here!!!!
            curl_setopt(
                $curl_h,
                CURLOPT_HTTPHEADER,
                [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'x-apikey: ' . $apikey
                ]
            );
            curl_setopt($curl_h, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl_h);
            $decoded = json_decode($response, true);
            $value = $decoded[0]['Value']; // var_export($decoded, true);
            $responseText = "The $attribute of $planet is $value.";
            sendMessage([
                'fulfillmentText' => $responseText,
                "url" => "$restdbioUrl",
                "value" => "$value"
            ]);
        }
        elseif ($action == 'saveFeedback') {
            $outputContexts = $update['queryResult']['outputContexts'];
            foreach ($outputContexts as $outputContext) {
                if (endsWith($outputContext['name'], '/session-vars')) {
                    $firstName = $outputContext['parameters']['given-name'];
                    $emailAddress = $outputContext['parameters']['email'];
                    $comment = $outputContext['parameters']['any'];
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => 'https://feedback-7a39.restdb.io/rest/feedback',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{"Name":"' . $firstName . '","Email":"' . $emailAddress . '","Comment":"' . $comment . '"}',
                        CURLOPT_HTTPHEADER => [
                            'Cache-Control: no-cache',
                            'Content-Type: application/json',
                            'x-apikey: XXXXXXXXXXXX' // replace API AUTH KEY here!!!!
                        ],
                    ]);
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                    if ($err) {
                        echo 'cURL Error #:' . $err;
                    } else {
                        sendMessage([
                            'fulfillmentText' => 'Thank you! Your feedback was successfully received!'
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        sendMessage([
            'fulfillmentText' => $e->getMessage()
        ]);
    }
}

function sendMessage($parameters)
{
    echo json_encode($parameters);
}

try {
    $update_response = file_get_contents('php://input');
    $update = json_decode($update_response, true);

    if (isset($update['queryResult']['action'])) {
        processMessage($update);
    }
} catch (Exception $e) {
    sendMessage([
        'fulfillmentText' => 'Unable to parse: ' . $e->getMessage()
    ]);
}

?>
