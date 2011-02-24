<?php

/**
 * This is the main Contact form.
 */

class Form_Submitlink extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'post' );

                $this->addElement ( 'textarea', 'urls',
                        array ('label' => 'URLs:', 'validators' => array (
                            array ('StringLength', false, array (5, 8000 ) ),
                            array('regex', false, array('pattern'=>'/((http|ed2k|magnet)\:?[^\n]*)+/',
                                'messages'=>array('regexNotMatch'=>'You can send only HTTP, ed2k or magnet links.'))) ), 'required' => true,'rows' => 10,'cols' => 80 )
                 );

                $this->addElement ( 'captcha', 'captcha', array ('label' => 'Please, insert the 5 characters shown:', 'required' => true,
                    'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                     'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                     'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );

                $checkboxDecorator = array(
                                'ViewHelper',
                                'Errors',
                                array(array('data' => 'HtmlTag'), array('tag' => 'span', 'class' => 'element')),
                                array('Label', array('tag' => 'dt'),
                                array(array('row' => 'HtmlTag'), array('tag' => 'span')),
                            ));

                $this->addElement('checkbox', 'agree', array(
                    'decorators' => $checkboxDecorator,
                    'required' => true,
                    'checked' =>false
                    ));

                // add the submit button
                $this->addElement ( 'submit', 'submit', array (
                    'label' => 'Send',
                    'class' => 'large magenta awesome') );
        }
}


