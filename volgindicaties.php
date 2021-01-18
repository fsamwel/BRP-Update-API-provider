<?php

include ('config.php');
include ('api_classes.php');
include ('api_functions.php');

$apiKey = authenticate();

$db=mysqli_connect (DBURL, DBUSER, DBPASS) or new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Connecting to database failed: " . mysqli_error($db));
mysqli_set_charset($db, "utf8");
mysqli_select_db ($db, DBNAME);


switch ($_SERVER['REQUEST_METHOD'])
{
    case 'GET':
        if (isset($_REQUEST['burgerservicenummer']))
        {
            // GET /volgindicaties/{burgerservicenummer}
            
            $burgerservicenummer = $_REQUEST['burgerservicenummer'];
            checkBurgerservicenummer($burgerservicenummer);
            
            $result = runResourceQuery($db, 'SELECT `einddatum` '
                    . 'FROM upd_volgindicaties '
                    . 'WHERE `user`="' . $apiKey . '" AND `burgerservicenummer`="' . $burgerservicenummer . '"', true);
            $response = new Volgindicatie($burgerservicenummer, $result['einddatum']);
        }
        else
        {
            // GET /volgindicaties
            
            $today = date("Y-m-d");
            $result = runCollectionQuery($db, 'SELECT `burgerservicenummer`, `einddatum` '
                    . 'FROM upd_volgindicaties '
                    . 'WHERE `user`="' . $apiKey . '" AND (`einddatum`>"' . $today . '" OR `einddatum` IS NULL)');
            
            $volgindicaties = array();
            foreach ($result as $row)
            {
                $volgindicaties[] = new Volgindicatie($row['burgerservicenummer'], $row['einddatum']);
            }
            
            $response = new VolgindicatieCollectie($volgindicaties);
        }

        $message = json_encode($response);

        if ($message===false)
            jsonError(json_last_error());
        else
        {
            http_response_code(200);
            header("Content-Type: application/json");
            header("api-version: " . API_VERSION);

            echo $message;
        }
        break;
    case 'PUT':
        if (isset($_REQUEST['burgerservicenummer']))
        {
            // PUT /volgindicaties/{burgerservicenummer}
            
            $burgerservicenummer = $_REQUEST['burgerservicenummer'];
            checkBurgerservicenummer($burgerservicenummer);
            
            $requestBody = file_get_contents('php://input');
            if (strlen($requestBody)>0)
            {
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        
                if (array_key_exists('content-type', $headers))
                {
                    if ($headers['content-type']!='application/json')
                    {
                        returnError(415);
                    }
                }
                //else assume default application/json
                
                $content = json_decode($requestBody);
                if (is_null($content))
                {
                    new Foutbericht('request', 'paramsValidation', 'De request body is geen valide json.', 400, 
                        'De request body kan niet worden begrepen als json object.');
                    die();
                }
                
                if (property_exists($content, 'einddatum') and ! is_null($content->einddatum))
                {
                    
                    checkDateString($content->einddatum, 'einddatum');
                    $einddatum = '"' . $content->einddatum . '"';
                }
                else
                {
                    $einddatum = 'NULL';
                }
                
                /*
                 * Hier is alleen geïmplementeerd dat volgindicaties in een locale 
                 * database worden opgeslagen. In een productiesituatie zal er ook een volgindicatie
                 * of afnemerindicatie moeten worden gezet in het locale burgerzakensysteem,
                 * gegevensmagazijn of datadistributiesysteem wanneer het een binnengemeentelijke
                 * persoon betreft, of een afnemerindicatie worden gestuurd naar 
                 * GBA-V wanneer het een buitengemeentelijke persoon betreft.
                 */
                
                
                $sql = 'INSERT INTO `upd_volgindicaties` (`user`, `burgerservicenummer`, `einddatum`) '
                        . 'VALUES ("' . $apiKey . '", "' . $burgerservicenummer . '", ' . $einddatum . ') '
                        . 'ON DUPLICATE KEY UPDATE `einddatum`=' . $einddatum;
                $result = mysqli_query($db, $sql);
                if (!$result and strlen(mysqli_error($db))>0)
                {
                    new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Inserting or updating to database resulted in an error." . mysqli_error($db));
                    die();
                }
                
                $result = runResourceQuery($db, 'SELECT `einddatum` '
                        . 'FROM upd_volgindicaties '
                        . 'WHERE `user`="' . $apiKey . '" AND `burgerservicenummer`="' . $burgerservicenummer . '"', true);
                $response = new Volgindicatie($burgerservicenummer, $result['einddatum']);
                
                $message = json_encode($response);

                if ($message===false)
                    jsonError(json_last_error());
                else
                {
                    http_response_code(200);
                    header("Content-Type: application/json");
                    header("api-version: " . API_VERSION);

                    echo $message;
                }
            }
        }
        else
        {
            returnError(405);
        }
        break;
    default:
        returnError(405);
}
