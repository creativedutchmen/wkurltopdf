<?php

require_once("lib/phpwkhtmltopdf/WkHtmlToPdf.php");

Class Extension_WKUrlToPdf extends Extension
{
	public function getSubscribedDelegates(){
		return array(
			array(
				'page' => '/blueprints/pages/',
				'delegate' => 'AppendPageContent',
				'callback' => 'appendType'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'FrontendPageResolved',
				'callback' => 'processRequest'
			)
		);
	}
	
	/**
	 * Append type for maintenance pages to page editor.
	 *
	 * @param array $context
	 *  delegate context
	 */
	public function appendType($context)
	{

		// Find page types
		$elements = $context['form']->getChildren();
		$fieldset = $elements[0]->getChildren();
		$group = $fieldset[2]->getChildren();
		$div = $group[1]->getChildren();
		$types = $div[2]->getChildren();

		// Search for existing maintenance type
		$flag = false;
		foreach($types as $type) {
			if($type->getValue() == 'pdf') {
				$flag = true;
			}
		}

		// Append maintenance type
		if($flag == false) {
			$mode = new XMLElement('li', 'pdf');
			$div[2]->appendChild($mode);
		}
	}
	
	public function processRequest($context)
	{
		if(in_array('pdf',$context['page_data']['type']) && substr($_REQUEST['symphony-page'], -4) == '.pdf') {
			$this->generatePdf(substr($_REQUEST['symphony-page'], 0, -4));
		}
	}

	public function generatePdf($url)
	{
		try{
			$path = dirname(__FILE__);
			$tmp_name = uniqid();
			$command = $path .'/bin/wkhtmltopdf-amd64 --print-media-type --page-size A4 --disable-internal-links --disable-smart-shrinking ' . escapeshellarg(URL . '/' . $url) . ' ' . $path . '/tmp/' . $tmp_name;
			
			// we use proc_open with pipes to fetch error output
	        $descriptors = array(
	            2   => array('pipe','w'),
	        );
	        $process = proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell'=>true));

	        if(is_resource($process)) {

	            $stderr = stream_get_contents($pipes[2]);
	            fclose($pipes[2]);

	            $result = proc_close($process);
	        }
			if(file_exists($path . '/tmp/' . $tmp_name)){
				header('Content-type: application/pdf');
				header("Cache-Control: max-age=6000, public"); //In seconds
				header_remove("Expires");
				echo file_get_contents($path  . '/tmp/' . $tmp_name);
				die();
			}
			else{
				die(__('Oops! There was an error generating the pdf...'));
			}
		}
		catch(Exception $e){
			echo $e;
		}
	}
}