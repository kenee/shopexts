<?php
class ctl_article extends shopPage{

    var $type = 'articles';

    function index($articleid) {
        $objArticle = $this->system->loadModel('content/article');
        $this->pagedata['article'] = $objArticle->get($articleid);
        $this->id = array('node_id'=>$articleid);
        if(!$this->pagedata['article']){
            $this->system->error(404);
            exit;
        }
        $this->path=array('title'=>'');
        $this->title = $this->pagedata['article']['title'];

        $this->output();
    }
}
?>
