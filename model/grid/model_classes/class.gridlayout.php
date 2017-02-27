<?php
namespace WEPPO\Grid;

/**
 * Ein Layout besteht aus einer Liste von "Zeilen" oder "Spalten" -> Reihe (Row)
 * 
 * je nach Type des Layouts wird "Row" anders interpretiert
 * 
 * 
 */
class Layout extends \WEPPO\Model\TableRecord {
	
	/**
	 * 
	 */
	use HasOptions;
	
	/**
	 * Mode:
	 */
	use HasMode;
	
	/**
	 * Für Manual-Mode: Reihen
	 */
	use HasChildren;
	
	/**
	 * Multilevel Cache
	 */
	use IsCachable;
	function getCacheIdentifier() {
		return \WEPPO\Grid\identifier($this->name);
	}
	function getCacheFolder() {
		static $cacheFolder = 'grid/layout';
		return $cacheFolder;
	}
	function useCache() {
		//return $this->option('useCache', false);
		return $this->useCache;
	}
	function invalidateParent($lang=null) {
	}
	
	
	
	
	
	# Für DB-Anbindung
	static function getTablename() {
		return 'gridlayouts';
	}
	
	# Für DB-Anbindung
	static function getCast() {
		return array_merge(parent::getCast(), array(
			'options' => 'json',
		));
	}
	
	public function __construct($id = 0, $cols = '*') {
		parent::__construct($id, $cols);
		$this->db_mode();
	}
	
	
	
	
	
	/**
	 * Layout über den Namen laden
	 */
	static function create($name) {
		static::where('name', $name);
		$l = static::getOne();
		return $l;
	}
	
	/**
	 * je nach Type des Layouts wird "Row" anders interpretiert
	 * 
	 * Typ 'rowsfirst': Row ist eine Zeile und enthält Zellen unterschiedlicher Breite als Spalten.
	 * Typ 'colsfirst': Row sind Spalten unterschiedlicher Breite, die mehrere Zellen untereinander beinhalten
	 */
	function getType() {
		if (!$this->type) return 'rowsfirst';
		return $this->type;
	}
	
	function isTypeRowsfirst() {
		return $this->getType() == 'rowsfirst';
	}
	
	function isTypeColsfirst() {
		return $this->getType() == 'colsfirst';
	}
	
	/**
	 * Alle Reihen für das Layout laden
	 * db-mode: aus DB laden
	 * manual-mode: children zurückgeben
	 */
	function getRows() {
		
		# Das Verhalten hier unterscheidet sich je nach Mode
		
		if ($this->is_db_mode()) {
			# Wenn db-mode, alle Reihen zu diesem Layout aus der DB laden
			
			\WEPPO\Grid\Row::where('layout_id', $this->id);
			\WEPPO\Grid\Row::orderBy('ordering', 'ASC'); # die veränderliche Anordnung/Reihenfoge der Elemente
			\WEPPO\Grid\Row::orderBy('id', 'ASC'); # eigentlich nicht nötig...
			$rows = \WEPPO\Grid\Row::get();
			
			# Vorverarbeitung der Reihen
			foreach ($rows as &$r) {
				/*
				 * Deaktiviert, das übernimmt jetzt: ExplicitClassTableRecord
				# Provider: das Reihen-Objekt wird durch ein
				# anderes, spezialisiertes Objekt (Klasse ist von Row abgeleiteten)
				# ersetzt (dieses Objekt ist dann z.B. zuständig für das Erzeugen der Zellen usw..)
				$provider = $r->option('provider');
				if (!empty($provider) && class_exists($provider)) {
					# dies ist eine Art "Upcast"
					$r = $r->upcastTo($provider);
				}
				*/
				
				# als Parent wird gleich dieses Layout gesetzt
				$r->set_parent($this);
				
				//unset($r);
			}
			
			return $rows;
		} else if ($this->is_manual_mode()) {
			# im manual-mode sollten die Reihen als Children hinzugefügt worden sein.
			# parent wird bei add_child gesetzt!
			return $this->get_children();
		}
	}
	
