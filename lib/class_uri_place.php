<?php
require_once ('class_uri_base.php');

class uri_place extends uri_base {

	protected $tables; /* Tables that form part of the place URI */

	function __construct () {
		parent::__construct ();
		$this->tables = array ('provincies', 'gemeentes', 'deelgemeentes', 'straten', 'huisnummers', 'relicten');
	}

	/*
	 * Function to translate between an uri_id and the id & table of the real item
	 * @param string $uri_id
	 * @return array (id, table)
	 */
	protected function get_id_by_uri_id ($uri_id) {
		$q = "SELECT entity_type, entity_id FROM uri WHERE id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $uri_id);
		$st->execute ();
		$st->bind_result ($type, $id);
		$st->fetch ();
		$st->close ();
		$st = null;
		return array ('entity_id' => $id, 'entity_type' => $type);
	}

	/*
	 * Function to get an item by its id
	 * @param string $table
	 * @param string $id
	 * @return array (assoc) $item
	 */
	protected function get_item_by_id ($table, $id) {
		if (!in_array ($table, $this->tables)) {
			echo "Error: illegal table specified in get_item_by_id!";
			return false;
		}
		$q_t = "SELECT * FROM %s WHERE `id` = '%s'";
		$q = sprintf ($q_t, $this->c->real_escape_string ($table), $this->c->real_escape_string ($id));
		$r = $this->c->query ($q);
		$item = $r->fetch_assoc ();
		$r->free ();
		return $item;
	}

	/*
	 * Function to translate between an uri_id (uri.php?id=foo) and the entire item
	 * @param string $uri_id
	 * @return array $item (assoc)
	 */
	public function translate_uri_id_to_id ($uri_id) {
		/* Translate to id */
		$item = $this->get_id_by_uri_id ($uri_id);
		$item = $this->get_item_by_id ($item['entity_type'], $item['entity_id']);
		return $item;
	}

	/*
	 * Function to get the uri_id from the id & table
	 * @param string $entity_id
	 * @param string $entity_type
	 * @return string $uri_id
	 */
	public function translate_id_to_uri_id ($entity_id, $entity_type) {
		$q = "SELECT id FROM uri WHERE entity_type = ? AND entity_id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('ss', $entity_type, $entity_id);
		$st->execute ();
		$st->bind_result ($uri_id);
		$st->fetch ();
		$st->close ();
		$st = null;
		return $uri_id;
	}

	/*
	 * Function to fetch a provincie from the DB with all of its (direct) children
	 * @param string $provincie_id
	 * @return array $provincie (naam, wgs84_lat, wgs84_long, children => array (id ...)) (assoc)
	 */
	public function fetch_provincie ($provincie_id) {
	}
}

/* Monumenten "zonder nummer" zijn deel van de straat en niet van het nummer zonder_nummer! */

?>