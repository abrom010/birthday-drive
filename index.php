<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
<title>Birthday Drive</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="styles.css">
<body>
<div class="root-div">
	<div class="nav-bar">
		<a href="#">Sign in</a>
	</div>
	<div class="about">
		<h1>Birthday Drive</h1>
		<p>
			This web app was made to find the best route on a birthday fast food restaurant spree.
			A route consists of a starting location, a set amount of stops, and a recall to the start.
			We define the best route as the route with the least amount of travel time
			after the first stop. This is helpful for time sensitive food delivery.
			The Bing Maps API is used to fetch the paths between locations.
		</p>
	</div>

	<div class="interactive-section">	
		<div class="left-column">	
			<div>
				<h2>Add your locations</h2>
				<div>
					<input type="text" class="location-input"></input>
					<button class="location-button">+</button>
				</div>

				<div class="locations"></div>
			</div>
		</div>
		<div class="right-column">
			<div>
				<h2>Optimal Route</h2>
				<button class="calculate-button">Calculate optimal route</button>
				<div class="optimal-route"></div>
			</div>
		</div>
	</div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>		
<script src="script.js"></script>

</body>
</html>


