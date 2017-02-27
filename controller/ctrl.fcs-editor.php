<?php
namespace FCS;

require_once WEPPO_ROOT.'inc/Controller/class.controller.php';

/**
 * Das ist der Editor-Controller für die FCS (fermeculturesauvage.net)-Umgebung.
 * Hiermit können Layout und Content usw. über ein Web-Frontend bearbeitet werden.
 * 
 * TODO: von FCS unabhängig machen (Zugriffsberechtigung etc..)
 */
class Editor extends \Weppo\Controller\ControllerBase {
    
    public function __construct(&$page, &$requestHandler) {
        parent::__construct($page, $requestHandler);
        
        if (!\FCS\is_admin()) {
            die(json_encode(array('status'=>'login')));
        }
    }
    
    
    
    function doIndex() {
        
        $body = '';
        
        
        
        $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null; # im wesentlichen für vorschau
        $blogpost_id = isset($_GET['blogpost_id']) ? intval($_GET['blogpost_id']) : null;
        //$content_name = isset($_GET['content_name']) ? ($_GET['content_name']) : null;
        
        $cell_id    = isset($_GET['cell_id'])    ? intval($_GET['cell_id'])    : null;
        $row_id     = isset($_GET['row_id'])     ? intval($_GET['row_id'])     : null;
        $layout_id  = isset($_GET['layout_id'])  ? intval($_GET['layout_id'])  : null;
        $template_name  =isset($_GET['template_name'])  ? ($_GET['template_name'])  : null;
        
        $is_preview = isset($_GET['preview'])  ? intval($_GET['preview'])  : false;
        $lang = isset($_GET['lang']) ? $_GET['lang'] : \i18n\i18n::getInstance()->lang;
        
        if ($blogpost_id || $content_id || $cell_id || $row_id || $layout_id || $template_name) {
            
            $blogpost = null;
            if ($blogpost_id) {
                try {
                    $blogpost = new \WEPPO\Grid\BlogPost($blogpost_id);
                } catch (\Exception $e) {
                    $blogpost = null;
                }
            }
            if ($blogpost) {
                if ($blogpost->noTranslation) {
                    $old_lang = $lang;
                    $lang = \i18n\i18n::$defaultLanguage;
                    if ($old_lang != $lang) {
                        # nochmal laden... (ist doof aber nötig, weil sich die sprache vielleicht geändert haben könnte
                        $blogpost = new \WEPPO\Grid\BlogPost($blogpost_id);
                    }
                }
                $blogpost->loadTags($lang);
            }
            
            if ($content_id) {
                try {
                    $content = new \WEPPO\Grid\Content($content_id);
                } catch (\Exception $e) {
                    $content = null;
                }
            } else {
                $content = null;
            }
            if ($content) {
                if ($content->is(CONTENT_CLASS_BLOG)) {
                    \WEPPO\System::redirect('/editor/?blogpost_id='.$content->id);
                    return;
                }
                if ($content->noTranslation) {
                    $lang = \i18n\i18n::$defaultLanguage;
                }
            }
            
            if ($cell_id) {
                try {
                    $cell = new \WEPPO\Grid\Cell($cell_id);
                } catch (\Exception $e) {
                    $cell = null;
                }
            } else {
                $cell = null;
            }
            
            if ($row_id) {
                try {
                    $row = new \WEPPO\Grid\Row($row_id);
                } catch (\Exception $e) {
                    $row = null;
                }
            } else {
                $row = null;
            }
            
            if ($layout_id) {
                try {
                    $layout = new \WEPPO\Grid\Layout($layout_id);
                } catch (\Exception $e) {
                    $layout = null;
                }
            } else {
                $layout = null;
            }
            
            if ($template_name) {
                try {
                    $template = new \WEPPO\Grid\Template($template_name);
                } catch (\Exception $e) {
                    $template = null;
                }
            } else {
                $template = null;
            }
            
            
            if ($is_preview) {
                $body = '';
                if ($content) {
                    if (!$cell) {
                        $body .= '<p>Kein Kontext geladen.</p>';
                    }
                    $body .= $content->getPreviewOutput($cell, $lang);
                } else if ($row) {
                    $body .= $row->getPreviewOutput($lang);
                } else if ($layout) {
                    $body .= $layout->getPreviewOutput($lang);
                } else if ($blogpost) {
                    $c = null;
                    $body .= $blogpost->getPreviewOutput($c, $lang);
                }
                echo $body;
                return;
            }
            
        } else {
            $content = null;
            $row = null;
            $cell = null;
            $layout = null;
            $template = null;
            $blogpost = null;
            
            # alle Layouts
            \WEPPO\Grid\Layout::orderBy('name', 'ASC');
            $layouts = \WEPPO\Grid\Layout::get();
            
            # alle Contents, wenn anwendbar den Layouts zuordnen
            $contents = array();
            # Inhalte zu dem jeweiligen Layout
            foreach ($layouts as &$l) {
                \WEPPO\Grid\Content::join('gridcells LC', 'LC.content_id = gridcontents1.id', 'INNER');
                \WEPPO\Grid\Content::join('gridrows LR', 'LR.id = LC.row_id', 'INNER');
                \WEPPO\Grid\Content::where('LR.layout_id', $l->id);
                \WEPPO\Grid\Content::groupBy('gridcontents1.name');
                \WEPPO\Grid\Content::orderBy('gridcontents1.name', 'ASC');
                $contents[$l->id] = \WEPPO\Grid\Content::get(null, 'gridcontents1.*'); // ,LC.id AS cell_id,LC.row_id,LR.layout_id
            }
            # Inhalte ohne Zuordnung zu einer Zelle
            \WEPPO\Grid\Content::join('gridcells LC', 'LC.content_id = gridcontents1.id', 'LEFT');
            \WEPPO\Grid\Content::where('LC.id IS NULL');
            \WEPPO\Grid\Content::groupBy('gridcontents1.name');
            \WEPPO\Grid\Content::orderBy('gridcontents1.name', 'ASC');
            $contents[0] = \WEPPO\Grid\Content::get(null, 'gridcontents1.*,LC.id AS cell_id');
            
            
            # Alle Templates
            $templates = \WEPPO\Grid\Template::getList();
            foreach ($templates as &$t) {
                $t->loadUsage();
            }
            
            # Alle Blog Posts
            \WEPPO\Grid\BlogPost::orderBy('name', 'ASC');
            $blogposts = \WEPPO\Grid\BlogPost::get();
            
        }
        
        if (!$template) {
            $preview_url = '/editor/?preview=1&lang='.$lang;
            if ($content) {
                $preview_url .= '&content_id='.$content->id;
            }
            if ($cell) {
                $preview_url .= '&cell_id='.$cell->id;
            }
            if ($row) {
                $preview_url .= '&row_id='.$row->id;
            }
            if ($layout) {
                $preview_url .= '&layout_id='.$layout->id;
            }
            if ($blogpost) {
                $preview_url .= '&blogpost_id='.$blogpost->id;
            }
        }
        
        $T = new \WEPPO\View\Template('view.editor');
        $T->set('body', $body);
        
        if (isset($preview_url)) {
            $T->set('preview_url', $preview_url);
        }
        
        if ($content) {
            $templates = \WEPPO\Grid\Template::getList(false);
        }
        if ($blogpost) {
            $templates = \WEPPO\Grid\Template::getList(false);
            $templates = array_filter($templates, function($t) {
                # wenns nicht mit 'blog.article' startet, raus damit
                if (strpos($t->short_name, 'blog.article') !== 0) {
                    return false;
                }
                # wenns mit '.preview' raus damit
                if (strpos($t->short_name, '.preview') === strlen($t->short_name)-8) {
                    return false;
                }
                return true;
            });
        }
        
        if ($row) {
            \WEPPO\Grid\Content::orderBy('name', 'ASC');
            \WEPPO\Grid\Content::groupBy('name');
            $contents = \WEPPO\Grid\Content::get();
            $layout = $row->getLayoutContext(true);
        }
        
        
        if (isset($template))   $T->set('template',  $template);
        if (isset($content))    $T->set('content', $content);
        if (isset($cell))       $T->set('cell',    $cell);
        if (isset($row))        $T->set('row',     $row);
        if (isset($layout))     $T->set('layout',  $layout);
        if (isset($layouts))    $T->set('layouts',  $layouts);
        if (isset($contents))   $T->set('contents',  $contents);
        if (isset($templates))  $T->set('templates',  $templates);
        if (isset($lang))       $T->set('lang',  $lang);
        if (isset($blogpost))   $T->set('blogpost',  $blogpost);
        if (isset($blogposts))  $T->set('blogposts',  $blogposts);
        
        echo $T->getOutput();
    }
    
    
    function doLayout($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        $layout_id = isset($_GET['layout_id']) ? intval($_GET['layout_id']) : null;
        try {
            $layout = new \WEPPO\Grid\Layout($layout_id);
        } catch (\Exception $e) {
            $layout = null;
        }
        
        if ($command == 'save') {
            if ($layout) {
                $old_type = $layout->getType();
                $layout->name = isset($_POST['name']) ? \WEPPO\Grid\identifier($_POST['name']) : '';
                $layout->options = isset($_POST['options']) ? trim($_POST['options']) : '';
                $layout->type = isset($_POST['type']) ? trim($_POST['type']) : null;
                $layout->useCache = isset($_POST['cache']) ? !!intval($_POST['cache']) : false;
                
                if (empty($layout->options)) $layout->options = '{}';
                $layout->options = json_decode($layout->options);
                if ($layout->options === null) {
                    echo json_encode(array('msg'=>'Optionen sind fehlerhaft.'));
                    return;
                }
                
                if (!empty($layout->name)) {
                
                    # schauen ob name schon vergeben
                    \WEPPO\Grid\Layout::where('id', $layout->id, '<>');
                    \WEPPO\Grid\Layout::where('name', $layout->name);
                    $exists = \WEPPO\Grid\Layout::getOne();
                    
                    if (!$exists) {
                        $layout->save();
                        $layout->invalidate();
                        if ($layout->getType() != $old_type) {
                            echo json_encode(array('select_layout' => $layout->id));
                        } else {
                            echo json_encode(array());
                        }
                    } else {
                        echo json_encode(array('msg'=>'Name wird schon verwendet.'));
                    }
                } else {
                    echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
                }
            }
        } else if ($command == 'create') {
            $name = isset($_GET['name']) ? \WEPPO\Grid\identifier($_GET['name']) : '';
            if ($name) {
                \WEPPO\Grid\Layout::where('name', $name);
                $exists = \WEPPO\Grid\Layout::getOne();
                if (!$exists) {
                    $layout = new \WEPPO\Grid\Layout();
                    $layout->name = $name;
                    if ($layout->save()) {
                        $layout->invalidate();
                        \WEPPO\System::redirect('/editor/?layout_id=' . $layout->id);
                        return;
                    }
                } else {
                }
            } else {
            }
            \WEPPO\System::redirect('/editor/?layout-creation-failed');
        } else if ($command == 'add') {
            $name = isset($_POST['name']) ? \WEPPO\Grid\identifier($_POST['name']) : '';
            if ($name) {
                \WEPPO\Grid\Layout::where('name', $name);
                $exists = \WEPPO\Grid\Layout::getOne();
                if (!$exists) {
                    $layout = new \WEPPO\Grid\Layout();
                    $layout->name = $name;
                    $layout->save();
                    $layout->invalidate();
                    echo json_encode(array('select_layout'=>$layout->id));
                } else {
                    echo json_encode(array('msg'=>'Name wird schon verwendet.'));
                }
            } else {
                echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
            }
            
        } else if ($command == 'delete') {
            if ($layout) {
                //_o($layout);
                $layout->invalidate();
                $layout->remove();
            }
            echo json_encode(array());
        }
        
    }
    
