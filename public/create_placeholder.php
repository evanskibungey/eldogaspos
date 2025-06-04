<?php
// Create a simple placeholder image
$width = 300;
$height = 300;

// Create image
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 240, 240); // Light gray
$text_color = imagecolorallocate($image, 150, 150, 150); // Dark gray
$border_color = imagecolorallocate($image, 200, 200, 200); // Medium gray

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Draw border
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Add text
$text = "No Image";
$font_size = 5; // Built-in font size
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;
imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save image
imagejpeg($image, 'images/placeholder.jpg', 90);
imagedestroy($image);

echo "Placeholder image created successfully at images/placeholder.jpg";
?>
