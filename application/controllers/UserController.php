<?php
class UserController extends Zend_Controller_Action
{

    protected $session = null;
    protected $_model;


    public function init()
    {

        $this->_helper->layout()->setLayout('with_search_form');
        $this->view->headTitle()->append(' - ');
        $this->view->lang =  $this->_helper->checklang->check();

        $aNamespace = new Zend_Session_Namespace('Foofind');
        $this->location = $aNamespace->location;

    }


    public function indexAction()
    {
        //by now just redir to /
        $this->_redirect ( '/' );
        return ;
    }


    public function registerAction()
    {
        $this->view->headTitle()->append($this->view->translate('New user'));
        $request = $this->getRequest ();
        $form = $this->_getUserRegisterForm ();


        if ($this->getRequest ()->isPost ())
        {

            if ($form->isValid ( $request->getPost () ))
            {
                $formulario = $form->getValues ();

                 //check 2 passwords matches
                $checkpasswords = ($formulario['password1']  == $formulario['password2'] );
                if ( $checkpasswords == FALSE)
                {
                    $view = $this->initView();
                    $view->error .= $this->view->translate('The  passwords entered do not match.');
                }

                //check agree tos and privacy
                $checkagree = ($formulario['agree'] == '1');
                if ( $checkagree == FALSE  )
                {
                    $view = $this->initView();
                    $view->error .= $this->view->translate('Please, accept the terms of use and privacy policy');
                    
                }


                 $model = $this->_getModel ();

                //check user email and nick if exists
                $checkemail = $model->checkUserEmail ( $formulario ['email'] );
                $checkuser = $model->checkUsername ( $formulario ['username'] );

                //not allow to use the email as username
                if ( $formulario['email'] == $formulario['username'])
                {

                    $view = $this->initView();
                    $view->error .= $this->view->translate('You can not use your email as username. Please,
									      choose other username');
                }


                //check user email and nick if exists
                $checkemail = $model->checkUserEmail ( $formulario ['email'] );
                $checkuser = $model->checkUsername ( $formulario ['username'] );

                if ($checkemail !== NULL)
                {
                    $view = $this->initView ();
                    $view->error .= $this->view->translate ( 'This email is taken.' );

                }

                if ($checkuser !== NULL)
                {
                    $view = $this->initView ();
                    $view->error .= $this->view->translate ( 'This username is taken.' );

                }

                if ($checkemail == NULL and $checkuser == NULL and $checkpasswords == TRUE and $checkagree == TRUE )
                {

                    // success: insert the new user on ddbb
                    //update the ddbb with new password
                    $data ['email'] = $formulario ['email'];
                    $data ['password'] =  $formulario ['password1'] ;
                    $data ['username'] = $formulario ['username'];
                    $model->saveUser ( $data );

                    //once token generated by model save, now we need it to send to the user by email
                    $token = $model->getUserToken($formulario['email']);

                    //now lets send the validation token by email to confirm the user email
                    $hostname = 'http://' . $this->getRequest ()->getHttpHost ();

                    $mail = new Zend_Mail ( );
                    $mail->setBodyHtml ( $this->view->translate ( 'Please, click on this url to finish your register process:' ).'<br />'
                            . $hostname  . '/' . $this->view->lang  . '/user/validate/t/'  . $token .
                            '<br /><br />_______________________________<br />' . utf8_decode ( $this->view->translate ( 'The foofind team.' ) ) );
                    $mail->setFrom ( 'noreply@foofind.com', 'foofind.com' );

                    $mail->addTo($formulario['email']);
                    $mail->setSubject ( $formulario ['username'] . $this->view->translate ( ', confirm your email' ) );
                    $mail->send ();
                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Check your inbox email to finish the register process' ) );
                    $this->_redirect ( '/' );
                }

            }
        }
        $this->view->form = $form;
    }


    public function editAction()
    {

        $this->view->headTitle()->append( $this->view->translate ( 'Edit your profile' ) );

        $id = $this->view->id = (int)$this->getRequest()->getParam('id');

        $auth = Zend_Auth::getInstance ();
        $model = $this->_getModel ();
        $user = $model->fetchUser( $id )->IdUser;


        if (($auth->getIdentity()->IdUser  == $user) )
        { //if is the user profile owner lets edit

            require_once APPLICATION_PATH . '/forms/UserEdit.php';
            $form = new Form_UserEdit ( );
            $form->submit->setLabel('Save profile');
            $this->view->form = $form;



            if ($this->getRequest ()->isPost ())
            {

                $formData = $this->getRequest()->getPost();
                if ($form->isValid($formData))
                {


                    //chekusername if exists, dont let change it
                    $checkuser = $model->checkUsername ( $form->getValue('username') );

                    if ( !is_null($checkuser) and ($checkuser['username'] != $auth->getIdentity()->username) )
                    {
                        $this->view = $this->initView ();
                        $this->view->error = $this->view->translate ( 'This username is taken. Please choose another one.' );
                        return;
                    }


                    $data['IdUser'] = $id;
                    $data['username'] = $form->getValue('username');
                    $data['location'] = $form->getValue('location');

                    if ($form->getValue('password') )
                    {
                        $data['password'] = hash('sha256', trim( $form->getValue('password') ), TRUE);
                    }

                    $model = $this->_getModel ();
                    $model->updateUser ( $data );

                    //now need to get the fresh user row to pass to auth
                    $freshuser = $model->fetchUser($id);
                    unset ($freshuser['password']); //not accesible ever, but just in case...

                    //update the auth data stored
                    $auth = Zend_Auth::getInstance ();
                    $auth->getStorage()->write( (object)$freshuser );

                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Your profile was edited succesfully!' ) );
                    $this->_redirect ( '/' );

                } else
                {
                    $form->populate($formData);

                }

            } else
            {
                $id = $this->_getParam('id', 0);
                if ($id > 0)
                {
                    $user = new Model_Users();
                    $form->populate($user->fetchUser($id)->toArray() );
                }


            }

        } else
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'You are not allowed to view this page' ) );
            $this->_redirect ( '/' );
            exit ();
        }

    }


    public function deleteAction()
    {
        $this->view->headTitle()->append( $this->view->translate ( 'Delete your profile' ) );

        $id = (int)$this->getRequest()->getParam('id');

        $auth = Zend_Auth::getInstance ();
        $model = $this->_getModel ();
        $user = $model->fetchUser( $id )->IdUser;


        if (($auth->getIdentity()->IdUser  == $user) )
        { //if is the user profile owner lets delete it

            if ($this->getRequest()->isPost())
            {
                $del = $this->getRequest()->getPost('del');
                if ($del == 'Yes')
                {
                    //delete user, and all his content
                    $model->deleteUser($id);
                    $model->deleteUserComments($id);
                    $model->deleteUserCommentsVotes($id);
                    $model->deleteUserVotes($id);

                    //kill the session and go home
                    Zend_Auth::getInstance ()->clearIdentity ();
                    $this->session->logged_in = false;
                    $this->session->username = false;
                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Your account has been deleted.' ) );
                    $this->_redirect ( '/' );
                    return ;

                } else
                {
                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Nice to hear that' ) );
                    $this->_redirect ( '/' );
                     return ;
                }

            } else
            {
                $id = $this->_getParam('id', 0);

            }

        } else
        {

            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'You are not allowed to view this page' ) );
            $this->_redirect ( '/' );
            exit ();
        }
    }




    public function profileAction()
    {
        $request = $this->getRequest ();
        $username = (string)$this->_request->getParam ( 'username' );

        $model = $this->_getModel ();
        $this->view->user = $model->fetchUserByUsername($username);

        if ( $this->view->user == NULL )
        {
            //$this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'This user does not exist' ) );
            //$this->_redirect ( '/' );
        }

//
//        var_dump($this->view->user);
//        die();

        $user_id = $this->view->user['IdUser'];

        //lets overwrite the password and token values to assure not passed to the view ever!
        unset ($this->view->user['password']);
        unset ($this->view->user['token']);

         //lets fetch last 5 user comments

        $this->view->comments = $model->getUserComments( $user_id, 5 )->toArray() ;

        $this->view->headTitle()->append( $this->view->translate ( 'User profile - ' ).$this->view->user['username'] );


        $auth = Zend_Auth::getInstance ();

        if (($auth->getIdentity()->IdUser  == $this->view->user['IdUser']) )
        { //if is the user profile owner lets delete it

            $this->view->editprofile = '<ul id="tabnav">
        <li class="tab2"><a href="/'.$this->view->lang .'/user/edit/id/'.$auth->getIdentity()->IdUser. ' ">'.$this->view->translate('edit profile').'</a></li></ul>';

        }

    }

    protected function _getModel()
    {
        if (null === $this->_model)
        {
            require_once APPLICATION_PATH . '/models/Users.php';
            $this->_model = new Model_Users ( );
        }
        return $this->_model;
    }

    /**
     *
     * @return Form_UserRegister
     */
    protected function _getUserRegisterForm()
    {
        require_once APPLICATION_PATH . '/forms/UserRegister.php';
        $form = new Form_UserRegister ( );
        return $form;
    }

    /**
     * forgot - sends (resets) a new password to the user
     *
     */

    public function forgotAction()
    {
        $this->view->headTitle()->append($this->view->translate('Forgot your password?'));
        $request = $this->getRequest ();
        $form = $this->_getUserForgotForm ();

        if ($this->getRequest ()->isPost ())
        {

            if ($form->isValid ( $request->getPost () ))
            {

                // collect the data from the form
                $f = new Zend_Filter_StripTags ( );
                $email = $f->filter ( $this->_request->getPost ( 'email' ) );

                $model = $this->_getModel ();
                $mailcheck = $model->checkUserEmail( $email );

                if ($mailcheck == NULL)
                {
                    // failure: email does not exists on ddbb
                    $view = $this->initView ();
                    $view->error = $this->view->translate ( 'This email is not in our database. Please, try again.' );

                } else
                { // success: the email exists , so lets change the password and send to user by mail
                    
                    $mailcheck = $mailcheck->toArray ();

                    //regenerate the token
                    $mailcheck['token'] = md5 ( uniqid ( rand (), 1 ) );
     
                    $model->updateUser($mailcheck);
                                
                    //now lets send the validation token by email to confirm the user email
                    $hostname = 'http://' . $this->getRequest ()->getHttpHost ();

                    $mail = new Zend_Mail ( );
                    $mail->setBodyHtml ( $this->view->translate ( 'Somebody , probably you, wants to restore your foofind access. Click on this url to restore your foofind account:' ).'<br />'
                            . $hostname . $this->view->translate ( '/en/user/validate/t/' ) .  $mailcheck['token'] .
                            '<br /><br />'.
                            $this->view->translate('Otherwise, ignore this message.').
                            '<br />--------------<br />' . utf8_decode ( $this->view->translate ( 'The foofind team.' ) ) );
                    $mail->setFrom ( 'noreply@foofind.com', 'foofind.com' );

                    $mail->addTo($mailcheck ['email']);
                    $mail->setSubject ( $mailcheck ['username'] . $this->view->translate ( ', restore your foofind access' ) );
                    $mail->send ();
                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Check your inbox email to restore your foofind.com access' ) );

                    $this->_redirect ( '/' );

                }

            }
        }
        // assign the form to the view
        $this->view->form = $form;

    }

   

    /**
     *
     * @return Form_UserForgotForm
     */
    protected function _getUserForgotForm()
    {
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
    public function validateAction()
    {

        // Do not attempt to render a view
        $this->_helper->viewRenderer->setNoRender ( true );
        $token = $this->_request->getParam ( 't' ); //the token



        if (! is_null ( $token ))
        {

            //lets check this token against ddbb
            $model = $this->_getModel ();
            $validatetoken = $model->validateUserToken ( $token );

            if ($validatetoken !== NULL)
            {

                //first kill previous session or data from client
                //kill the user logged in (if exists)
                Zend_Auth::getInstance ()->clearIdentity ();
                $this->session->logged_in = false;
                $this->session->username = false;

                //update the active status to 1 of the user
                $data ['active'] = '1';
                $data ['IdUser'] = $validatetoken ['IdUser'];

                //reset the token
                $data['token'] = NULL;
                $model->updateUser($data);

                //LETS OPEN THE GATE!
                //update the auth data stored
                $data = $model->fetchUser($validatetoken ['IdUser']);
                $auth = Zend_Auth::getInstance ();
                
                $auth->getStorage()->write( (object)$data);

                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Welcome' ) .' '. $data['username'] );
                $this->_redirect ( '/' );

            } else
            {
               $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Sorry, this token does not exist or has been already used' )  );
               $this->_redirect ( '/' );
                
            }

        } else
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Sorry, register url no valid or expired.' ) );
            $this->_redirect ( '/' );
        }

    }

}