    function doRow($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        $row_id = isset($_GET['row_id']) ? intval($_GET['row_id']) : null;
        try {
            $row = new \WEPPO\Grid\Row($row_id);
        } catch (\Exception $e) {
            $row = null;
        }
        
        if ($command == 'select') {
            if ($row) {
                echo json_encode($row);
            }
        } else if ($command == 'save') {
            if ($row) {
                $row->classes = isset($_POST['classes']) ? $_POST['classes'] : '';
                $row->class = isset($_POST['class']) ? $_POST['class'] : '';
                $row->styles  = isset($_POST['styles'])  ? $_POST['styles']  : '';
                $row->options = isset($_POST['options']) ? trim($_POST['options']) : '';
                $row->useCache = isset($_POST['cache']) ? !!intval($_POST['cache']) : false;
                $row->size    = isset($_POST['size']) ? intval($_POST['size']) : 0;
                
                if (empty($row->options)) $row->options = '{}';
                $row->options = json_decode($row->options);
                if ($row->options === null) {
                    echo json_encode(array('msg'=>'Optionen sind fehlerhaft.'));
                    return;
                }
                
                $row->save();
                $row->invalidate();
                echo json_encode(array('select_row'=>$row->id));
            }
        } else if ($command == 'move') {
            if ($row) {
                $direction = isset($_GET['direction']) ? $_GET['direction'] : '';
                if ($direction == 'up') $direction = -1;
                else if ($direction == 'down') $direction = 1;
                else  $direction = 0;
                if ($direction) {
                    \WEPPO\Grid\Row::where('layout_id', $row->layout_id);
                    \WEPPO\Grid\Row::where('ordering', $row->ordering, $direction > 0 ? '>' : '<');
                    \WEPPO\Grid\Row::orderBy('ordering', $direction > 0 ? 'ASC' : 'DESC');
                    $reference_row = \WEPPO\Grid\Row::getOne();
                    if ($reference_row) {
                        # tauschen
                        $reference_value = $reference_row->ordering;
                        $reference_row->ordering = $row->ordering;
                        $row->ordering = $reference_value;
                        
                        $reference_row->save(array('ordering'));
                        $row->save(array('ordering'));
                        $row->invalidate();
                    }
                }
                echo json_encode(array('select_row'=>$row->id/*, 'q'=>\WEPPO\Grid\Row::getLastQuery()*/));
            }
        } else if ($command == 'delete') {
            if ($row) {
                $row->invalidate();
                $row->remove();
            }
            echo json_encode(array());
        } else if ($command == 'add') {
            $layout_id = isset($_GET['layout_id']) ? intval($_GET['layout_id']) : null;
            if ($layout_id) {
                $row = new \WEPPO\Grid\Row();
                $row->layout_id = $layout_id;
                
                # ordering ermitteln
                \WEPPO\Grid\Row::where('layout_id', $row->layout_id);
                \WEPPO\Grid\Row::orderBy('ordering', 'DESC');
                $reference_row = \WEPPO\Grid\Row::getOne();
                if ($reference_row) {
                    $row->ordering = $reference_row->ordering + 1;
                } else {
                    $row->ordering = 1;
                }
                
                
                $row->save();
                $row->invalidate();
                echo json_encode(array('select_row'=>$row->id));
            }
        } 
        
    }
    
