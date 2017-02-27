<?php $site = new \WEPPO\View\Template('view.site');

$template= $this->get('template');
$content = $this->get('content');
$cell    = $this->get('cell');
$row     = $this->get('row');
$layout  = $this->get('layout');
$blogpost= $this->get('blogpost');
$lang    = $this->get('lang');

?>

<?php $site->startParam('body'); ?>


	<script src="/js/tinymce/tinymce.min.js"></script>
	<script src="/js/tinymce/jquery.tinymce.min.js"></script>

	<script>
	var HTMLEditor;
	
	var ParamEditors = {
		editors: ['text', 'html'],
		
		hideAll: function() {
			for (var e in ParamEditors.editors) {
				ParamEditors[ParamEditors.editors[e]].hide();
			}
		},
		initAll: function() {
			for (var e in ParamEditors.editors) {
				ParamEditors[ParamEditors.editors[e]].init();
			}
		},
		clearAll: function() {
			for (var e in ParamEditors.editors) {
				ParamEditors[ParamEditors.editors[e]].setContent('');
			}
		},
		
		show: function(se) {
			
			if (typeof se == 'undefined') se = $('#paramTypeSelect').val();
			
			ParamEditors.hideAll();
			if (typeof ParamEditors[se] != 'undefined') {
				ParamEditors[se].show();
			}
		},
		
		setContent: function (t, v) {
			if (typeof v == 'undefined') {
				v = t;
				t = $('#paramTypeSelect').val();
			}
			
			if (typeof ParamEditors[t] != 'undefined') {
				ParamEditors[t].setContent(v);
				return true;
			}
			return false;
		},
		getContent: function (t) {
			if (typeof t == 'undefined') {
				t = $('#paramTypeSelect').val();
			}
			
			if (typeof ParamEditors[t] != 'undefined') {
				return ParamEditors[t].getContent();
			}
			return null;
		},
		
		
		'text': {
			init: function() {
			},
			setContent: function(v) {
				$('#paramValue').val(v);
			},
			getContent: function() {
				return $('#paramValue').val();
			},
			hide: function() {$('#paramValue').hide();},
			show: function() {$('#paramValue').show();},
		},
		'html': {
			init: function() {
				initHTMLEditor('#paramValue');
			},
			setContent: function(v) {
				HTMLEditor.setContent(v, {format:'raw'});
			},
			getContent: function() {
				return HTMLEditor.getContent({format:'raw'});
			},
			hide: function() {HTMLEditor.hide();},
			show: function() {HTMLEditor.show();},
		},
		
	};
	
	
	function initHTMLEditor(selector) {
		var isblog = selector == '#blogpostText';
		var isParam = selector == '#paramValue';
		
		var imagesContainer, sizeBox, urlBox;
		
		var updateImageURL = function() {
			if (urlBox.settings.image_size && urlBox.settings.image_file) {
				urlBox.value('/picture/' + urlBox.settings.image_size + '/' + urlBox.settings.image_file);
			} else {
				urlBox.value('');
			}
		};
		
		
		tinymce.init({
			selector:selector,
			menubar: false,
			plugins: [
				'advlist autolink lists link picture charmap print preview anchor',
				'searchreplace visualblocks code fullscreen',
				'insertdatetime media table contextmenu paste code',
				'autoresize' + (isblog||isParam?' save':'')
			],
			// alignleft aligncenter alignright alignjustify |
			toolbar: 'undo redo | insert | styleselect | bold italic | bullist numlist outdent indent | link picture | code fullscreen' + (isblog||isParam?' save':''),
			content_css: '/css/styles.css',
			language: 'de',
			autoresize_max_height: $(document).height()-80,
			relative_urls : false,
			remove_script_host : true,
			document_base_url : "/",
			save_onsavecallback: isblog ? saveBlogpost : (isParam ? saveParam : null),
			
			
			image_list: '/picture/list' /*[
				{title: 'IMG_0553.JPG', value: 'uploads/IMG_0553.JPG'},
				{title: 'IMG_0665.JPG', value: 'uploads/IMG_0665.JPG'},
				{title: 'IMG_0672.JPG', value: 'uploads/IMG_0672.JPG'},
				{title: 'IMG_0682.JPG', value: 'uploads/IMG_0682.JPG'},
				{title: 'IMG_0697.JPG', value: 'uploads/IMG_0697.JPG'},
				{title: 'IMG_0702.JPG', value: 'uploads/IMG_0702.JPG'},
				{title: 'IMG_0715.JPG', value: 'uploads/IMG_0715.JPG'},
			]*/,
			image_caption: false,
			image_class_list: [{title:'- keine -',value:''},{title:'Zentriert',value:'center'},{title:'Zentriert volle Breite',value:'fullCenter'},{title:'Rechts',value:'right'},{title:'Links',value:'left'},],
			image_description: false,
			image_dimensions: false,
			
			
			
			//automatic_uploads: true,
			//images_upload_url: '/upload/', //should return { "location": "folder/sub-folder/new-location.png" }
		}).then(function(editors) {
			HTMLEditor = editors[0];
		});
	}
	
	
	function ajaxResultPreprocess(d) {
		if (typeof d.msg != 'undefined') {
			alert(d.msg);
			return false;
		} else if (typeof d.status != 'undefined' && d.status == 'login') {
			$('#admin_login_container').show();
			return false;
		}
		return true;
	}
	
	</script>
	
	
	<div class="container" style="width:100%;padding:4px;max-width:100%;">
		
		<div style="position:absolute;right:10px;">
			<a href="/editor/?logout">Logout</a>
			| <a href="/editor/">Editor</a>
			| <a href="/">Zur Seite</a>
		</div>
		
		<?php
		
		function wartungsstatus() {
			echo '<span style="font-size:70%;"><a href="/editor/">Wartung</a>: ';
			if (!!FCS_WARTUNG) {
				echo '<span style="color:red;">'.htmlentities(FCS_WARTUNG).'</span>';
			} else {
				echo '<span style="color:green;">aus</span>';
			}
			echo '</span>';
		}
		
		wartungsstatus();
		
		
		if ($content)      : include (APP_ROOT.'templ/inc.content-editor.php');
		elseif ($row)      : include (APP_ROOT.'templ/inc.row-editor.php');
		elseif ($layout)   : include (APP_ROOT.'templ/inc.layout-editor.php');
		elseif ($template) : include (APP_ROOT.'templ/inc.template-editor.php');
		elseif ($blogpost) : include (APP_ROOT.'templ/inc.blogpost-editor.php');
		else               : include (APP_ROOT.'templ/inc.overview-editor.php');
		endif;
		
		?>
		
		
		
	</div>
		
	<div class="container">
		<!-- preview -->
		<div id="editor_preview" data-url="<?= htmlentities($this->get('preview_url')) ?>">
		
		
		</div>
		
	</div>
	
	
	
	<div id="admin_login_container">
		<div class="inner">
			<p style="color:red;font-weight:bold;">Die Aktion konnte nicht durchgeführt werden.</p>
			<h3>Login erforderlich</h3>
			<?php \FCS\admin_login_form(); ?>
			<p>Die Aktion muss anschließend erneut aufgerufen werden.</p>
		</div>
	</div>
	
	
	
	
	
	<script>
		
		var LFB = {
			loadingElement: null,
			loadFinishTimer: null,
			loadEndTimer: null,
			
			startLoading: function(el) {
				if (LFB.loadingElement) {
					LFB.endLoading();
				}
				LFB.loadingElement = $(el);
				LFB.loadingElement.removeClass('LFBdone');
				LFB.loadingElement.addClass('LFBloading');
			},
			
			endLoading: function() {
				LFB.loadingElement.removeClass('LFBloading').delay(400)
				.addClass('LFBdone');
				
				/*
				if (LFB.loadEndTimer) {
					clearTimeout(LFB.loadEndTimer);
				}*/
				
				if (LFB.loadFinishTimer) {
					clearTimeout(LFB.loadFinishTimer);
				}
				
				
				
				
				var el = LFB.loadingElement;
				LFB.loadingElement = null;
				LFB.loadFinishTimer = setTimeout(function() {
					LFB.loadFinishTimer = null;
					el.removeClass('LFBdone');
				}, 1000);
			},
		}
		
		
		function updatePreview(func) {
			$('#editor_preview').load($('#editor_preview').data('url'), function() {
				//console.log('dd');
				if (typeof func == 'function') func();
			});
		}
		
		$(document).ready(function() {
			
			
			
			$('#admin_login_form').submit(function(e) {
				
				LFB.startLoading($('#admin_login_form input[type=submit]'));
				
				e.preventDefault();
				
				var $this = $(this);
				
				var data = {
					name: $this.find('[name=name]').val(),
					pw: $this.find('[name=pw]').val(),
					mode: 'ajax',
					login: 'Login',
				};
				
				$.ajax({
					dataType: "json",
					method: 'post',
					url: '/editor/',
					data: data,
					success: function(d) {
						LFB.endLoading();
						if (ajaxResultPreprocess(d)) {
							if (typeof d.status != 'undefined' && d.status == 'ok') {
								$('#admin_login_container').hide();
							}
						}
					},
					error: function(d) {
						LFB.endLoading();
					}
				});
				
				return false;
			});
		});
	</script>
	
	
	
	
	
<?php $site->endParam('body'); ?>

<?php echo $site->getOutput(); ?>