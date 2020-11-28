<?php

function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) { // if (!$length)
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

function processMessage($json)
{
    try {
        $action = $json['queryResult']['action'];
        $params = $json['queryResult']['parameters'];

        if ($action == "checkDateOfBirth") {
            $dob = (string)$params["date"];
            if (startsWith($dob, 'UUUU')) {
//change context to ask for year
                $sessionid = $json["session"];
                $contextName = $sessionid . '/contexts/awaiting_year_of_birth';
                $contextToDelete = $sessionid . '/contexts/awaiting_patient_name';
                sendMessage(array(
                    "fulfillmentText" => 'What is the year of birth?',
                    "outputContexts" => array(
                        array(
                            "name" => $contextName,
                            "lifespanCount" => 1
                        ),
                        array(
                            "name" => $contextToDelete,
                            "lifespanCount" => 0
                        )
                    )
                ));
            } else {
                sendMessage(array(
                    "fulfillmentText" => "What is the patient's name?"
                ));
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
