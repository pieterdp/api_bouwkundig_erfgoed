<?php
require_once ('class_uri_base.php');

class uri_place extends uri_base {

	protected $tables; /* Tables that form part of the place URI */

	function __construct () {
		parent::__construct ();
		$this->tables = array ('provincies', 'gemeentes', 'deelgemeentes', 'straten', 'huisnummers', 'relicten', 'adres');
	}

	/*
	 * Function to fetch a place with all its children
	 * @param string $type
	 * @param string $id
	 * @return $item (id, naam, wgs84_lat, wgs84_long, children => array (uri_id))
	 */
	protected function boilerplate_fetch_place_with_children ($type, $id) {
		$place = array ();
		$q = "SELECT x.id, x.naam, x.wgs84_lat, x.wgs84_long, c.id as child FROM %s x, %s c WHERE x.id = ? AND c.%s = x.id";
		$q = sprintf ($q, $this->c->real_escape_string ($type), $this->c->real_escape_string ($this->parent_relation[$type][1]), $this->c->real_escape_string ($this->parent_relation[$type][0]));
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $id);
		$st->execute ();
		$st->bind_result ($n_id, $naam, $wgs84_lat, $wgs84_long, $child);
		$st->store_result ();
		while ($st->fetch ()) {
			if (!isset ($place['naam'])) {
				/* Is the same for all records */
				$place['naam'] = $naam;
				$place['id'] = $n_id;
				$place['wgs84_long'] = $wgs84_long;
				$place['wgs84_lat'] = $wgs84_lat;
			}
			if (!isset ($place['children'])) {
				$place['children'] = array ();
			}
			array_push ($place['children'], $this->translate_id_to_uri_id ($child, $this->c->real_escape_string ($this->parent_relation[$type][1])));
		}
		$st->close ();
		$st = null;
		return $place;
	}


	/*
	 * Function to translate between an uri_id and the id & table of the real item
	 * @param string $uri_id
	 * @return array (id, table)
	 */
	public function get_id_by_uri_id ($uri_id) {
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
	 * @return array $provincie (naam, wgs84_lat, wgs84_long, children => array (uri_id ...)) (assoc)
	 */
	public function fetch_provincies ($provincie_id) {
		return $this->boilerplate_fetch_place_with_children ('provincies', $provincie_id);
	}

	/*
	 * Function to fetch a gemeente with children
	 */
	public function fetch_gemeentes ($gemeente_id) {
		return $this->boilerplate_fetch_place_with_children ('gemeentes', $gemeente_id);
	}
	/*
	 * Function to fetch a deelgemeente with children
	 */
	public function fetch_deelgemeentes ($deelgemeente_id) {
		return $this->boilerplate_fetch_place_with_children ('deelgemeentes', $deelgemeente_id);
	}
	/*
	 * Function to fetch a straat with children (warning! relicts with number "zonder number" are direct children of the straat!)
	 */
	public function fetch_straten ($straat_id) {
		$children = array ();
		/* Fetch the straat itself */
		$straat = $this->get_item_by_id ('straten', $straat_id);
		/* Fetch all adressen with str_id with huisnummer set => huisnummer is child */
		$q = "SELECT h.id as huisnummer_id FROM adres a, huisnummers h, straten s
		WHERE
		h.id = a.huisnummer_id AND
		a.str_id = s.id AND
		h.naam NOT LIKE '%zonder%' AND
		s.id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $straat['id']);
		$st->execute ();
		$st->bind_result ($child);
		$st->store_result ();
		while ($st->fetch ()) {
			array_push ($children, $this->translate_id_to_uri_id ($child, 'huisnummers'));
		}
		$st->close ();
		$st = null;
		/* Fetch all adressen with str_id with huisnummer = zonder nummer => relict is child */
		$q = "SELECT r.id FROM adres a, huisnummers h, straten s, relicten r, link l
		WHERE
		h.id = a.huisnummer_id AND
		a.str_id = s.id AND
		h.naam LIKE '%zonder%' AND
		r.id = l.ID_link_r AND
		a.id = l.ID_link_a AND
		s.id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $straat['id']);
		$st->execute ();
		$st->bind_result ($child);
		$st->store_result ();
		while ($st->fetch ()) {
			array_push ($children, $this>translate_id_to_uri_id ($child, 'relicten'));
		}
		$st->close ();
		$st = null;
		return array (
			'id' => $straat['id'],
			'naam' => $straat['naam'],
			'wgs84_lat' => $straat['wgs84_lat'],
			'wgs84_long' => $straat['wgs84_long'],
			'children' => $children
			
		);
	}
	/*
	 * Function to fetch a huisnummer with children (see above)
	 */
	public function fetch_huisnummers ($huisnummer_id) {
		$children = array ();
		/* Huisnummer */
		$huisnummer = $this->get_item_by_id ('huisnummers', $huisnummer_id);
		$q = "SELECT r.id as relict FROM relicten r, link l, adres a, huisnummers h WHERE
		r.id = l.ID_link_r AND
		a.id = l.ID_link_a AND
		a.huisnummer_id = h.id AND
		h.id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $huisnummer['id']);
		$st->execute ();
		$st->bind_result ($child);
		$st->store_result ();
		while ($st->fetch ()) {
			array_push ($children, $this->translate_id_to_uri_id ($child, 'relicten'));
		}
		$st->close ();
		$st = null;
		return array (
			'id' => $huisnummer['id'],
			'naam' => $huisnummer['naam'],
			'wgs84_lat' => $huisnummer['wgs84_lat'],
			'wgs84_long' => $huisnummer['wgs84_long'],
			'children' => $children
			
		);
	}
	/*
	 * Function to fetch a monument with children
	 */
	public function fetch_relicten ($relict_id) {
		$children = array ();
		/* Relict */
		$relict = $this->get_item_by_id ('relicten', $relict_id);
		/* Relict with address as children */
		$q = "SELECT a.id as child FROM relicten r, adres a, link l WHERE
		r.id = l.ID_link_r AND
		a.id = l.ID_link_r AND
		r.id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $relict['id']);
		$st->execute ();
		$st->bind_result ($child);
		$st->store_result ();
		while ($st->fetch ()) {
			array_push ($children, $this->translate_id_to_uri_id ($child, 'adres'));
		}
		$st->close ();
		$st = null;
		return array (
			'id' => $relict['id'],
			'naam' => $relict['naam'],
			'alt_naam' => $relict['alt_naam'],
			'wgs84_lat' => $relict['wgs84_lat'],
			'wgs84_long' => $relict['wgs84_long'],
			'children' => $children
			
		);
	}
	/*
	 * Function fetch an address
	 * Address implements parent but not children
	 */
	public function fetch_adres ($adres_id) {
		$parents = array ();
		/* Adres */
		$adres = $this->get_item_by_id ('adres', $adres_id);
		/* Adres with relict as parent */
		/* To be replaced by something better */
		$q = "SELECT r.id as parent FROM relicten r, adres a, link l WHERE
		r.id = l.ID_link_r AND
		a.id = l.ID_link_r AND
		a.id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $adres['id']);
		$st->execute ();
		$st->bind_result ($parent);
		$st->store_result ();
		while ($st->fetch ()) {
			array_push ($parents, $this->translate_id_to_uri_id ($parent, 'relicten'));
		}
		$st->close ();
		$st = null;
		return array (
			'id' => $adres['id'],
			'wgs84_lat' => $adres['wgs84_lat'],
			'wgs84_long' => $adres['wgs84_long'],
			'parents' => $parents
			
		);
	}
}

/* Monumenten "zonder nummer" zijn deel van de straat en niet van het nummer zonder_nummer! */

?>
