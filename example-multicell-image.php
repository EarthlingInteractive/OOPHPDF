<?php

/* kitten photo credit Wikimedia user "Saving Public Ryan"
   licensed under Creative Commons Attribution-Share Alike 4.0 International
   https://commons.wikimedia.org/wiki/File:Cute-kittens-12929201-1600-1200.jpg
*/

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
	->setFontSize(12)
	->setFontStyle('B')
	->setFillColorArray(array(255, 200, 200)) // light red
	->setLn(0)
	->setWidth(3.75)
	->setHeightToAuto();

// create the right box containing an image
$rightBox = new OOPHPDF_ImageMultiCell($pdf, 'kitten.jpg', 0, 0.2);
$rightBox
	//->setText("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.")
	->setFontSize(12)
	->setFontStyle('I')
	->setFillColorArray(array(200, 200, 255)) // light blue
	->setLn(0)
	->setWidth(3.75)
	->setHeightToAuto();

// create the first row and auto size it
$row1 = new OOPHPDF_Row($pdf, array($leftBox, $rightBox));
$row1->setHeightToAuto();

// create the second row and auto size it
$row2 = new OOPHPDF_Row($pdf, array($rightBox, $leftBox));
$row2->setHeightToAuto();

// create the table and add the rows to it
$table = new OOPHPDF_Table($pdf, array($row1, $row2));

// draw the table
$table->drawAtPosition(0.5, 1);

// output the document
$pdf->Output('document.pdf', 'I');
