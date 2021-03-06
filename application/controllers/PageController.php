<?php

class PageController extends Zend_Controller_Action
{

        /**
         * The default action - show the different pages
         */

        public function init(){
            
            $this->_helper->layout()->setLayout('page');

            $request = $this->getRequest ();
            $requesttitle .= ' '.$this->_getParam('q');
            $this->view->headTitle()->append(' - ');
            $this->view->headTitle()->append($requesttitle);

            $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
            $this->view->mensajes = $this->_flashMessenger->getMessages ();
            $this->view->lang =  $this->_helper->checklang->check();


        }

        /*default action */
         public function indexAction(){

             $this->_redirect( '/');
       }
       

       public function submitAction(){
           
       }

       public function aboutAction(){
                
       }

       public function tosAction(){

       }

       public function privacyAction(){

       }


       public function legalAction(){

       }

       public function apiAction(){

       }

        public function complaintAction() {
                $request = $this->getRequest ();
                $form = $this->_getComplaintForm ();


                if ($this->getRequest ()->isPost ()) {

                        
                        if ($form->isValid ( $request->getPost () )) {

                                //check agree tos and privacy
                                $checkagree = ($this->_request->getPost ( 'agree' ) == '1');
                                if ( $checkagree == FALSE  )
                                {
                                    $view = $this->initView();
                                    $view->error .= $this->view->translate('Please, accept the terms of use and privacy policy');
                    
                                } else {

                                // collect the data from the user
                                $f = new Zend_Filter_StripTags ( );

                                $name = $f->filter ( $this->_request->getPost ( 'name' ) );
                                $surname = $f->filter ( $this->_request->getPost ( 'surname' ) );
                                $company = $f->filter ( $this->_request->getPost ( 'company' ) );
                                $email = $f->filter ( $this->_request->getPost ( 'email' ) );
                                $phonenumber = $f->filter ( $this->_request->getPost ( 'phonenumber' ) );
                                $linkreported = $f->filter ( $this->_request->getPost ( 'linkreported' ) );
                                $urlreported = $f->filter ( $this->_request->getPost ( 'urlreported' ) );
                                $reason = $f->filter ( $this->_request->getPost ( 'reason' ) );
                                $message = $f->filter ( $this->_request->getPost ( 'message' ) );

                                
                                $user_info = $_SERVER ['REMOTE_ADDR'];
                                $user_info .= ' ' . $_SERVER ['HTTP_USER_AGENT'];

                                $mail = new Zend_Mail ('utf-8');

                                
                                $body = $user_info
                                .'<br/><br/><br/>Name: '.$name.' '.$surname
                                .'<br/><br/>Company: '.$company
                                .'<br/><br/>Phone number: '.$phonenumber
                                .'<br/><br/>Link reported: '.$linkreported
                                .'<br/><br/>Url reported: '.$urlreported
                                .'<br/><br/>Reason: '.$reason
                                .'<br/><br/>Message: '.$message;
                                $mail->setBodyHtml ( $body );
                                $mail->setFrom ( $email );
                                $mail->addTo ( 'hola@foofind.com', 'foofind hola' );
                                $mail->setSubject ( 'foofind.com - complaint petition  from ' . $email );

                                try {
                                      $mail->send();
                                    } catch (Exception $e) {
                                      echo "Failed to Send Email.";
                                    }


                                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Message sent successfully!' ) );
                                $this->_redirect ( '/' );

                        }
                        
                        }

                }
                // assign the form to the view
                $this->view->form = $form;

        }


        /**
         *
         * @return Form_Complaint
         */
        protected function _getComplaintForm() {
                require_once APPLICATION_PATH . '/forms/Complaint.php';
                $form = new Form_Complaint ( );

                return $form;
        }

       public function contactAction(){

                $request = $this->getRequest ();
                $form = $this->_getContactForm();


                // check to see if this action has been POST'ed to
                if ($this->getRequest ()->isPost ()) {

                        // now check to see if the form submitted exists, and
                        // if the values passed in are valid for this form
                        if ($form->isValid ( $request->getPost () )) {


                                //check agree tos and privacy
                                $checkagree = ($this->_request->getPost ( 'agree' ) == '1');
                                if ( $checkagree == FALSE  )
                                {
                                    $view = $this->initView();
                                    $view->error .= $this->view->translate('Please, accept the terms of use and privacy policy');

                                } else {


                                // collect the data from the user
                                $f = new Zend_Filter_StripTags ( );
                                $email = $f->filter ( $this->_request->getPost ( 'email' ) );
                                $message = $f->filter ( $this->_request->getPost ( 'message' ) );

                                
                                $user_info .= $_SERVER ['REMOTE_ADDR'];
                                $user_info .= ' ' . $_SERVER ['HTTP_USER_AGENT'];

                                $mail = new Zend_Mail ('utf-8');
                                $body = $user_info.'<br/>'.$message;
                                $mail->setBodyHtml ( $body );
                                $mail->setFrom ( $email );
                                $mail->addTo ( 'hola@foofind.com', 'hola foofind' );

                                $mail->setSubject ( 'foofind.com - message contact  from ' . $email );
                                 try {
                                      $mail->send();
                                    } catch (Exception $e) {
                                      echo "Failed to Send Email.";
                                    }

                                $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Message sent successfully!' ) );
                                $this->_redirect ( '/' );

                        }

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
                require_once APPLICATION_PATH . '/forms/Contact.php';
                $form = new Form_Contact( );

                return $form;
        }




       
}

