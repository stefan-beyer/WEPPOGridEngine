<script>

	function addLayout() {
		var name = prompt("Name des neuen Layouts", "Neues Layout");
		if (name) {
			$.ajax({
				dataType: "json",
				method: 'post',
				data: {name: name},
				url: '/editor/layout/add',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/?layout_id='+d.select_layout;
					}
				}
			});
		}
	}
	
	function delete_layout(ev, id) {
		if (!id) return;
		
		if (confirm("Layout löschen?")) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				data: {layout_id: id},
				url: '/editor/layout/delete',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/';
					}
					LFB.endLoading();
				}
			});
		}
	}
	
	function addContent() {
		var name = prompt("Name des neuen Inhalts", "Neuer_Inhalt");
		if (name) {
			$.ajax({
				dataType: "json",
				method: 'post',
				data: {name: name},
				url: '/editor/content/add',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/?content_id='+d.select_content;
					}
				}
			});
		}
	}
	
	function delete_content(ev, id) {
		if (!id) return;
		
		if (confirm("Inhalt löschen?")) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				data: {content_id: id},
				url: '/editor/content/delete',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/';
					}
					LFB.endLoading();
				}
			});
		}
	}
	
	
	function addTemplate() {
		var name = prompt("Name der neuen Vorlage", "neues.template");
		if (name) {
			$.ajax({
				dataType: "json",
				method: 'post',
				data: {name: name},
				url: '/editor/template/add',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/?template_name='+d.select_template;
					}
				}
			});
		}
	}
	
	function delete_template(ev, name) {
		if (!id) return;
		
		if (confirm("Template löschen?")) {
			LFB.startLoading(ev.target);
			$.ajax({
				dataType: "json",
				method: 'get',
				data: {template_name: name},
				url: '/editor/template/delete',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/';
					}
					LFB.endLoading();
				}
			});
		}
	}
	
	function addBlogPost() {
		var name = prompt("Name des neuen Posts", "Neuer_Post");
		if (name) {
			$.ajax({
				dataType: "json",
				method: 'post',
				data: {name: name},
				url: '/editor/blogpost/add',
				success: function(d) {
					if (ajaxResultPreprocess(d)) {
						window.location.href= '/editor/?blogpost_id='+d.select_blogpost;
					}
				}
			});
		}
	}
	
	function wartung(ev, s) {
		LFB.startLoading(ev.target);
		$.ajax({
			dataType: "json",
			method: 'post',
			data: {wartung: s?1:0},
			url: '/editor/sys/wartung',
			success: function(d) {
				if (ajaxResultPreprocess(d)) {
					if (d.status == s) {
						window.location.href= '/editor/';
					} else {
						alert('Statusänderung nicht erfolgt');
					}
				}
				LFB.endLoading();
			}
		});
	}
	
	
</script>





<div class="row editor-top">
	<div class="twelve columns">
		<h2>
			Editor
			<input type="button" class="small" <?= FCS_WARTUNG ? 'value="Wartungsmodus beenden" onclick="wartung(event, false);"' : 'value="Wartungsmodus starten" onclick="wartung(event, true);"' ?> />
		</h2>
	</div>
</div>

<div class="row" style="max-height:500px;overflow-y:scroll;">
	<div class="four columns"  style="max-height:500px;overflow-y:scroll;">
		<h3 style="">
			Layouts <input class="plus icon button" type="button" value="" onclick="addLayout()"/>
		</h3>
		<div style="padding:10px;">
			<?php $layouts = $this->get('layouts'); ?>
			<?php if (is_array($layouts)) : ?>
			<ul>
				<?php foreach ($layouts as &$layout) : ?>
					<li>
						<a onclick="delete_layout(event, <?= $layout->id ?>)" style="display:block;float:right;"><img src="/img/delete.png" alt="delete"/></a>
						<a href="/editor/?layout_id=<?= $layout->id ?>"><strong><?= htmlentities($layout->name) ?></strong><!-- (ID <?= $layout->id ?>)--></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>
	<div class="four columns"  style="max-height:500px;overflow-y:scroll;">
		<h3 style="">
			Inhalte <input class="plus icon button" type="button" value="" onclick="addContent()"/>
		</h3>
		<div style="padding:10px;">
			<?php $layouts = $this->get('layouts'); $contents = $this->get('contents'); ?>
			<?php if (is_array($layouts) && is_array($contents)) : ?>
			<ul>
				<?php foreach ($layouts as &$layout) : ?>
					<li class="closable">
						<a><big><?= htmlentities($layout->name) ?></big><!-- (ID <?= $layout->id ?>)--></a>
						<?php $_contents = isset($contents[$layout->id]) ? $contents[$layout->id] : array(); ?>
						<ul class="closable-inner">
							<?php foreach ($_contents as &$content) : ?>
								<li>
									<a onclick="delete_content(event, <?= $content->id ?>)" style="display:block;float:right;"><img src="/img/delete.png" alt="delete"/></a>
									<a href="/editor/?content_id=<?= $content->id ?>"><strong><?= htmlentities($content->name) ?></strong> (<?= $content->getCategoryName() ?>-Inhalt)</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
				<li class="closable">
					<a><big><em>(nicht direkt zugeordnet)</em></big></a>
					<?php $_contents = isset($contents[0]) ? $contents[0] : array(); ?>
					<ul class="closable-inner">
						<?php foreach ($_contents as &$content) : ?>
							<li>
								<a onclick="delete_content(event, <?= $content->id ?>)" style="display:block;float:right;"><img src="/img/delete.png" alt="delete"/></a>
								<a href="/editor/?content_id=<?= $content->id ?>"><strong><?= htmlentities($content->name) ?></strong> (<?= $content->getCategoryName() ?>-Inhalt)</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
			</ul>
			<?php endif; ?>
			
			
		</div>
	</div>
	<div class="four columns"  style="max-height:500px;overflow-y:scroll;">
		<h3 style="">
			Vorlagen <input class="plus icon button" type="button" value="" onclick="addTemplate()"/>
		</h3>
		<div style="padding:10px;">
			<?php $templates = $this->get('templates'); ?>
			<?php if (is_array($templates)) : ?>
			<ul>
				<?php foreach ($templates as &$template) : ?>
					<li>
						<a onclick="delete_template(event, '<?= $template->short_name ?>')" style="display:block;float:right;"><img src="/img/delete.png" alt="delete"/></a>
						<a href="/editor/?template_name=<?= urlencode($template->short_name) ?>"><strong><?= htmlentities($template->short_name) ?></strong></a> (<?= $template->usage ?>)
					</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="row" style="max-height:500px;overflow-y:scroll;">
	<div class="four columns"  style="max-height:500px;overflow-y:scroll;">
		<h3 style="">
			Blog Posts <input class="plus icon button" type="button" value="" onclick="addBlogPost()"/>
		</h3>
		<div style="padding:10px;">
			<?php $blogposts = $this->get('blogposts'); ?>
			<?php if (is_array($blogposts)) : ?>
			<ul>
				<?php foreach ($blogposts as &$post) : ?>
					<li>
						<a onclick="delete_content(event, <?= $post->id ?>)" style="display:block;float:right;"><img src="/img/delete.png" alt="delete"/></a>
						<?= !$post->visible ? '<s>' : '' ?>
						<a href="/editor/?blogpost_id=<?= $post->id ?>"><strong><?= htmlentities($post->name) ?></strong><!-- (ID <?= $content->id ?>)--></a>
						<?= !$post->visible ? '</s>' : '' ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>
</div>
