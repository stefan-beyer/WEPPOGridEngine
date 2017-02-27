<style>
	.gridrow:hover {
		-webkit-box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		-moz-box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		cursor:pointer;
	}
	.gridrow.editor_selected {
		-webkit-box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
		-moz-box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
		box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
	}
</style>
<script>
	var layout_id = <?= $layout->id ?>;
	var layout_type = "<?= $layout->getType() ?>";
	
	$(document).ready(function() {
		updatePreview();
		
		$('#editor_preview').delegate('.gridrow', 'click', function() {
			var $this = $(this);
			selectRow($this.data('row_id'));
		});
		
	});
	
	function selectRow(id) {
		var prev = $('#editor_preview .gridrow.editor_selected');
		prev.removeClass('editor_selected');
		
		var $row = $('#editor_preview .gridrow[data-row_id='+id+']');
		$row.addClass('editor_selected');
		$.getJSON('/editor/row/select?row_id='+id, function(d) {
			if (!ajaxResultPreprocess(d)) return;
			
			console.log(d);
			$('#rowClasses').val(d.classes);
			$('#rowClass').val(d.class);
			$('#rowStyles').val(d.styles);
			$('#rowOptions').val(JSON.stringify(d.options));
			$('#rowID').val(d.id);
			$('#rowCache').prop('checked', d.useCache);
			$('#cellSize').val(d.size);
			
			$('#rowEditor').show('slide');
			
		});
	}
	
	function addRow(ev) {
		LFB.startLoading(ev.target);
		$.ajax({
			dataType: "json",
			method: 'get',
			url: '/editor/row/add?layout_id='+layout_id,
			success: function(d) {
				if (ajaxResultPreprocess(d)) {
					updatePreview(function() {
						selectRow(d.select_row);
					});
				}
				LFB.endLoading();
			},
			error: function(d) {
				LFB.endLoading();
			}
		});
	}
	
	function saveRow(ev) {
		var classes		= $('#rowClasses').val();
		var styles		= $('#rowStyles').val();
		var options		= $('#rowOptions').val();
		var Class		= $('#rowClass').val();
		var size 		= $('#cellSize').val();
		var cache 		= $('#rowCache').prop('checked');
		var row_id		= $('#rowID').val();
		
		if (row_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/row/save?row_id='+row_id,
				data: {
					classes:classes,
					styles:styles,
					options:options,
					'class':Class,
					size:size,
					cache:cache ? 1 : 0,
				},
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							selectRow(d.select_row);
						});
					}
					LFB.endLoading();
				},
				error: function(d) {
					LFB.endLoading();
				}
			});
		}
		
	}
	
	function cancelRow() {
		var prev = $('#editor_preview .gridrow.editor_selected');
		prev.removeClass('editor_selected');
		
		$('#rowClasses').val('');
		$('#rowStyles').val('');
		$('#rowOptions').val('');
		$('#rowClass').val('');
		$('#rowID').val('');
		$('#rowCache').prop('checked', false);
		$('#rowEditor').hide('fast');
	}
	
	function deleteRow(ev) {
		if (confirm("Reihe löschen?")) {
			LFB.startLoading(ev.target);
			var row_id = $('#rowID').val();
			if (row_id) {
				$.ajax({
					dataType: "json",
					method: 'get',
					url: '/editor/row/delete?row_id='+row_id,
					success: function(d) {
						if (ajaxResultPreprocess(d)) {
							cancelRow();
							updatePreview();
						}
						LFB.endLoading();
					},
					error: function(d) {
						LFB.endLoading();
					}
				});
			}
		}
	}
	
	function downRow(ev) {
		var row_id		= $('#rowID').val();
		if (row_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/row/move?direction=down&row_id='+row_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							selectRow(d.select_row);
						});
					}
					LFB.endLoading();
				},
				error: function(d) {
					LFB.endLoading();
				}
			});
		}
	}
	
	function upRow(ev) {
		var row_id		= $('#rowID').val();
		if (row_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/row/move?direction=up&row_id='+row_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							selectRow(d.select_row);
						});
					}
					LFB.endLoading();
				},
				error: function(d) {
					LFB.endLoading();
				}
			});
		}
	}
	
	function editCells() {
		//if (confirm("Zellen dieser Reihe bearbeiten?")) {
			var row_id = $('#rowID').val();
			if (row_id) {
				window.location.href= '/editor/?row_id='+row_id;
			}
		//}
	}
	
	function saveLayout(ev) {
		var name = $('#layoutName').val();
		var options = $('#layoutOptions').val();
		var type = $('#layoutType').val();
		var cache = $('#layoutCache').prop('checked');
		
		if (name) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/layout/save?layout_id='+layout_id,
				data: {
					name:name,
					options:options,
					type:type,
					cache:cache ? 1 : 0
				},
				success: function(d) {
					LFB.endLoading();
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/?layout_id=' + d.select_layout;
					}
				},
				error: function(d) {
					LFB.endLoading();
				}
			});
		} else {
			alert("Name darf nicht leer sein.");
		}
	}
	
