<?php
/*
 * Download class
 */
class download {

	function __construct ($filepath) {
		$this->download_file ($filepath);
	}

	public function download_file ($filepath) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filepath));
		ob_clean ();
		flush ();
		readfile ($filepath);
		return true;
	}
}

?>