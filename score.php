<?php

$font = __DIR__.'/CaveatBrush-Regular.ttf';

function box($text, $sz) {
  global $font;

  $box = imagettfbbox($sz, 0, $font, $text);

  $min_x0 = min([$box[0], $box[2], $box[4], $box[6]]);
  $max_x0 = max([$box[0], $box[2], $box[4], $box[6]]);
  $min_y0 = min([$box[1], $box[3], $box[5], $box[7]]);
  $max_y0 = max([$box[1], $box[3], $box[5], $box[7]]);

  $w = $max_x0 - $min_x0;
  $h = $max_y0 - $min_y0;

  return [$w, $h];
}

function txt($img, $text, $x, $y, $color, $sz) {
  global $font;

  list($w, $h) = box($text, $sz);

  imagettftext($img, $sz, 0, $x - $w / 2, $y - $h / 2 + $sz, $color, $font, $text);
}

function measure(&$text) {
  global $width;

  foreach($text as &$txt) {
    list($ww, $hh) = box($txt['txt'], $txt['sz']);
    $txt['w'] = $ww;
    $txt['h'] = $hh;

    if($ww > $width) {
      $txt['sz'] = $txt['sz'] * 0.75 * $width / $ww;

      list($ww, $hh) = box($txt['txt'], $txt['sz']);
      $txt['w'] = $ww;
      $txt['h'] = $hh;
    }
  }
}

$img = imagecreatefrompng(__DIR__.'/score.png');

$hash = '';

foreach(['score', 'name', 'board'] as $key)
  $hash .= $_GET[$key].'|';

$hash_ok = md5($hash);

if(($_GET['hash'] ?? '') != $hash_ok) {
  header('Content-Type: image/png');
  imagepng($img);
  exit;
}

$width = imagesx($img);
$height = imagesy($img);

$text_color = imagecolorallocate($img, 255, 255, 192);
$rect_color = imagecolorallocate($img, 0, 0, 0);

$texts = [
  [
    'txt' => 'HexaDash score',
    'sz' => 16,
    'color' => [0, 255, 0],
  ],
  [
    'txt' => $_GET['score'],
    'sz' => 24,
    'color' => [255, 255, 255],
  ],
  [
    'txt' => $_GET['name'],
    'sz' => 20,
    'color' => [255, 255, 0],
  ],
  [
    'txt' => $_GET['board'],
    'sz' => 12,
    'color' => [0, 255, 255],
  ],
];

measure($texts);

$total_h = 0;

$max_w = 0;

foreach($texts as $txt) {
  $total_h += $txt['h'] + $txt['sz'] / 4;
  $max_w = max($max_w, $txt['w']);
}

$y0 = $height / 2 - $total_h / 2;
$y1 = $height / 2 + $total_h / 2;

$y = $y0 + $texts[0]['sz'] / 4;

$y0 -= $texts[0]['sz'] / 2;

$x0 = $width / 2 - $max_w / 2;
$x1 = $width / 2 + $max_w / 2;

$pad_h = 16;
$pad_v = 12;

$overlay = imagecreatetruecolor($max_w + 2 * $pad_h, $total_h + 2 * $pad_v);

$overlay_color = imagecolorallocate($overlay, 32, 32, 32);
$overlay_text = imagecolorallocate($overlay, 255, 255, 192);

$yo = $texts[0]['sz'] / 2;

imagefilledrectangle($overlay, 0, 0, $max_w + 2 * $pad_h, $total_h + 2 * $pad_v, $overlay_color);

foreach($texts as $txt) {
  txt($overlay, $txt['txt'], $max_w / 2 + $pad_h, $yo + $pad_v,
    imagecolorallocate($overlay, $txt['color'][0], $txt['color'][1], $txt['color'][2]), $txt['sz']);
  $yo += $txt['h'] + $txt['sz'] / 4;
}

imagecopymerge($img, $overlay, $width / 2 - $max_w / 2 - $pad_h, $height / 2 - $total_h / 2 - $pad_v, 0, 0,
  $max_w + 2 * $pad_h, $total_h + 2 * $pad_v, 90);

header('Content-Type: image/png');
imagepng($img);
