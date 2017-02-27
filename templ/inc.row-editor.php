<style>
	.gridcell {
		min-height:20px;
		min-width:20px;
	}
	.gridcell:hover {
		-webkit-box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		-moz-box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		box-shadow: 0px 0px 14px 0px rgba(255,205,5,0.6);
		cursor:pointer;
	}
	.gridcell.editor_selected {
		-webkit-box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
		-moz-box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
		box-shadow: 0px 0px 18px 0px rgba(255,205,5,1);
	}
</style>
<script>
	var row_id = <?= $row->id ?>;
	
	$(document).ready(function() {
		
		updatePreview();
		
		$('#editor_preview').delegate('.gridcell', 'click', function() {
			var $this = $(this);
			selectCell($this.data('cell_id'));
			return false;
		});
		
	});
	
	function saveRow() {
	}
	
	//function updateEditContentLink() {
	//	var content_id = $('#cellContent').val();
	//	var cell_id = $('#cellID').val();
	//	$('#cellEditContentLink').attr('href', '/editor/?content_id='+content_id+'&cell_id='+cell_id);
	//}
	
	function selectCell(id) {
		
		$.getJSON('/editor/cell/select?cell_id='+id, function(d) {
			if (ajaxResultPreprocess(d)) {
				
				var prev = $('#editor_preview .gridcell.editor_selected');
				prev.removeClass('editor_selected');
				var $cell = $('#editor_preview .gridcell[data-cell_id='+id+']');
				$cell.addClass('editor_selected');
				
				
				$('#cellClasses').val(d.classes);
				$('#cellStyles').val(d.styles);
				$('#cellOptions').val(JSON.stringify(d.options));
				$('#cellClass').val(d.class);
				$('#cellSize').val(d.size);
				$('#cellCache').prop('checked', d.useCache);
				$('#cellID').val(d.id);
				$('#cellContent').val(d.content_id);
				$('#cellEditor').show('slide');
			}
		});
	}
	
	function saveCell(ev) {
		var classes = $('#cellClasses').val();
		var styles = $('#cellStyles').val();
		var options = $('#cellOptions').val();
		var Class = $('#cellClass').val();
		var size = $('#cellSize').val();
		var cache = $('#cellCache').prop('checked');
		var cell_id = $('#cellID').val();
		var content_id = $('#cellContent').val();
		
		
		if (cell_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/cell/save?cell_id='+cell_id+'&row_id='+row_id,
				data: {
					classes:classes,
					styles:styles,
					options:options,
					'class':Class,
					size:size,
					content_id:content_id,
					cache: cache ? 1 : 0,
				},
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							//if (typeof d.edit_content != 'undefined' && confirm('Neuen Inhalt bearbeiten?')) {
							//	window.location.href= '/editor/?content_id='+d.edit_content+'&cell_id='+d.in_cell;
							//} else {
								selectCell(d.select_cell);
							//}
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
	
	function cancelCell() {
		var prev = $('#editor_preview .gridcell.editor_selected');
		prev.removeClass('editor_selected');
		
		$('#cellClasses').val('');
		$('#cellStyles').val('');
		$('#cellOptions').val('');
		$('#cellClass').val('');
		$('#cellSize').val('');
		$('#cellCache').prop('checked', false);
		$('#cellID').val('');
		$('#cellEditor').hide('fast');
	}
	
	function addCell(ev) {
		LFB.startLoading(ev.target);
		$.ajax({
			dataType: "json",
			method: 'get',
			url: '/editor/cell/add?row_id='+row_id,
			success: function(d) {
				if (ajaxResultPreprocess(d)) {
					updatePreview(function() {
						if (typeof d.select_cell != 'undefined') {
							selectCell(d.select_cell);
						} else if (typeof d.error != 'undefined') {
							alert(d.error);
						}
						LFB.endLoading();
					});
				} else {
					LFB.endLoading();
				}
			},
			error: function(d) {
				LFB.endLoading();
			}
		});
	}
	
	function deleteCell(ev) {
		if (confirm("Zelle löschen?")) {
			var cell_id = $('#cellID').val();
			if (cell_id) {
				LFB.startLoading(ev.target);
				$.ajax({
					dataType: "json",
					method: 'get',
					url: '/editor/cell/delete?cell_id='+cell_id,
					success: function(d) {
						if (ajaxResultPreprocess(d)) {
							cancelCell();
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
	
	
	function leftCell(ev) {
		var cell_id		= $('#cellID').val();
		if (cell_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/cell/move?direction=left&cell_id='+cell_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							selectCell(d.select_cell);
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
	
	function rightCell(ev) {
		var cell_id		= $('#cellID').val();
		if (cell_id) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/cell/move?direction=right&cell_id='+cell_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						updatePreview(function() {
							selectCell(d.select_cell);
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
	
	
	
	function editContent() {
		//if (confirm("Zellen dieser Reihe bearbeiten?")) {
			var cell_id = parseInt($('#cellID').val());
			var content_id = parseInt($('#cellContent').val());
			
			if (cell_id && cell_id > 0 && content_id) {
				// Content-Editor via ID im Zell-Kontext aufrufen
				window.location.href= '/editor/?content_id='+content_id+'&cell_id='+cell_id;
			} else {
				var name = prompt("Kein Inhalt definiert. Neuen Inhalt erzeugen?", 'Neuer Inhalt');
				if (name) {
					if (cell_id > 0) {
						$.ajax({
							dataType: "json",
							method: 'post',
							data: {name:name},
							url: '/editor/content/add?for_cell_id='+cell_id,
							success: function(d) {
								if (ajaxResultPreprocess(d)) {
									window.location.href= '/editor/?content_id='+d.select_content+'&cell_id='+cell_id;
								}
							}
						});
					}
				}
			}
		//}
	}
	
</script>

<div class="row editor-top">
	<div class="six columns">
		<h3 style="">
			Reihe
		</h3>
		<a href="/editor/?layout_id=<?= $layout->id ?>">&lt;&lt; Zurück zum Layout <strong>»<?= htmlentities($layout->name) ?>«</strong></a>
	</div>
	<div class="six columns">
	</div>
</div>



<big>Zelle <input class="plus icon button" type="button" value="" onclick="addCell(event)"/></big>

<div id="editor">
	<div id="cellEditor" style="display:none;">
		<input type="hidden" id="cellID" value=""/>
		<div class="row">
			<div class="two columns">
				<label for="cellClasses">CSS-Klassen</label>
				<textarea class="u-full-width code smaller" type="text" placeholder="Klassen" id="cellClasses"></textarea>
			</div>
			<div class="two columns">
				<label for="cellStyles">CSS-Styles</label>
				<textarea class="u-full-width code smaller" type="text" placeholder="CSS" id="cellStyles"></textarea>
			</div>
			<div class="three columns">
				<label><input type="checkbox" id="cellCache" value="1" /> Cache verwenden</label>
				<label for="cellClass">Zellen-Klasse</label>
				<input class="u-full-width code smaller" type="text" id="cellClass" value="" />
				<div class="closable">
					<a>Optionen</a>
					<div class="closable-inner">
					<textarea class="u-full-width code smaller" type="text" placeholder="Optionen" id="cellOptions"></textarea>
					</div>
				</div>
			</div>
			<div class="three columns">
				<div style="float:left;width:80%;">
					<label for="cellSize">Inhalt <!--<a id="cellEditContentLink"href="">Inhalt bearbeiten</a>--></label>
					<select class="u-full-width smaller" id="cellContent">
						<option value="">- Kein Inhalt -</option>
						<?php $contents = $this->get('contents');
						foreach ($contents as &$c) : ?>
							<option value="<?= htmlentities($c->id) ?>"><?= $c->getCategoryName(true) ?> <?= htmlentities($c->name) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div style="float:right;width:20%;">
					<label for="">&nbsp;</label>
					<input class="button u-full-width" style="padding:0 0;" type="button" value="&gt;&gt;" onclick="editContent()"/>
				</div>
				
				<?php
				$p = $row->getLayoutContext();
				if ($p && $p->isTypeRowsfirst()) :
				?>
					<div style="float:left;width:60%;">
						<label for="cellSize">Größe</label>
						<select class="u-full-width code smaller" id="cellSize">
							<?php for ($i=1; $i<=12; $i++) : ?>
								<option value="<?= $i ?>"><?= $i ?>/12</option>
							<?php endfor; ?>
						</select>
					</div>
					<div style="float:right;width:40%;">
						<label for="">&nbsp;</label>
						<input class="left icon button" type="button" value="" onclick="leftCell(event)"/>
						<input class="right icon button" type="button" value="" onclick="rightCell(event)"/>
					</div>
				<?php elseif ($p && $p->isTypeColsfirst()) : ?>
					<div style="clear:both;">
						<input class="up icon button" type="button" value="" onclick="leftCell(event)"/><br/>
						<input class="down icon button" type="button" value="" onclick="rightCell(event)"/>
					</div>
				<?php endif; ?>
				
				
				
				
				
			</div>
			<div class="two columns">
				<input class="button-primary" type="button" value="Speichern" onclick="saveCell(event)"/>
				<input class="button" type="button" value="Löschen" onclick="deleteCell(event)"/>
				<input class="button" type="button" value="Abbrechen" onclick="cancelCell()"/>
			</div>
		</div>
	</div>
</div>
