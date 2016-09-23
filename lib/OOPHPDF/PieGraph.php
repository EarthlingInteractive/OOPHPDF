<?php
/**
 * Draws pie charts and accompanying legends.
 *
 * This class relies on SVGGraph library to draw the pie proper as an SVG image,
 * and on OOPHPDF_Multicell to draw the legend. SVGGraph does have support
 * for drawing a legend but it has proved to be not as flexible as drawing one
 * via MultiCell.
 *
 * @since v.0.0.3
 */
class OOPHPDF_PieGraph extends OOPHPDF_Object implements OOPHPDF_Drawable  {

	// OOPHPDF_MultiCell object
	protected $legendLabelMultiCell;

	// OOPHPDF_MultiCell object
	protected $legendBoxMultiCell;

	/*
	 * Used for both pie wedges and legend box fill color.
	 * An array containing arrays of RGB values, like this:
	 * array(
	 * 	  array(255,255,255,),
	 * 	  array(0,0,0)
	 * );
	 */
	protected $wedgeAndLegendColorsRgb = array();

	// the value (percentage) for each pie wedge; determines the proportion of a wedge.
	protected $pieWedgeValues = array();

	// true = show MultiCell legend (uses tcpdf's multicell to make a legend;
	// svggraph's built-in legend is not used in this class).
	protected $isDisplayLegend = true;

	// the space between a legend block and the legend text to the left of it
	protected $legendLabelLeftPadding = 0.0;

	// the space between each row of legend blocks/text lines
	protected $legendVerticalSpacing = 0.0;

	// the distance to offset pie horizontally from legend origin.
	protected $pieOffsetX = 0;

	// the distance to offset pie vertically from ledged origin.
	protected $pieOffsetY = 0;

  // rgb values for 'No Data' text that displays in lieu of a non-existent pie.
  protected $emptyPieTextColor = array(239, 55, 58);

	/*
	 * There is a relationship between the aspect_ratio setting and these width &
   * height values. If aspect_ratio in the graph_settings array is 1.0, the
   * smallest value for width or height here controls the size of the circle
   * (the pie proper); HOWEVER, the svg image itself is rectilinear, the height
   * and width of which is set here (give it a border and this becomes visible).
   * The circular pie is then drawn inside it.
	 *
	 * The pie can be made smaller in diameter even when these values are larger
   * by setting the padding to take up space and force the pie to be sized to
   * fit what's left over inside the dimensions of the graphic. So if width
   * were 300 and height were 200, a pie circle of 100 would be the result
   * IF top and/or bottom padding = 100 OR left and/or right padding = 200.
	 *
	 * This also means the pie circle can be "moved around" inside the area of
   * the svg image. (could be useful when applying a border to the svggraphic
   * for layout purposes.)
	 */
	protected $svgGraphicWidth = 100.0;
	protected $svgGraphicHeight = 100.0;

	/**
   * 'Override' the default settings for SVGGraph and SVGGraphPieGraph set
   * in svggraph.ini in the SVGGraph library. There are dozens of default
   * settings, so if something with SVGGraph is not working as you expect,
   * you might want to check that file.
   *
   * @see "svggraph.ini"
	 * @var array $svgGraphSettings
	 */
	protected $svgGraphSettings = array(
		'aspect_ratio' => 1.0,
		'show_data_labels' => true,
		'show_label_key' => false,
		'show_label_percent' => true,
		'label_font_weight' => 'bold',
		'label_font_size' => 8,
		'label_percent_decimals' => 0,
		'label_position' => 0.5, // distance of label from center
		'stroke_width' => 0, // border around pie and slices
		'legend_entries' => array(),
		'legend_stroke_width' => 0, // border around legend entry box
		'legend_font_size' => 0,
		'legend_entry_width' => 0, // width of legend entry box
		'legend_entry_height' => 0, // height of legend entry box
		'legend_shadow_opacity' => 0,
		'legend_position' => 'outer top left',
		'legend_padding' => 0, // space between entries
		'legend_show_empty' => false, // true=show even if no values
		'start_angle' => -90,
		'sort' => false,
		'legend_text_side' => 'right',
		'legend_colour' => 'rgb(0,0,0)',
		'pad_top' => 0, // adds space between the legend and the pie.
		'pad_left' => 0,
		'pad_right' => 0,
		'pad_bottom' => 0
	);