    function doSys($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        if ($command == 'wartung') {
            if (isset($_POST['wartung']) && \FCS\is_admin()) {
                
                $status = !!$_POST['wartung'];
                
                # wenn gerade keine sperre oder wenn die sperre von mir ist
                if (!FCS_WARTUNG || (FCS_WARTUNG == \FCS\get_admin())) {
                    if (@file_put_contents(FCS_WARTUNG_FILE, $status ? \FCS\get_admin() : '') !== false) {
                        echo json_encode(array('status' => $status));
                    } else {
                        echo json_encode(array('msg'=>'Fehler: Datei schreiben?'));
                    }
                } else {
                    echo json_encode(array('msg'=>'Fehler: Bereits gesperrt?'));
                }
            } else {
                echo json_encode(array('msg'=>'Fehler in der Verarbeitung.'));
            }
            
        }
        
    }
    
    function doCell($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        $cell_id = isset($_GET['cell_id']) ? intval($_GET['cell_id']) : null;
        try {
            $cell = new \WEPPO\Grid\Cell($cell_id);
        } catch (\Exception $e) {
            $cell = null;
        }
        
        if ($command == 'select') {
            if ($cell) {
                echo json_encode($cell);
            }
        } else if ($command == 'save') {
            if ($cell) {
                $cell->classes = isset($_POST['classes']) ? $_POST['classes'] : '';
                $cell->class = isset($_POST['class']) ? $_POST['class'] : '';
                $cell->styles  = isset($_POST['styles'])  ? $_POST['styles']  : '';
                $cell->size = isset($_POST['size']) ? intval($_POST['size']) : 0;
                $cell->useCache = isset($_POST['cache']) ? !!intval($_POST['cache']) : false;
                $cell->content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
                
                $cell->options = isset($_POST['options']) ? trim($_POST['options']) : '';
                
                if (empty($cell->options)) $cell->options = '{}';
                $cell->options = json_decode($cell->options);
                if ($cell->options === null) {
                    echo json_encode(array('msg'=>'Optionen sind fehlerhaft.'));
                    return;
                }
                
                $cell->save();
                $cell->invalidate();
                
                echo json_encode(array('select_cell'=>$cell->id));
            }
        } else if ($command == 'move') {
            if ($cell) {
                $direction = isset($_GET['direction']) ? $_GET['direction'] : '';
                if ($direction == 'left') $direction = -1;
                else if ($direction == 'right') $direction = 1;
                else  $direction = 0;
                if ($direction) {
                    \WEPPO\Grid\Cell::where('row_id', $cell->row_id);
                    \WEPPO\Grid\Cell::where('ordering', $cell->ordering, $direction > 0 ? '>' : '<');
                    \WEPPO\Grid\Cell::orderBy('ordering', $direction > 0 ? 'ASC' : 'DESC');
                    $reference_cell = \WEPPO\Grid\Cell::getOne();
                    if ($reference_cell) {
                        # tauschen
                        $reference_value = $reference_cell->ordering;
                        $reference_cell->ordering = $cell->ordering;
                        $cell->ordering = $reference_value;
                        
                        $reference_cell->save(array('ordering'));
                        $cell->save(array('ordering'));
                        $cell->invalidate();
                    }
                }
                echo json_encode(array('select_cell'=>$cell->id/*, 'q'=>\WEPPO\Grid\Row::getLastQuery()*/));
            }
        } else if ($command == 'delete') {
            if ($cell) {
                $cell->invalidate();
                $cell->remove();
            }
            echo json_encode(array());
        } else if ($command == 'add') {
            $row_id = isset($_GET['row_id']) ? intval($_GET['row_id']) : null;
            if ($row_id) {
                # schauen, ob noch platz in der Zeile ist:
                \WEPPO\Grid\Cell::where('row_id', $row_id);
                $cells = \WEPPO\Grid\Cell::get();
                $sum = 0;
                foreach ($cells as &$c) {
                    $sum += $c->size;
                }
                if ($sum < 12) {
                    $cell = new \WEPPO\Grid\Cell();
                    $cell->row_id = $row_id;
                    $cell->size = 1;
                    
                    # ordering ermitteln
                    \WEPPO\Grid\Cell::where('row_id', $cell->row_id);
                    \WEPPO\Grid\Cell::orderBy('ordering', 'DESC');
                    $reference_cell = \WEPPO\Grid\Cell::getOne();
                    if ($reference_cell) {
                        $cell->ordering = $reference_cell->ordering + 1;
                    } else {
                        $cell->ordering = 1;
                    }
                    
                    $cell->save();
                    $cell->invalidate();
                    echo json_encode(array('select_cell'=>$cell->id));
                } else {
                    echo json_encode(array('error'=>'Kein Platz mehr.'));
                }
            }
        }
        
    }
    
