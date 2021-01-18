<?php
/*
 * Deze resource is alleen maar bedoeld voor demonstratie en testdoeleinden.
 * De resource ontvangt in de payload van een POST bericht een of meerdere berichten
 * zoals die in een GBA-V (VOA) bericht kunnen zitten. Een bericht bestaat uit
 * meerdere regels. Elke regel heeft de volgende opbouw:
 *   - (0, 7)   BERICHT-ID
 *   - (8, 13)  RECORD-ID
 *   - (14, 16) LENGTE
 *   - (17, -)  INHOUD
 * De API zoekt in alle regels naar burgerservicenummers. Een regel bevat een 
 * burgerservicenummer, wanneer record-id = "010120".
 * 
 * Deze API controleert niet of de berichten valide zijn. Er wordt ook geen onderscheid
 * gemaakt tussen de berichttypen waarin burgerservicenummers voorkomen. Dus elk 
 * burgerservicenummer in elk type bericht wordt als wijziging opgeslagen.
 * Voor de praktijd (productie) is dat waarschijnlijk niet correct. Voor testen 
 * is dat wel makkelijk, omdat gewoon rijen met als template "01234567010120009{burgerservicenummer}"
 * kunnen worden opgenomen.
 * 
 * Bij gemeenten zullen er meestal wijzigingen binnenkomen in de vorm van 
 * bijvoorbeeld StUF npsLk01 berichten, of in de vorm van Gv01-, Gv02-of Ag31-bericht
 * uit GBA-V.
 */

include ('config.php');
include ('api_classes.php');
include ('api_functions.php');

$db=mysqli_connect (DBURL, DBUSER, DBPASS) or new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Connecting to database failed: " . mysqli_error($db));
mysqli_set_charset($db, "utf8");
mysqli_select_db ($db, DBNAME);

$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$burgerservicenummers = array();

if (array_key_exists('content-type', $headers))
{
    $contentType = $headers['content-type'];
}
else
{
    $contentType = "text/plain";
}

switch ($_SERVER['REQUEST_METHOD'])
{
    case 'POST':
        switch ($contentType)
        {
            case "text/plain":
                $messages = explode(PHP_EOL, file_get_contents('php://input'));

                $today = date("Y-m-d");

                foreach ($messages as $line)
                {
                    $recordId = substr($line, 8, 6);
                    if ($recordId == "010120")
                    {
                        // line contains burgerservicenummer
                        $burgerservicenummer = str_pad(substr($line, 17), 9, "0", STR_PAD_LEFT);
                        checkBurgerservicenummer($burgerservicenummer);
                            
                        $sql = 'INSERT INTO `upd_wijzigingen` (`burgerservicenummer`, `datum`) '
                                . 'VALUES ("' . $burgerservicenummer . '", "' . $today . '") '
                                . 'ON DUPLICATE KEY UPDATE `datum`="' . $today . '"';
                        $result = mysqli_query($db, $sql);
                        if (!$result)
                        {
                            new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Inserting or updating to database resulted in an error.");
                            die();
                        }
                        $burgerservicenummers[] = new Bericht($burgerservicenummer, $today);
                    }            
                }
                break;
            case "application/json":
                $requestBody = file_get_contents('php://input');
                $content = json_decode($requestBody);
                if (is_null($content))
                {
                    new Foutbericht('request', 'paramsValidation', 'De request body is geen valide json.', 400, 
                        'De request body kan niet worden begrepen als json object.');
                    die();
                }
                
                if (!is_array($content))
                {
                    new Foutbericht('request', 'paramsValidation', 'De request body is geen array van objecten.', 400, 
                        'De request body kan niet worden begrepen als json array.');
                    die();
                }
                
                foreach ($content as $message)
                {
                    if (property_exists($message, 'burgerservicenummer'))
                    {
                        checkBurgerservicenummer($message->burgerservicenummer);
                    }
                    else
                    {
                        new Foutbericht('request', 'paramsValidation', 'Een of meerdere parameters zijn niet correct.', 400, 
                                'Burgerservicenummer ontbreekt in bericht.', 
                                new invalidParams('required', 'burgerservicenummer', "Parameter is verplicht."));
                        die();
                    }
                    
                    if (property_exists($message, 'datum') and !is_null($message->datum))
                    {
                        checkDateString($message->datum, 'datum');
                    }
                    else
                    {
                        new Foutbericht('request', 'paramsValidation', 'Een of meerdere parameters zijn niet correct.', 400, 
                                'Datum ontbreekt in bericht.', 
                                new invalidParams('required', 'datum', "Parameter is verplicht."));
                        die();
                    }
                    
                    $sql = 'INSERT INTO `upd_wijzigingen` (`burgerservicenummer`, `datum`) '
                            . 'VALUES ("' . $message->burgerservicenummer . '", "' . $message->datum . '") '
                            . 'ON DUPLICATE KEY UPDATE `datum`="' . $message->datum . '"';
                    $result = mysqli_query($db, $sql);
                    if (!$result)
                    {
                        new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Inserting or updating to database resulted in an error.");
                        die();
                    }
                    $burgerservicenummers[] = new Bericht($message->burgerservicenummer, $message->datum);
                }
                
                break;
            default:
                returnError(415);
        }
        
        

        http_response_code(201);
        header("Content-Type: json");
        header("api-version: " . API_VERSION);
        echo json_encode($burgerservicenummers);
        
        break;
    default:
        returnError(405);
}