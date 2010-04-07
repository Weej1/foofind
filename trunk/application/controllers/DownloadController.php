<?php

require_once APPLICATION_PATH . '/models/Files.php';

class DownloadController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();

        $this->view->lang =  $this->_helper->checklang->check();


    }


    


    public function fileAction()
    {

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();


        
        //plugin Qs
        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';

        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);

        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
    

        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        if ($form->isValid ( $request->getPost () )) {

              // Create a filter chain and add filters
             $f = new Zend_Filter();
             $f->addFilter(new Zend_Filter_StripTags())
                    ->addFilter(new Zend_Filter_HtmlEntities());
              $q = $f->filter ( $this->_request->getPost ( 'q' ) );

              $form->setAction( '/'. $this->view->lang.'/search/'.$q);

              $form->loadDefaultDecoratorsIsDisabled(false);
              foreach($form->getElements() as $element) {
                $element->removeDecorator('DtDdWrapper');
                $element->removeDecorator('Label');
              }
        }
        // assign the form to the view
        $this->view->form = $form;


        //*************************************************************************** get file


                $id = $this->_request->getParam ( 'id' );
		$model = $this->_getModel ();
		$this->view->file = $model->getFile( $id );
                

                if ($this->view->file){ // if the id ad exists then render the ad and comments

                        $this->view->metadata = $model->getMetadata( $id );
                        $this->view->sources = $model->getSources( $id );


                        $this->view->file_size = $this->_formatSize($this->view->file['Size']);
                        $this->view->file_content_type = $this->_contentType($this->view->file['ContentType']);

                        

                        $this->view->headTitle()->append(' - ');
                        $this->view->headTitle()->append($this->view->file['Filename']);

                } else {

                     $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'This file does not exist or may have been deleted!' ) );
		     $this->_redirect ( '/'.$this->view->lang );
                     return ;
                }




               



    }

   
    protected function _getModel() {
		if (null === $this->_model) {

			require_once APPLICATION_PATH . '/models/Download.php';
			$this->_model = new Model_Download ( );
		}
		return $this->_model;
	}




   
    /**
     *
     * @return Form_Search
     */
    protected function _getSearchForm() {
        require_once APPLICATION_PATH . '/forms/Search.php';
        $form = new Form_Search( );

        return $form;
    }



    protected function _contentType($type)
    {
        if ($type == 1) $type = 'audio';
        elseif ($type == 2) $type = 'video';
        elseif ($type == 5) $type = 'image';
        else $type = 'document';

        return $type;
    }




    //TODO refactor this function ,is duplicated from search controller (this sucks)
    protected function _formatSize($bytes)
    {
        $size = $bytes / 1024;
        if($size < 1024)
        {
            $size = number_format($size, 2);
            $size .= ' KB';
        }
        else
        {
            if ($size / 1024 < 1024)
            {
                $size = number_format($size / 1024, 2);
                $size .= ' MB';
            }
            else if ($size / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024, 2);
                $size .= ' GB';
            }
        }
        return $size;
    }





}