	protected $legendBoxSettings = array(
		'height' 			=> 0.5,
		'width' 			=> 0.5,
		'fontSize' 			=> 6.0,
		'textColorArray' 	=> array(255, 255, 255), // white
		'fillColorArray'	=> array(255, 200, 200), // pink. yep, pink
		'alignHorizontal' 	=> 'L',
		'alignVertical' 	=> 'M',
		'cellPaddingLeft' 	=> 0.0,
		'cellPaddingTop' 	=> 0.0,
		'cellPaddingRight' 	=> 0.0,
		'cellPaddingBottom' => 0.0
	);

	protected $legendLabelSettings = array(
		'height' 			=> 0.5,
		'width' 			=> 0.5,
		'fontSize' 			=> 6.0,
		'textColorArray' 	=> array(0,0,0), // black
		'fillColorArray'	=> null, // will default to the pdf body color
		'alignHorizontal' 	=> 'L',
		'alignVertical' 	=> 'B',
		'cellPaddingLeft' 	=> 0.0,
		'cellPaddingTop' 	=> 0.0,
		'cellPaddingRight' 	=> 0.0,
		'cellPaddingBottom' => 0.0
	);

	/**
	 * Sets the width of the svg graphic, within which the pie circle is drawn.
	 *
	 * The graphic is rectilinear, and it can be larger than the pie.
   * (set $svgGraphSettings=>pad_left, pad_right, or a combo of both to force
   * the pie circle to become a smaller inside the graphic area proper.)
	 *
	 * @param float $width 	a value in the pdf's units of the graphic the pie
   *                      circle is contained within.
	 */
	public function setSvgGraphicWidth($width) {
		$this->svgGraphicWidth = $width;
	}

	/**
	 * Sets the height of the svg graphic, within which the pie circle is drawn.
	 *
	 * The graphic is rectilinear, and it can be larger than the pie.
   * (set $svgGraphSettings=>pad_top, pad_bottom, or a combo of both to force
   * the pie circle to become a smaller inside the graphic area proper.)
	 *
	 * @param $height float 	a value in the pdf's units of the graphic the pie
   *                        circle is contained within.
	 */
	public function setSvgGraphicHeight($height) {
		$this->svgGraphicHeight = $height;
	}


	public function setPieOffsetX($pieOffsetX) {
		$this->pieOffsetX = $pieOffsetX;
		return $this;
	}

	public function setPieOffsetY($pieOffsetY) {
		$this->pieOffsetY = $pieOffsetY;
		return $this;
	}

	/**
	 * True or false whether to display the MultiCell's legend (not the SVGGraph
   * legend; that is not used, the TCPDF's MultiCell is used to draw the legend
   * instead as it it more flexible).
   *
	 * @param $isDisplayLegend
	 * @return $this
	 */
	public function isDisplayLegend($isDisplayLegend) {
		$this->isDisplayLegend = $isDisplayLegend;
		return $this;
	}

	public function setLegendVerticalSpacing($legendVerticalSpacing) {
		$this->legendVerticalSpacing = $legendVerticalSpacing;
		return $this;
	}

	public function setLegendLabelLeftPadding($legendLabelLeftPadding) {
		$this->legendLabelLeftPadding = $legendLabelLeftPadding;
		return $this;
	}

	/**
	 * The colors are used in coloring the pie wedges as well as coloring
	 * the corresponding legend blocks, if the legend display is enabled.
   *
	 * @param array $colors example: [255, 255, 255] for white
	 * @return $this
	 */
	public function setWedgeAndLegendColorsRgb(Array $colors) {
		$this->wedgeAndLegendColorsRgb = $colors;
		return $this;
	}

