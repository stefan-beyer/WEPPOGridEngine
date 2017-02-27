<?php

namespace WEPPO\Grid;

/**
 * Verschiedene Provider mit Blog-Kontext, um automatisch Imnhalte erzeugen zu lassen.
 * 
 */

/**
 * Methode zum Abfragen des Blog-Controllers
 * für alle BlogProvider
 */
trait BlogProvider {

    /**
     * Falls verfügbar, BlogController-Objekt holen.
     * @return BlogController
     */
    static function getBlogController() {
        if (class_exists('\WEPPO\Grid\BlogController')) {
            return BlogController::getInstance();
        }
        return null;
    }

}

/**
 * Blog-Zellen-Provider
 */
class BlogProviderCell extends Cell {

    use BlogProvider;
}

/**
 * Blog-Reihen-Provider
 */
class BlogProviderRow extends Row {

    use BlogProvider;
}

/**
 * Zellen-Provider für Navigation zwischen einzelnen Artikeln
 * 
 * Erzeugt ein Content-Objekt mit der Naviagation
 * 
 * Delegiert die Aufgabe wieder zurück an den Blog-Controller
 */
class BlogSingleNavigationCell extends BlogProviderCell {

    function getContent() {

        # Das ganze kann nur durchgeführt werden, wenn eine Blog-Controller-Instace verfügbar ist
        if ($bc = self::getBlogController()) {
            $c = new \WEPPO\Grid\Content();
            $c->loadEmpty();
            $c->directContent = $bc->createSinglePostNav();
            $c->set_parent($this);
            return $c;
        }
        return null;
    }

}

/**
 * Zellen-Provider für einen Artikel
 * 
 * Nimmt den current_post aus dem BlogController, bereitet ihn etwas auf
 * und gibt ihn dann als Content der Zelle zurück.
 */
class BlogArticleCell extends BlogProviderCell {

    function getContent() {

        $lang = \i18n\i18n::getInstance()->lang;

        # Das ganze kann nur durchgeführt werden, wenn eine Blog-Controller-Instace verfügbar ist
        if (($bc = self::getBlogController()) && $bc->current_post) {
            $template = $bc->current_post->getTemplate($lang);
            if ($template) {
                $template->setName('blog.article');
            }
            $bc->current_post->set_parent($this);
            return $bc->current_post;
        } else {

            $c = parent::getContent();
            if ($c) {
                $template = $c->getTemplate($lang);
                $tn = $this->option("template");
                if ($template && $tn) {
                    $template->setName($tn);
                }
                return $c;
            }
        }

        return null;
    }

}

// BlogArticleCell

/**
 * Zellen-Provider für den Meta-Block eines Artikels
 * 
 * Nimmt den current_post aus dem BlogController, bereitet ihn etwas auf
 * (setzt u.A. das 'blog.meta'-Template
 * und gibt ihn dann als Content der Zelle zurück.
 */
class BlogMetaCell extends BlogProviderCell {

    function getContent() {

        # Das ganze kann nur durchgeführt werden, wenn eine Blog-Controller-Instace verfügbar ist
        if (($bc = self::getBlogController()) && $bc->current_post) {
            $lang = BlogController::getLang();
            $template = $bc->current_post->getTemplate($lang);
            if ($template) {
                $template->setName('blog.meta');
            }
            $bc->current_post->set_parent($this);
            return $bc->current_post;
        }

        return null;
    }

}


class BlogListRow extends BlogProviderRow {

    function getCells() {

        # Das ganze kann nur Dirchgeführt werden, wenn eine Blog-Controller-Instace verfügbar ist
        $bc = self::getBlogController();
        if (!!$bc) {

            $cells = array();
            if (!count($bc->current_post_list)) {
                $c = new Cell();
                $c->loadEmpty();
                $c->directContent = '<p>' . __('No posts found') . '.</p>';
                $c->set_parent($this);
                $cells[] = $c;
            } else {
                $lang = BlogController::getLang();

                $navigation_cell = new Cell();
                $navigation_cell->loadEmpty();
                $navigation_cell->manual_mode();
                $navigation_cell->set_parent($this);
                $navigation_cell->directContent = '<center class="postNav">';
                if ($bc->page > 1) {
                    $navigation_cell->directContent .= '<a class="button icon left u-pull-left" href="?p=' . ($bc->page - 1) . '"></a>';
                }
                $page_count = ceil($bc->current_total_count / BLOG_PAGE_SIZE);
                if ($page_count > 1) {
                    $navigation_cell->directContent .= $bc->page . ' / ' . $page_count;
                }
                if ($bc->page * BLOG_PAGE_SIZE < $bc->current_total_count) {
                    $navigation_cell->directContent .= '<a class="button icon right u-pull-right" href="?p=' . ($bc->page + 1) . '"></a>';
                }
                $navigation_cell->directContent .= '</center>';

                $cells[] = $navigation_cell;




                foreach ($bc->current_post_list as &$post) {

                    $c = new Cell();
                    $c->loadEmpty();
                    $c->manual_mode();
                    $c->set_parent($this);
                    $c->_content = &$post;
                    $post->set_parent($c);

                    $post->loadTags($lang);
                    # template in vorschau ändern
                    $template = $post->getTemplate($lang);
                    if ($template) {
                        $template->setName($template->getName() . '.preview');
                        //$bc->preparePostTemplate($post, $lang);
                    }


                    $cells[] = $c;
                }
                //_o($bc->current_post_list);

                $cells[] = $navigation_cell;
            }

            return $cells;
        }
        return null;
    }

}