	/**
	 * String mit dem Layout-HTML-Kontext versehen
	 * Seiteneffekt auf $s !
	 */
	function wrapWithContext(&$s) {
		$identifier = identifier($this->name);
		$s = PHP_EOL.'<div data-layout_id="'.$this->id.'" class="gridlayout gridlayout-'.$identifier.' layout_type_'.$this->getType().' '.($this->isTypeColsfirst() ? 'row' : '').'">'
			.PHP_EOL.$s.PHP_EOL
			.'</div>'.PHP_EOL;
	}
	
	function wrapPreviewWithContext(&$s) {
		$this->wrapWithContext($s);
	}
	
	
	
	/**
	 * HTML-Output Erzeugen,
	 * in dem die Zeilen geholt und druchgegangen werden.
	 */
	function getOutput($lang, $default = true) {
		
		
		$useCache = $this->useCache();
		
		$fromCache = false;
		
		# aus cache laden
		if ($useCache) {
			$ret = $this->getFromCache($lang);
			if (is_string($ret)) {
				$result = $ret;
				$fromCache = true;
			}
		}
		
		if (!$fromCache) {
			
			# erzeugen
			$result = '';
			$rows = $this->getRows();
			foreach ($rows as &$row) {
				$result .= $row->getOutput($lang, $default);
			}
			$this->wrapWithContext($result);
			
			
			# cachen
			if ($useCache) {
				//echo 'write';
				
				if ($this->writeCache($result, $lang)) {
					
				}
			}
		}
		
		
		$result = "\n<!--       Layout ".($fromCache ? ' AUS CACHE ' : ' ON THE FLY ')." ".htmlentities($this->name).' ('.$this->id.") \n–––––––––––––––––––––––––––––––––––––––––––––––– -->\n".$result;
		
		return $result;
	}
	
	
	/**
	 * Layout-Preview ist speziell: 
	 * nur die Namen der Inhalte werden in den richtigen Zellen angezeigt
	 * und es werden ein paar mehr infos noch angezeigt
	 */
	function getPreviewOutput($lang) {
		$result = '<!-- Layout Preview -->'.PHP_EOL;
		$rows = $this->getRows();
		
		if (count($rows)) {
			foreach ($rows as &$row) {
				
				$cells = $row->getCells();
				$rowPreview = '';
				if (is_array($cells)) {
					if (count($cells)) {
						foreach ($cells as &$cell) {
							
							$contentPreview = $cell->getPreviewOutput();
							
							$cell->wrapWithContext($contentPreview);
							
							$rowPreview .= $contentPreview;
						}
					} else {
						$rowPreview = '<em>Keine Zellen</em>';
					}
				} else {
					# Wenn provider gesetzt, hat dieser in dem Kontext keine Reihen Zellen
					if ($class = $row->class) {
						$rowPreview = '<small>Provider <code>'.$class.'</code> did not provide any Cells in this Context.</small>';
					} else {
						$rowPreview = '<em>Keine Zellen</em>';
					}
				}
				$row->wrapWithContext($rowPreview); // nicht preview weil das hier extra gehandhabt wird
				$result .= $rowPreview;
				unset($row);
			}
		} else {
			$result = '<em>Keine Reihen</em>';
		}
		$this->wrapWithContext($result);
		return $result;
	}
	
	/**
	 * Short-Cut
	 * zum Erzeugen eines Inhalts
	 */
	static function createOutput($layout_name, $lang) {
		$layout = static::create($layout_name);
		if (!$layout) {
			return '<p>Layout »' . htmlentities($layout_name) . '« nicht gefunden. <a href="/editor/layout/create?name='.htmlentities($layout_name).'">Layout erstellen &gt;&gt;</a></p>';
		} else {
			return $layout->getOutput($lang);
		}
	}
	
	//public function remove() {
		//Row::where(
	//}
	
	
	
}