    function doContent($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        if ($command == 'template-change') {
            
            $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null;
            
            if ($content_id) {
                $template_name = isset($_GET['template_name']) ? ($_GET['template_name']) : null;
                
                try {
                    $content = new \WEPPO\Grid\Content($content_id);
                } catch (\Exception $e) {
                    $content = null;
                }
                
                if ($content) {
                    $content->invalidate();
                    $content->template = $template_name;
                    $content->save(array('template'));
                    
                    # Template Code zurück liefern
                    $code = '';
                    try {
                        $template = new \WEPPO\Grid\Template($template_name);
                        $code = $template->getCode();
                    } catch (\Exception $e) { }
                    
                    echo json_encode(array('templateCode' => $code));
                }
            }
        } else if ($command == 'save') {
            
            $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null;
            
            if ($content_id) {
                
                try {
                    $content = new \WEPPO\Grid\Content($content_id);
                } catch (\Exception $e) {
                    $content = null;
                }
                
                if ($content) {
                    $save = array();
                    
                    
                    if (isset($_POST['noTranslation'])) {
                        $content->noTranslation = !!intval($_POST['noTranslation']);
                        $save[] = 'noTranslation';
                    }
                    
                    //if (isset($_POST['template'])) {
                    //    $update['template'] = ($_POST['template']);
                    //}
                    
                    if (isset($_POST['name'])) {
                        
                        $name = \WEPPO\Grid\identifier($_POST['name']);
                        if (empty($name)) {
                            echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
                            return;
                        }
                        if ($name != $content->name) {
                            \WEPPO\Grid\Content::where('id', $content->id, '<>');
                            //\WEPPO\Grid\Content::where('category', $content->category);
                            //\WEPPO\Grid\Content::where('name', $name);
                            $exists = \WEPPO\Grid\Content::createFromName($name, $content->class);
                            if ($exists) {
                                echo json_encode(array('msg'=>'Name schon in Verwendung.'));
                                return;
                            }
                            $content->name = $name;
                            $save[] = 'name';
                        }
                    }
                    
                    
                    if (count($save)) {
                        $content->save($save);
                        $content->invalidate();
                        
                        # Sonderaktion wenn nur noTranslation gemacht wurde
                        if (in_array('noTranslation', $save) && count($save)==1) {
                            # falls no translation aktiviert ist: parameter in anderen sprachen löschen
                            if ($content->noTranslation) {
                                \WEPPO\Grid\ContentParam::where('content_id', $content->id);
                                \WEPPO\Grid\ContentParam::where('lang', \i18n\i18n::$defaultLanguage, '<>');
                                \WEPPO\Grid\ContentParam::delete();
                                
                                echo json_encode(array('select_lang' => \i18n\i18n::$defaultLanguage));
                            } else {
                                echo json_encode(array());
                            }
                            return;
                        }
                        
                        echo json_encode(array('select_content'=>$content->id));
                        return;
                        
                    } else {
                        echo json_encode(array());
                    }
                }
            }
            echo json_encode(array('msg'=>'fail'));
        } else if ($command == 'add') {
            
            $name = isset($_POST['name']) ? \WEPPO\Grid\identifier($_POST['name']) : '';
            $for_cell_id = isset($_GET['for_cell_id']) ? intval($_GET['for_cell_id']) : null;
            
            if (!empty($name)) {
                // TODO class vom editor?
                
                $exists = \WEPPO\Grid\Content::createFromName($name, CONTENT_CLASS_PAGE);
                
                if (!$exists) {
                    $content = new \WEPPO\Grid\Content();
                    $content->class = CONTENT_CLASS_PAGE;
                    $content->name = $name;
                    $content->save();
                    
                    $content->invalidate();
                    
                    # falls content neu für eine spezielle zelle:
                    if ($for_cell_id) {
                        try {
                            $cell = new \WEPPO\Grid\Cell($for_cell_id);
                            $cell->content_id = $content->id;
                            $cell->save(array('content_id'));
                            $cell->invalidate();
                        } catch (\Exception $e) {}
                    }
                    echo json_encode(array('select_content'=>$content->id));
                } else {
                    echo json_encode(array('msg'=>'Name schon in Verwendung.'));
                }
            } else {
                echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
            }
        } else if ($command == 'delete') {
            $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null;
            if ($content_id) {
                try {
                    $content = new \WEPPO\Grid\Content($content_id);
                } catch (\Exception $e) {
                    $content = null;
                }
                if ($content) {
                    $content->invalidate();
                    $content->remove();
                }
            }
            echo json_encode(array());
        }
    }
    
