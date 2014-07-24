<?php

require_once ('mysql_connect.php');

class uri_base extends db_connect {

	function __construct () {
		parent::__construct ();
	}

	/* URI table */
	public function create_uri_table_places () {
		$q_t = "SELECT DISTINCT id FROM %s";
		$tablenames = array ('provincies', 'gemeentes', 'deelgemeentes', 'straten', 'huisnummers', 'relicten');
		$this->c->autocommit (false);
		$this->c->begin_transaction ();
		foreach ($tablenames as $tablename) {
			$items = array ();
			$q = sprintf ($q_t, $tablename);
			$st = $this->c->prepare ($q) or die ($this->c->error);
			$st->execute ();
			$st->bind_result ($r);
			while ($st->fetch ()) {
				array_push ($items, $r);
			}
			$st->close;
			$st = null;
			$q = "INSERT INTO uri (entity_type, entity_id) VALUES (?, ?)";
			foreach ($items as $item) {
				$st = $this->c->prepare ($q) or die ($this->c->error);
				$st->bind_param ('ss', $tablename, $item);
				$st->execute ();
				$st->close ();
				$st = null;
			}
		}
		$this->c->commit ();
		$this->c->autocommit (true);
		return true;
	}
	
}

?>