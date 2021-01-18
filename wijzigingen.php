<?php

include ('config.php');
include ('api_classes.php');
include ('api_functions.php');


switch ($_SERVER['REQUEST_METHOD'])
{
    case 'GET':
        $apiKey = authenticate();

        if (isset($_REQUEST['vanaf']))
        {
            $vanaf = $_REQUEST['vanaf'];
            checkDateString($vanaf, 'vanaf');
            
            $where = ' AND `datum`>="' . $vanaf . '"';
        }
        else
        {
            $where = '';
        }

        $db=mysqli_connect (DBURL, DBUSER, DBPASS) or new Foutbericht('internal', 'server_error', 'Internal Server Error', 500, "Connecting to database failed: " . mysqli_error($db));
        mysqli_set_charset($db, "utf8");
        mysqli_select_db ($db, DBNAME);
        
        $today = date("Y-m-d");
        $sql = 'SELECT `burgerservicenummer` FROM `upd_wijzigingen` '
                . 'WHERE `burgerservicenummer` IN '
                . '(SELECT `burgerservicenummer` FROM `upd_volgindicaties` WHERE `user`="' . $apiKey . '" AND (`einddatum`>"' . $today . '" OR `einddatum` IS NULL))'
                . $where;
        
        $result = runCollectionQuery($db, $sql);
        $burgerservicenummers = array();
        foreach ($result as $row)
        {
            $burgerservicenummers[] = $row['burgerservicenummer'];
        }

        $message = json_encode(new GewijzigdePersonenHalCollectie($burgerservicenummers));

        if ($message===false)
            jsonError(json_last_error());
        else
        {
            http_response_code(200);
            header("Content-Type: application/hal+json");
            header("api-version: " . API_VERSION);

            echo $message;
        }
        break;
    default:
        returnError(405);
}
