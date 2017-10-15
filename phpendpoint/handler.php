<?php 
require_once('./config.php');
require_once('./vendors/GooglePlaces.php');
require_once('./vendors/GooglePlacesClient.php');
require_once('./vendors/TimezoneMapper.php');

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
            //$results['response_code']        = 'OK';
            $results["airport_name"] = $response["results"][0]['name'];
            
        }
        else {
            $results["airport_name"] = "";
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

 dispatch('/getflight/:flight', 'getflight');
    function getJetlag($arrival, $lat, $lng){
        
        
        $arrival1 = explode(" ", $arrival);
        $justDate = $arrival1[0];
        $url = 'https://api.sunrise-sunset.org/json?lat='.$lat.'&lng='.$lng.'&date='.$justDate;
        $curl = curl_init();

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        if ($error = curl_error($curl))
        {
            throw new \Exception('CURL Error: ' . $error);
        }

        curl_close($curl);
        $response = json_decode($response);
        
        
        
        $arrivalTimezoneString = TimezoneMapper::latLngToTimezoneString($lat, $lng);
        
        $arrivalDate = new DateTime($arrival, new DateTimeZone($arrivalTimezoneString));
        //$arrivalDate->setTimezone(new DateTimeZone($arrivalTimezoneString));
        
        
        $sunriseTime = new DateTime($justDate.' '.$response->results->sunrise, new DateTimeZone('UTC'));
        $sunriseTime->setTimezone(new DateTimezone($arrivalTimezoneString));
        
        $sunsetTime = new DateTime($response->results->sunset, new DateTimeZone('UTC'));
        $sunsetTime->setTimezone(new DateTimezone($arrivalTimezoneString));
        
        
        $diffToSunrise  = $arrivalDate->diff($sunriseTime);
        $diffToSunset   = $arrivalDate->diff($sunsetTime);
        
        if($diffToSunrise->h > $diffToSunset->h):
            $info['suntime_suggestion'] = 'You are arriving approx. '.$diffToSunset->h.' hr(s) before sunset. Please make sure to avoid heavy sleep before you land in order to avoid jetlag.';
        elseif($diffToSunrise->h < $diffToSunset->h):
            $info['suntime_suggestion'] = 'You are arriving approx. '.$diffToSunrise->h.' hr(s) before sunrise. Please make sure to get adequate sleep before you land in order to avoid jetlag.';
        endif;
        
        
        //print_r($sunriseTime);
        //print_r($arrivalDate);
        //print_r($diffToSunrise);
        //print_r($diffToSunset);
        
        
        
        //var_dump(($response->results->sunrise));
        return $info['suntime_suggestion'];
        
    }
    function getflight() {
        $flight = strtoupper(params("flight"));
        
        /* read and handle xml */
        $source = './demo/flights.xml';
        
        $xmlstr = file_get_contents($source);
        $xmlcont = new SimpleXMLElement($xmlstr);
        $response = array();
        foreach($xmlcont->flight as $node) 
        {
            if($flight == strtoupper($node->id->__toString())) {
                
                
                $response['from_airport_name']   = $node->departure->location->__toString();
                $response['departure_time']      = $node->departure->datetime->__toString();
                $response['departure_gate']      = $node->departure->gate->__toString();  
                
                $response['to_airport_name']   = $node->arrival->location->__toString();
                $response['arrival_time']      = $node->arrival->datetime->__toString();
                $response['arrival_gate']      = $node->arrival->gate->__toString(); 
                
                $jetLagString = getJetlag($response['arrival_time'], $node->arrival->lat->__toString(), $node->arrival->lon->__toString());
                
                $dateArr = explode(' ', $response['arrival_time']);
                
                $response['information']['arrival_string'] = 'You will be arriving at  '.$dateArr[1].' local time on Gate: '.$response['arrival_gate'];
                $response['information']['sleep_suggest'] = $jetLagString;
                
            }
            
        }
        
        if(is_array($response) && !empty($response)) {
            $results['response_code']        = 'OK';
            $results['response']    = $response;
            
        }
        else {
            $results['response_code']    = 'NO_DATA';
            $results['error']       = 'Error retrieving airport name, please check your location.';
        }
        
        return json_encode( $response);
        
    }



run();