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

        public function translateAction(){
            $request = $this->getRequest();
            $newlangs = array('fr'=>'Français', 'de'=>'Deutsch', 'pt'=>'Português', 'it'=>'Italiano' );
            
            $lform = new Zend_Form();
            $lform->setMethod ( 'post' );

            $lform->addElement ( 'select', 'safe_newlang', array ('multiOptions' => $newlangs));
            $elem_newlang = $lform->getElement('safe_newlang');
            $elem_newlang->removeDecorator('label')->removeDecorator('HtmlTag');

            // add the submit button
            $lform->addElement ( 'submit', 'changelang', array ('label'=>'Choose language', 'class'=>'magenta awesome') );

            $lform->getElement('changelang')->removeDecorator('DtDdWrapper');
            
            $lform->populate($request->getParams());
            $newlang = $elem_newlang->getValue();
            
            $this->view->langsform = $lform;

            if ($request->isPost()) {
                if ($newlang!=null){
                    if (array_key_exists($newlang, $newlangs))
                        $this->view->newlangtext = $newlangs[$newlang];
                    else
                        $newlang==null;
                }
                if ($newlang!=null){
                    $options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
                    $translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', 'en', $options );
                    $adapter = $translate->getAdapter();
                    $es = $adapter->getMessages('es');
                    $en = $adapter->getMessages($this->view->lang);
                    $lang = $adapter->getMessages($newlang);

                    $tform = new Zend_Form();
                    $tform->setMethod ( 'post' );
                    $tform->setAttrib('class', 'texts');


                    $tform->addElement ( 'captcha', 'safe_captcha', array (
                        'label' => 'Please, insert the 5 characters shown:', 'required' => true,
                        'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                         'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                         'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );

                    $index = 0;
                    foreach ($es as $key => $text)
                    {
                        if (strpos($key, "safe_")===0) continue;

                        if (array_key_exists($key, $en))
                           $text = $en[$key];
                        else
                           $text = $key;

                        $text = preg_replace("/(\<[^\>]*>)([^\<]*)(\<\/[^\>]*\>)/", "$2", $text);
                        $text = preg_replace("/(\<[^\>]*\>)/", "", $text);
                        $text = preg_replace("/(\'?%[a-zA-Z\-]*%?\'?)/", "...", $text);
                        if (array_key_exists($key, $lang))
                           $val = $lang[$key];
                        else
                           $val = '';

                        $maxlen = strlen($text)*2;
                        if ($maxlen<20) $maxlen=20;
                        if ($maxlen<100) $type = "text"; else {
                            $type = "textarea";
                            $rows = $maxlen/50;
                        }
                        $tform->addElement ( $type, "text$index", array (
                            'validators' => array (array ('StringLength', false, array (1, $maxlen ) ) ), 'required' => false,
                            'label' => $text, 'value' => $val, 'cols'=>40, rows=>$rows) );
                        $input = $tform->getElement("text$index");
                        $valid = $input->getValidator("StringLength")->setEncoding('UTF-8');
                        $index++;
                    }

                    $tform->addElement('hidden', 'safe_newlang', array("value"=>$newlang));


                    $tform->getElement('safe_newlang')->removeDecorator('label')->removeDecorator('HtmlTag');
                    // add the submit button
                    $tform->addElement ( 'submit', 'submit_texts', array ('label' => 'Send texts', 'class'=>'magenta awesome') );
                    $this->view->textsform = $tform;

                }
                $this->view->newlang = $newlang;
                $tform->populate($request->getParams());
                if ($tform->getElement("submit_texts")->getValue()!=null)
                {
                    $data = $tform->getValues();
                    if ($tform->isValid($data))
                    {
                        $newdata = false;
                        $index = 0;
                        foreach ($es as $key => $text)
                        {
                            if (strpos($key, "safe_")===0) continue;

                            $mod = false;
                            $val = $data["text$index"];
                            if ($val!="" && (!array_key_exists($key, $lang) || ($mod=($lang[$key]!=$val))))
                            {
                                $body .= "\"$key\";\"$val\"".($mod?' ***':"")."<br>";
                                $newdata = true;
                            }
                            else
                                $body .= "<br>";
                            $index++;
                        }

                        if (!$newdata) {
                            $this->view->error = $this->view->translate ( 'Please, translate at least one text.' );
                            return;
                        }

                        $mail = new Zend_Mail ('utf-8');
                        $mail->setBodyHtml ($body);


                        $auth = Zend_Auth::getInstance ();
                        if ($auth->hasIdentity ())
                        {
                            $mail->setFrom($auth->getIdentity()->email, $auth->getIdentity()->username);
                        } else {
                            $mail->setFrom("noreply@foofind.com");
                        }

                        $config = Zend_Registry::get('config');
                        $mail->addTo($config->translation->email);
                        $mail->setSubject ("Translation: source-$newlang.csv");
                        $mail->send ();
                        $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Your translation has been sent. Thanks for your help!' ) );
                        $this->_redirect ( '/' );
                    }
                }
            }
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

                                     echo  'Failed to Send Email.';
                                      $this->_redirect ( '/' );
                                    }


                                //now we send the autoreply  to the claimer
                                $mailautoreply = new Zend_Mail('utf-8');

                                $body2 =
                                '<br/><br/>Esta es una respuesta automática a su solicitud de retirada de enlace.<br/><br/>
Hemos recibido su solicitud y la procesaremos lo antes posible. No se requiere ninguna acción por su parte para completar el proceso.<br/><br/>

Tenga en cuenta que sólo podemos retirar enlaces específicos, no palabras de búsqueda, y que no podemos evitar que terceros sitios sigan ofreciendo los enlaces retirados por nosotros. Si lo desea, puede comprobar la no disponibilidad de los enlaces que ha solicitado retirar visitando foofind.com dentro de unos días.
<br/><br/>
Atentamente,<br/>
El equipo Foofind.
';
                                $mailautoreply->setBodyHtml ( $body2 );
                                $mailautoreply->setFrom ( 'noreply@foofind.com' );
                                $mailautoreply->addTo ( $email , $name.' '.$surname);
                                $mailautoreply->setSubject ( 'foofind.com - about your complaint petition  , ' . $name.' '.$surname );

                                try {
                                      $mailautoreply->send();
                                    } catch (Exception $e) {

                                     echo  'Failed to Send Email.';
                                      $this->_redirect ( '/' );
                                    }


                                //process finished
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
