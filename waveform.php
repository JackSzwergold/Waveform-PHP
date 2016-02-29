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
 *
 */

//**************************************************************************************//
// Basic SoundCloud API PNG waveform.
// http://w1.sndcdn.com/fxguEjG4ax6B_m.png

// SoundCloud JSON waveform data that is accessible, but not really advertised by SoundCloud so who knows when it might go away.
// https://wis.sndcdn.com/fxguEjG4ax6B_m.png

//**************************************************************************************//
// Here is where the magic happens!

// $image_file = 'waveform1.png';
$image_file = 'waveform2.png';

$source_width = 1800;
# $source_height = 280; // Full size waveform which is just a 2x mirror of the waveform itself.
$source_height = 140; // The waveform is just 140 pixels high.

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

// Simple debugging output.
if (TRUE) {

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

}

?>