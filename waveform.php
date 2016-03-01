<?php

/**
 * Waveform (waveform.php) (c) by Jack Szwergold
 *
 * Waveform is licensed under a
 * Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License.
 *
 * You should have received a copy of the license along with this
 * work. If not, see <http://creativecommons.org/licenses/by-nc-sa/4.0/>.
 *
 * w: http://www.preworn.com
 * e: me@preworn.com
 *
 * Created: 2016-02-28, js
 * Version: 2016-02-28, js: creation
 *          2016-02-28, js: development & cleanup
 *          2016-02-29, js: logic to regenerate waveform from raw waveform data.
 *          2016-02-29, js: restructuring into functions.
 *
 */

//**************************************************************************************//
// Basic SoundCloud API PNG waveform.
// http://w1.sndcdn.com/fxguEjG4ax6B_m.png

// SoundCloud JSON waveform data that is accessible, but not really advertised by SoundCloud so who knows when it might go away.
// https://wis.sndcdn.com/fxguEjG4ax6B_m.png

//**************************************************************************************//
// Here is where the magic happens!

//**************************************************************************************//
// Generate and render JSON data output.
function parse_waveform_image_data ($image_file, $source_width, $source_height) {

	$image_processed = imagecreatefrompng($image_file);
	imagealphablending($image_processed, true);
	imagesavealpha($image_processed, true);

	$waveform_data = array();

	for ($width = 0; $width < $source_width; $width++) {

	  for ($height = 0; $height < $source_height; $height++) {

		$color_index = @imagecolorat($image_processed, $width, $height);

		if (FALSE) {
		  $rgb_array = array();

		  $red = ($color_index >> 16) & 0xFF;
		  $green = ($color_index >> 8) & 0xFF;
		  $blue = $color_index & 0xFF;

		  $rgb_array['red'] = intval($red);
		  $rgb_array['green'] = intval($green);
		  $rgb_array['blue'] = intval($blue);
		}
		else {
		  $rgb_array = imagecolorsforindex($image_processed, $color_index);
		}

		// Peak detection is based on whether there is an alpha channel or not.
		if ($rgb_array['alpha'] == 127) {
		  break;
		}

	  } // $height loop.

	  // Value is based on the delta between the actual height versus detected height.
	  $waveform_data[] = $source_height - $height;

	} // $width loop.

	return $waveform_data;

} // parse_waveform_image_data


//**************************************************************************************//
// Generate and render JSON data output.
function render_JSON ($waveform_data, $source_width, $source_height) {

  // Set a data array.
  $data = array();
  $data['width'] = $source_width;
  $data['height'] = $source_height;
  $data['samples'] = $waveform_data;

  // Encode the JSON.
  $ret = json_encode((object) $data);
  $ret = str_replace('\/','/', $ret);

  // Output the JSON data.
  header('Content-Type: application/json');
  print_r($ret);

} // render_JSON


//**************************************************************************************//
// Render a PNG based on the raw JSON data.
function render_image ($waveform_data, $source_width, $source_height) {

  // Create the image canvas.
  $image = imagecreate($source_width, $source_height * 2);

  // Set the colors.
  $background_color = imagecolorallocate($image, 187, 187, 187);
  $waveform_color = imagecolorallocate($image, 246, 150, 49);

  // Define a color as transparent.
  imagecolortransparent($image, $background_color);
  // imagecolortransparent($image, $waveform_color);

  // Set the line thickness.
  imagesetthickness($image, 1);

  // Draw the lines of the waveform.
  foreach ($waveform_data as $key => $value) {
   // imageline($image, $key, $value, $key, ($source_height * 2) - $value, $waveform_color);
   imageline($image, $key, ($source_height - $value), $key, ($source_height + $value), $waveform_color);
  }

  // Set the content headers.
  header("Content-type: image/png" );

  // Output the PNG file.
  imagepng($image);

  // Deallocate the colors.
  imagecolordeallocate($image, $background_color);
  imagecolordeallocate($image, $waveform_color);

  // Destroy the image to free up memory.
  imagedestroy($image);

  exit;

} // render_image


//**************************************************************************************//
// Swap one color for another.
function swap_colors ($waveform_data, $image, $source_color, $swap_color) {

  // Swap colors based on the index with a new RGB color.
  imagecolorset($image, $source_color, 150, 49, 246);

  // Set the content headers.
  header("Content-type: image/png" );

  // Output the PNG file.
  imagepng($image);

  // Deallocate the color.
  imagecolordeallocate($image, $source_color);

  // Destroy the image to free up memory.
  imagedestroy($image);

  exit;

} // swap_colors


//**************************************************************************************//

// Set the image file.
// $image_file = 'waveform1.png';
// $image_file = 'waveform2.png';
$image_file = 'waveform3.png';

// Set the width and height.
$source_width = 1800;
// $source_height = 280; // Full size waveform which is just a 2x mirror of the waveform itself.
$source_height = 140; // The waveform is just 140 pixels high.

// Parse the waveform image data.
$waveform_data = parse_waveform_image_data($image_file, $source_width, $source_height);

// Render the image.
render_image($waveform_data, $source_width, $source_height);

?>