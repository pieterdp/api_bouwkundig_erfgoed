/* JS file for OSM */
function add_osm_map (mapid, clat, clong) {
	var map = L.map (mapid).setView ([clat, clong], 15);
	L.tileLayer ('http://{s}.tiles.mapbox.com/v3/kameraadpjotr.j01fejdk/{z}/{x}/{y}.png', {
	attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
	maxZoom: 18
	}).addTo (map);
	return map;
}
/*var marker = L.marker ([clat, clong]).addTo (map);
	marker.bindPopup (title);*/
	/*document.getElementById ('monument_' + i).innerHTML*/

/* Execute the actions */
function app_start () {
	var total_items = parseInt (document.getElementById ('totalitems').innerHTML);
	for (var i = 1; i <= total_items; i++) {
		/* 
		 * Monument name
		 */
		var m_name = document.getElementById ('monument_' + i).innerHTML;
		/*
		 * Get amount of address positions
		 */
		var a_amount = parseInt (document.getElementById (i + '_a').innerHTML);
		/*
		 * Add the map using the first position
		 */
		var map = add_osm_map (
			'map_' + i,
			document.getElementById ('wgs84_lat_' + i + '_a1').innerHTML,
			document.getElementById ('wgs84_long_' + i + '_a1').innerHTML
		);
		/*
		 * Add markers for every position
		 */
		for (var j = 1; j <= a_amount; j++) {
		//straat_1_a1 gemeente_1_a1
			var alat = document.getElementById ('wgs84_lat_' + i + '_a' + j).innerHTML;
			var along = document.getElementById ('wgs84_long_' + i + '_a' + j).innerHTML;
			var astraat = document.getElementById ('straat_' + i + '_a' + j).innerHTML;;
			var agemeente = document.getElementById ('gemeente_' + i + '_a' + j).innerHTML;;
			var marker = L.marker ([alat, along]).addTo (map);
			marker.bindPopup ('<h5>' + m_name + '</h5><p>' + astraat + ', ' + agemeente + '</p>');
		}
	}
}
window.addEventListener ('load', app_start);