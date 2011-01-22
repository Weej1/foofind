<?php

class VoteController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();

        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) return;

        $this->identity = $auth->getIdentity();
        $this->umodel = new Model_Users();
        $this->fmodel = new Model_Files();
    }

    public function fileAction()
    {
        //if userType = 1 dont let vote
        if ( $this->identity->type == 1 ){
            echo 'You are not allowed to do that. (user type 1)';
            return ;
        }
        
        $request = $this->getRequest();
        $url = $request->getParam('id');
        $uri = $this->_helper->fileutils->url2uri($url);
        $hexuri = $this->_helper->fileutils->uri2hex($uri);
        $type = $request->getParam('type');

        try {
            $data = array();
            $data['_id'] = $hexuri."_".$this->identity->_id;
            $data['u'] = $this->identity->_id;
            $data['l'] = $this->_helper->checklang->check();
            $data['d'] = new MongoDate(time());
            $data['k'] = ($type==1?1:-1)*$this->identity->karma;
            $this->umodel->saveVote($data);

        } catch (Exception $e)
        {
        }
        
        $votes = $this->umodel->getFileVotesSum($hexuri, $this->identity->_id);
        unset($votes['user']);
        $this->fmodel->updateVotes($hexuri, $votes);

        echo Zend_Json::encode($votes);
    }
    
    public function commentAction()
    {
        //if userType = 1 dont let vote
        if ( $this->identity->type == 1 ){
           echo 'You are not allowed to do that. (user type 1)';
           return ;
        }

        $request = $this->getRequest();
        $id = $request->getParam('id');
        $type = $request->getParam('type');

        try {
            $comment = $this->umodel->getComment($id);
                    
            $data = array();
            $data['_id'] = $id."_".$this->identity->_id;
            $data['f'] = $comment['f'];
            $data['u'] = $this->identity->_id;
            $data['k'] = ($type==1?1:-1)*$this->identity->karma;
            $data['d'] = new MongoDate(time());
            $this->umodel->saveCommentVote($data);
        } catch (Exception $e)
        {
        }

        $votes = $this->umodel->getCommentVotesSum($id, $this->identity->_id);
        unset($votes['u']);
        $this->umodel->updateCommentVotes($id, $votes);

        echo Zend_Json::encode($votes);
    }

    private function getData($request)
    {
        $data = array ('VoteType' => (int)$request->getParam('type'), 'IdUser' => $this->identity->IdUser,
                        'karma' => $this->identity->karma);

        return $data;
    }
}