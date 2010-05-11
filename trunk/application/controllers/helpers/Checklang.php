<?php

class Zend_Controller_Action_Helper_Checklang extends Zend_Controller_Action_Helper_Abstract {
    
    function init()
    {

        $this->lang = $this->getRequest()->getParam("language");
        if ($this->lang != null)
        {
            if (Zend_Registry::isRegistered( "Zend_Locale")) $locale = Zend_Registry::get ( "Zend_Locale" );
        } else {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) $this->lang = $auth->getIdentity()->lang;
            if ($this->lang == null) $this->lang = $_COOKIE['lang'];
        }

        if ( $locale == null) {
            $locale = new Zend_Locale ($this->lang);
            if (!in_array($locale->getLanguage(), array('en', 'es'))) {
                $locale->setLocale ('en');
            }
            $this->lang = $locale->getLanguage ();
            Zend_Registry::set ( 'Zend_Locale', $locale );
        }

        $options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
        $translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', 'auto', $options );

        if ($translate->isAvailable ( $this->lang )) {
            $translate->setLocale ( $locale );
            Zend_Form::setDefaultTranslator ( $translate );
            Zend_Registry::set ( 'Zend_Translate', $translate );
        } else {
            //header('Location: /en');
            exit;
        }
    }

    function check(){
        return $this->lang;
    }
    
}

