/* JS file for OSM */
function add_osm_map (mapid, clat, clong, title) {
	var map = L.map (mapid).setView ([clat, clong], 15);
	L.tileLayer ('http://{s}.tiles.mapbox.com/v3/kameraadpjotr.j01fejdk/{z}/{x}/{y}.png', {
	attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
	maxZoom: 18
	}).addTo (map);
	var marker = L.marker ([clat, clong]).addTo (map);
	marker.bindPopup (title);
	return map;
}

/* Execute the actions */
function app_start () {
	var total_items = parseInt (document.getElementById ('totalitems').innerHTML);
	for (var i = 1; i <= total_items; i++) {
		/* monument_i
		wgs84_lat_i
		wgs84_long_i
		*/
		var map = add_osm_map (
			'map_' + i,
			document.getElementById ('wgs84_lat_' + i).innerHTML,
			document.getElementById ('wgs84_long_' + i).innerHTML,
			document.getElementById ('monument_' + i).innerHTML
		);
	}
}
window.addEventListener ('load', app_start);