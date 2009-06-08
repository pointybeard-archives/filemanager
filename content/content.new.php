<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionFileManagerNew extends AdministrationPage{

		private $_FileManager;

		function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			
			$this->setTitle('Symphony &ndash; File Manager &ndash; Untitled');
		}
		
		private function __actionUpload(){
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			
			$file = General::processFilePostData($_FILES['fields']);
			$file = $file['upload']['file'];
			
			$context = $this->_context;
			array_shift($context);
			
			$dest_path = DOCROOT . $FileManager->getStartLocation() . (is_array($context) && !empty($context) ? '/' . implode('/', $context) . '/' : NULL);
			
/*
	Array
	(
	    [0] => KnuckleboneWitch.jpg
	    [1] => image/jpeg
	    [2] => /Applications/MAMP/tmp/php/phpYCREds
	    [3] => 0
	    [4] => 25854
	)
*/
			
			$permission = $_POST['fields']['upload']['permissions'];
			
			return General::uploadFile($dest_path, $file[0], $file[2], $permission);
					
		}
		
		private function __actionDirectory(){
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			
			$context = $this->_context;
			array_shift($context);
			
			$path = DOCROOT . $FileManager->getStartLocation() . (is_array($context) && !empty($context) ? '/' . implode('/', $context) . '/' : NULL) . '/' . $_POST['fields']['directory']['name'];
			$permission = $_POST['fields']['directory']['permissions'];
			
			return @mkdir($path, intval($permission, 8));
		}
		
		private function __actionFile(){
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			
			$context = $this->_context;
			array_shift($context);
			
			$path = DOCROOT . $FileManager->getStartLocation() . (is_array($context) && !empty($context) ? '/' . implode('/', $context) . '/' : NULL);
			$permission = $_POST['fields']['file']['permissions'];
			$content = $_POST['fields']['file']['contents'];
			$filename = $_POST['fields']['file']['name'];
			
			$file = new File($path . '/' . $filename);
			$file->setContents($content);
			$file->setPermissions($permission);
			
			return $file->isFile();		
		}
		
		function action(){
			
			switch($_POST['type']){
				case 'upload':
					$this->__actionUpload();
					array_shift($this->_context);
					redirect(extension_filemanager::baseURL() . 'browse/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL));
					break;
					
				case 'file':
					$this->__actionFile();
					array_shift($this->_context);
					redirect(extension_filemanager::baseURL() . 'browse/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL));					
					break;
					
				case 'directory':
					$this->__actionDirectory();
					array_shift($this->_context);
					redirect(extension_filemanager::baseURL() . 'browse/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL) . '/' . $_POST['fields']['directory']['name']);
					break;
				
			}

			
			
		/*	if(isset($_POST['action']['save'])){
				$fields = $_POST['fields'];
				
				$file->setName($fields['name']);
				
				if(isset($fields['contents'])) $file->setContents($fields['contents']);
				
				$file->setPermissions($fields['permissions']);
				
				$relpath = str_replace(DOCROOT . $FileManager->getStartLocation(), NULL, dirname($_GET['file']));
				
				if($file->isWritable())
					redirect($FileManager->baseURL() . 'properties/?file=' . rtrim(dirname($_GET['file']), '/') . '/' . $file->name());
				
				else redirect($FileManager->baseURL() . 'browse/' . $relpath);
				
			}*/

		}
		
		function view(){
			
			$this->_Parent->Page->addStylesheetToHead(URL . '/extensions/filemanager/assets/styles.css', 'screen', 70);
			
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);
			
			$this->setPageType('form');	
			
			$type = array_shift($this->_context);
			
			$this->appendSubheading(trim($FileManager->getStartLocationLink(), '/') . $FileManager->buildBreadCrumbs($this->_context));
			
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->Form->prependChild(Widget::Input('MAX_FILE_SIZE', Administration::instance()->Configuration->get('max_upload_size', 'admin'), 'hidden'));
			$this->Form->prependChild(Widget::Input('type', $type, 'hidden'));
			
			$fields = array();
			
			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];
			}
			
			else{
				
				$fields['file']['permissions'] = Administration::instance()->Configuration->get('write_mode', 'file');
				$fields['upload']['permissions'] = Administration::instance()->Configuration->get('write_mode', 'file');
				$fields['directory']['permissions'] = Administration::instance()->Configuration->get('write_mode', 'directory');
			}
			

			switch($type){
				
				case 'upload':
				default:
				
					$fieldset = new XMLElement('fieldset');
					$fieldset->setAttribute('class', 'settings type-upload');
					$fieldset->appendChild(new XMLElement('legend', 'Upload Existing File'));
					
					$div = new XMLElement('div');
					$div->setAttribute('class', 'group');

					$label = Widget::Label('File', NULL, 'file');
					$span = new XMLElement('span');
					$span->appendChild(Widget::Input('fields[upload][file]', NULL, 'file'));
					$label->appendChild($span);

					if(isset($this->_errors['upload']['file'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['upload']['file']));
					else $div->appendChild($label);

					$label = Widget::Label('Permissions');
					$label->appendChild(Widget::Input('fields[upload][permissions]', General::sanitize($fields['upload']['permissions'])));

					if(isset($this->_errors['upload']['permissions'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['upload']['permissions']));
					else $div->appendChild($label);	

					$fieldset->appendChild($div);
					$this->Form->appendChild($fieldset);				
				
					break;
					
					
				case 'directory':

					$fieldset = new XMLElement('fieldset');
					$fieldset->setAttribute('class', 'settings type-directory');
					$fieldset->appendChild(new XMLElement('legend', 'Create New Directory'));

					$div = new XMLElement('div');
					$div->setAttribute('class', 'group');

					$label = Widget::Label('Name');
					$label->appendChild(Widget::Input('fields[directory][name]', General::sanitize($fields['directory']['name'])));

					if(isset($this->_errors['directory']['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['directory']['name']));
					else $div->appendChild($label);

					$label = Widget::Label('Permissions');
					$label->appendChild(Widget::Input('fields[directory][permissions]', General::sanitize($fields['directory']['permissions'])));

					if(isset($this->_errors['directory']['permissions'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['directory']['permissions']));
					else $div->appendChild($label);	

					$fieldset->appendChild($div);
					$this->Form->appendChild($fieldset);				
				
					break;
					
					
				case 'file':

					$fieldset = new XMLElement('fieldset');
					$fieldset->setAttribute('class', 'settings type-file');
					$fieldset->appendChild(new XMLElement('legend', 'Create New File'));

					$div = new XMLElement('div');
					$div->setAttribute('class', 'group');

					$label = Widget::Label('Name');
					$label->appendChild(Widget::Input('fields[file][name]', General::sanitize($fields['name'])));

					if(isset($this->_errors['file']['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['file']['name']));
					else $div->appendChild($label);

					$label = Widget::Label('Permissions');
					$label->appendChild(Widget::Input('fields[file][permissions]', General::sanitize($fields['file']['permissions'])));

					if(isset($this->_errors['file']['permissions'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['file']['permissions']));
					else $div->appendChild($label);	

					$fieldset->appendChild($div);


					$label = Widget::Label('Contents');
					$label->appendChild(Widget::Textarea('fields[file][contents]', '25', '50', General::sanitize($fields['file']['contents']), array('class' => 'code')));

					if(isset($this->_errors['file']['contents'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['file']['contents']));
					else $fieldset->appendChild($label);

					$this->Form->appendChild($fieldset);				
				
					break;
			}

			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', 'Create', 'submit', array('accesskey' => 's')));

			
			$this->Form->appendChild($div);			

		}
	}
	
?>
