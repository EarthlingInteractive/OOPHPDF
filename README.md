# OOPHPDF
An object-oriented wrapper for writing PDF documents using PHP and TCPDF

## Description

OOPHPDF is a PHP library that provides an object-oriented interface to common TCPDF drawing methods.  Examples of implemented objects include the MultiCell, Image, and a basic Table with Rows that contain cells.  Instead of directly calling TCPDF methods to draw items, you create an object that represents the item you want to draw, configure it, query it as needed, then draw it.

## Why?

TCPDF is great for writing PDF documents, but its state-based design can be limiting when writing complex documents.

For example, try writing two MultiCell boxes side-by-side with different fonts, but make the height of both match the height of the tallest, so that they are only as tall as needed to fit the text of both.  You'd need to set up the context for each box, determine its height, determine the largest height, then draw both boxes (which involves setting up the context for each again).

This library simplifies the problem by treating each item on the PDF as an object.

Using this library, you would create and configure an object for each box, find the height of each, determine the largest height, set that height on both boxes, then draw them.  You're no longer responsible for setting up and restoring context; the objects maintain their own contexts.

## Example

This example implements the problem described in the "Why" section, where two boxes of text with different font sizes are written next to each other with the same height.

The code below assumes the TCPDF object is already created with units in inches and a blank page in portrait Letter size.  It also assumes you will output the PDF document afterwards.  See the full example in ```example.php```.

```
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

// find the height of the tallest box
$maxHeight = max($leftBox->getHeight(), $rightBox->getHeight());

// make both boxes use the tallest height
$leftBox->setHeight($maxHeight);
$rightBox->setHeight($maxHeight);

// draw both boxes
$leftBox->drawAtPosition(0.5, 1);
$rightBox->drawAtPosition(4.25, 1);
```
