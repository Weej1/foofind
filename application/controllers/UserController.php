<?php
/**
 * foofind user controller - Handling user related actions
 *
 */

class UserController extends Zend_Controller_Action {

	protected $session = null;
	protected $_model;

	
	public function init() {

                $this->_helper->layout()->setLayout('with_search_form');
                $this->view->headTitle()->append(' - ');

		 $lang = Zend_Registry::get('Zend_Locale');
                 $this->view->lang = $lang;
                 $this->lang = $lang;

                $aNamespace = new Zend_Session_Namespace('Foofind');
		$this->location = $aNamespace->location;

	}

	/**
	 * Default action - if logged in, go to profile. If logged out, go to register.
	 *
	 */
	public function indexAction() {
		//by now just redir to /
		$this->_redirect ( '/' );

	}

	/**
	 * register - register a new user into the foofind database
	 */

	public function registerAction() {
		$request = $this->getRequest ();
		$form = $this->_getUserRegisterForm ();

		
		if ($this->getRequest ()->isPost ()) {

			
			if ($form->isValid ( $request->getPost () )) {

				// since we now know the form validated, we can now
				// start integrating that data submitted via the form
				// into our model
				$formulario = $form->getValues ();
				//Zend_Debug::dump($formulario);


				$model = $this->_getModel ();

				//check user email and nick if exists
				$checkemail = $model->checkUserEmail ( $formulario ['email'] );
				$checkuser = $model->checkUsername ( $formulario ['username'] );

				//not allow to use the email as username
				if ( $formulario['email'] == $formulario['username']) {

					$view = $this->initView();
					$view->error = $this->view->translate('You can not use your email as username. Please,
									      choose other username');
				}



				if ($checkemail !== NULL) {
					$view = $this->initView ();
					$view->error = $this->view->translate ( 'This email is taken. Please, try again.' );

				}

				if ($checkuser !== NULL) {
					$view = $this->initView ();
					$view->error = $this->view->translate ( 'This username is taken. Please, try again.' );

				}

				if ($checkemail == NULL and $checkuser == NULL) {

					// success: insert the new user on ddbb


					//update the ddbb with new password
					$password = $this->_generatePassword ();
					$data ['password'] = sha1 ( $password );
					$data ['email'] = $formulario ['email'];
					$data ['username'] = $formulario ['username'];

					$model->saveUser ( $data );

					//once token generated by model save, now we need it to send to the user by email
					$token = $model->getUserToken($formulario['email']);
					Zend_Debug::dump($token);


					//now lets send the validation token by email to confirm the user email
					$hostname = 'http://' . $this->getRequest ()->getHttpHost ();

					$mail = new Zend_Mail ( );
					$mail->setBodyHtml ( $this->view->translate ( 'Please, click on this url to finish your register process:<br />' )
							    . $hostname . $this->view->translate ( '/en/user/validate/t/' ) . $token .
							    '<br /><br />' . utf8_decode ( $this->view->translate ( 'After validate this link you will be able to access with your email and this password:' ) ) .
							    '<br />' . utf8_decode ( $this->view->translate ( 'Password:' ) ) .'  '.$password );
					$mail->setFrom ( 'noreply@foofind.com', 'foofind.com' );

					$mail->addTo($formulario['email']);
					$mail->setSubject ( $formulario ['username'] . $this->view->translate ( ', confirm your email' ) );
					$mail->send ();

					$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Check your inbox email to finish the register process' ) );

					$this->_redirect ( '/'.$this->view->lang.'/auth/login' );


				}

			}
		}
		// assign the form to the view
		$this->view->form = $form;

	}


       public function editAction() {

           $id = (int)$this->getRequest()->getParam('id');

           $auth = Zend_Auth::getInstance ();
           $model = $this->_getModel ();
           $user = $model->fetchUser( $id )->IdUser;


            if (($auth->getIdentity()->IdUser  == $user) ){ //if is the user profile owner lets edit

                require_once APPLICATION_PATH . '/forms/UserEdit.php';
		$form = new Form_UserEdit ( );
                $form->submit->setLabel('Save profile');
                $this->view->form = $form;



		if ($this->getRequest ()->isPost ()) {

			    $formData = $this->getRequest()->getPost();
                            if ($form->isValid($formData)) {
                                

                                $data['IdUser'] = $id;
                                $data['email'] = $form->getValue('email');
                                $data['username'] = $form->getValue('username');
                                $data['location'] = $form->getValue('location');

                                if ($form->getValue('password') ){
                                $data['password'] = sha1 ( $form->getValue('password') );
                                }

                                $model = $this->_getModel ();
				$model->updateUser ( $data );

                                //update the auth data stored 
                                $auth = Zend_Auth::getInstance ();
                                $auth->getStorage()->write( (object)$data);

                                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Your profile was edited succesfully!' ) );
				$this->_redirect ( '/' );

                            } else {
                                 $form->populate($formData);
                                 
                            }

                        } else {
                            $id = $this->_getParam('id', 0);
                            if ($id > 0) {
                                 $user = new Model_Users();
                                 $form->populate($user->fetchUser($id)->toArray() );
                            }


		}

            } else {
                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Your are not allowed to view this page' ) );
		$this->_redirect ( '/' );
            }

	}




	public function profileAction(){

		$request = $this->getRequest ();
		$user_id = (int)$this->_request->getParam ( 'id' );

                 if ($user_id == null){
                  $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'this user does not exist' ) );
		  $this->_redirect ( '/' );
               }

