<?php
namespace WEPPO\Grid;

require_once APP_ROOT.'controller/class.fcs-base.php';

/**
 * Blog-Logik
 * 
 * @TODO Von FCS unabhänig machen??
 */
class BlogController extends Base {
    
    var $main_content = '';
    var $pathData;
    var $action;
    
    var $current_post = null;
    var $current_post_list;
    var $current_total_count;
    
    var $page;
    
    var $visible_only = true;
    
    
    
    public function __construct(&$page, &$requestHandler) {
        parent::__construct($page, $requestHandler);
        
        $this->indexAction = 'list';
        
        if (\FCS\is_admin()) $this->visible_only = false;
        
    }
    
    
    
    function getMainContent() {
        return $this->main_content;
    }
    
    function doTag($pd) {
        $tag = isset($pd[0]) ? urldecode($pd[0]) : '';
        
        if (empty($tag)) {
            $this->doList($pd);
            return;
        }
        
        
        $this->current_post_list = $this->loadPosts($this->current_total_count, function() use ($tag) {
            # sprache beachten
            //\WEPPO\Grid\BlogPost::whereTag($tag, self::getLang());
            # sprache nicht berücksichtigen
            \WEPPO\Grid\BlogPost::whereTag($tag, null);
        });
        
        $this->main_content = $this->createPostList(static::getLang());
        
        $this->doOutput();
        
    }
    
    function doList($pd) {
        #                                            referenz!
        $this->current_post_list = $this->loadPosts($this->current_total_count);
        
        $this->main_content = $this->createPostList(static::getLang());
        
        $this->doOutput();
    }
    
    function doArticle($pd) {
        
        $this->pathData = $pd;
        $this->action = 'article';
        
        $post_name = isset($this->pathData[0]) ? $this->pathData[0] : '';
        if ($post_name) {
            $this->current_post = \WEPPO\Grid\BlogPost::createFromName($post_name, CONTENT_CLASS_BLOG, $this->visible_only ? true : null);
        } else {
            $this->current_post = null;
        }
        
        if ($this->current_post) {
            $this->current_post->loadTags(self::getLang());
            
            $template = $this->current_post->getTemplate(self::getLang());
            if ($template) {
                
                //$this->preparePostTemplate($this->current_post, self::getLang());
                
                $this->T->set('title', $this->T->get('title') . ' - ' . strip_tags($template->get('title')));
            }
        }
        
        
        $layout = \WEPPO\Grid\Layout::create('BlogArticle');
        
        $this->main_content = $layout->getOutput(self::getLang()); // sprach bleibt so, weil wir hier nicht die einzelsprache des artikels beeinflussen
        
        $this->doOutput();
    }
    
    /*
    function preparePostTemplate(&$post, $lang) {
        $template = $post->getTemplate($lang);
        if ($template) {
            $post_url = $this->baseURL.'article/'.identifier($post->name);
            $template->set('url', $post_url);
            $template->set('baseURL', $this->baseURL);
            $post->prepareTemplate($lang);
        }
    }*/
    
