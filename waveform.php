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
 *          2016-02-29, js: color swapping works well and more efficiently than redrawing.
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

      // Peak detection is based on matching a transparent PNG value.
      $match_color_index = array(0, 0, 0, 127);
      $diff_value = array_diff($match_color_index, array_values($rgb_array));
      // if ($rgb_array['alpha'] == 127) {
      if (empty($diff_value)) {
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
function render_image ($image_file, $waveform_data, $source_width, $source_height) {

  // Create the image canvas.
  $image = imagecreate($source_width, $source_height * 2);

  // Set the colors.
  if (FALSE) {
    $background_color = imagecolorallocate($image, 239, 239, 239);
    $waveform_color = imagecolorallocate($image, 255, 255, 204);
  }
  else {
    $background_color = imagecolorallocatealpha($image, 239, 239, 239, 255);
    $waveform_color = imagecolorallocatealpha($image, 255, 255, 204, 255);
  }

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

  // swap_colors($image_file, $image, $background_color, array('red' => 150, 'green' => 49, 'blue' => 246));

  // Set the content headers.
  header("Content-type: image/png" );
  header("Content-Disposition: inline; filename=\"{$image_file}\"");

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
function swap_colors ($image_file, $image_processed, $color_swap_map) {

  // Swap colors based on the index with a new RGB color.
  foreach ($color_swap_map as $key => $value) {
    $source_color = imagecolorclosest($image_processed, $value['source']['red'], $value['source']['green'], $value['source']['blue']);
    imagecolorset($image_processed, $source_color, $value['swap']['red'], $value['swap']['green'], $value['swap']['blue']);
  }

  // Set the content headers.
  header("Content-type: image/png" );
  header("Content-Disposition: inline; filename=\"{$image_file}\"");

  // Output the PNG file.
  imagepng($image_processed);

  // Deallocate the color.
  imagecolordeallocate($image_processed, $source_color);

  // Destroy the image to free up memory.
  imagedestroy($image_processed);

  exit;

} // swap_colors


//**************************************************************************************//

// Set the image file.
$image_array = array();
$image_array[] = 'waveform1.png';
$image_array[] = 'waveform2.png';
$image_array[] = 'waveform3.png';

shuffle($image_array);

$image_file = $image_array[0];

if (FALSE) {

  // Testing the color swappping logic.
  $image_processed = imagecreatefrompng($image_file);
  // $color_sample = imagecolorat($image_processed, 0, 130);
  // $color_sample_index = imagecolorsforindex($image_processed, $color_sample);

  // Detect the color closest to the set values.
  // $color_sample_index = imagecolorclosest($image_processed, 239, 239, 239);
  // $color_sample_index = imagecolorclosest($image_processed, 0, 0, 0);

  // Set the color map array.
  $color_swap_map = array();
  $color_swap_map[0]['source'] = array('red' => 239, 'green' => 239, 'blue' => 239);
  $color_swap_map[0]['swap'] = array('red' => 95, 'green' => 95, 'blue' => 95);
  $color_swap_map[1]['source'] = array('red' => 0, 'green' => 0, 'blue' => 0);
  $color_swap_map[1]['swap'] = array('red' => 255, 'green' => 255, 'blue' => 204);

  // Actually swap the colors.
  swap_colors($image_file, $image_processed, $color_swap_map);

}
else {

  // Set the width and height.
  $source_width = 1800;
  // $source_height = 280; // Full size waveform which is just a 2x mirror of the waveform itself.
  $source_height = 140; // The waveform is just 140 pixels high.

  // Parse the waveform image data.
  $waveform_data = parse_waveform_image_data($image_file, $source_width, $source_height);

  // Render the image.
  render_image($image_file, $waveform_data, $source_width, $source_height);

}

?>