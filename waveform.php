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
// Parse the waveform image for raw data.
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
function swap_colors ($filename, $color_map, $background_color, $transparent_color, $render_image_as_base64 = FALSE, $render_image_as_div = FALSE) {

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

    $source_color_index[$color_map_key] = imagecolorclosest($image_processed, $rgb_src['red'], $rgb_src['green'], $rgb_src['blue']);
    imagecolorset($image_processed, $source_color_index[$color_map_key], $rgb_dst['red'], $rgb_dst['green'], $rgb_dst['blue']);

  }

  // Create a new filename based on the swapped colors.
  $pathinfo = pathinfo($filename);
  $new_filename = $pathinfo['filename'] . '_' . implode('_', $filename_array) . '.' . $pathinfo['extension'];

  // Set the transparent color.
  if ($transparent_color) {
    $rgb_trans = hex_to_rgb($transparent_color);
    $transparent_color_index = imagecolorclosest($image_processed, $rgb_trans['red'], $rgb_trans['green'], $rgb_trans['blue']);
    imagecolortransparent($image_processed, $transparent_color_index);
  }

  if ($render_image_as_base64) {
    render_image_as_base64($image_processed, $new_filename, $source_color_index, $background_color, $render_image_as_div);
  }
  else {
    render_image_as_png($image_processed, $new_filename, $source_color_index, $background_color);
  }

} // swap_colors


//**************************************************************************************//
// Render a PNG image based on the raw JSON data.
function render_data_as_image ($filename, $waveform_data, $source_width, $source_height, $colors) {

  // Create the image canvas.
  $image_processed = imagecreate($source_width, $source_height * 2);

  // Get the RGB values from the hex values.
  $background_rgb = hex_to_rgb($colors['background']);
  $foreground_rgb = hex_to_rgb($colors['foreground']);

  // Create the color indexes.
  $background_color = imagecolorallocate($image_processed, $background_rgb['red'], $background_rgb['green'], $background_rgb['blue']);
  $waveform_color = imagecolorallocate($image_processed, $foreground_rgb['red'], $foreground_rgb['green'], $foreground_rgb['blue']);

  // Set the background color.
  imagefill($image_processed, 0, 0, $background_color);

  // Define a color as transparent.
  imagecolortransparent($image_processed, $background_color);

  // Set the line thickness.
  imagesetthickness($image_processed, 1);

  // Draw the lines of the waveform.
  foreach ($waveform_data as $key => $value) {
    imageline($image_processed, $key, ($source_height - $value), $key, ($source_height + $value), $waveform_color);
  }

  // Create a new filename based on the colors.
  $pathinfo = pathinfo($filename);
  $new_filename = $pathinfo['filename'] . '_' . implode('_', $colors) . '.' . $pathinfo['extension'];

  // Render the image as a PNG.
  $deallocate_colors = array($background_color, $waveform_color);
  render_image_as_png($image_processed, $new_filename, $deallocate_colors);

} // render_data_as_image


//**************************************************************************************//
// Generate and render JSON data output.
function render_data_as_json ($waveform_data, $source_width, $source_height) {

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

} // render_data_as_json


//**************************************************************************************//
// Render the image.
function render_image_as_png ($image_processed, $new_filename, $deallocate_colors) {

  // Set the content headers.
  header("Content-type: image/png" );
  header("Content-Disposition: inline; filename=\"{$new_filename}\"");

  // Output the PNG file.
  imagepng($image_processed);

  // Deallocate the color.
  foreach ($deallocate_colors as $deallocate_color) {
    imagecolordeallocate($image_processed, $deallocate_color);
  }

  // Destroy the image to free up memory.
  imagedestroy($image_processed);

  exit;

} // render_image_as_png


//**************************************************************************************//
// Render the image tag.
function render_image_as_base64 ($image_processed, $new_filename, $source_color_index, $background_color, $render_image_as_base64 = FALSE) {

  // Set the content headers.
  // header("Content-type: text/plain" );
  header("Content-type: text/html;charset=UTF-8" );

  // Capture the rendered image in a variable and base64 encode it.
  ob_start();
  imagepng($image_processed);
  $image_processed_data = ob_get_contents();
  ob_end_clean();
  $image_base64_data = base64_encode($image_processed_data);
  $image_base64 = 'data:image/png;base64,' . $image_base64_data;

  $background_color_css = !empty($background_color) ? sprintf("background-color: #%s; ", $background_color) : '';

  $data_div = sprintf('<div style="%1$sbackground-image: url(%2$s); background-repeat: no-repeat; width: %3$spx; height: %4$spx; padding: 10px;">', $background_color_css, $image_base64, 1800, 280)
            . '<h3>This is an image rendered as direct data background via CSS.</h3>'
            . '</div>'
            ;
  $image_tag = sprintf('<img src="%s" width="%2$d" height="%3$d" border="0">', $image_base64, 900, 140);

  // Simple HTML document for testing.
  if ($render_image_as_base64) {
    echo $data_div;
  }
  else {
    echo sprintf('<div style="%1$swidth: %2$s; height: %3$s; margin: 0 0 10px 0; padding: 0; overflow: hidden;">', $background_color_css, '30%', '140px');
    echo $image_tag;
    echo '</div>';
    echo sprintf('<div style="%1$swidth: %2$s; height: %3$s; margin: 0 0 10px 0; padding: 0; overflow: hidden;">', $background_color_css, '100%', '140px');
    echo $image_tag;
    echo '</div>';
  }

  // Deallocate the color.
  imagecolordeallocate($image_processed, $source_color_index);

  // Destroy the image to free up memory.
  imagedestroy($image_processed);

  exit;

} // render_image_as_base64


//**************************************************************************************//
// This is where all of the functions actually come into play.

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

  // Waveform colors.
  $colors = array('background' => 'efefef', 'foreground' => '335511');

  // Parse the waveform image data.
  $waveform_data = parse_waveform_image_data($filename, 1800, 140);

  if (FALSE) {
    // Render the data as a JSON object.
    render_data_as_json($waveform_data);
  }
  else {
    // Render the data as a PNG image.
    render_data_as_image($filename, $waveform_data, 1800, 140, $colors);
  }

}
else {

  // Set the color map array.
  $color_map = array();

  // Waveform background color.
  $color_map['background'] = array('src' => 'efefef', 'dst' => '888888');

  // Waveform foreround color.
  $color_map['foreground'] = array('src' => '000000', 'dst' => '335511');

  $background_color = 'aa0000';
  $transparent_color = '888888';

  // Actually swap the colors.
  swap_colors($filename, $color_map, $background_color, $transparent_color, TRUE, FALSE);

}

?>