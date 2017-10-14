<?php 
require_once('./config.php');
require_once('./vendors/GooglePlaces.php');
require_once('./vendors/GooglePlacesClient.php');

require_once('./vendors/limonade.php');

function before() {
    
}

dispatch('/', 'hello');
    function hello()
    {
        return 'Hello from default!';
    }
dispatch('/test', 'test');
    function test()
    {
        return 'Hello from test!';
    }
dispatch('/getairport/:lat/:lng', 'findairport');
    
    function getAirportName($lat, $lng) {
        
        $API_KEY = API_KEY;
        if(!$lat || !$lng) {
            return json_encode(array('error'=>true, "errorMsg" => "Latitude or Longitude value missing!"));
        }
        
        $google_places = new joshtronic\GooglePlaces($API_KEY);

        //$google_places->location = array(52.507443, 13.390391);
        $google_places->location = array($lat, $lng);
        $google_places->rankby   = 'distance';
        $google_places->name     = 'airport'; // Requires keyword, name or types
        $response                = $google_places->nearbySearch();
        $results    = "";
        if(is_array($response) && !empty($response)) {
            $results['response']        = 'OK';
            $results['airport_name']    = $response["results"][0]['name'];
            
        }
        else {
            $results['response']    = 'NO_DATA';
            $results['error']       = 'Error retrieving airport name, please check your location.';
        }
        // Decode the response.
        $object = json_encode ( $results );
        
        return $object;
    }

    function findairport() {
        $lat = params("lat");
        $lng = params("lng");
        
        return getAirportName($lat, $lng);
        
    };

 dispatch('/getjetlag/:flight', 'getjetlag');
    function getjetlag() {
        $flight = strtoupper(params("flight"));
        
        /* read and handle xml */
        $source = './demo/flights.xml';
        
        $xmlstr = file_get_contents($source);
        $xmlcont = new SimpleXMLElement($xmlstr);
        
        foreach($xmlcont as $node) 
        {
          echo($node->id);
        }
        
        
        
    }

run();