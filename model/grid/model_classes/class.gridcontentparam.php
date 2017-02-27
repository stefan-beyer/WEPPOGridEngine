<?php
namespace WEPPO\Grid;


class ContentParam extends \WEPPO\Model\TableRecord {
	use HasMode;
	use HasParent;
	
	static function getTablename() {
		return 'gridcontentparams';
	}
	
	public function __construct($id = 0, $cols = '*') {
		parent::__construct($id, $cols);
		$this->db_mode();
	}
	
	
	static function create($name, $content_id, $lang) {
		static::where('name', $name);
		static::where('content_id', $content_id);
		static::where('lang', $lang);
		return static::getOne();
	}
	
	
	
	function invalidate() {
		try {
			$c = new \WEPPO\Grid\Content($this->content_id);
			$c->invalidate($this->lang);
		} catch (\Exception $e) {
			
		}
	}
	
}