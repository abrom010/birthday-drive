// function to convert seconds to days, hours, minutes, seconds
function secondsToDays(seconds) {
	seconds = Number(seconds);
	var d = Math.floor(seconds / (3600*24));
	var h = Math.floor(seconds % (3600*24) / 3600);
	var m = Math.floor(seconds % 3600 / 60);
	var s = Math.floor(seconds % 60);

	var dDisplay = d > 0 ? d + (d == 1 ? " day, " : " days, ") : "";
	var hDisplay = h > 0 ? h + (h == 1 ? " hour, " : " hours, ") : "";
	var mDisplay = m > 0 ? m + (m == 1 ? " minute, " : " minutes, ") : "";
	var sDisplay = s > 0 ? s + (s == 1 ? " second" : " seconds") : "";
	return dDisplay + hDisplay + mDisplay + sDisplay;
}

// calculate optimal route button
$('.calculate-button').on('click', function() {
	$('.optimal-route').html('');
	if($('.locations').children().length<2) {
		$('.optimal-route').html("<p>Add locations to calculate an optimal route.</p>");
		return;
	}

	// add the loader class to the optimal route div
	$(".optimal-route").addClass("loader");

	// get all of the locations in the locations div
	let addresses = [];
	$(".locations").children().each(function () {
		addresses.push($(this).text());
	});
	
	// request optimal route from backend
	$.ajax({
		url: 'calculate_route.php',
		type: 'POST',
		data: {
			addresses: addresses
		},
		success: function(response) {
			// display optimal route, remove loader class
			let response_object = JSON.parse(response);
			let time = response_object.time;
			let route = response_object.route;
			$('.optimal-route').html('');
			$('.optimal-route').append("<h3>"+secondsToDays(time)+"</h3");
			for(let i=0; i<route.length; i++) {
				$(".optimal-route").append("<p>"+route[i]+"</p>");
			}
			$('.optimal-route').removeClass('loader');
		},
		error: function() {
			$('.optimal-route').html("<p>Error calculating optimal route.</p>");
			$('.optimal-route').removeClass('loader');
		}
	});
});

// add location button
$('.location-button').on('click', function() {
	$('.locations').append("<div><button class=\"location-remove-button\"></button><p>"+$('.location-input').val()+"</p></div>");
	$('.location-input').val('');
});

// make location input click the add button
$('.location-input').on('keypress', function(event) {
	if(event.key==='Enter') {
		event.preventDefault();
		$('.location-button').trigger('click');
	}
});

$(document).on('click', '.location-remove-button', function() {
	$(this).parent().remove();
});
