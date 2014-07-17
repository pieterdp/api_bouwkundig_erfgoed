/* Extra scripts for apps */
/* Open the pop-up */
function v_open_url (href) {
	window.open (href, '_blank', 'width=600,height=800,resizable=yes,scrollbars=yes');
}

function add_field (i) {
	var parent = document.getElementById ('form_container');
	var fieldset = document.createElement ('fieldset');
	fieldset.setAttribute ('id', 'base_input-' + i);
	fieldset.setAttribute ('class', 'base_input');
	var action = document.createElement ('fieldset');
	action.setAttribute ('id', 'base_action-' + i);
	action.setAttribute ('class', 'base_action');
	action.appendChild (add_checkbox ('Split', 'action-split-' + i));
	action.appendChild (add_split (i));
	action.appendChild (add_checkbox ('Merge', 'action-merge-' + i));
	var merge = document.createElement ('fieldset');
	merge.setAttribute ('id', 'base_merge-' + i);
	merge.setAttribute ('class', 'base_merge');
	merge.appendChild (add_input ('Prefix', 'prefix-' + i));
	merge.appendChild (add_input ('Suffix', 'suffix-' + i));
	action.appendChild (merge);
	action.appendChild (add_checkbox ('Delete', 'action-delete-' + i));
	/*fieldset.appendChild (add_input ('Field name', 'name-' + i));*/
	fieldset.appendChild (add_input ('Source field', 'source-' + i));
	fieldset.appendChild (add_input ('Destination field', 'dest-' + i));
	fieldset.appendChild (action);
	parent.appendChild (fieldset);
}

function add_input (nlabel, name) {
	var row = document.createElement ('div');
	row.setAttribute ('class', 'row');
	var input = document.createElement ('input');
	input.setAttribute ('type', 'text');
	input.setAttribute ('id', name);
	input.setAttribute ('name', name);
	input.setAttribute ('size', 32);
	var label = document.createElement ('label');
	label.setAttribute ('class', 'conversion');
	label.setAttribute ('for', name);
	label.innerHTML = nlabel;
	row.appendChild (label);
	row.appendChild (input);
	return row;
}

function add_checkbox (nlabel, name, value) {
	var row = document.createElement ('div');
	row.setAttribute ('class', 'row');
	var input = document.createElement ('input');
	input.setAttribute ('type', 'checkbox');
	input.setAttribute ('id', name);
	input.setAttribute ('name', name);
	input.setAttribute ('value', value);
	var label = document.createElement ('label');
	label.setAttribute ('class', 'conversion');
	label.setAttribute ('for', name);
	label.innerHTML = nlabel;
	row.appendChild (label);
	row.appendChild (input);
	return row;
}

function add_split (i) {
	var fieldset = document.createElement ('fieldset');
	fieldset.setAttribute ('id', 'base_split-' + i);
	fieldset.setAttribute ('class', 'base_split');
	fieldset.appendChild (add_input ('Split character', 'spliton-' + i));
	fieldset.appendChild (add_input ('Split options', 'splitoptions-' + i));
	var spliti = document.createElement ('input');
	spliti.setAttribute ('type', 'hidden');
	spliti.setAttribute ('name', 'split-i-' + i);
	spliti.setAttribute ('id', 'split-i-' + i);
	spliti.setAttribute ('value', 1);
	fieldset.appendChild (spliti);
	fieldset.appendChild (add_input ('Split destination field', 'splitdest-' + i + '-1'));
	return fieldset;
}

function add_split_dest (i) {
	var j = get_split_i (i) + 1;
	set_split_i (i, j);
	return add_input ('Split destination field', 'splitdest-' + i + '-' + j);
}

function get_i () {
	var i = document.getElementById ('i').getAttribute ('value');
	return parseInt (i);
}

function set_i (i) {
	document.getElementById ('i').setAttribute ('value', i);
	return true;
}

function get_split_i (i) {
	/* i = form i | j = split i */
	var spliti = document.getElementById ('split-i-' + i);
	if (spliti != null) {
		var j = spliti.getAttribute ('value');
	} else {
		var j = 1;
	}
	return parseInt (j);
}

function set_split_i (i, j) {
	document.getElementById ('split-i-' + i).setAttribute ('value', j);
	return true;
}

/* EventListener actions */
function add_form_row () {
	var i = get_i () + 1;
	add_field (i);
	set_i (i);
	window.scrollBy(0, window.innerHeight);
	document.getElementById ('source-' + i).focus ();
	return true;
}

function add_split_row () {
	var i = get_i ();
	var container = document.getElementById ('base_split-' + i);
	container.appendChild (add_split_dest (i));
	return true;
}

function reset_form () {
	location.reload (true);
	return true;
}


/* Execute the actions */
function app_start () {
	document.getElementById ('add-other').addEventListener ('click', add_form_row);
	document.getElementById ('add-other-split').addEventListener ('click', add_split_row);
	document.getElementById ('trash').addEventListener ('click', reset_form);
}
window.addEventListener ('load', app_start);