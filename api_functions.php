<?php
/**
 * Created by: Frank Samwel
 */


/**
 * Sends a problem+json error for given status code and then terminates execution
 * 
 * @param int $statusCode   http status code
 */
function returnError($statusCode)
{
    switch($statusCode)
    {
        case 404:
            new Foutbericht('request', 'notFound', 'Opgevraagde resource bestaat niet.', 404, 'Resource ' . $_SERVER["REQUEST_URI"] . ' is not found in this API.');
            break;
        case 405:
            new Foutbericht('patherrors', 'methodNotAllowed', 'Deze method is niet toegestaan op deze resource.', 405, 'Method ' . $_SERVER["REQUEST_METHOD"] . ' is not allowed for this path.');
            break;
        case 415:
            new Foutbericht('request', 'unsupported', 'Opgegeven media type voor de gestuurde content wordt niet ondersteund.', 415, 'The payload Content-Type in the request is not supported.');
            break;
        case 501:
            new Foutbericht('internal', 'notImplemented', 'Deze resource of method is nog niet geïmplementeerd.', 501, "This resource or method has not been implemented yet.");
            break;
        default:
            new Foutbericht('internal', 'serverError', 'Interne server fout.', 500, "Tried to create error with uncaught statuscode $statusCode.");
    }

    die();
}


/**
 * executes database query that expects one (1) row as result and returns this row as an array of column => value
 *
 * @param object $db
 * @param string $sql
 * @return array
 */
function runResourceQuery($db, $sql, $errorIfNone=false)
{
    $result = mysqli_query($db, $sql);
    if (!$result)
    {
        error_log("Error in runResourceQuery met $sql: " . mysqli_errno($db) . ": " . mysqli_error($db));
        new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "A database error occurred. " . mysqli_errno($db) . ": " . mysqli_error($db));
        die();
    }

    if ($result->num_rows==0 and $errorIfNone)
        returnError(404); // Not Found

    if ($result->num_rows==0 and !$errorIfNone)
        error_log("No results for resource query $sql");

    return $result->fetch_assoc();
}


/**
 * executes database query that expects a list (0..*) rows as result and returns these row as an array
 * each response array item is itself an array of column => value
 *
 * @param object $db
 * @param string $sql
 * @return array
 */
function runCollectionQuery($db, $sql)
{
    $result = mysqli_query($db, $sql);
    if (!$result)
    {
        error_log("Error in runCollectionQuery met $sql: " . mysqli_errno($db) . ": " . mysqli_error($db));
        new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "A database error occurred. " . mysqli_errno($db) . ": " . mysqli_error($db) . $sql);
        die();
    }

    $rows = array();
    while ($row = $result->fetch_assoc())
        $rows[] = $row;

    return $rows;
}

/**
 * Checks a correct api key is given in header x-api-key
 * Sends authentication error if not and then terminates execution
 * 
 */
function authenticate()
{
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        
    if (array_key_exists('x-api-key', $headers))
    {
        $apiKey = $headers['x-api-key'];
        if (preg_match("/^[\d\w]+$/", $apiKey)===0)
        {
            new Foutbericht('request', 'authentication', 
                    'Niet correct geauthenticeerd.', 401, 
                    'x-api-key header should only contain letters (a-z and A-Z) or numbers.');
            die();
        }
        
        return $apiKey;
    }
    else
    {
        new Foutbericht('request', 'authentication', 
                    'Niet correct geauthenticeerd.', 401, 
                    'x-api-key header is missing.');
            die();
    }
}


/**
 * Checks a correct date is given in format YYYY-MM-DD
 * Sends 400 error if not and then terminates execution
 * 
 * @param string $inputDate   the string holding the date value
 * @param string $fieldName   the name of the parameter the user filled with the date
 * 
 */
function checkDateString($inputDate, $fieldName)
{
    if (preg_match("/^[1-2][0-9]{3}-[0-1][0-9]-[0-3][0-9]$/", $inputDate)===0)
    {
        new Foutbericht('request', 'paramsValidation', 'Een of meerdere parameters zijn niet correct.', 400, 
                'Parameter die niet correct is: ' . $fieldName . ' (voldoet niet aan formaat YYYY-MM-DD): ' . $inputDate, 
                new invalidParams('dateformat', $fieldName, "Waarde is geen geldige datum."));
        die();
    }
    elseif (date('Y-m-d', strtotime($inputDate))!=$inputDate)
    {
        new Foutbericht('request', 'paramsValidation', 'Een of meerdere parameters zijn niet correct.', 400, 
                'Parameter die niet correct is: ' . $fieldName . ' (datum is geen echte datum): ' . $inputDate, 
                new invalidParams('date', $fieldName, "Waarde is geen geldige datum."));
        die();
    }
}


/**
 * Checks a correct burgerservicenummer is given of 9 digits
 * does not check for "11-proef"
 * Sends 400 error if not and then terminates execution
 * 
 * @param string $burgerservicenummer
 * 
 */
function checkBurgerservicenummer($burgerservicenummer)
{
    if (preg_match("/^[0-9]{9}$/", $burgerservicenummer)===0)
    {
        new Foutbericht('request', 'paramsValidation', 'Een of meerdere parameters zijn niet correct.', 400, 
                'Parameter die niet correct is: burgerservicenummer: ' . $burgerservicenummer, 
                new invalidParams('pattern', 'burgerservicenummer', "Waarde voldoet niet aan patroon"));
        die();
    }
}


/**
 * Sends a problem+json error response for an error returned from json_encode
 * and then terminates execution
 * 
 * @param type $error
 */
function jsonError($error)
{
    switch ($error)
    {
        case JSON_ERROR_DEPTH: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'The maximum stack depth has been exceeded'); break;
        case JSON_ERROR_STATE_MISMATCH: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'Invalid or malformed JSON'); break;
        case JSON_ERROR_CTRL_CHAR: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'Control character error, possibly incorrectly encoded'); break;
        case JSON_ERROR_SYNTAX: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'Syntax error'); break;
        case JSON_ERROR_UTF8: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'Malformed UTF-8 characters, possibly incorrectly encoded'); break;
        case JSON_ERROR_RECURSION: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'One or more recursive references in the value to be encoded'); break;
        case JSON_ERROR_INF_OR_NAN: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'One or more NAN or INF values in the value to be encoded'); break;
        case JSON_ERROR_UNSUPPORTED_TYPE: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'A value of a type that cannot be encoded was given'); break;
        case JSON_ERROR_INVALID_PROPERTY_NAME: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'A property name that cannot be encoded was given'); break;
        case JSON_ERROR_UTF16: new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, 'Malformed UTF-16 characters, possibly incorrectly encoded'); break;
    }
    die();
}