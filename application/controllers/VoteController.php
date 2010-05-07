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
    }

    public function fileAction()
    {
        $request = $this->getRequest();

        try {
            $id = (int)$request->getParam('id');
            $data = $this->getData($request);
            $data['IdFile'] = $id;
            $data['lang'] = $this->_helper->checklang->check();
            if ($idfn = $request->getParam('idfilename')) $data['IdFilename'] = (int)$idfn;

            switch ((int)$data['VoteType'])
            {
                case 1:
                    $this->umodel->deleteVote($id, $this->identity->IdUser, 2);
                    break;
                case 2:
                    $this->umodel->deleteVote($id, $this->identity->IdUser, 1);
                    break;
            }

            $this->umodel->saveVote($data);
        } catch (Exception $e)
        {
        }
        
        $votes = $this->umodel->getVotes($id);
        echo Zend_Json::encode($votes->toArray());
    }

    public function commentAction()
    {
        $request = $this->getRequest ();

        $data = $this->getData($request);
        $data['IdComment'] = (int)$request->getParam('id');
        $this->umodel->saveCommentVote($data);
    }

    private function getData($request)
    {
        $data = array ('VoteType' => (int)$request->getParam('type'), 'IdUser' => $this->identity->IdUser,
                        'karma' => $this->identity->karma);

        return $data;
    }
}

/*
CREATE TABLE `ff_comment_vote` (
  `IdComment` int(4) unsigned NOT NULL,
  `IdUser` int(4) unsigned NOT NULL,
  `VoteType` int(1) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `karma` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`IdComment`,`IdUser`,`VoteType`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
*/