    function createSinglePostNav() {
        if (!$this->current_post) return '';
        
        # zeitlich vorhergegangenen und nachfolgenden Post laden
        # das functioniert nur wenn die daten unterschiedlich sind
        
        
        \WEPPO\Grid\BlogPost::createObjects(false);
        \WEPPO\Grid\BlogPost::orderBy('date', 'ASC');
        \WEPPO\Grid\BlogPost::orderBy('gridcontents1.id', 'ASC');
        if ($this->visible_only) {
            \WEPPO\Grid\BlogPost::where('visible', 1);
        }
        $all = \WEPPO\Grid\BlogPost::get();
        $post_count = count($all);
        
        $prev = -1;
        $next = -1;
        $num = -1;
        foreach ($all as $k=>&$v) {
            if ($v['id'] === $this->current_post->id) {
                $prev = $k+1;
                $num  = $k+1;
                $next = $k-1;
                break;
            }
        }
        
        if ($next >= 0 && $next < $post_count) {
            $next = $all[$next]['id'];
            try {
                $next = new \WEPPO\Grid\BlogPost($next);
                $next = $next->name;
            } catch (\Exception $e) {
                $next = null;
            }
        } else {
            $next = null;
        }
        
        if ($prev >= 0 && $prev < $post_count) {
            $prev = $all[$prev]['id'];
            try {
                $prev = new \WEPPO\Grid\BlogPost($prev);
                $prev = $prev->name;
            } catch (\Exception $e) {
                $prev = null;
            }
        } else {
            $prev = null;
        }
        
        unset ($all);
        
        /*
        \WEPPO\Grid\BlogPost::where('date', $this->current_post->date->format('Y-m-d H:i:s'), '>');
        \WEPPO\Grid\BlogPost::where('gridcontents1.id', $this->current_post->id, '<>');
        \WEPPO\Grid\BlogPost::orderBy('date', 'ASC');
        \WEPPO\Grid\BlogPost::orderBy('gridcontents1.id', 'ASC');
        $prev = \WEPPO\Grid\BlogPost::get(1);
        if ($prev && isset($prev[0])) $prev = $prev[0]->name;
        
        
        # Anzahl der davor.. um "Position" zu bestimmen
        \WEPPO\Grid\BlogPost::createObjects(false);
        \WEPPO\Grid\BlogPost::where('date', $this->current_post->date->format('Y-m-d H:i:s'), '<=');
        \WEPPO\Grid\BlogPost::where('gridcontents1.id', $this->current_post->id, '<>');
        \WEPPO\Grid\BlogPost::orderBy('date', 'DESC');
        \WEPPO\Grid\BlogPost::orderBy('gridcontents1.id', 'DESC');
        $num = count(\WEPPO\Grid\BlogPost::get());
        $num++;*/
        
        $navigation = '<center class="postNav">';
        if ($next) {
            $navigation .= ' <a class="button icon left u-pull-left" title="'.__('older', false).'" href="'.$this->baseURL.'article/'.$next.'"></a>';
        }
        if ($post_count > 1) {
            $navigation .= $num.' / ' . $post_count;
        }
        if ($prev) {
            $navigation .= '<a class="button icon right u-pull-right" title="'.__('newer', false).'" href="'.$this->baseURL.'article/'.$prev.'"></a> ';
        }
        $navigation .= '</center>';
        return $navigation;
    }
    
    function getPostCount() {
        \WEPPO\Grid\BlogPost::createObjects(false);
        if ($this->visible_only) \WEPPO\Grid\BlogPost::where('visible', 1);
        $posts = \WEPPO\Grid\BlogPost::get();
        return count($posts);
        /*
        if (isset($post_count[0])) $post_count = $post_count[0]['COUNT'];
        else $post_count = 0;
        return $post_count;*/
    }
    
    function loadPosts(&$totalCount, $pre = null) {
        $this->page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if ($this->page < 1) $this->page = 1;
        
        $limit = array(($this->page - 1)*BLOG_PAGE_SIZE, BLOG_PAGE_SIZE);
        
        if ($pre) $pre();
        $totalCount = $this->getPostCount();
        
        if ($pre) $pre();
        \WEPPO\Grid\BlogPost::orderBy('date', 'DESC');
        \WEPPO\Grid\BlogPost::orderBy('gridcontents1.id', 'DESC');
        if ($this->visible_only) \WEPPO\Grid\BlogPost::where('visible', 1);
        $posts = \WEPPO\Grid\BlogPost::get($limit);
        
        //_o($posts);
        
        return $posts;
    }
    
    function createPostList($lang) {
        
        $layout = \WEPPO\Grid\Layout::create('BlogList');
        
        return $layout->getOutput($lang);
    }
    
    /**
     * @deprecated
     */
    function render($part, &$caller) {
        return 'nothing';
    }
    
}