    function doParam($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null;
        $param_name = isset($_GET['param_name']) ? ($_GET['param_name']) : null;
        $lang = isset($_GET['lang']) ? ($_GET['lang']) : null;
        
        $value = isset($_POST['value']) ? ($_POST['value']) : null;
        $type = isset($_POST['type']) ? ($_POST['type']) : 'text';
        
        if ($content_id) {
            try {
                $content = new \WEPPO\Grid\Content($content_id);
            } catch (\Exception $e) {
                $content = null;
            }
        }
        
        if ($content && $param_name && $lang) {
            $param = \WEPPO\Grid\ContentParam::create($param_name, $content_id, $lang);
        } else {
            $param = null;
        }
        
        if ($command == 'select') {
            if ($content && $param && $param->content_id == $content->id) {
                echo json_encode($param);
            }
        } else if ($command == 'copy') {
            $from_lang = isset($_GET['from_lang']) ? ($_GET['from_lang']) : null;
            $msg = array();
            if ($content && $lang && $from_lang && $lang != $from_lang) {
                $params = $content->getParams($from_lang);
                if (is_array($params)) {
                    foreach ($params as &$p) {
                        \WEPPO\Grid\ContentParam::where('content_id', $p->content_id);
                        \WEPPO\Grid\ContentParam::where('lang', $lang);
                        \WEPPO\Grid\ContentParam::where('name', $p->name);
                        $exists = \WEPPO\Grid\ContentParam::getOne();
                        if (!$exists) {
                            $p->id = 0; # neuer parameter
                            $p->lang = $lang;
                            $p->save();
                            $msg[] = $p->name.' ok';
                        } else {
                            $msg[] = '"'.$p->name.'" wurde nicht kopiert, da in der Zielsprache schon vorhanden.';
                        }
                    }
                }
                $data = array();
                if (count($msg)) {
                    $data['msg'] = implode(PHP_EOL, $msg);
                }
                $this->_jsonParamListResult($content, $lang, $data);
            }
        } else if ($command == 'save') {
            if ($content) {
                if (!$param) {
                    $param = new \WEPPO\Grid\ContentParam();
                    $param->content_id = $content->id;
                    $param->lang = $lang;
                    $param->name = $param_name;
                }
                // TODO Type-behandlung
                $param->type = $type;
                $param->value = $value;
                $param->save();
                
                $exclude = null;
                if ($content) {
                    $content->invalidate();
                    if ($content->is(CONTENT_CLASS_BLOG)) {
                        $exclude = &\WEPPO\Grid\BlogPost::$managedParams;
                    }
                }
                $this->_jsonParamListResult($content, $lang, null, $exclude);
            }
        } else if ($command == 'delete') {
            if ($content) {
                if ($param) {
                    $param->remove();
                }
                $exclude = null;
                if ($content) {
                    $content->invalidate();
                    if ($content->is(CONTENT_CLASS_BLOG)) {
                        $exclude = &\WEPPO\Grid\BlogPost::$managedParams;
                    }
                }
                $this->_jsonParamListResult($content, $lang, null, $exclude);
            }
        }
        
        
    }
    
