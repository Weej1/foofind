<?php
class AuthController extends Zend_Controller_Action
{
    public function init()
    {
        $this->referer = $_SERVER['HTTP_REFERER'];
        $this->view->lang =  $this->_helper->checklang->check();

        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
    }


    public function indexAction()
    {
        $this->_redirect ( '/' );
    }

    /**
     * Log in - show the login form or handle a login request
     *
     */
    public function loginAction()
    {

        $auth = Zend_Auth::getInstance ();
        if ($auth->hasIdentity()) $this->_redirect ( '/' );

        $request = $this->getRequest ();
        $form = $this->_getUserLoginForm();
        $this->view->source = $request->getParam('source');

        if ($this->hasValidReferer())
        {
            $aNamespace = new Zend_Session_Namespace('Foofind');
            $aNamespace->redir = $this->referer;
        }

        switch ($this->view->source )
        {
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

        if ($this->getRequest ()->isPost ())
        {
            if ($form->isValid ( $request->getPost () ))
            {


                // collect the data from the user
                $f = new Zend_Filter_StripTags ( );
                $email = $f->filter ( trim( $this->_request->getPost ( 'email' ) ) );
                $password = $f->filter ( trim( $this->_request->getPost ( 'password' ) ) ); //trim whitespaces from copy&pasting the pass from email
                $password = hash('sha256', $password, FALSE);

                //DDBB validation
                require_once APPLICATION_PATH . '/models/Users.php';
                $model = new Model_Users();
                $checkuser  = $model->checkUserLogin($email,$password);


                if ( $checkuser  )
                {
                    // success: store database row to auth's storage
                    // system. (Not the password though!)
                    unset ( $checkuser['password'] );
                    unset ( $checkuser['_id']); // unset the _id mongo , the casting to object in zend auth is unable to convert to array value

                    // do the authentication
                    $auth = Zend_Auth::getInstance ();
                    $auth->getStorage ()->write ( (object)$checkuser );

                    $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'You are now logged in, ' ) . $auth->getIdentity()->username );

                    //check if user wants to be remembered by 7 days
                    $seconds  = 60 * 60 * 24 * 7;

                    if ($this->_request->getPost ( 'rememberme' ) == "1" )
                    {
                        Zend_Session::RememberMe($seconds);
                    }
                    else
                    {
                        Zend_Session::ForgetMe();
                    }

                    Zend_Session::start();

                    //check the redir value if setted
                    $aNamespace = new Zend_Session_Namespace('Foofind');
                    $redir = $aNamespace->redir;

                    if ($redir !== null)
                    {
                        $aNamespace->redir = null; //reset redir value
                        $this->_redirect ( $redir );
                    }
                    else
                        $this->_redirect ( '/' );
                } else
                {
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
    protected function _getUserLoginForm()
    {
        require_once APPLICATION_PATH . '/forms/UserLogin.php';
        $form = new Form_UserLogin ( );
        return $form;
    }

    /**
     * Log out - delete user information and clear the session, then redirect to
     * the log in page.
     */
    public function logoutAction()
    {
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