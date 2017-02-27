<?php
//_o($blogpost);
?>
<script>
	var blogpost_id = <?= $blogpost->id ?>;
	var blogpost_name = "<?= $blogpost->name ?>";
	var lang = "<?= $lang ?>";
	
	
	
	
	
	
	$(document).ready(function() {
		
		
		initHTMLEditor('#blogpostText');
		
		
		
		updatePreview();
		
		
		$('#paramList').delegate('a', 'click', function() {
			$.getJSON('/editor/param/select?content_id='+blogpost_id+'&param_name='+$(this).data('param_name')+'&lang='+lang, function(d) {
				if (ajaxResultPreprocess(d)) {
					$('#paramName').val(d.name);
					$('#paramValue').val(d.value);
				}
				//$('#paramID').val(d.id);
			});
		});
		
		
		/*
		$('#templateSelection').change(function(){
			var selection = $(this).val();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/content/template-change?content_name='+content_name+'&template_name='+selection,
				success: function(d) {
					$('#templateEditor').val(d.templateCode);
					updatePreview();
				}
			});
		});
		*/
		
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
				url: '/editor/blogpost/save?blogpost_id='+blogpost_id,
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						if (typeof d.select_lang != 'undefined') {
							window.location.href = '/editor/?blogpost_id='+blogpost_id+'&lang='+d.select_lang;
							return;
						}
						$('#languageSelection').val(lang);
					}
					updatePreview();
				}
			});
		});
		
		
		$('#languageSelection').change(function(){
			var selection = $(this).val();
			window.location.href = '/editor/?blogpost_id='+blogpost_id+'&lang='+selection;
		});
		
		
		
	});
	
	function saveBlogpost(ev) {
		LFB.startLoading(ev.target);
		
		var data = {
			name: $('#contentName').val(),
			template: $('#templateSelection').val(),
			title: $('#blogpostTitle').val(),
			intro: $('#blogpostIntro').val(),
			picture: $('#blogpostPicture').val(),
			text: HTMLEditor.getContent({format: 'raw'}), //$('#blogpostText').val(),
			tags: $('#blogpostTags').val(),
			date: $('#blogpostDate').val(),
			author: $('#blogpostAuthor').val(),
			visible: $('#blogpostVisible').is(':checked'),
		};
		
		
		$.ajax({
			dataType: "json",
			method: 'post',
			data: data,
			url: '/editor/blogpost/save?blogpost_id='+blogpost_id+'&lang='+lang,
			success: function(d) {
				if (ajaxResultPreprocess(d)) {
					updatePreview();
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
		var value = $('#paramValue').val();
		
		if (name) {
			LFB.startLoading(ev.target);
			
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/param/save?content_id='+blogpost_id+'&param_name='+name+'&lang='+lang,
				data: {value:value},
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
		$('#paramValue').val('');
	}
	
	function deleteParam(ev) {
		var name = $('#paramName').val();
		if (name && confirm("Parameter " + name + " entfernen?")) {
			LFB.startLoading(ev.target);
			
			clearParam();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/param/delete?content_id='+blogpost_id+'&param_name='+name+'&lang='+lang,
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
	
	function copyParams(ev) {
		
		var from_lang = $('#copyParamsSelection').val();
		if (from_lang!='' && from_lang != lang) {
			LFB.startLoading(ev.target);
			//clearParam();
			$.ajax({
				dataType: "json",
				method: 'get',
				url: '/editor/blogpost/copy-params?blogpost_id='+blogpost_id+'&from_lang='+from_lang+'&lang='+lang,
				success: function(d) {
					LFB.endLoading();
					if (ajaxResultPreprocess(d)) {
						
						// reload
						window.location.href = '/editor/?blogpost_id='+blogpost_id+'&lang='+lang;
						
						/*if (typeof d.paramList != 'undefined') {
							$('#paramList').empty().append(d.paramList);
						}*/
						updatePreview();
					}
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
		<h3>Blog Post</h3>
		
		<label for="languageSelection">Sprache wechseln</label>
		<select class="" id="languageSelection" <?= $blogpost->noTranslation ? 'disabled' : '' ?> >
			<?php foreach (\i18n\i18n::$availableLanguages as $k => $v) : $selected = $k == $lang; ?>
			<option value="<?= htmlentities($k) ?>" <?= $selected ? 'selected' : ''?> ><?= htmlentities($v) ?></option>
			<?php endforeach; ?>
		</select>
		
		<label class="inline"><input id="noTransCheck" type="checkbox" <?= $blogpost->noTranslation ? 'checked' : '' ?> /><span class="label-body">Keine Übersetzungen</span></label>
		
		<br/>
		
		<a href="/editor/">&lt;&lt; Zurück zur <strong>Übersicht</strong></a>
	</div>
	<div class="five columns">
		<label>Name</label>
		<input class="u-full-width code" type="text" id="contentName" value="<?= htmlentities($blogpost->name) ?>"/>
		<label for="templateSelection">Template</label>
		<select class="u-full-width code" id="templateSelection">
			<option value="" >- Template wählen -</option>
			<?php $templates = $this->get('templates');
			foreach ($templates as &$t) : $selected = $t->short_name==$blogpost->template;?>
				<option value="<?= htmlentities($t->short_name) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlentities($t->short_name) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="two columns">
		<label>&nbsp;</label>
		<input class="button-primary" type="button" value="Speichern" onclick="saveBlogpost(event)"/>
	</div>
</div>
<!-- editor -->
<?php
	$posttemplate = $blogpost->getTemplate($lang);
	$title = $posttemplate->get('title', false);
	$intro = $posttemplate->get('intro', false);
	$text = $posttemplate->get('text', false);
	$picture = $posttemplate->get('picture', false);
	
	/*
	 * - Dinge die unabhängig von der sprache sind sollten eig. in metadaten sein
	 * - dinge, die für ein bestimmtes template nur verwendet werden, sollten als parameter umgesetzt werden
	 * */
?>
<div id="editor" class="row">
	<div class="four columns">
		
		<h5><small style="font-weight:normal;">sprachen<u>abhängig</u></small></h5>
		
		<?php if (!$blogpost->noTranslation) : ?>
			<select class="small" id="copyParamsSelection">
				<option value="">- Sprache -</option>
				<?php foreach (\i18n\i18n::$availableLanguages as $k => $v) : ?>
				<option value="<?= htmlentities($k) ?>"><?= htmlentities($v) ?></option>
				<?php endforeach; ?>
			</select>
			<input class="small" type="button" value="Werte kopieren" onclick="copyParams(event);" />
		<?php endif; ?>
		
		<label for="blogpostTitle">Title</label>
		<input class="u-full-width" style="" type="text" id="blogpostTitle" value="<?= htmlentities($title) ?>"/>
		
		<label for="blogpostPicture">Post-Bild</label>
		<input class="u-full-width" style="" type="text" id="blogpostPicture" value="<?= htmlentities($picture) ?>"/>
		
		<label for="blogpostTags">Tags</label>
		<input class="u-full-width" style="" type="text" id="blogpostTags" value="<?= htmlentities(implode(', ', $blogpost->tags)) ?>"/>
		
		<h5>Meta <small style="font-weight:normal;">sprachen<u>un</u>abhängig</small></h5>
		
		<label for="blogpostAuthor">Autor</label>
		<input class="u-full-width" style="" type="text" id="blogpostAuthor" value="<?= htmlentities($blogpost->author) ?>"/>
		
		<label for="blogpostDate">Datum</label>
		<input class="u-full-width" style="" type="text" id="blogpostDate" value="<?= htmlentities($blogpost->date->format('Y-m-d')) ?>"/>
		
		<label for="blogpostVisible"><input id="blogpostVisible" type="checkbox" <?=$blogpost->visible ? 'checked' : '' ?>  /><span> Sichtbar</span></label>
		
		
	</div>
	<div class="eight columns">
		
		<div class="container">
			<label for="blogpostIntro">Intro</label>
			<textarea class="u-full-width" style="" id="blogpostIntro" ><?= htmlentities($intro) ?></textarea>
			
			<label for="blogpostText">Text</label>
			<textarea class="u-full-width code smaller" style="min-height:300px;" id="blogpostText" ><?= htmlentities($text) ?></textarea>
			<input class="button-primary u-pull-right" type="button" value="Speichern" onclick="saveBlogpost(event)"/>
		</div>
		
		<hr/>
		
		
		
		
		<div class="container closable">
			<a>Weitere Parameter</a>
			<div class="closable-inner">
				<div style="float:left;">
					<label class="inline">Parameter</label>
				</div>
				<div style="float:right;">
					<input class="small" type="button" value="Löschen" onclick="deleteParam(event)">
					<input class="small" type="button" value="Leeren" onclick="clearParam(event)">
					<input class="small button-primary" type="button" value="Speichern" onclick="saveParam(event)"/>
				</div>
				<div class="u-cf"></div>
				<div id="paramList" style="padding:5px;width:200px;outline:solid 1px black; float:left; overflow-y:scroll; min-height:300px;">
					<?php
					$params = $blogpost->getParams($lang);
					foreach ($params as &$p) {
						if (in_array($p->name, \WEPPO\Grid\BlogPost::$managedParams)) continue;
						echo '<a data-param_name="'.htmlentities($p->name).'" data-param_id="'.htmlentities($p->id).'">', htmlentities($p->name), '</a><br/>';
					}
					?>
				</div>
				<div style="overflow:hidden;padding:5px;">
					<input type="hidden" id="paramID" value=""/>
					<input class="u-full-width code smaller" type="text" placeholder="Name" id="paramName" value=""/>
					<textarea class="u-full-width code smaller" placeholder="Wert" id="paramValue" style="min-height:200px;"></textarea>
				</div>
			</div>
		</div>
		
		
	</div>
</div>





