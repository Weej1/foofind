<?php

/**
 * CommentController
 *
 * @author  dani remeseiro
 */

class CommentController extends Zend_Controller_Action {

	/**
	 * Overriding the init method to also load the session from the registry
	 *
	 */
	public function init() {
		parent::init ();
		$this->_helper->viewRenderer->setNoRender ( true );

		$this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );

		//$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->baseUrl = Zend_Controller_Front::getParam ( $route );

		$locale = Zend_Registry::get ( "Zend_Locale" );
		$this->view->lang = $locale->getLanguage ();
	}

	public function createAction() {
		$request = $this->getRequest ();
		$id = $this->_request->getParam ( 'filename' );

                $fmodel = new Model_Files();
                $this->filename = $fmodel->getFilename($id);
                
		//first we check if user is logged, if not redir to login
		$auth = Zend_Auth::getInstance ();
		if (! $auth->hasIdentity ()) {

			//keep this url in zend session to redir after login
			$aNamespace = new Zend_Session_Namespace('Foofind');
			$aNamespace->redir =  "/{$this->view->lang}/download/{$this->filename['IdFile']}/{$this->filename['Filename']}.html";
			$this->_redirect ( "/{$this->view->lang}/auth/login" );
		} else {
			$form = $this->_getCommentForm ();

			// check to see if this action has been POST'ed to
			if ($this->getRequest ()->isPost () ) {

				// now check to see if the form submitted exists, and
				// if the values passed in are valid for this form
				if ($form->isValid ( $request->getPost () )) {
                                    
					$formulario = $form->getValues ();

                                        //if comment its empty dont do nothing as redir to same ad
                                        if (empty ($formulario['text'])){
                                            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Write something!' ) );

                                            $this->_redirect ( "/{$this->view->lang}/download/{$this->filename['IdFile']}/{$this->filename['Filename']}.html" );
                                        }

					//strip html tags to body
					$formulario['text'] = strip_tags($formulario['text']);

					//anti hoygan to body
					$split=explode(". ", $formulario['text']);

					foreach ($split as $sentence) {
						$sentencegood = ucfirst(mb_convert_case($sentence, MB_CASE_LOWER, "UTF-8"));
						$formulario['text'] = str_replace($sentence, $sentencegood, $formulario['text']);
					}

                                        /*
					//get the ip of the ad publisher
					if (getenv(HTTP_X_FORWARDED_FOR)) {
					    $ip = getenv(HTTP_X_FORWARDED_FOR);
					} else {
					    $ip = getenv(REMOTE_ADDR);
					}
					$formulario['ip'] = $ip;
                                        */
                                        
					$formulario['IdFile'] = $this->filename['IdFile'];
					$formulario['IdFilename'] = $id;

					//get this ad user owner
					$formulario['IdUser'] = $auth->getIdentity()->IdUser;
					$formulario['lang'] = $this->view->lang;

					$model = new Model_Users();
					$model->saveComment( $formulario );

					$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Comment published succesfully!' ) );

					$this->_redirect ( "/{$this->view->lang}/download/{$this->filename['IdFile']}/{$this->filename['Filename']}.html");
				}
			}
		}

	}

	public function editAction() {
		$request = $this->getRequest ();
		$form = $this->_getCommentForm ();

		// check to see if this action has been POST'ed to
		if ($this->getRequest ()->isPost ()) {

			// now check to see if the form submitted exists, and
			// if the values passed in are valid for this form
			if ($form->isValid ( $request->getPost () )) {

				// since we now know the form validated, we can now
				// start integrating that data submitted via the form
				// into our model
				$formulario = $form->getValues ();
			}
		}
	}

	/**
	 *
	 * @return Form_AdEdit
	 */
	protected function _getCommentForm() {
		require_once APPLICATION_PATH . '/forms/Comment.php';
		$form = new Form_Comment ();

		// assign the form to the view
		$this->view->form = $form;
		return $form;
	}

	public function deleteAction() {

	}
}

