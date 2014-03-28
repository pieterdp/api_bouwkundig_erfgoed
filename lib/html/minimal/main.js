/* Javascript for minimal template */


function aResize () {
	var h = window.innerWidth;
	var rel = h / 1280;
	/* Now recompute everything */
	/*var tsw = window.getComputedStyle (document.getElementById ('topmenu-separator')).getPropertyValue('width');*/
	document.getElementById ('topmenu-separator').style.width = Math.round (1100 * rel) + "px";
	document.getElementById ('topmenu').style.width = Math.round (980 * rel) + "px";
	document.getElementById ('footmenu').style.width = Math.round (980 * rel) + "px";
	document.getElementById ('body').style.width = Math.round (900 * rel) + "px";
	return true;
}
/*Element Height = Viewport height - element.offset.top - desired bottom margin*/
