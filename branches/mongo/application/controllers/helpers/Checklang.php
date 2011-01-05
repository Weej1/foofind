<?php

class Zend_Controller_Action_Helper_Checklang extends Zend_Controller_Action_Helper_Abstract {

    static $langcodes = array('en'=>1, 'es'=>2, 'fr'=>3, 'it'=>4);

    function init()
    {
        $this->lang = $this->getRequest()->getParam("language");
        if ($this->lang == null)
        {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) $this->lang = $auth->getIdentity()->lang;
        }
        if ($this->lang == null && array_key_exists('lang', $_COOKIE))
            $this->lang = $_COOKIE['lang'];

        $locale = new Zend_Locale ($this->lang);

        $this->langtest = in_array($locale->getLanguage(), array('fr','it','tr'));

        if (!$this->langtest && !in_array($locale->getLanguage(), array('en', 'es'))) {
            $locale->setLocale ('en');
        }
        $this->lang = $locale->getLanguage ();
        $options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
        $translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', $this->lang, $options );

        if ($translate->isAvailable ( $this->lang )) {
            $translate->setLocale ( $locale );
            Zend_Form::setDefaultTranslator ( $translate );
            Zend_Registry::set ( 'Zend_Translate', $translate );
        } else {
            header('Location: /en');
            exit;
        }
    }

    function check(){
        return $this->lang;
    }

    function isTest(){
        return $this->langtest;
    }
    
    function getcode($lang = null)
    {
        if (!isset($lang)) $lang = $this->lang;
        return Zend_Controller_Action_Helper_Checklang::$langcodes[$lang];
    }
}