	/**
	 * Sets key/value pairs for legend names to graph values.
	 * Expects this format: legendLabelName => graphValue
   *
	 * @param array $values
	 * @return $this
	 */
	public function setPieWedgeValues(array $values) {
		$this->pieWedgeValues = $values;
		return $this;
	}

	/**
	 * A means to 'override' the values in SVGGraph's default settings array.
   *
   * @see "svggraph.ini"
	 * @param array $graphicSettings
	 */
	public function mergeSvgGraphicSettings(array $graphicSettings) {
		$newSettings = array_merge($this->svgGraphSettings, $graphicSettings);
		$this->svgGraphSettings = $newSettings;
	}

	/**
	 * A means to 'override' the many default settings used by OOPHPDF_MultiCell
   * for drawing the legend boxes.
   *
   * @see "OOPHPDF_Multicell"
	 * @param array $legendBoxSettings
	 */
	public function mergeLegendBoxSettings(array $legendBoxSettings) {
		$newSettings = array_merge($this->legendBoxSettings, $legendBoxSettings);
		$this->legendBoxSettings = $newSettings;
	}

	/**
	 * A means to 'override' the many default settings used by MultiCell for
	 * drawing the legend label text.
   *
	 * @param array $legendLabelSettings
	 */
	public function mergeLegendLabelSettings(array $legendLabelSettings) {
		$newSettings = array_merge($this->legendLabelSettings, $legendLabelSettings);
		$this->legendLabelSettings = $newSettings;
	}

  public function setEmptyPieTextColor(array $rgbColor) {
    $this->emptyPieTextColor = $rbgColor;
  }

	public function draw() {
		$this->drawAtPosition($this->pdf->getX(), $this->pdf->getY());
	}

	public function drawAtPosition($x, $y) {
		$curX = $x;
		$curY = $y;
		$curX += $this->pieOffsetX;
		$curY += $this->pieOffsetY;

		$this->drawLegend($x, $y);

		if (array_sum($this->pieWedgeValues) > 0) {

			$this->drawPie($curX, $curY);

		} else {
			$this->drawEmptyPie($curX, $y);
		}
	}

  /**
   * Creates the pie circle as an SVG image using SVGGraph, then imports it
   * into the pdf using TCPDF.
   */
	public function drawPie($curX, $curY) {

		// process the values and colors, to deal with a bug in SVGGraph where a value of 0
		// doesn't use a color from the list, which would cause the slices to not be the correct color
		$values = $this->pieWedgeValues;
		$colors = $this->getGraphColorsText();
		self::fixGraphValuesAndColors($values, $colors);

		/*
		 Create the pie as a circle in an SVG image; width in pixels, height in pixels, settings.
		 If aspect_raitio is 1.0, the smallest value controls the size; HOWEVER, the svg image itself
		 is rectilinear (give it a border and this becomes visible) and will be what height and width
		 you set it to, such as 100 x 200.
		*/
		$graph = new SVGGraph($this->svgGraphicWidth, $this->svgGraphicHeight, $this->svgGraphSettings);

		$graph->Values($values);
		$graph->Colours($colors);
		$svg = $graph->Fetch('PieGraph');

		// This imports the svg into the PDF.
		$this->pdf->ImageSVG('@' . $svg,
		$curX, // abscissa of upper-left corner of image
		$curY, // ordinate of upper-left corner of image
		0, // w 2.7
		0, // h 3.3
		'',  // link
		'',  // align
		'',  // palign
		array(/*'LRB' => array('width' => .02,
							'cap' => 'butt',
							'join' => 'miter',
							'dash' => 0,
							'color' => array(108, 109, 112)) */)
		);
	}

	public function drawEmptyPie($curX, $y) {
		$noDataMessage = new OOPHPDF_Multicell($this->pdf);
		$noDataMessage
			->setText("No data to display")
			->setFontSize(8)
			->setTextColorArray(Align_EP_UtilPdfColor::$RGB_PIE_MAGENTARED)
			->drawAtPosition($curX+1, $y);
	}