    private function _jsonParamListResult(&$content, $lang, $data = null, $exclude = null) {
        $result = new \stdclass;
        
        if (is_array($data)) {
            foreach ($data as $k=>&$d) {
                $result->{$k} = $d;
            }
        }
        
        $result->paramList = array();
        $params = $content->getParams($lang);
        foreach ($params as &$p) {
            if (is_array($exclude) && in_array($p->name, $exclude)) continue;
            $result->paramList[] = '<a data-param_name="'.htmlentities($p->name).'" data-param_id="'.htmlentities($p->id).'">'. htmlentities($p->name). '</a><br/>';
        }
        echo json_encode($result);
    }
    
    
    
    function doTemplate($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        
        //$content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : null;
        $template_name = isset($_GET['template_name']) ? ($_GET['template_name']) : null;
        
        try {
            $template = new \WEPPO\Grid\Template($template_name);
        } catch (\Exception $e) {
            $template = null;
        }
        
        if ($command == 'save') {
            if ($template) {
                if (isset($_POST['code'])) {
                    if (!$template->saveCode($_POST['code'])) {
                        echo json_encode(array('msg'=>'Speichern fehlgeschlagen.'));
                        return;
                    }
                }
                if (isset($_POST['name'])) {
                    $name = \WEPPO\Grid\identifier($_POST['name']);
                    if (empty($name)) {
                        echo json_encode(array('msg'=> 'Name darf nicht leer sein.'));
                        return;
                    }
                    if (!$template->changeName($name)) {
                        echo json_encode(array('msg'=>'Name ändern nicht erfolgreich'));
                    }
                }
                echo json_encode(array('select_template'=>$template->short_name));
            }
        } else if ($command == 'add') {
            
            $template_name = isset($_POST['name']) ? ($_POST['name']) : null;
            
            if (empty($template_name)) {
                echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
                return;
            }
            
            $template = new \WEPPO\Grid\Template($template_name, null, false);
            
            if ($template->exists()) {
                echo json_encode(array('msg'=>'Name schon vergeben'));
                return;
            }
            
            if (!$template->create()) {
                echo json_encode(array('msg'=>'Speichern fehlgeschlagen'));
                return;
            }
            
            echo json_encode(array('select_template'=>$template->short_name));
            
        } else if ($command == 'delete') {
            if ($template) {
                $template->invalidate();
                $template->remove();
            }
            echo json_encode(array());
        }
    }
    
