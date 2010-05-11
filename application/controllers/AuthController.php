<?php

class AuthController extends Zend_Controller_Action {
	public function init() {
            $this->referer = $_SERVER['HTTP_REFERER'];
            $locale = Zend_Registry::get ( "Zend_Locale" );
            $this->lang = $locale->getLanguage ();
            $this->view->lang = $locale->getLanguage ();

            $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
            $this->view->mensajes = $this->_flashMessenger->getMessages ();
	}

	
	public function indexAction() {
            $this->_redirect ( '/' );
	}

	/**
	 * Log in - show the login form or handle a login request
	 *
	 */
        public function loginAction() {

            $auth = Zend_Auth::getInstance ();
            if ($auth->hasIdentity()) $this->_redirect ( '/' );
            
            $request = $this->getRequest ();
            $form = $this->_getUserLoginForm();
            $this->view->source = $request->getParam('source');

            if ($this->hasValidReferer()){
                $aNamespace = new Zend_Session_Namespace('Foofind');
                $aNamespace->redir = $this->referer;
            }

            switch ($this->view->source ) {
                case 'vote.foo':
                case 'comment.foo':
                    $this->view->message = "Please, login to ".substr($this->view->source,0,strpos($this->view->source, "."));
                    $form->setAction("/{$this->view->lang}/auth/login");
                    $this->_helper->layout()->disableLayout();
                    break;
                default:
                    $this->_helper->layout()->setLayout('with_search_form');
                    $this->view->headTitle()->append(' - ');
                    break;
            }

            $this->view->headTitle()->append( $this->view->translate ( 'Login' ) );

            if ($this->getRequest ()->isPost ()) {
                    if ($form->isValid ( $request->getPost () )) {

                            // collect the data from the user
                            $f = new Zend_Filter_StripTags ( );
                            $email = $f->filter ( trim( $this->_request->getPost ( 'email' ) ) );
                            $password = $f->filter ( trim( $this->_request->getPost ( 'password' ) ) ); //trim whitespaces from copy&pasting the pass from email

                            //DDBB validation
                            // setup Zend_Auth adapter for a database table

                            $readConf = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
                            $dbAdapter = Zend_Db::factory ($readConf->resources->db);

                            $authAdapter = new Zend_Auth_Adapter_DbTable ( $dbAdapter );
                            $authAdapter->setTableName ( 'ff_users' );
                            $authAdapter->setIdentityColumn ( 'email' );
                            $authAdapter->setCredentialColumn ( 'password' );
                            // Set the input credential values to authenticate against
                            $authAdapter->setIdentity ( $email );
                            $authAdapter->setCredential  ( hash('sha256', $password, TRUE) );

                            // do the authentication
                            $auth = Zend_Auth::getInstance();


                            //check first if the user is activated (by confirmed email)
                            $select = $authAdapter->getDbSelect ();
                            $select->where ( 'active > 0' );
                            //check if the user is not locked (spammers, bad users, etc)
                            $select->where ( 'locked = 0' );

                            $result = $authAdapter->authenticate ();

                            if ($result->isValid ()) {
                                    // success: store database row to auth's storage
                                    // system. (Not the password though!)
                                    $data = $authAdapter->getResultRowObject ( null, 'password' );
                                    $auth->getStorage ()->write ( $data );

                                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'You are now logged in, ' ) . $auth->getIdentity()->username );

                                    //check the redir value if setted
                                    $aNamespace = new Zend_Session_Namespace('Foofind');
                                    $redir = $aNamespace->redir;

                                    if ($redir !== null){
                                      $aNamespace->redir = null; //reset redir value
                                      $this->_redirect ( $redir );
                                    }
                                    else
                                        $this->_redirect ( '/' );
                            } else {
                                    // failure: wrong username
                                    $view = $this->initView ();
                                    $view->error = $this->view->translate ( 'Wrong email or password, please try again' );
                            }

                    }
            }
            // assign the form to the view
            $this->view->form = $form;

	}

	/**
	 *
	 * @return Form_UserLogin
	 */
	protected function _getUserLoginForm() {
		require_once APPLICATION_PATH . '/forms/UserLogin.php';
		$form = new Form_UserLogin ( );
		return $form;
	}

	/**
	 * Log out - delete user information and clear the session, then redirect to
	 * the log in page.
	 */
	public function logoutAction() {
		Zend_Auth::getInstance ()->clearIdentity ();
		$this->session->logged_in = false;
		$this->session->username = false;

                if ($this->hasValidReferer())
                    $this->_redirect($this->referer);
		else
                    $this->_redirect ( '/' );
                
	}

        function hasValidReferer()
        {
            if (!$this->referer) return false;

            # invalid if is the same URL
            $currentURI = $_SERVER['SCRIPT_URI'];
            if (strcmp($this->referer, $currentURI) == 0) return false;

            # invalid if is not in this site
            $barpos = strpos($currentURI, "/", 8);
            if (strncmp($this->referer, $currentURI, $barpos ) != 0) return false;

            return true;
        }
}

