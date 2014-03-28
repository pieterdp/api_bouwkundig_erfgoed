<?php

class html_generator {

	protected $tmpl; /* HTML template - <name>.tmpl - should contain [TITLE] & [CONTENT] at minimum, may be hooked */
	protected $loc; /* Location of the template */

	function __construct ($template = null) {
		if ($template === null) {
			$template = 'minimal';
		}
		$this->loc = 'lib/html/'.$template.'/';
		if (!file_exists ($this->loc.$template.'.tmpl')) {
			die ("Error: template '$template.tmpl' does not exist in html/-subdirectory.");
		}
		$this->tmpl = file_get_contents ($this->loc.$template.'.tmpl');
	}

	/*
	 * Function to load a template
	 * @param string $template_name
	 * @param string $template_loc
	 * @return string $template
	 */
	protected function load_template ($template, $lc = null) {
		if ($lc == null) {
			$lc = $this->loc;
		}
		if (!file_exists ($lc.$template.'.tmpl')) {
			die ("Error: template '$template.tmpl' does not exist in html/-subdirectory.");
		}
		return file_get_contents ($lc.'/'.$template.'.tmpl');
	}
	
	/*
	 * Create a base page - i.e. replace [TITLE] & [CONTENT]
	 * @param string $title
	 * @param string $content
	 * @return string $page
	 */
	public function create_base_page ($title, $content) {
		$page = str_replace (array ('[TITLE]', '[CONTENT]'), array ($title, $content), $this->tmpl);
		return $page;
	}
}

?>