    function doBlogpost($pd) {
        $command = isset($pd[0]) ? $pd[0] : '';
        
        $blogpost_id = isset($_GET['blogpost_id']) ? intval($_GET['blogpost_id']) : null;
        $lang = isset($_GET['lang']) ? ($_GET['lang']) : null;
        
        try {
            $blogpost = new \WEPPO\Grid\BlogPost($blogpost_id);
        } catch (\Exception $e) {
            $blogpost = null;
        }
        
        
        if ($command === 'copy-params') {
            
            $from_lang = isset($_GET['from_lang']) ? ($_GET['from_lang']) : null;
            $msg = array();
            if ($blogpost && $lang && $from_lang && $lang != $from_lang) {
                $params = $blogpost->getParams($from_lang);
                if (is_array($params)) {
                    foreach ($params as &$p) {
                        \WEPPO\Grid\ContentParam::where('content_id', $p->content_id);
                        \WEPPO\Grid\ContentParam::where('lang', $lang);
                        \WEPPO\Grid\ContentParam::where('name', $p->name);
                        $exists = \WEPPO\Grid\ContentParam::getOne();
                        if (!$exists) {
                            $p->id = 0; # neuer parameter
                            $p->lang = $lang;
                            $p->save();
                            $msg[] = $p->name.' ok';
                        } else {
                            $msg[] = '"'.$p->name.'" wurde nicht kopiert, da in der Zielsprache schon vorhanden.';
                        }
                    }
                }
                
                // TODO tags kopieren
                
                
                $data = array();
                if (count($msg)) {
                    $data['msg'] = implode(PHP_EOL, $msg);
                }
                echo json_encode($data);
            }
        } else if ($command === 'add') {
            
            $name = isset($_POST['name']) ? \WEPPO\Grid\identifier($_POST['name']) : '';
            if (!empty($name)) {
                
                $exists = \WEPPO\Grid\BlogPost::createFromName($name, null);
                
                if (!$exists) {
                    $lang = \i18n\i18n::$defaultLanguage;
                    
                    # Blog Post
                    $blogpost = new \WEPPO\Grid\BlogPost();
                    $blogpost->loadEmpty();
                    $blogpost->name = $name;
                    $blogpost->class = CONTENT_CLASS_BLOG;
                    $blogpost->template = 'blog.article';
                    $blogpost->noTranslation = 1;
                    if ($blogpost->save()) {
                        $blogpost->invalidate();
                        
                        # managed Params
                        foreach (\WEPPO\Grid\BlogPost::$managedParams as $pname) {
                            $p = new \WEPPO\Grid\ContentParam();
                            $p->name = $pname;
                            $p->content_id = $blogpost->id;
                            $p->lang = $lang;
                            $p->value = '';
                            if ($pname == 'text') $p->type = 'html';
                            else $p->type = 'text';
                            $p->save();
                        }
                        
                        # meta
                        $meta = new \WEPPO\Grid\BlogMeta();
                        $meta->loadEmpty();
                        $meta->content_id = $blogpost->id;
                        $meta->save();
                        
                        echo json_encode(array('select_blogpost'=>$blogpost->id));
                    }
                } else {
                    echo json_encode(array('msg'=>'Name schon in Verwendung.'));
                }
                return;
            } else {
                echo json_encode(array('msg'=>'Name darf nicht leer sein.'));
                return;
            }
            echo json_encode(array('msg'=>'Anderer Fehler.'));
        } else if ($command === 'save') {
            if ($blogpost) {
                
                # Blog Post
                $saveFilter = array();
                if (isset($_POST['name'])) {
                    $blogpost->name = \WEPPO\Grid\identifier($_POST['name']);
                    
                    # verwendung von Content ist ok und richtig hier
                    \WEPPO\Grid\BlogPost::where('gridcontents1.id', $blogpost->id, '<>');
                    //\WEPPO\Grid\Content::where('category', $blogpost->category);
                    //\WEPPO\Grid\Content::where('name', $blogpost->name);
                    $exists = \WEPPO\Grid\BlogPost::createFromName($blogpost->name, null);
                    if ($exists) {
                        echo json_encode(array('msg'=>'Name schon in Verwendung.'));
                        return;
                    }
                    
                    $saveFilter[] = 'name';
                }
                if (isset($_POST['template'])) {
                    $blogpost->template = $_POST['template'];
                    $saveFilter[] = 'template';
                }
                if (isset($_POST['noTranslation'])) {
                    $blogpost->noTranslation = !!intval($_POST['noTranslation']);
                    $saveFilter[] = 'noTranslation';
                }
                if (count($saveFilter)) {
                    if (!$blogpost->save($saveFilter)) {
                        echo json_encode(array('msg'=>'Speichern von Blogpost fehlgeschlagen'));
                        return;
                    }
                    
                    # Params und Tags der anderen Sprachen löschen
                    if (in_array('noTranslation', $saveFilter) && count($saveFilter)==1) {
                        if ($blogpost->noTranslation) {
                            
                            \WEPPO\Grid\ContentParam::where('content_id', $blogpost->id);
                            \WEPPO\Grid\ContentParam::where('lang', \i18n\i18n::$defaultLanguage, '<>');
                            \WEPPO\Grid\ContentParam::delete();
                            
                            \WEPPO\Grid\BlogTag::where('content_id', $blogpost->id);
                            \WEPPO\Grid\BlogTag::where('lang', \i18n\i18n::$defaultLanguage, '<>');
                            \WEPPO\Grid\BlogTag::delete();
                            
                            # default sprache auswählen
                            echo json_encode(array('select_lang' => \i18n\i18n::$defaultLanguage));
                        } else {
                            echo json_encode(array());
                        }
                        return;
                    }
                }
                
                # Meta
                \WEPPO\Grid\BlogMeta::where('content_id', $blogpost->content_id);
                $update = array();
                $cast = \WEPPO\Grid\BlogPost::getCast();
                if (isset($_POST['date'])) $update['date'] = $cast['date']->parse($_POST['date']);
                if (isset($_POST['author'])) $update['author'] = $_POST['author'];
                if (isset($_POST['visible'])) $update['visible'] = $_POST['visible']=='true';
                
                if (count($update)) {
                    \WEPPO\Grid\BlogMeta::update($update);
                }
                
                # managed params
                if ($lang) {
                    foreach (\WEPPO\Grid\BlogPost::$managedParams as $pname) {
                        if (isset($_POST[$pname])) {
                            $pvalue = $_POST[$pname];
                            \WEPPO\Grid\ContentParam::where('content_id', $blogpost->content_id);
                            \WEPPO\Grid\ContentParam::where('lang', $lang);
                            \WEPPO\Grid\ContentParam::where('name', $pname);
                            $p = \WEPPO\Grid\ContentParam::getOne();
                            if (!$p) {
                                $p = new \WEPPO\Grid\ContentParam();
                                $p->content_id = $blogpost->content_id;
                                $p->lang = $lang;
                                $p->name = $pname;
                                if ($pname == 'text') $p->type = 'html';
                                else $p->type = 'text';
                            }
                            $p->value = $pvalue;
                            if ($p->id === 0) {
                                $p->save();
                            } else {
                                $p->save(array('value'));
                            }
                        }
                    }
                    
                    # Tags
                    if (isset($_POST['tags'])) {
                        $tags = $cast['tags']->parse($_POST['tags']);
                        if (is_array($tags)) {
                            \WEPPO\Grid\BlogTag::where('content_id', $blogpost->content_id);
                            \WEPPO\Grid\BlogTag::where('lang', $lang);
                            \WEPPO\Grid\BlogTag::delete();
                            foreach ($tags as $t) {
                                $tt = new \WEPPO\Grid\BlogTag();
                                $tt->content_id = $blogpost->content_id;
                                $tt->lang = $lang;
                                $tt->tag = $t;
                                $tt->save();
                            }
                        }
                        
                    }
                }
                
                echo json_encode(array());
            }
        } else if ($command == 'delete') {
            if ($blogpost) {
                $blogpost->invalidate();
                $blogpost->remove();
            }
            echo json_encode(array());
        }
        
        
    }
    
}


