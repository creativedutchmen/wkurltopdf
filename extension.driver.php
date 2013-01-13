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
			$pdf = new WkHtmlToPdf();
			$pdf->addPage(URL . '/' . $url);
			$pdf->setPageOptions(array(
				'print-media-styles',
			    'disable-smart-shrinking',
			));
			//$pdf->addToc();
			$pdf->send('b.pdf');
			die();
			//var_dump($pdf);
			//echo 'b';
			//die();
		}
		catch(Exception $e){
			echo $e;
		}
	}
}