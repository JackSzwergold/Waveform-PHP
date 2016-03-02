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
function parse_waveform_image_data ($filename, $source_width, $source_height) {

  $image_processed = imagecreatefrompng($filename);
  imagealphablending($image_processed, true);
  imagesavealpha($image_processed, true);

  $waveform_data = array();

  for ($width = 0; $width < $source_width; $width++) {

    for ($height = 0; $height < $source_height; $height++) {

      // Get the index of the color of a pixel.
      $color_index = @imagecolorat($image_processed, $width, $height);

      // Get the colors for an index.
      $rgb_array = imagecolorsforindex($image_processed, $color_index);

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
function render_data_as_image ($filename, $waveform_data, $source_width, $source_height) {

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

  // swap_colors($filename, $image, $background_color, array('red' => 150, 'green' => 49, 'blue' => 246));

  // Set the content headers.
  header("Content-type: image/png" );
  header("Content-Disposition: inline; filename=\"{$filename}\"");

  // Output the PNG file.
  imagepng($image);

  // Deallocate the colors.
  imagecolordeallocate($image, $background_color);
  imagecolordeallocate($image, $waveform_color);

  // Destroy the image to free up memory.
  imagedestroy($image);

  exit;

} // render_data_as_image


//**************************************************************************************//
// Convert HEX values to an RGB array.
function hex_to_rgb ($hex_value) {

  $rgb_components = array('red', 'green', 'blue');

  // Convert the HEX value into an RGB array.
  $raw_rgb_array = array_map('hexdec', str_split($hex_value, 2));

  // Round the final values and assign them to an array.
  $ret = array();
  if (!empty($raw_rgb_array)) {
    foreach ($rgb_components as $rgb_key => $rgb_component) {
      $ret[$rgb_component] = $raw_rgb_array[$rgb_key];
    }
  }

  // Return the final values.
  return $ret;

} // rgb_to_hex

//**************************************************************************************//
// Swap one color for another.
function swap_colors ($filename, $color_map) {

  // Testing the color swappping logic.
  $image_processed = imagecreatefrompng($filename);

  // Swap colors based on the index with a new RGB color.
  $filename_array = array();
  foreach ($color_map as $color_map_key => $color_map_value) {

    // Convert the hex values to RGB values.
    $rgb_src = hex_to_rgb($color_map_value['src']);
    $rgb_dst = hex_to_rgb($color_map_value['dst']);

    // Set the destination hex values as part of the filename array.
    $filename_array[] = $color_map_value['dst'];

    $source_color_index = imagecolorclosest($image_processed, $rgb_src['red'], $rgb_src['green'], $rgb_src['blue']);
    imagecolorset($image_processed, $source_color_index, $rgb_dst['red'], $rgb_dst['green'], $rgb_dst['blue']);

  }

  // Create a new filename based on the swapped colors.
  $pathinfo = pathinfo($filename);
  $new_filename = $pathinfo['filename'] . '_' . implode('_', $filename_array) . '.' . $pathinfo['extension'];




  if (TRUE) {

    // Set the content headers.
    // header("Content-type: text/plain" );
    header("Content-type: text/html;charset=UTF-8" );

    // Capture the rendered image in a variable and base64 encode it.
    ob_start();
    imagepng($image_processed);
    $image_processed_data = ob_get_contents();
    ob_end_clean();
    $image_processed_base64 = base64_encode($image_processed_data);

    $data_css = 'url(data:image/png;base64,' . $image_processed_base64 . ')';
    $data_div = sprintf('<div style="width: 1800px; height: 280px; padding: 10px; background-image:%s; background-repeat: no-repeat;"><h3>This is an image rendered as direct data background via CSS.</h3></div>', $data_css );

    // Simple HTML document for testing.
    echo <<<EOT
<!DOCTYPE html>
<html>
<head>
	<title>Waveform Test</title>
</head>
<body>
${data_div}
</body>
</html>
EOT;

    exit;
  }
  else {
    render_image($image_processed, $new_filename, $source_color);
  }

} // swap_colors



//**************************************************************************************//
// Render the image.
function render_image ($image_processed, $new_filename, $source_color) {

  // Set the content headers.
  header("Content-type: image/png" );
  header("Content-Disposition: inline; filename=\"{$new_filename}\"");

  // Output the PNG file.
  imagepng($image_processed);

  // Deallocate the color.
  imagecolordeallocate($image_processed, $source_color);

  // Destroy the image to free up memory.
  imagedestroy($image_processed);

  exit;

} // render_image

//**************************************************************************************//

// Set the image file.
$image_array = array();
$image_array[] = 'waveform1.png';
$image_array[] = 'waveform2.png';
$image_array[] = 'waveform3.png';
$image_array[] = 'waveform4.png';
$image_array[] = 'waveform5.png';
$image_array[] = 'waveform6.png';

shuffle($image_array);

$filename = $image_array[0];

if (FALSE) {

  // Set the width and height.
  $source_width = 1800;
  // $source_height = 280; // Full size waveform which is just a 2x mirror of the waveform itself.
  $source_height = 140; // The waveform is just 140 pixels high.

  // Parse the waveform image data.
  $waveform_data = parse_waveform_image_data($filename, $source_width, $source_height);

  // Render the image.
  render_data_as_image($filename, $waveform_data, $source_width, $source_height);

}
else {

  // Set the color map array.
  $color_map = array();

  // Waveform background color.
  $color_map[0] = array('src' => 'efefef', 'dst' => '888888');

  // Waveform color.
  $color_map[1] = array('src' => '000000', 'dst' => 'ffff00');

  // Actually swap the colors.
  swap_colors($filename, $color_map);

}

?>