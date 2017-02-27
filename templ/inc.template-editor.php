<script>
	function saveTemplate(ev) {
		var name = $('#templateName').val();
		var code = $('#templateCode').val();
		var orgname = $('#templateOrgName').val();
		
		if (name) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'post',
				url: '/editor/template/save?template_name='+orgname,
				data: {name:name,code:code},
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						if (typeof d.select_template != 'undefined' && d.select_template != orgname) {
							window.location.href = '/editor/?template_name='+d.select_template;
						}
					}
					LFB.endLoading();
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
	<div class="six columns">
		<h3 style="">
			Vorlage
		</h3>
		<a href="/editor/">&lt;&lt; Zurück zur Übersicht</a>
	</div>
	<div class="four columns">
		<input type="hidden" id="templateOrgName" value="<?= htmlentities($template->short_name) ?>" />
		<label for="templateName">Name</label>
		<input class="u-full-width code" style="font-size:150%;" type="text" id="templateName" value="<?= htmlentities($template->short_name) ?>"/>
	</div>
	<div class="two columns">
		<label>&nbsp;</label>
		<input class="button-primary" type="button" value="Speichern" onclick="saveTemplate(event)"/>
	</div>
</div>

<div class="row">
	<div class="twelve columns">
		<label for="templateCode">Code</label>
		<textarea class="u-full-width code smaller" type="text" placeholder="Template-Code" id="templateCode" style="min-height:300px;"><?= htmlentities($template->getCode()) ?></textarea>
	</div>
</div>