</script>
<div class="row editor-top">
	<div class="three columns">
		<h3 style="">
			Layout
		</h3>
		<a href="/editor/">&lt;&lt; Zurück zur Übersicht</a>
	</div>
	<div class="three columns">
		<label for="layoutName">Name</label>
		<input class="u-full-width code" style="" type="text" id="layoutName" value="<?= htmlentities($layout->name) ?>"/>
	</div>
	<div class="two columns">
		<label for="layoutType">Typ</label>
		<select class="u-full-width code smaller" id="layoutType">
			<?php foreach (array('rowsfirst'=>'Zeilen-Layout', 'colsfirst'=>'Spalten-Layout') as $k=>$v) : ?>
				<option value="<?= $k ?>" <?= $k == $layout->getType() ? 'selected' : '' ?>><?= $v ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="two columns">
		<label for="layoutOptions">Optionen</label>
		<input class="u-full-width code smaller" type="text" id="layoutOptions" value="<?= htmlentities(json_encode($layout->options)) ?>"/>
		<label><input type="checkbox" id="layoutCache" value="1" <?= $layout->useCache ? 'checked' : '' ?>/> Cache verwenden</label>
	</div>
	<div class="two columns">
		<label>&nbsp;</label>
		<input class="button-primary" type="button" value="Speichern" onclick="saveLayout(event)"/>
	</div>
</div>



<big>Reihe <input class="plus icon button" type="button" value="" onclick="addRow(event)"/></big>

<div id="rowEditor" style="display:none;">
	<input type="hidden" id="rowID" value=""/>
	<div class="row">
		<div class="two columns">
			<label for="rowClasses">CSS-Klassen</label>
			<textarea class="u-full-width code smaller" type="text" placeholder="Klassen" id="rowClasses"></textarea>
		</div>
		<div class="<?= $layout->isTypeColsfirst() ? 'two' : 'three' ?> columns">
			<label for="rowStyles">CSS-Styles</label>
			<textarea class="u-full-width code smaller" type="text" placeholder="CSS" id="rowStyles"></textarea>
		</div>
		<div class="three columns">
			<label><input type="checkbox" id="rowCache" value="1" /> Cache verwenden</label>
			<label for="rowClass">Reihen-Klasse</label>
			<input class="u-full-width code smaller" type="text" id="rowClass" value="" />
			<div class="closable">
				<a>Optionen</a>
				<div class="closable-inner">
					<textarea class="u-full-width code smaller" type="text" placeholder="Optionen" id="rowOptions"></textarea>
				</div>
			</div>
		</div>
		<div class="<?= $layout->isTypeColsfirst() ? 'two' : 'one' ?> columns">
			<?php if ($layout->isTypeRowsfirst()) : ?>
				<input class="up icon button" type="button" value="" onclick="upRow(event)"/><br/>
				<input class="down icon button" type="button" value="" onclick="downRow(event)"/>
			<?php elseif ($layout->isTypeColsfirst()) : ?>
				<label for="cellSize">Größe</label>
				<select class="u-full-width code smaller" id="cellSize">
					<?php for ($i=1; $i<=12; $i++) : ?>
						<option value="<?= $i ?>"><?= $i ?>/12</option>
					<?php endfor; ?>
				</select>
				<input class="left icon button" type="button" value="" onclick="upRow(event)"/>
				<input class="right icon button" type="button" value="" onclick="downRow(event)"/>
			<?php endif; ?>
		</div>
		<div class="three columns">
			<input class="button-primary" type="button" value="Speichern" onclick="saveRow(event)"/>
			<input class="button" type="button" value="Löschen" onclick="deleteRow(event)"/>
			<input class="button" type="button" value="Abbrechen" onclick="cancelRow()"/>
			<input class="button" type="button" value="Zellen &gt;&gt;" onclick="editCells()"/>
		</div>
		</div>
	</div>
</div>


<div class="small smaller"><em>Die Vorschau enthält nur die Namen der Inhalte.</em></div>