	public function drawLegend($x, $y) {
		$curY = $y;
		$labelTextX = $x + $this->legendBoxSettings['width'] + $this->legendLabelLeftPadding;

		$this->createLegendMultiCells();
		$this->configureLegendMultiCellSettings();

		$i=0;
		foreach ($this->pieWedgeValues as $pieWedgeName => $pieWedgeValue) {
			$this->legendBoxMultiCell->setFillColorArray($this->wedgeAndLegendColorsRgb[$i++])
				->setText($pieWedgeValue . "%")
				->drawAtPosition($x, $curY)
				;

			$this->legendLabelMultiCell->setText($pieWedgeName)
				->setTextColorArray($this->legendLabelSettings['textColorArray'])
				->drawAtPosition($labelTextX, $curY);

			$lineHeight = max($this->legendBoxSettings['height'], $this->legendLabelSettings["height"]);
			$curY += $lineHeight + $this->legendVerticalSpacing;
		}
		$i = null;

	}

	protected function createLegendMultiCells() {
		// instantiate the MultiCell object needed.
		$this->legendBoxMultiCell = new OOPHPDF_MultiCell($this->pdf);
		$this->legendLabelMultiCell = new OOPHPDF_Multicell($this->pdf);
	}

	protected function configureLegendMultiCellSettings() {
		$this->legendBoxMultiCell->setHeight($this->legendBoxSettings['height'])
			 ->setWidth($this->legendBoxSettings['width'])
			 ->setFontSize($this->legendBoxSettings['fontSize'])
			 ->setTextColorArray($this->legendBoxSettings['textColorArray'])
			 ->setAlignHorizontal($this->legendBoxSettings['alignHorizontal'])
			 ->setAlignVertical($this->legendBoxSettings['alignVertical'])
			 ->setCellPaddingLeft($this->legendBoxSettings['cellPaddingLeft'])
			 ->setCellPaddingTop($this->legendBoxSettings['cellPaddingTop'])
			 ->setCellPaddingRight($this->legendBoxSettings['cellPaddingRight'])
			 ->setCellPaddingBottom($this->legendBoxSettings['cellPaddingBottom'])
		;

		$this->legendLabelMultiCell->setHeight($this->legendLabelSettings["height"])
			   ->setWidth($this->legendLabelSettings["width"])
			   ->setAlignHorizontal($this->legendLabelSettings['alignHorizontal'])
			   ->setAlignVertical($this->legendLabelSettings['alignVertical'])
			   ->setFillColorArray($this->legendLabelSettings['fillColorArray'])
			   ->setFontSize($this->legendLabelSettings["fontSize"])
			   ->setCellPaddingLeft($this->legendLabelSettings['cellPaddingLeft'])
			   ->setCellPaddingTop($this->legendLabelSettings['cellPaddingTop'])
			   ->setCellPaddingRight($this->legendLabelSettings['cellPaddingRight'])
			   ->setCellPaddingBottom($this->legendLabelSettings['cellPaddingBottom'])
		;

	}

	// Needed for rendering
	protected function getGraphColorsText() {
		$graphColorsText = array();
		foreach ($this->wedgeAndLegendColorsRgb as $color) {
			$graphColorsText[] = 'rgb('.$color[0].','.$color[1].','.$color[2].')';
		}

		return $graphColorsText;
	}

	public static function fixGraphValuesAndColors(&$values, &$colors) {
		$i = -1;
		$resultValues = array();
		$resultColors = array();

		foreach ($values as $pieKey => $pieValue) {
			$i++;

			// skip this chunk if there isn't a value
			if ($pieValue <= 0) {
				continue;
			}

			$resultValues[$pieKey] = $pieValue;
			$resultColors[] = $colors[$i % count($colors)];
		}

		$values = $resultValues;
		$colors = $resultColors;
	}

}
