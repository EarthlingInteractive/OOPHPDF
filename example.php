<?php

require __DIR__ . '/vendor/autoload.php';

// create a new PDF document
$pdf = new TCPDF('P', 'in', 'LETTER');

// set margins
$pdf->SetMargins(0.5, 1, 0.5);
$pdf->setFooterMargin(0.5);
$pdf->setHeaderMargin(0.5);

// set auto page breaks
$pdf->SetAutoPageBreak(true, 1);

// start a new page
$pdf->AddPage();

// create the left box
$leftBox = new OOPHPDF_MultiCell($pdf);
$leftBox
	->setText("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.")
	->setFontSize(24)
	->setFontStyle('B')
	->setFillColorArray(array(255, 200, 200)) // light red
	->setLn(0)
	->setWidth(3.75)
	->setHeightToAuto();

// create the right box
$rightBox = new OOPHPDF_MultiCell($pdf);
$rightBox
	->setText("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.")
	->setFontSize(16)
	->setFontStyle('I')
	->setFillColorArray(array(200, 200, 255)) // light blue
	->setLn(0)
	->setWidth(3.75)
	->setHeightToAuto();

// find the maximum height of both boxes
$maxHeight = max($leftBox->getHeight(), $rightBox->getHeight());

// make both boxes use the max height
$leftBox->setHeight($maxHeight);
$rightBox->setHeight($maxHeight);

// draw both boxes
$leftBox->drawAtPosition(0.5, 1);
$rightBox->drawAtPosition(4.25, 1);

// output the document
$pdf->Output('document.pdf', 'I');
