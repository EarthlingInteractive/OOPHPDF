<?php
require __DIR__ . '/vendor/autoload.php';

// create a new PDF document
$pdf = new TCPDF('P', 'in', 'LETTER');

// set margins
$pdf->SetMargins(0.5, 1, 0.5);
$pdf->setFooterMargin(0.5);
$pdf->setHeaderMargin(0.5);

// if set to true, a 2nd arg determines how far up from the bottom to
// trigger the page break.
$pdf->SetAutoPageBreak(false);

// start a new page
$pdf->AddPage();

// create piegraph object
$pg = new OOPHPDF_PieGraph($pdf);


// **** configure the piegraph's pie and legend  ***
// colors for wedge and legend's color-blocks
// note there are more colors than values but the last color won't appear
// as there are only 5 values in $wedgeAndLegendBoxValues.
$wedgeAndLegendColors = array(
  array(0, 152, 206), // aqua blue
	array(60, 185, 46), // green
	array(255, 108, 12), // orange
	array(89, 81, 148), // purple
  array(207, 208, 209), // grey
  array(204, 51, 204) // magenta
);

// values to appear as legend label text (the marx bros.) and legend
// color-block values.
$wedgeAndLegendBoxValues = array(
  'Groucho' => 31,
  'Harpo' => 42,
  'Chico' => 17,
  'Zeppo' => 6,
  'Karl' => 4
);

// legend color-blocks configuration
$legendBoxSettings = [
  'height' 			=> 0.6,
  'width' 			=> 0.45,
  'fontSize' 			=> 10,
  'textColorArray' 	=> array(255, 255, 255), // white
  'cellPaddingLeft' 	=> 0.1
];

// for the text labels to the right of the legend color blocks
$legendLabelSettings = [
  'height' 			=> 0.6,
  'width' 			=> 1.5,
  'fontSize' 			=> 9.0,
  'textColorArray' 	=> array(0, 0, 0), // black
  'fillColorArray'	=> null, // will default to the pdf body color
];

// set the colors to use for the pie wedges. pie colors, like the values for
// them, are used in the order of the array
$pg->setWedgeAndLegendColorsRgb($wedgeAndLegendColors);

$pg->setPieWedgeValues($wedgeAndLegendBoxValues);

// Suppress the default labeling in the pie wedges.
$pg->mergeSvgGraphicSettings(array("show_data_labels" => false));

// override the default configs for drawing the legend color-blocks
$pg->mergeLegendBoxSettings($legendBoxSettings);

// override some of the default settings for drawing the labels
$pg->mergeLegendLabelSettings($legendLabelSettings);

// space between legend block and label text
$pg->setLegendLabelLeftPadding(0.08);

//space between legend blocks/text lines
$pg->setLegendVerticalSpacing(0.2);

// Affects the diameter of the pie itself (the smallest controls diameter, but
// these dimensions shape the graphic the pie is contained within.)
$pg->setSvgGraphicHeight(120.0);
$pg->setSvgGraphicWidth(120.0);

// offset the pie to the left of where ever the legend is drawn.
$pg->setPieOffsetX(-2.3);
// top of pie will now be at same x position as where the legend is drawn
$pg->setPieOffsetY(0.0);


$x = 4.5;
$y = 2.75;
$pg->drawAtPosition($x, $y);

// output the document
$pdf->Output('document.pdf', 'I');
