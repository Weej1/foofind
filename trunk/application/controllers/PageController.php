<?php

class PageController extends Zend_Controller_Action
{

        /**
         * The default action - show the different pages
         */

        public function init(){
            $this->_helper->layout()->setLayout('page');
        }

        /*default action */
         public function indexAction(){

             $this->_redirect('/page/submit', $options);
       }
       

       public function submitAction(){
           
       }

       public function aboutAction(){

       }

       public function tosAction(){

       }

       public function privacyAction(){

       }


       public function apiAction(){

       }



       public function contactAction(){

            $request = $this->getRequest ();
                $form = $this->_getContactForm();


                // check to see if this action has been POST'ed to
                if ($this->getRequest ()->isPost ()) {

                        // now check to see if the form submitted exists, and
                        // if the values passed in are valid for this form
                        if ($form->isValid ( $request->getPost () )) {

                                // collect the data from the user
                                $f = new Zend_Filter_StripTags ( );
                                $email = $f->filter ( $this->_request->getPost ( 'email' ) );
                                $message = $f->filter ( $this->_request->getPost ( 'message' ) );

                                //get the username if its nolotiro user
                                $user_info = $this->view->user->username;
                                $user_info .= $_SERVER ['REMOTE_ADDR'];
                                $user_info .= ' ' . $_SERVER ['HTTP_USER_AGENT'];

                                $mail = new Zend_Mail ('utf-8');
                                $body = $user_info.'<br/>'.$message;
                                $mail->setBodyHtml ( $body );
                                $mail->setFrom ( $email );
                                $mail->addTo ( 'daniel@mp2p.net', 'Daniel Remeseiro' );
                                $mail->setSubject ( 'foofind.com - complaint  from ' . $email );
                                $mail->send ();

                                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Message sent successfully!' ) );
                                $this->_redirect ( '/' );

                        }
                }
                // assign the form to the view
                $this->view->form = $form;

       }



        /**
         *
         * @return Form_Contact
         */
        protected function _getContactForm() {
                require_once APPLICATION_PATH . '/forms/Complaint.php';
                $form = new Form_Complaint ( );

                return $form;
        }




       
}