		$model = $this->_getModel ();
                $modelarray = $model->fetchUser($user_id);

                //lets overwrite the password and token values to assure not passed to the view ever!
                unset ($modelarray['password']);
                unset ($modelarray['token']);

		$this->view->user = $modelarray;

	}


	
	protected function _getModel() {
		if (null === $this->_model) {
			// autoload only handles "library" components.  Since this is an
			// application model, we need to require it from its application
			// path location.
			require_once APPLICATION_PATH . '/models/Users.php';
			$this->_model = new Model_Users ( );
		}
		return $this->_model;
	}

	/**
	 *
	 * @return Form_UserRegister
	 */
	protected function _getUserRegisterForm() {
		require_once APPLICATION_PATH . '/forms/UserRegister.php';
		$form = new Form_UserRegister ( );
		return $form;
	}

	/**
	 * forgot - sends (resets) a new password to the user
	 *
	 */

	public function forgotAction() {
		$request = $this->getRequest ();
		$form = $this->_getUserForgotForm ();

		if ($this->getRequest ()->isPost ()) {

			if ($form->isValid ( $request->getPost () )) {

				// collect the data from the form
				$f = new Zend_Filter_StripTags ( );
				$email = $f->filter ( $this->_request->getPost ( 'email' ) );

				$model = $this->_getModel ();
				$mailcheck = $model->checkUserEmail( $email );

				if ($mailcheck == NULL) {
					// failure: email does not exists on ddbb
					$view = $this->initView ();
					$view->error = $this->view->translate ( 'This email is not in our database. Please, try again.' );

				} else { // success: the email exists , so lets change the password and send to user by mail
					//Zend_Debug::dump($mailcheck->toArray());
					$mailcheck = $mailcheck->toArray ();

					//update the ddbb with new password
					$password = $this->_generatePassword ();
					$data ['password'] = sha1 ( $password );
					$data ['IdUser'] = $mailcheck ['IdUser'];

					//Zend_Debug::dump($data);
					$model->updateUser($data);

					//lets send the new password..
					$mail = new Zend_Mail ( );
					$mail->setBodyHtml ( utf8_decode ( $this->view->translate ( 'Hi, this is your new password:<br />' ) ) . $password );
					$mail->setFrom ( 'noreply@foofind.com', 'foofind.com' );

					$mail->addTo ( $mailcheck ['email'] );
					$mail->setSubject ( utf8_decode ( $this->view->translate ( 'Your foofind.com new password' ) ) );
					$mail->send ();

					$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Check your inbox email to get your new password' ) );

					$this->_redirect ( '/'.$this->view->lang.'/' );

				}

			}
		}
		// assign the form to the view
		$this->view->form = $form;

	}

	/**
	 * @abstract generate a text plain random password
	 * remember it's no encrypted !
	 * @return string (7) $pass
	 */
	protected function _generatePassword() {
		$salt = "abcdefghjkmnpqrstuvwxyz123456789";
		mt_srand( ( double ) microtime () * 1000000 );
		$i = 0;
		while ( $i <= 6 ) {
			$num = mt_rand() %33;
                        $pass .= substr ( $salt, $num, 1 );
			$i ++;
		}

		return $pass;
	}

	/**
	 *
	 * @return Form_UserForgotForm
	 */
	protected function _getUserForgotForm() {
		require_once APPLICATION_PATH . '/forms/UserForgot.php';
		$form = new Form_UserForgot ( );
		return $form;
	}

	/**
	 * Validate - check the token generated  sent by mail by registerAction, then redirect to
	 * the logout  page (index home).
	 * @param t
	 *
	 */
	public function validateAction() {

		// Do not attempt to render a view
		$this->_helper->viewRenderer->setNoRender ( true );
		$token = $this->_request->getParam ( 't' ); //the token



		if (! is_null ( $token )) {

			//lets check this token against ddbb
			$model = $this->_getModel ();
			$validatetoken = $model->validateUserToken ( $token );

			//$validatetoken = $validatetoken->toArray ();
			//Zend_Debug::dump ( $validatetoken );

			if ($validatetoken !== NULL) {
				//OK
				//update the active status to 1 of the user
				$data ['active'] = '1';
				$data ['IdUser'] = $validatetoken ['IdUser'];

				//Zend_Debug::dump($data);
				$model->updateUser($data);

				$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Register finished succesfully. Now you can login.' ) );
				//kill the user logged in (if exists)
				Zend_Auth::getInstance ()->clearIdentity ();
				$this->session->logged_in = false;
				$this->session->username = false;

				$this->_redirect ( '/'.$this->view->lang );

			} else {
				// just redirect to index, dont tell anything to badboys
				throw new Zend_Controller_Action_Exception ( 'This token does not exist', 404 );

			}

		} else {
			$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Sorry, register url no valid or expired.' ) );
			$this->_redirect ( '/' );
		}

	}

}

