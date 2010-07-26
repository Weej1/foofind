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
        $request = $this->getRequest();
        $url = $request->getParam('id');
        $uri = $this->_helper->fileutils->url2uri($url);
        $hexuri = $this->_helper->fileutils->uri2hex($uri);

      //if userType = 1 dont let vote
       if ( $this->identity->type == 1 ){
           echo 'You are not allowed to do that. (user type 1)';
           return ;
       }

       // vote: files votes { _id: idFile_idUser, u: idUser (index1), k: user karma, d: date, l: language }
       try {
            $type = $request->getParam('type');

            $data = array();
            $data['_id'] = $hexuri."_".$this->identity->_id;
            $data['u'] = $this->identity->_id;
            $data['l'] = $this->_helper->checklang->check();
            $data['d'] = new MongoDate(date());
            $data['k'] = ($type==1?1:-1)*$this->identity->karma;
            $this->umodel->saveVote($data);
        } catch (Exception $e)
        {
        }

        $votes = $this->umodel->getFileVotesSum($hexuri, $this->identity->_id);
        unset($votes['user']);
        $file = $this->fmodel->updateVotes($hexuri, $votes);

        echo Zend_Json::encode($votes);
    }

    public function commentAction()
    {
        $request = $this->getRequest ();
        $id = (int)$request->getParam('id');

       //if userType = 1 dont let vote
       if ( ($this->identity->type == 1 ) and ( APPLICATION_ENV == 'production') ){
           echo 'You are not allowed to do that. (user type 1)';
           return ;
       }
       
        
        try {
            
            $data = $this->getData($request);
            $data['IdComment'] = (int)$request->getParam('id');

            switch ((int)$data['VoteType'])
            {
                case 1:
                    $this->umodel->deleteCommentVote($id, $this->identity->IdUser, 2);
                    break;
                case 2:
                    $this->umodel->deleteCommentVote($id, $this->identity->IdUser, 1);
                    break;
            }

            $this->umodel->saveCommentVote($data);
        } catch (Exception $e)
        {
        }

        $votes = $this->umodel->getCommentVotes($id);
        echo Zend_Json::encode($votes->toArray());
    }

    private function getData($request)
    {
        $data = array ('VoteType' => (int)$request->getParam('type'), 'IdUser' => $this->identity->IdUser,
                        'karma' => $this->identity->karma);

        return $data;
    }
}