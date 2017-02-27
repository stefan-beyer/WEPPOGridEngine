<?php
namespace WEPPO\Grid;

/**
 * Eine 'Reihe' ist je nach Typ des Layouts
 * eine Spalte oder eine Zeile im Layout.
 * Diese enthält dann Zellen, die dann entsprechend umgekehrt Zeilen, bzw. Spalten
 * darstellen.
 */
class Row extends ExplicitClassTableRecord {
	/**
	 * Mode:
	 */
	use HasMode;
	
	/**
	 * Parent ist hier dann das Layout
	 */
	use HasParent;
	
	/**
	 * Für manual-mode: Zellen
	 */
	use HasChildren;
	
	/**
	 * 
	 */
	use HasOptions;
	
	/**
	 * Upcast zu spezialisiertem Objekt
	 */
	//use CanUpCast;
	
	
	/**
	 * Multilevel Cache
	 */
	use IsCachable;
	function getCacheIdentifier() {
		return 'ROW-ID_'.$this->id;
	}
	function getCacheFolder() {
		static $cacheFolder = 'grid/row';
		return $cacheFolder;
	}
	function useCache() {
		//return $this->option('useCache', false);
		return $this->useCache;
	}
	function invalidateParent($lang=null) {
		if ($this->is_manual_mode()) return;
		
		$l = $this->getLayoutContext();
		if ($l) $l->invalidate($lang);
	}
	
	
	
	
	static $_NULL = null;
	
	# Für DB-Anbindung
	static function getTablename() {
		return 'gridrows';
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
	 * Zellen Laden
	 */
	function getCells() {
		
		if ($this->is_db_mode()) {
			# Wenn db-mode, alle Zellen zu dieser Reihe aus der DB laden
			
			\WEPPO\Grid\Cell::where('row_id', $this->id);
			\WEPPO\Grid\Cell::orderBy('ordering', 'ASC');
			\WEPPO\Grid\Cell::orderBy('id', 'ASC');
			$cells = \WEPPO\Grid\Cell::get();
			
			# Vorverarbeitung der Zellen
			foreach ($cells as &$c) {
				/*
				 * Deaktiviert, das übernimmt jetzt: ExplicitClassTableRecord
				# Provider: das Zellen-Objekt wird durch ein
				# anderes, spezialisiertes Objekt (Klasse ist von Cell abgeleiteten)
				# ersetzt (dieses Objekt ist dann z.B. zuständig für das Erzeugen des Inhalts usw..)
				$provider = $c->option('provider');
				if (!empty($provider) && class_exists($provider)) {
					$c = $c->upcastTo($provider);
				}*/
				
				# als Parent wird gleich dieses Layout gesetzt
				$c->set_parent($this);
				
				//unset($c);
			}
			return $cells;
		} else if ($this->is_manual_mode()) {
			# im manual-mode sollten die Reihen als Children hinzugefügt worden sein.
			# parent wird bei add_child gesetzt!
			return $this->get_children();
		}
	}
	
	/**
	 * String mit dem Row-HTML-Kontext versehen
	 * Seiteneffekt auf $s !
	 */
	function wrapWithContext(&$s) {
		
		# wir brauchen den Layout-Context um den Typ des Layouts zu bestimmen und damit
		# das genaue Verhalten einer Reihe
		$p = $this->getLayoutContext();
		if (!$p) throw new \Exception('Row::wrapWithContext: no LayoutContext');
		
		if ($p->isTypeRowsfirst()) {
			
			# Eine Reihe ist hier eine Zeile mit voller Breite (die dann Zellen als Spalten enthält).
			$s = PHP_EOL.'<div data-row_id="'.$this->id.'" class="row gridrow '.$this->classes.'" style="'.$this->styles.'">'.PHP_EOL
				.$s
				.PHP_EOL.'</div>'.PHP_EOL;
			
		} else if ($p->isTypeColsfirst()) {
			
			# Eine Reihe ist hier eine Spalte mit entsprechender Breite ($this->size) (die dann Zellen als Zeilen enthält).
			$s = PHP_EOL.'<div data-row_id="'.$this->id.'" class="'.getSizeClass($this->size).' columns gridrow '.$this->classes.'" style="'.$this->styles.'">'.PHP_EOL
				.$s
				.PHP_EOL.'</div>'.PHP_EOL;
			
		}
	}
	
	/**
	 * HTML-Output Erzeugen,
	 * in dem die Zellen geladen und druchgegangen werden.
	 */
	function getOutput($lang = null, $default = true) {
		
		$fromCache = false;
		
		if (property_exists($this, 'directContent')) {
			# Wenn ein directContent gesetzt ist, wird nur dieser verwendet.
			# an dieser Stelle ist es dann nicht mehr Sprachabhängig.
			$result = $this->directContent;
			
			$this->wrapWithContext($result);
			
			# in diesem Fall wird nicht gecached
			
		} else {
			
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
				# sonst: Zellen abrufen und interieren
				$result = '';
				$cells = $this->getCells();
				if (is_array($cells)) {
					foreach ($cells as &$cell) {
						$result .= $cell->getOutput($lang, $default);
					}
				}
				
				$this->wrapWithContext($result);
				
				# cachen
				if ($useCache) {
					if ($this->writeCache($result, $lang)) {
						
					}
				}
			}
		}
		
		
		# für den erzeugten Code wird noch ein HTML-Kommentar zur besseren Kontrolle erzeugt
		$result = "\n\n<!--       Row ".($fromCache ? ' AUS CACHE ' : ' ON THE FLY ')." ID ".$this->id."\n–––––––––––––––––––––––––––––––––––––––––––––––– -->\n".$result;
		
		return $result;
	}
	
	/**
	 * Mit kompletten Context (also auch vom Parent-Layout) ummanteln.
	 */
	function wrapPreviewWithContext(&$s) {
		$this->wrapWithContext($s);
		$layout = $this->getLayoutContext();
		$layout->wrapPreviewWithContext($s);
	}
	
	/**
	 * Layout-Kontext liefern
	 * entweder Parent zurückliefern
	 * oder aus der DB holen (nur wenn $load=true)
	 */
	function &getLayoutContext($load = true) {
		if ($this->has_parent()) return $this->get_parent();
		
		if ($load) {
			try {
				$p = new \WEPPO\Grid\Layout($this->layout_id);
				$this->set_parent($p);
				return $p;
			} catch (\Exception $e) {
				return self::$_NULL;
			}
		} else {
			throw new \Exception("Row::getLayoutContext: no parent! not loading");
		}
	}
	
	/**
	 * Preview für den Editor
	 */
	function getPreviewOutput($lang) {
		if ($this->is_manual_mode()) return null;
		
		$context = $this->getLayoutContext();
		$result = $this->getOutput($lang);
		if ($context) {
			$context->wrapPreviewWithContext($result);
		}
		return $result;
	}
	
	
	
}