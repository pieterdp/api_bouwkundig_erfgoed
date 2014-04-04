<?php
include_once ('lib/class_html.php');

class skin extends html_generator {

	public $tm; /* Topmenu */
	public $fm; /* Footer menu */
	public $c; /* Content */

	/*
	 * Create base page (supersedes the one in class_html
	 */
	public function create_base_page ($title, $content) {
		return str_replace (array ('[TITLE]', '[TOPMENU]', '[CONTENT]', '[FOOTMENU]'), array ($title, $this->topmenu (), $content, $this->footmenu ()), $this->tmpl);
	}
	
	/*
	 * Generate the menu
	 * @param array $menu_items = array ('item_name' => 'link')
	 * @param string $active_item name of the active item (optional) - gets class="active"
	 * @return $string topmenu
	 */
	public function topmenu ($menu_items = null, $active_item = null) {
		if ($menu_items == null) {
			$menu_items = array (
				'home' => '',
				'apps' => 'apps.php',
				'blog' => 'blog.php'
			);
		}
		$menu_items = array_reverse ($menu_items); /* Reverse the order, as these elements are floated and thus last one is shown first */
		$tmtmpl = $this->load_template ('default-menu');
		$this->tm = '';
		$i = 1;
		foreach ($menu_items as $name => $link) {
			if ($name != $active_item || $active_item == null) {
				$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]', '[CLASS]'), array ($i, $name, $link, ''), $tmtmpl);
			} else {
				$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]', '[CLASS]'), array ($i, $name, $link, ' active'), $tmtmpl);
			}
			$i++;
		}
		return $this->tm;
	}

	/*
	 * Generate the footmenu
	 * @param array $menu_items = array ('item_name' => 'link')
	 * @return $string footmenu
	 */
	public function footmenu ($menu_items = null) {
		if ($menu_items == null) {
			$menu_items = array (
				'about' => 'about.php',
				'contact' => 'contact.php',
				'sitemap' => 'sitemap.php'
			);
		}
		$menu_items = array_reverse ($menu_items); /* Reverse the order, as these elements are floated and thus last one is shown first */
		$tmtmpl = $this->load_template ('default-footer');
		$this->tm = '';
		$i = 1;
		foreach ($menu_items as $name => $link) {
			$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]'), array ($i, $name, $link), $tmtmpl);
			$i++;
		}
		return $this->tm;
	}
}
?>