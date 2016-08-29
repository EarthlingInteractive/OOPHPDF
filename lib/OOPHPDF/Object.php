<?php

class OOPHPDF_Object {
	/**
	 * @var TCPDF
	 */
	protected $pdf;
	
	public function __construct(TCPDF $pdf) {
		$this->pdf = $pdf;
	}
	
	protected function getPageRemainingHeight() {
		return $this->pdf->getPageHeight() - $this->pdf->GetY() - $this->pdf->getBreakMargin();
	}
}
