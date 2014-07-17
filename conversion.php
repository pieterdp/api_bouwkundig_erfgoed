<?php
include_once ('lib/html_generator.php');
include_once ('lib/class_jobs.php');
include_once ('lib/class_download.php');

$html = include_skin ('minimal');
$iconset = load_icons ();

if (isset ($_POST['submit']) && $_POST['submit'] == "true") {
	/* There can be anywhere from 1 to n fields; the amount is stored in i */
	$fields = $_POST['i'];
	$fieldnames = array ('name', 'source', 'dest', 'action-split', 'spliton', 'splitoptions', 'splitdest', 'split-i', 'action-merge', 'prefix', 'suffix', 'action-delete');
	/* if ($i > PHP_INT_MAX) {
		header ("location: http://erfgoeddb.helptux.be/conversion.php", 503);
		exit (0);
	}*/
	$job = array ();
	$job['i'] = $fields;
	$job['jobname'] = $_POST['jobname'];
	$job['fields'] = array ();
	for ($i = 1; $i <= $fields; $i++) {
		$params = array ();
		foreach ($fieldnames as $fieldname) {
			if ($fieldname == 'splitdest') {
				$spliti = $_POST['split-i-'.$i];
				$params['splitdest'] = array ();
				for ($j = 1; $j <= $spliti; $j++) {
					array_push ($params['splitdest'], $_POST['splitdest-'.$i.'-'.$j]);
				}
				continue;
			}
			if ($fieldname == 'action-merge' || $fieldname == 'action-delete' || $fieldname == 'action-split') {
				if (isset ($_POST[$fieldname.'-'.$i])) {
					$params[$fieldname] = true;
				}
				continue;
			}
			if ($fieldname == 'name') {
				$params['name'] = $_POST['source-'.$i];
				continue;
			}
			$params[$fieldname] = $_POST[$fieldname.'-'.$i];
		}
		array_push ($job['fields'], $params);
	}
	$j = new job_creator ($job);
	$jobxml = $j->parse_as_xml ();
	$filename = 'jobs/xml/'.$j->jparameters['id'].'.xml';
	if (file_put_contents ($filename, $jobxml) === false) {
		echo "Error: failed to write xml to $filename!";
		exit (0);
	}
	$d = new download ($filename);
	if (unlink ($filename) != true) {
		echo "Error: failed to remove $filename!";
		exit (0);
	}
	header ("location: http://erfgoeddb.helptux.be/conversion.php");
	exit (0);
}

$expl = '';
/*<div class="row"><label class="conversion" for="name-1">Field name</label><input type="text" id="name-1" name="name-1" size="32"/></div>*/
$form = '<form method="post" action="" id="conv_form">
<div class="row"><label class="conversion" for="jobname">Job name</label><input type="text" id="jobname" name="jobname" size="32" /></div>
<div class="form" id="form_container">
	<fieldset class="base_input" id="base_input-1">
		<div class="row"><label class="conversion" for="source-1">Source field</label><input type="text" id="source-1" name="source-1" size="32"/></div>
		<div class="row"><label class="conversion" for="dest-1">Destination field</label><input type="text" id="dest-1" name="dest-1" size="32"/></div>
		<fieldset class="base_action" id="base_action-1">
			<input type="checkbox" name="action-split-1" id="action-split-1" value="split" /><label class="conversion" for="action-split-1">Split</label>
			<fieldset class="base_split" id="base_split-1">
				<div class="row"><label class="conversion" for="spliton-1">Split character</label><input type="text" name="spliton-1" id="spliton-1" size="16"/></div>
				<div class="row"><label class="conversion" for="splitoptions-1">Options</label><input type="text" name="splitoptions-1" id="splitoptions-1" size="32"/></div>
				<div class="row"><label class="conversion" for="splitdest-1-1">Destination field</label><input type="text" name="splitdest-1-1" id="splitdest-1-1" size="32"/></div>
				<input type="hidden" name="split-i-1" id="split-i-1" value="1" />
			</fieldset>
			<input type="checkbox" name="action-merge-1" id="action-merge-1" value="merge" /><label class="conversion" for="action-merge-1">Merge</label>
			<fieldset class="base_merge" id="base_merge-1">
				<div class="row"><label class="conversion" for="prefix-1">Prefix</label><input type="text" name="prefix-1" id="prefix-1" size="16"/></div>
				<div class="row"><label class="conversion" for="suffix-1">Suffix</label><input type="text" name="suffix-1" id="suffix-1" size="16"/></div>
			</fieldset>
			<input type="checkbox" name="action-delete-1" id="action-delete-1" value="delete" /><label class="conversion" for="action-delete-1">Delete</label>
		</fieldset>
	</fieldset>
</div>
<div class="add_more">
	<img class="icon" src="'.$iconset.'trash.gif" id="trash" alt="Reset." />
</div>
<div class="add_more">
	<img class="icon" src="'.$iconset.'field_input.gif" id="add-other-split" alt="Nog een splitdest." />
</div>
<div class="add_more">
	<img class="icon" src="'.$iconset.'plus.gif" id="add-other" alt="Nog een veld." />
</div>
<input type="hidden" name="submit" value="true" id="submit" />
<input type="hidden" name="i" value="1" id="i" />
<input type="submit" value="Aanmaken" id="smb" />
</form>
';

echo $html->create_base_page ('Aanmaken jobbestand', $expl.$form);
exit (0);
?>