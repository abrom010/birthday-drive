<?php
require_once('api_keys.php');
$online = true;

function get_route_graph($online, $addresses) {
	global $BING_MAPS_API_KEY;

	// array infrastructure
	$route_maps = array();
	for($i=0; $i<count($addresses); $i++) {
		$route_maps[$addresses[$i]] = array();
	}

	if(!$online) {
		sleep(1);
		for($i=0; $i<count($addresses); $i++) {
			for($j=0; $j<count($addresses); $j++) {
				$address1 = $addresses[$i];
				$address2 = $addresses[$j];
				$route_maps[$address1][$address2] = rand(0, 10000);
			}
		}
		return $route_maps;
	}



	// setting up requests to run async
	$requests = [];
	$multi_curl = curl_multi_init();
	for($i=0; $i<count($addresses); $i++) {
		for($j=0; $j<count($addresses); $j++) {
			if($i==$j) continue;
			$address1 = preg_replace('/\s+/', '%20', $addresses[$i]);
			$address2 = preg_replace('/\s+/', '%20', $addresses[$j]);
			$url = "http://dev.virtualearth.net/REST/v1/Routes?wp.0=$address1&wp.1=$address2&key=$BING_MAPS_API_KEY"; 
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_AUTOREFERER, TRUE);
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
			array_push($requests, array('curl_handle' => $curl_handle, 'address1' => $addresses[$i], 'address2' => $addresses[$j]));
			curl_multi_add_handle($multi_curl, $curl_handle);
		}
	}

	// running the requests
	do {
		$status = curl_multi_exec($multi_curl, $active);
		if($active) {
			curl_multi_select($multi_curl);
		}
	} while($active && $status==CURLM_OK);

	// getting the responses
	foreach($requests as $request) {
		$http_code = curl_getinfo($request['curl_handle'], CURLINFO_HTTP_CODE);
		if($http_code!=200) return NULL;
		$res = curl_multi_getcontent($request['curl_handle']);
		$json = json_decode($res, true);
		$travel_duration = $json['resourceSets'][0]['resources'][0]['travelDuration'];
		$route_maps[$request['address1']][$request['address2']] = $travel_duration;
		curl_close($request['curl_handle']);
	}

	curl_multi_close($multi_curl);

	return $route_maps;
}

function remove_item(&$arr, $value) {
	$index = array_search($value, $arr);
	if($index>-1) {
		array_splice($arr, $index, 1);
	}
	return $index;
}

function insert_item(&$arr, $value, $index) {
	$new_arr = array_slice($arr, 0, $index);
	array_push($new_arr, $value);
	for($i=$index; $i<count($arr); $i++) {
		array_push($new_arr, $arr[$i]);
	}
	for($i=0; $i<count($new_arr); $i++) {
		$arr[$i] = $new_arr[$i];
	}
}

function helper($current_travel_duration, $current_route, $unvisited_locations, $location_count, &$min_travel_duration, $origin_location, $route_graph, &$optimal_route) {
	if($current_travel_duration > $min_travel_duration) return;
	if(count($current_route) == $location_count) {
		$min_travel_duration = $current_travel_duration;
		$optimal_route = $current_route;
		return;
	}

	// explore every possible next step
	$last_location = $current_route[count($current_route)-1];
	for($i=0; $i<count($unvisited_locations); $i++) {
		$next_location = $unvisited_locations[$i];
		if(($next_location == $origin_location && count($unvisited_locations)>1)) continue;
		array_push($current_route, $next_location);
		$current_travel_duration += $route_graph[$last_location][$next_location];
		$index = remove_item($unvisited_locations, $next_location);

		helper($current_travel_duration, [...$current_route], [...$unvisited_locations], $location_count, $min_travel_duration, $origin_location, $route_graph, $optimal_route);

		array_pop($current_route);
		$current_travel_duration -= $route_graph[$last_location][$next_location];
		insert_item($unvisited_locations, $next_location, $index);
	}
}

function calculate_optimal_route($online, $addresses, $route_graph) {
	// we want to optimize for least time on the road after the first stop has been reached
	// we return to our starting point

	// now i'm thinking depth first search, saving a distance for each route,
	// and pruning based off of a minimum distance

	// set up state
	$min_travel_duration = PHP_INT_MAX;
	$optimal_route = array();

	$origin_location = array_shift($addresses);
	array_push($addresses, $origin_location);

	// search every starting possibility
	$location_count = count($addresses);
	for($i=0; $i<$location_count; $i++) {
		$location = $addresses[$i];
		if($location==$origin_location) continue;
		$unvisited_locations = $addresses;
		$index = remove_item($unvisited_locations, $location);
		helper(0, array($location), [...$unvisited_locations], $location_count, $min_travel_duration, $origin_location, $route_graph, $optimal_route);
		insert_item($unvisited_locations, $location, $index);
	}

	array_unshift($optimal_route, $origin_location);

	// adding the travel time of getting to the first location
	if(!$online) {
		$min_travel_duration += rand(0,10000);
	} else {
		global $BING_MAPS_API_KEY;
		$address1 = preg_replace('/\s+/', '%20', $optimal_route[0]);
		$address2 = preg_replace('/\s+/', '%20', $optimal_route[1]);
		$contents = file_get_contents("http://dev.virtualearth.net/REST/v1/Routes?wp.0=$address1&wp.1=$address2&key=$BING_MAPS_API_KEY");
		$json = json_decode($contents, true);
		$additional_time = $json['resourceSets'][0]['resources'][0]['travelDuration'];
		$min_travel_duration += $additional_time;
	}

	// return the route
	return array(
		'time'=> $min_travel_duration,
		'route'=> $optimal_route
	);
}

if (isset($_POST['addresses'])) {
	$addresses = $_POST['addresses'];
	$route_graph = get_route_graph($online, $addresses);
	if(is_null($route_graph)) {
		http_response_code(400);
		echo json_encode(array('message'=>'Error calculating optimal route'));
	} else {
		http_response_code(200);
		echo json_encode(calculate_optimal_route($online, $addresses, $route_graph));
	}
} else {
	$addresses = array('Miami, Florida', 'Springfield,Illinois', 'Atlanta,Georgia', 'Houston,Texas', 'Sacramento,California');
	$route_graph = get_route_graph($online, $addresses);
	if(is_null($route_graph)) {
		http_response_code(400);
		echo json_encode(array('message'=>'Error calculating optimal route'));
	} else {
		http_response_code(200);
		echo json_encode(calculate_optimal_route($online, $addresses, $route_graph));
	}
}
?>
