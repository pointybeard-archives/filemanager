<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionFileManagerBrowse extends AdministrationPage{

		function __construct(&$parent){
			parent::__construct($parent);
			$this->setTitle('Symphony &ndash; File Manager');
		}
	
		function action(){
			
			$checked = @array_keys($_POST['items']);
			
			if(!isset($_POST['action']['apply']) || empty($checked)) return;
			
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');		
			
			switch($_POST['with-selected']){
				
				case 'delete':
				
					$path = DOCROOT . $FileManager->getStartLocation();
					
					foreach($checked as $rel_file){
						$abs_file = $path . '/' . ltrim($rel_file, '/');

						if(!is_dir($abs_file) && file_exists($abs_file)) General::deleteFile($abs_file);
						elseif(is_dir($abs_file)){
							
							if(!@rmdir($abs_file))
								$this->pageAlert('{1} could not be deleted as is still contains files.', AdministrationPage::PAGE_ALERT_ERROR, array('<code>'.$rel_file.'</code>'));

						}
						
					}
					
					break;
					
				case 'archive':
				
					$path = (is_array($this->_context) && !empty($this->_context) ? '/' . implode('/', $this->_context) . '/' : NULL);
					$filename = $FileManager->createArchive($checked, $path);
					
					break;
					
					
			}
		}
	
		function view(){

			$this->_Parent->Page->addStylesheetToHead(URL . '/extensions/filemanager/assets/styles.css', 'screen', 70);

			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');

			$path = DOCROOT . $FileManager->getStartLocation() . (is_array($this->_context) && !empty($this->_context) ? '/' . implode('/', $this->_context) . '/' : NULL);

			$create_button = Widget::Anchor('Create a file or directory', extension_filemanager::baseURL() . 'new/upload/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL), 'Create a file or directory', 'create button');

			$this->setPageType('table');
			$this->appendSubheading(trim($FileManager->getStartLocationLink(), '/') . '/' . $FileManager->buildBreadCrumbs($this->_context), (is_writable($path) ? $create_button : NULL));

			$Iterator = new DirectoryIterator($path);

			$aTableHead = array(

				array('Name', 'col'),
				array('Size', 'col'),
				array('Permissions', 'col'),
				array('Modified', 'col'),
				array('Available Actions', 'col'),			

			);	

			$aTableBody = array();

			if(iterator_count($Iterator) <= 0){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', NULL, count($aTableHead))))
								);
			}

			else{

				foreach($Iterator as $file){
					if($row = $FileManager->buildTableRow($file, ($path != DOCROOT . $FileManager->getStartLocation()))) $aTableBody[] = $row;
				}
			
			}
			
			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody)
						);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, 'With Selected...'),
				array('archive', false, 'Archive'),
				array('delete', false, 'Delete')									
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));

			$this->Form->appendChild($tableActions);

		}
	}
	
?>
