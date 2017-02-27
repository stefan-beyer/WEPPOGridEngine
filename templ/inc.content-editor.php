<script>
	var content_id = <?= $content->id ?>;
	//var content_name = "<?= $content->name ?>";
	var cell_id = <?= $cell ? $cell->id : 'null' ?>;
	var lang = "<?= $lang ?>";
	
	
	
	$(document).ready(function() {
		
		ParamEditors.initAll();
		
		
		updatePreview();
		
		
		$('#paramList').delegate('a', 'click', function() {
			$.getJSON('/editor/param/select?content_id='+content_id+'&param_name='+$(this).data('param_name')+'&lang='+lang, function(d) {
				if (ajaxResultPreprocess(d)) {
					$('#paramName').val(d.name);
					
					$('#paramTypeSelect').val(d.type);
					ParamEditors.show();
					ParamEditors.setContent(d.value);
				}
			});
		});
		
		$('#paramTypeSelect').change(function(){
			var selection = $(this).val();
			//console.log(selection);
			// Warum funktioniert das nicht: ?
			/*if (selection == 'text') {
				HTMLEditor.setMode('code');
			} else if (selection == 'html') {
				HTMLEditor.setMode('design');
			} else {
				HTMLEditor.setMode('readonly');
			}*/
			ParamEditors.show(selection);
		});
		
		
		$('#templateSelection').change(function(){
			var selection = $(this).val();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/content/template-change?content_id='+content_id+'&template_name='+selection,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						$('#templateEditor').val(d.templateCode);
						updatePreview();
					}
				}
			});
		});
		
		$('#noTransCheck').click(function () {
			var ch = $('#noTransCheck').prop('checked');
			
			
			if (ch) {
				if (!confirm('Durch diese Aktion werden nur die Parameter der Dfault-Sprache beibehalten. Fortfahren?')) {
					$('#noTransCheck').prop('checked', false);
					return;
				}
			}
			
			$('#languageSelection').prop('disabled', ch);
			
			$.ajax({
				dataType: "json",
				method: 'post',
				data: {noTranslation: ch ? 1 : 0},
				url: '/editor/content/save?content_id='+content_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						if (typeof d.select_lang != 'undefined') {
							window.location.href = '/editor/?content_id='+content_id+'&lang='+d.select_lang+'&cell_id='+(cell_id ? cell_id : '');
							return;
						}
						$('#languageSelection').val(lang);
						updatePreview();
					}
				}
			});
		});
		
		$('#languageSelection').change(function(){
			var selection = $(this).val();
			window.location.href = '/editor/?content_id='+content_id+'&lang='+selection+'&cell_id='+(cell_id ? cell_id : '');
		});
		
		
	});
	
	function saveName(ev) {
		var name = $('#contentName').val();
		LFB.startLoading(ev.target);
		$.ajax({
			dataType: "json",
			method: 'post',
			data: {name: name},
			url: '/editor/content/save?content_id='+content_id,
			success: function(d) {
				if (ajaxResultPreprocess(d)) {
					
				}
				LFB.endLoading();
			},
			error: function(d) {
				LFB.endLoading();
			}
		});
	}
	
	
	function saveParam(ev) {
		var name = $('#paramName').val();
		var type = $('#paramTypeSelect').val();
		var value = ParamEditors.getContent();
		
		if (name && type) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/param/save?content_id='+content_id+'&param_name='+name+'&lang='+lang,
				data: {value:value, type:type},
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
					}
					if (typeof d.paramList != 'undefined') {
						$('#paramList').empty().append(d.paramList);
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
	
	function clearParam() {
		$('#paramName').val('');
		$('#paramTypeSelect').val('');
		ParamEditors.clearAll();
		ParamEditors.hideAll();
		
		//$('#paramValue').val('');
		//Editor.setContent('');
		//$('#paramID').val('');
	}
	
	function deleteParam(ev) {
		var name = $('#paramName').val();
		if (name && confirm("Parameter " + name + " entfernen?")) {
			LFB.startLoading(ev.target);
			clearParam();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/param/delete?content_id='+content_id+'&param_name='+name+'&lang='+lang,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
					}
					if (typeof d.paramList != 'undefined') {
						$('#paramList').empty().append(d.paramList);
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
	
	function editTemplate() {
		var name = $('#templateSelection').val();
		if (name) {
			window.open('/editor/?template_name=' + name);
			//window.location.href = '/editor/?template_name=' + name;
		}
	}
	
	function copyParams(ev) {
		
		var from_lang = $('#copyParamsSelection').val();
		if (from_lang!='' && from_lang != lang) {
			LFB.startLoading(ev.target);
			clearParam();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/param/copy?content_id='+content_id+'&from_lang='+from_lang+'&lang='+lang,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
					}
					if (typeof d.paramList != 'undefined') {
						$('#paramList').empty().append(d.paramList);
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
	
</script>
<div class="row editor-top">
	<div class="five columns">
		<h3>Inhalt bearbeiten</h3>
		
		<label for="languageSelection">Sprache wechseln</label>
		<select class="" id="languageSelection" <?= $content->noTranslation ? 'disabled' : '' ?> >
			<?php foreach (\i18n\i18n::$availableLanguages as $k => $v) : $selected = $k == $lang; ?>
			<option value="<?= htmlentities($k) ?>" <?= $selected ? 'selected' : ''?> ><?= htmlentities($v) ?></option>
			<?php endforeach; ?>
		</select>
		
		<label class="inline"><input id="noTransCheck" type="checkbox" <?= $content->noTranslation ? 'checked' : '' ?> /><span class="label-body">Keine Übersetzungen</span></label>
		
		<br/>
		
		<?php 
		$row = $cell ? $cell->getRowContext() : null;
		$layout = $row ? $row->getLayoutContext() : null; 
		if ($cell && $row && $layout) :?>
			<a href="/editor/?row_id=<?= $row->id ?>">&lt;&lt; Zurück zur <strong>Reihe</strong> im Layout »<?= htmlentities($layout->name) ?>«</a>
		<?php else : ?>
			<a href="/editor/">&lt;&lt; Zurück zur <strong>Übersicht</strong></a>
		<?php endif; ?>
	</div>
	<div class="five columns">
		<label>Name</label>
		<input class="u-full-width code" style="font-size:150%;" type="text" id="contentName" value="<?= htmlentities($content->name) ?>"/>
		<label for="templateSelection">Template</label>
		<select class="u-full-width code" id="templateSelection">
			<option value="" >- Template wählen -</option>
			<?php $templates = $this->get('templates');
			foreach ($templates as &$t) : $selected = $t->short_name==$content->template;?>
				<option value="<?= htmlentities($t->short_name) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlentities($t->short_name) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="two columns">
		<label>&nbsp;</label>
		<input class="button-primary" type="button" value="Speichern" onclick="saveName(event)"/>
	</div>
</div>
<!-- editor -->
<div id="editor" class="">
	<div class="row">
		
		<div style="float:left;">
			<label class="inline">Parameter</label>
			
			<?php if (!$content->noTranslation) : ?>
				<select class="small" id="copyParamsSelection">
					<option value="">- Sprache -</option>
					<?php foreach (\i18n\i18n::$availableLanguages as $k => $v) : ?>
					<option value="<?= htmlentities($k) ?>"><?= htmlentities($v) ?></option>
					<?php endforeach; ?>
				</select>
				<input class="small" type="button" value="Parameter kopieren" id="copyParams" onclick="copyParams(event);" />
			<?php endif; ?>
			
		</div>
		<div style="float:right;">
			<input type="hidden" id="paramID" value=""/>
			<input class="code smaller small" type="text" placeholder="Name" id="paramName" value=""/>
			<select id="paramTypeSelect" class="small">
				<option value=""></option>
				<option value="text">Text</option>
				<option value="html">HTML</option>
			</select>
			
			<input class="small" type="button" value="Löschen" onclick="deleteParam(event)">
			<input class="small" type="button" value="Leeren" onclick="clearParam()">
			<input class="small button-primary" type="button" value="Speichern" onclick="saveParam(event)"/>
		</div>
		<div class="u-cf"></div>
		
		<div id="paramList" style="padding:5px;width:200px;outline:solid 1px black; float:left; overflow-y:scroll; min-height:300px;">
			<?php
			$params = $content->getParams($lang);
			foreach ($params as &$p) {
				echo '<a data-param_name="'.htmlentities($p->name).'" data-param_id="'.htmlentities($p->id).'">', htmlentities($p->name), '</a><br/>';
			}
			?>
		</div>
		<div style="overflow:hidden;padding:0px;">
			<!--<textarea class="u-full-width code smaller" placeholder="Wert" id="paramTextValue" style="display:none;min-height:300px;"></textarea>-->
			<textarea class="u-full-width code smaller" placeholder="Wert"              id="paramValue"     style="display:none;min-height:300px;"></textarea>
		</div>
	</div>
	
	
	
	
	<div class="closable" class="row">
		<a>Vorlage anzeigen</a>
		<div class="closable-inner">
			<div style="float:left;"><label for="templateEditor">Vorlage</label></div>
			<div style="float:right;">
				<input class="button" type="button" value="Bearbeiten &gt;&gt;" onclick="editTemplate()"/>
			</div>
			<div class="u-cf"></div>
			<textarea disabled="disabled" class="u-full-width code smaller" placeholder="Template..." id="templateEditor" style="min-height:300px;"><?= htmlentities($content->getTemplateContent()) ?></textarea>
		</div>
	</div>
	
</div>
