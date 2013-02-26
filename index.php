<?php

$file = getFileFromList(dirname(__FILE__));
$result_x = 800;
$result_y = 480;
$font_size = 60;
$border_size = 1;
$text_colour = 'white';
$background_colour = 'black';
$picture_max_x = $result_x - $border_size - $border_size;
$picture_max_y = $result_y - $font_size;

/* Load the picture we're working with */
$picture = new Imagick($file);
$picture->rotateImage(new ImagickPixel($background_colour), getOrientation($picture->getImageOrientation()));
$src_x = $picture->getImageWidth();
$src_y = $picture->getImageHeight();
if ($src_x > $src_y) {
  $scale = $src_y/$src_x;
  $width = $picture_max_x - $font_size;
  $height = ceil(($picture_max_x - $font_size) * $scale);
  if ($height > $picture_max_y) {
    $width = ceil(($picture_max_y - $font_size) / $scale);
    $height = $picture_max_y - $font_size;
  }
} else {
  $scale = $src_x/$src_y;
  $width = ceil(($picture_max_y - $font_size) * $scale);
  $height = $picture_max_y - $font_size;
  if ($width > $picture_max_x) {
    $width = $picture_max_x - $font_size;
    $height = ceil(($picture_max_x - $font_size) / $scale);
  }
}
$picture->adaptiveResizeImage($width, $height);

/* Create some objects */
$final_output = new Imagick();
$footer_text = new ImagickDraw();

/* New image */
$final_output->newImage($result_x, $result_y, new ImagickPixel($background_colour));

/* White text */
$footer_text->setFillColor($text_colour);

/* Font properties */
$footer_text->setFont('Bookman-DemiItalic');
$footer_text->setFontSize($font_size);
$footer_text->setGravity (Imagick::GRAVITY_CENTER);

/* Create text */
$text_x = 0;
$text_y = $picture_max_y /2;
$final_output->annotateImage($footer_text, $text_x, $text_y, 0, date('H:i d/m'));

/* Locate the destination for the picture */
$x_place = (($result_x - $picture->getImageWidth()) / 2);
$y_place = (($result_y - $picture->getImageHeight()) / 2);

/* Now merge the picture on to the background */
$final_output->setImageColorspace($picture->getImageColorspace());
$final_output->compositeImage($picture, $picture->getImageCompose(), $x_place, $y_place);

/* Give image a format */
$final_output->setImageFormat('jpeg');

/* Output the image with headers */
header('Content-type: image/jpeg');
echo $final_output;

function getFileFromList($dirname = '.') {
  $files = recurse_dir($dirname);
  $new = true;
  try {
    if (file_exists(dirname(__FILE__) . '/sources.sqlite')) {
      $new = false;
    }
    $db = new PDO('sqlite:' . dirname(__FILE__) . '/sources.sqlite');
    if ($new == true) {
      $sql = "CREATE TABLE sources (strFilename TEXT, datUsed INTEGER)";
      $qry = $db->query($sql);
    }
    $sql = "DELETE FROM sources WHERE datUsed <= '" . date("Y-m-d H:i:s", strtotime("-30 minutes")) . "'";
    $qry = $db->query($sql);
    $sql = "SELECT strFilename FROM sources";
    $prepare = $db->prepare($sql);
    if ($prepare !== false) {
      $state = $prepare->execute();
      $data = $prepare->fetchAll();
      foreach($files as $handle => $file) {
        if ( ! isset($data[$handle])) {
          $new_files[] = $handle;
        }
      }
      $file = $new_files[rand(0, count($new_files) - 1)];
      $sql = "INSERT INTO sources (strFilename, datUsed) VALUES ( :file , :date )";
      $qry = $db->prepare($sql);
      $state = $qry->execute(array('file'=>$file, 'date'=>date('Y-m-d H:i:s')));
      return $file;
    }
  } catch(Exception $e) {
    exit(1);
  }
}

function recurse_dir($dirname = '.') {
  $files = array();
  $dir = opendir($dirname);
  while ($dir && ($file = readdir($dir)) !== false) {
    $path = $dirname . '/' . $file;
    if (is_dir($path) and $file != '.' and $file != '..') {
      $files = array_merge($files, recurse_dir($path));
    } elseif ($file == '.' or $file == '..') {
      // do nothing!
    } else {
      if (
        strtolower(substr($file, -3)) == 'jpg'
        or strtolower(substr($file, -4)) == 'jpeg'
        or strtolower(substr($file, -3)) == 'png'
        or strtolower(substr($file, -3)) == 'gif'
        or strtolower(substr($file, -3)) == 'bmp'
      ) {
        $files[$path] = $path;
      }
    }
  }
  ksort($files);
  return $files;
}

function getOrientation($o) {
  switch ($o) {
    case 3:
      return "180";
      break;
    case 4:
      return "180";
      break;
    case 5:
      return "90";
      break;
    case 6:
      return "90";
      break;
    case 7:
      return "270";
      break;
    case 8:
      return "270";
      break;
    default:
      return "0";
      break;
  }
}
