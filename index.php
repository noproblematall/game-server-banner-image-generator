
<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "game";
$base_url = 'localhost/';
$server_url = 'https://www.gametracker.com/server_info/';
$banner_directory = 'banners/';
$background_image = 'background/1.png';
$game_image = './game_image/arma3.png';
$game_image2 = './game_image/arma3_2.png';
$logo1_image = './logo/logo1.png';
$logo2_image = './logo/logo2.png';
$flag_image = './flag_image/br.gif';
$map_image = './map_image/stratis.jpg';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$banner_item = array();
$sql = "SELECT * FROM info LIMIT 1";
$result = $conn->query($sql);
$real_time_data = array();
if ($result->num_rows > 0) {
    $banner_item = $result->fetch_assoc();
}
$sql = "SELECT * FROM realtimedata WHERE time >= NOW() - INTERVAL 1 DAY";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        array_push($temp, $row['number'], $row['time']);
        array_push($real_time_data, $temp);        
    }
}


function image_gradientrect($img,$x,$y,$x1,$y1,$start,$end) {
    if($x > $x1 || $y > $y1) {
        return false;
    }
    $s = array(
        hexdec(substr($start,0,2)),
        hexdec(substr($start,2,2)),
        hexdec(substr($start,4,2))
    );
    $e = array(
        hexdec(substr($end,0,2)),
        hexdec(substr($end,2,2)),
        hexdec(substr($end,4,2))
    );
    $steps = $y1 - $y;
    for($i = 0; $i < $steps; $i++) {
        $r = $s[0] - ((($s[0]-$e[0])/$steps)*$i);
        $g = $s[1] - ((($s[1]-$e[1])/$steps)*$i);
        $b = $s[2] - ((($s[2]-$e[2])/$steps)*$i);
        $color = imagecolorallocate($img,$r,$g,$b);
        imagefilledrectangle($img,$x,$y+$i,$x1,$y+$i+1,$color);
    }
    return true;
}

function ImageRectangleWithRoundedCorners(&$im, $x1, $y1, $x2, $y2, $radius, $color) {
    // draw rectangle without corners
    imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);
    // draw circled corners
    imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
}


function validate_hex($hex) {
    // Complete patterns like #ffffff or #fff
    if(preg_match("/^#([0-9a-fA-F]{6})$/", $hex) || preg_match("/^#([0-9a-fA-F]{3})$/", $hex)) {
        // Remove #
        $hex = substr($hex, 1);
    }
    
    // Complete patterns without # like ffffff or 000000
    if(preg_match("/^([0-9a-fA-F]{6})$/", $hex)) {
        return $hex;
    }
    
    // Short patterns without # like fff or 000
    if(preg_match("/^([0-9a-f]{3})$/", $hex)) {
        // Spread to 6 digits
        return substr($hex, 0, 1) . substr($hex, 0, 1) . substr($hex, 1, 1) . substr($hex, 1, 1) . substr($hex, 2, 1) . substr($hex, 2, 1);
    }
    
    // If input value is invalid return black
    return "000000";
}

function hex2hsl($hex) {
    //Validate Hex Input
    $hex = validate_hex($hex);
    
    // Split input by color
    $hex = str_split($hex, 2);

    // Convert color values to value between 0 and 1
    $r = (hexdec($hex[0])) / 255;
    $g = (hexdec($hex[1])) / 255;
    $b = (hexdec($hex[2])) / 255;
    
    return rgb2hsl(array($r,$g,$b));
}

function rgb2hsl($rgb) {
    // Fill variables $r, $g, $b by array given.
    list($r, $g, $b) = $rgb;
    
    // Determine lowest & highest value and chroma
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $chroma = $max - $min;
    
    // Calculate Luminosity
    $l = ($max + $min) / 2;
    
    // If chroma is 0, the given color is grey
    // therefore hue and saturation are set to 0
    if ($chroma == 0)
    {
        $h = 0;
        $s = 0;
    }
    
    // Else calculate hue and saturation.
    // Check http://en.wikipedia.org/wiki/HSL_and_HSV for details
    else
    {
        switch($max) {
            case $r:
                $h_ = fmod((($g - $b) / $chroma), 6);
                if($h_ < 0) $h_ = (6 - fmod(abs($h_), 6)); // Bugfix: fmod() returns wrong values for negative numbers
                break;
            
            case $g:
                $h_ = ($b - $r) / $chroma + 2;
                break;
            
            case $b:
                $h_ = ($r - $g) / $chroma + 4;
                break;
            default:
                break;
        }
        
        $h = $h_ / 6;
        $s = 1 - abs(2 * $l - 1);
    }
    
    // Return HSL Color as array
    return array($h, $s, $l);
}

function hsl2rgb($hsl) {
    // Fill variables $h, $s, $l by array given.
    list($h, $s, $l) = $hsl;
    
    // If saturation is 0, the given color is grey and only
    // lightness is relevant.
    if ($s == 0 ) {
        $rgb = array($l, $l, $l);
    }
    
    // Else calculate r, g, b according to hue.
    // Check http://en.wikipedia.org/wiki/HSL_and_HSV#From_HSL for details
    else
    {
        $chroma = (1 - abs(2*$l - 1)) * $s;
        $h_     = $h * 6;
        $x         = $chroma * (1 - abs((fmod($h_,2)) - 1)); // Note: fmod because % (modulo) returns int value!!
        $m = $l - round($chroma/2, 10); // Bugfix for strange float behaviour (e.g. $l=0.17 and $s=1)
        
             if($h_ >= 0 && $h_ < 1) $rgb = array(($chroma + $m), ($x + $m), $m);
        else if($h_ >= 1 && $h_ < 2) $rgb = array(($x + $m), ($chroma + $m), $m);
        else if($h_ >= 2 && $h_ < 3) $rgb = array($m, ($chroma + $m), ($x + $m));
        else if($h_ >= 3 && $h_ < 4) $rgb = array($m, ($x + $m), ($chroma + $m));
        else if($h_ >= 4 && $h_ < 5) $rgb = array(($x + $m), $m, ($chroma + $m));
        else if($h_ >= 5 && $h_ < 6) $rgb = array(($chroma + $m), $m, ($x + $m)); 
    }
    
    return $rgb;
}

function rgb2hex($rgb) {
    list($r,$g,$b) = $rgb;
    $r = round(255 * $r);
    $g = round(255 * $g);
    $b = round(255 * $b);
    return "#".sprintf("%02X",$r).sprintf("%02X",$g).sprintf("%02X",$b);
}

function hsl2hex($hsl) {
    $rgb = hsl2rgb($hsl);
    return rgb2hex($rgb);
}


if (isset($_POST['banner560_95']) || isset($_POST['banner350_20']) || isset($_POST['banner160']) || isset($_POST['banner240'])) {
    if (isset($_POST['banner560_95'])) {
        $img_handle = banner560_95($banner_item,$real_time_data, $background_image, $game_image, $logo1_image);
        $directory = $banner_directory.'banner560_95.png';
        imageresolution($img_handle);
        // imagesavealpha ( $img_handle , true );
        ImagePng ($img_handle, $directory);
        echo $directory;
    }
    if (isset($_POST['banner350_20'])) {
        $img_handle = banner350_20($banner_item, $real_time_data, $game_image2, $logo2_image, $_POST['top_color'], $_POST['bottom_color'], $_POST['font_color']);
        $directory = $banner_directory.'banner350_20.png';
        ImagePng ($img_handle, $directory);
        echo $directory;
    }
    if(isset($_POST['banner160'])) {
        $img_handle = banner160($banner_item,$real_time_data, $game_image2, $logo1_image, $flag_image, $map_image, $_POST['height'], $_POST['background_color'], $_POST['font_color1'], $_POST['title_color'], $_POST['game_name_color']);
        $directory = $banner_directory.'banner160.png';
        ImagePng ($img_handle, $directory);
        echo $directory;
    }
    if(isset($_POST['banner240'])) {
        $img_handle = banner240($banner_item, $game_image2, $logo2_image, $flag_image, $map_image, $_POST['html_height'], $_POST['html_width'], $_POST['html_background_color'], $_POST['html_font_color'], $_POST['html_title_background_color'], $_POST['html_title_color'], $_POST['html_border_color'], $_POST['html_link_color'], $_POST['html_border_link_color']);
        $directory = $banner_directory.'banner240.png';
        ImagePng ($img_handle, $directory);
        echo $directory;
    }
    
    exit();
}

function LoadPng($imgname, $width, $height)
{
    $exist = file_exists($imgname);
    /* See if it failed */
    if(!$exist)
    {
        /* Create a grey image */
        $im  = imagecreatetruecolor($width, $height);
        $bgc = imagecolorallocate($im, 60, 69, 76);
        // $tc  = imagecolorallocate($im, 206, 53, 0);

        imagefilledrectangle($im, 0, 0, $width, $height, $bgc);

        /* Output an error message */
        // imagestring($im, 1, 5, 5, 'Error loading ', $tc);
    }else{
        $ext = pathinfo($imgname, PATHINFO_EXTENSION);
        if ($ext == 'png') {
            $im = @imagecreatefrompng($imgname);
        }else{
            $im = @imagecreatefromjpeg($imgname);
        }
        $im = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height]);
    }

    return $im;
}
function banner560_95($banner_item, $real_time_data, $background_image, $game_image, $logo1_image) {
    $img_handle = LoadPng($background_image, 560, 95);
    $first_color = imagecolorallocate($img_handle, 219, 98, 20);
    $second_color = imagecolorallocate($img_handle, 255, 255, 255);
    $third_color = imagecolorallocate($img_handle, 73, 182, 48);
    $fourth_color = imagecolorallocate($img_handle, 255, 0, 0);

    $graph_back_color = imagecolorallocate($img_handle, 19, 20, 20);
    $graph_line_color = imagecolorallocate($img_handle, 252, 190, 35);
    $graph_temp_color = imagecolorallocate($img_handle, 36, 35, 34);
    $graph_axis_color = imagecolorallocate($img_handle, 204, 214, 244);

    

    $font = realpath("font\TrebuchetMS.ttf");
    imagettftext($img_handle, 8, 0, 130, 15, $first_color, $font, 'SEVER NAME:  '.$banner_item['servername']);
    imagettftext($img_handle, 8, 0, 130, 45, $first_color, $font, 'IP ADDRESS:');
    imagettftext($img_handle, 10, 0, 130, 60, $second_color, $font, $banner_item['severip']);
    imagettftext($img_handle, 8, 0, 250, 45, $first_color, $font, 'PORT:');
    imagettftext($img_handle, 10, 0, 250, 60, $second_color, $font, $banner_item['severport']);
    imagettftext($img_handle, 8, 0, 300, 45, $first_color, $font, 'STATUS:');
    if ($banner_item['status']) {
        imagettftext($img_handle, 10, 0, 300, 60, $third_color, $font, 'Online');
    }else{
        imagettftext($img_handle, 10, 0, 300, 60, $fourth_color, $font, 'Offline');
    }
    imagettftext($img_handle, 8, 0, 160, 75, $first_color, $font, 'PLAYERS:');
    imagettftext($img_handle, 10, 0, 160, 87, $second_color, $font, $banner_item['players'].'/100');
    imagettftext($img_handle, 8, 0, 250, 75, $first_color, $font, 'RANK:');
    imagettftext($img_handle, 10, 0, 250, 87, $second_color, $font, $banner_item['rank'].'th');
    imagettftext($img_handle, 8, 0, 300, 75, $first_color, $font, 'CURRENT MAP:');
    imagettftext($img_handle, 10, 0, 300, 87, $second_color, $font, strtoupper($banner_item['map']));
    imagettftext($img_handle, 8, 0, 422, 15, $first_color, $font, '#OF PLAYERS ');
    imagettftext($img_handle, 7, 0, 495, 15, $first_color, $font, '(past 24hours)');

    $src  = @imagecreatefrompng($game_image);
    $size = getimagesize($game_image);
    imagecopy($img_handle, $src, 10, (95 - $size[1])/2, 0, 0, $size[0], $size[1]);
    $src = @imagecreatefrompng($logo1_image);
    $size = getimagesize($logo1_image);
    imagecopy($img_handle, $src, 427, 69, 0, 0, $size[0], $size[1]);

    // circle for player -------
    $circle_image = imagecreatetruecolor(22, 22);
    $black = imagecolorallocate($circle_image, 0, 0, 0);
    $circle_active_color = imagecolorallocate($circle_image, 52, 254, 4);
    $circle_disable_color = imagecolorallocate($circle_image, 4, 102, 4);
    imagecolortransparent ( $circle_image , $black);
    imagefilledarc ( $circle_image , 11 , 11 , 22 , 22 , 0 , 360 , $circle_disable_color , IMG_ARC_PIE );
    imagefilledarc ( $circle_image , 11 , 11 , 22 , 22 , 0 , 90 , $circle_active_color , IMG_ARC_PIE );    
    $rotate = imagerotate ( $circle_image , 90.0 , 0 );
    imagecopy($img_handle, $rotate, 134, 66, 0, 0, 22, 22);

    // ---- graph ------
    // imagesetthickness ( $img_handle , 0 );
    imagefilledrectangle ( $img_handle , 427 , 19 , 558 , 66 , $graph_back_color);
    imageline ( $img_handle , 440 , 25 , 440 , 63 , $graph_axis_color); //vertical line Y
    imageline ( $img_handle , 465 , 60 , 465 , 63 , $graph_axis_color);
    imageline ( $img_handle , 490 , 60 , 490 , 63 , $graph_axis_color);
    imageline ( $img_handle , 515 , 60 , 515 , 63 , $graph_axis_color);
    imageline ( $img_handle , 540 , 60 , 540 , 63 , $graph_axis_color);

    imageline ( $img_handle , 437 , 60 , 552 , 60 , $graph_axis_color); //horizantal line X
    imageline ( $img_handle , 437 , 26 , 440 , 26 , $graph_axis_color); //first rule
    imageline ( $img_handle , 437 , 43 , 440 , 43 , $graph_axis_color); //second rule
    imageline ( $img_handle , 440 , 26 , 552 , 26 , $graph_temp_color);
    imageline ( $img_handle , 440 , 43 , 552 , 43 , $graph_temp_color);
    imagettftext($img_handle, 7, 0, 430, 65, $graph_axis_color, $font, '0'); // 0
    

    if(count($real_time_data) > 0){
        $x_number = 100/count($real_time_data);
        $x1 = null;
        $y1 = null;
        $temp = 0;
        foreach ($real_time_data as $value) {
            if ($value[0] > $temp) {
                $temp = $value[0];
                continue;
            }
        }
        $y_number = 34/$temp;
        $i = 0;
        foreach ($real_time_data as $value) {
            if ($x1===null && $y1===null) {
                $x1 = 440 + $i*$x_number;
                $y1 = 60 - $y_number*$value[0];
            }else{
                $x2 = 440 + $i*$x_number;
                $y2 = 60 - $y_number*$value[0];
                imageline ( $img_handle , $x1 , $y1 , $x2 , $y2 , $graph_line_color);
                $x1 = $x2;
                $y1 = $y2;
            }
            $i++;
        }
        imagettftext($img_handle, 7, 0, 427, 31, $graph_axis_color, $font, $temp); // 2
    }else{
        imagettftext($img_handle, 8, 0, 450, 45, $graph_temp_color, $font, 'NO DATA AVAILABLE');
        imagettftext($img_handle, 7, 0, 427, 48, $graph_axis_color, $font, '10'); // 1
        imagettftext($img_handle, 7, 0, 427, 31, $graph_axis_color, $font, '20'); // 2
    }

    if (count($banner_item) > 0) {
        
    }
    return $img_handle;

}

function banner350_20($banner_item, $real_time_data, $game_image2, $logo2_image, $top_color="#692108", $bottom_color="#381007", $font_color="#ffffff") {
    $font_color = substr($font_color,1,6);
    $img_handle  = imagecreatetruecolor(350, 20);
    $first_color = imagecolorallocate($img_handle, 167, 51, 44);
    $second_color = imagecolorallocate($img_handle, 33, 10, 2);
    $third_color = imagecolorallocate($img_handle, 73, 182, 48);
    $font_color = imagecolorallocate($img_handle, hexdec(substr($font_color,0,2)), hexdec(substr($font_color,2,2)), hexdec(substr($font_color,4,2)));
    $font = realpath("font\TrebuchetMS.ttf");
    image_gradientrect($img_handle, 0,0,350,20,substr($top_color,1,6), substr($bottom_color,1,6));
    $src = @imagecreatefrompng($game_image2);
    imagecopy($img_handle, $src, 2, 2, 0, 0, 16, 16);
    $src = @imagecreatefrompng($logo2_image);
    imagecopy($img_handle, $src, 328, 0, 0, 0, 22, 20);
    imagerectangle ( $img_handle , 24 , 1 , 30 , 11 , $second_color );
    if ($banner_item['status']) {
        imagefilledrectangle ( $img_handle , 25 , 2 , 29 , 10 , $third_color );
    }else{
        imagefilledrectangle ( $img_handle , 25 , 2 , 29 , 10 , $first_color );
    }
    imagettftext($img_handle, 8, 0, 35, 10, $font_color, $font, '['.strtoupper($banner_item['country']).']  '.$banner_item['servername'].'  ['.strtoupper($banner_item['country']).']');
    imagettftext($img_handle, 8, 0, 35, 20, $font_color, $font, $banner_item['severip'].':'.$banner_item['severport']);
    imagettftext($img_handle, 8, 0, 150, 20, $font_color, $font, $banner_item['players']);
    imagettftext($img_handle, 8, 0, 180, 20, $font_color, $font, strtoupper($banner_item['map']));
    $temp = hex2hsl($bottom_color);
    $temp[2] = $temp[2] - 0.08;
    $temp = hsl2hex($temp);
    $temp = substr($temp,1,6);
    $graph_color = imagecolorallocate($img_handle, hexdec(substr($temp,0,2)), hexdec(substr($temp,2,2)), hexdec(substr($temp,4,2)));
    imagefilledrectangle ( $img_handle , 276 , 3 , 326 , 17 , $graph_color );

    if(count($real_time_data) > 0){
        $x_number = 50/count($real_time_data);
        $x1 = null;
        $y1 = null;
        $temp = 0;
        foreach ($real_time_data as $value) {
            if ($value[0] > $temp) {
                $temp = $value[0];
                continue;
            }
        }
        $y_number = 14/$temp;
        $i = 0;
        foreach ($real_time_data as $value) {
            if ($x1===null && $y1===null) {
                $x1 = 276 + $i*$x_number;
                $y1 = 17 - $y_number*$value[0];
            }else{
                $x2 = 276 + $i*$x_number;
                $y2 = 17 - $y_number*$value[0];
                imageline ( $img_handle , $x1 , $y1 , $x2 , $y2 , $font_color);
                $x1 = $x2;
                $y1 = $y2;
            }
            $i++;
        }
        
    }


    return $img_handle;
}

function banner160($banner_item,$real_time_data, $game_image2, $logo1_image, $flag_image, $map_image, $height, $background_color, $font_color, $title_color, $game_name_color) {
    $img_handle  = imagecreatetruecolor(160, $height);
    $background_color = substr($background_color,1,6);
    $font_color = substr($font_color,1,6);
    $title_color = substr($title_color,1,6);
    $game_name_color = substr($game_name_color,1,6);
    $background_color = imagecolorallocate($img_handle, hexdec(substr($background_color,0,2)), hexdec(substr($background_color,2,2)), hexdec(substr($background_color,4,2)));
    $font_color = imagecolorallocate($img_handle, hexdec(substr($font_color,0,2)), hexdec(substr($font_color,2,2)), hexdec(substr($font_color,4,2)));
    $title_color = imagecolorallocate($img_handle, hexdec(substr($title_color,0,2)), hexdec(substr($title_color,2,2)), hexdec(substr($title_color,4,2)));
    $game_name_color = imagecolorallocate($img_handle, hexdec(substr($game_name_color,0,2)), hexdec(substr($game_name_color,2,2)), hexdec(substr($game_name_color,4,2)));
    // $title_background = imagecolorallocate($img_handle, 90, 87, 83);
    $title_background = imagecolorallocatealpha ( $img_handle , 90 , 87 , 83 , 5 );
    $offline_color = imagecolorallocate($img_handle, 167, 51, 44);
    $active_color = imagecolorallocate($img_handle, 73, 182, 48);
    $border_color = imagecolorallocate($img_handle, 255, 255, 255);
    $line_color = imagecolorallocate($img_handle, 255, 153, 0);

    $graph_back_color = imagecolorallocate($img_handle, 20, 20, 20);
    $graph_line_color = imagecolorallocate($img_handle, 252, 190, 35);
    $graph_temp_color = imagecolorallocate($img_handle, 36, 35, 34);
    $graph_axis_color = imagecolorallocate($img_handle, 204, 214, 244);

    $font = realpath("font\TrebuchetMS.ttf");
    imagefilledrectangle($img_handle, 0, 0, 160, $height, $background_color);
    $src = @imagecreatefrompng($game_image2);
    imagecopy($img_handle, $src, 2, 2, 0, 0, 16, 16);
    // $string_image = imagecreatetruecolor(22, $height);
    // imagefilledrectangle($string_image, 0, 0, 22, $height, $background_color);
    // imagestringup( $string_image , 5 , 6 , $height , '['.strtoupper($banner_item['country']).']'.$banner_item['servername'].'['.strtoupper($banner_item['country']).']' , $game_name_color );
    // imageflip($string_image, IMG_FLIP_BOTH);
    // imagecopy($img_handle, $string_image, 2, 18, 0, 0, 22, $height);
    imagettftext($img_handle, 12, -90, 5, 17, $game_name_color, $font, '['.strtoupper($banner_item['country']).'] '.$banner_item['servername'].' ['.strtoupper($banner_item['country']).']');
    ImageRectangleWithRoundedCorners($img_handle, 25, 2, 156, 11, 2, $title_background);
    imagettftext($img_handle, 6, 0, 29, 10, $title_color, $font, 'HOST/PORT');
    if ($banner_item['status']) {
        imagefilledrectangle ( $img_handle , 27 , 15 , 31 , 25 , $active_color );
    }else{
        imagefilledrectangle ( $img_handle , 27 , 15 , 31 , 25 , $offline_color );
    }
    
    imagettftext($img_handle, 8, 0, 35, 25, $font_color, $font, $banner_item['severip'].':'.$banner_item['severport']);
    ImageRectangleWithRoundedCorners($img_handle, 25, 30, 156, 39, 2, $title_background);
    imagettftext($img_handle, 6, 0, 29, 38, $title_color, $font, 'SERVER NAME');
    imagettftext($img_handle, 8, 0, 27, 52, $font_color, $font, '['.strtoupper($banner_item['country']).'] '.$banner_item['servername'].' ['.strtoupper($banner_item['country']).']');
    ImageRectangleWithRoundedCorners($img_handle, 25, 58, 156, 67, 2, $title_background);
    imagettftext($img_handle, 6, 0, 29, 66, $title_color, $font, 'LOC     RANK');
    $src = @imagecreatefromgif($flag_image);
    imagecopy($img_handle, $src, 26, 71, 0, 0, 16, 11);
    imagettftext($img_handle, 8, 0, 53, 80, $font_color, $font, $banner_item['rank'].'th  (2nd pctile)');
    ImageRectangleWithRoundedCorners($img_handle, 25, 85, 156, 94, 2, $title_background);
    imagettftext($img_handle, 6, 0, 29, 93, $title_color, $font, 'PLAYERS');
    imagettftext($img_handle, 8, 0, 29, 107, $title_color, $font, $banner_item['players'].'/100');
    imagefilledrectangle($img_handle, 66, 100, 155, 106, $title_background);
    imagerectangle ( $img_handle , 65 , 99 , 156 , 107 , $border_color );
    imagesetthickness ( $img_handle , 7 );
    imageline( $img_handle , 66 , 103 , 89 , 103 , $line_color );
    ImageRectangleWithRoundedCorners($img_handle, 25, 112, 156, 121, 2, $title_background);
    imagettftext($img_handle, 6, 0, 29, 120, $title_color, $font, 'MAP');
    imagettftext($img_handle, 8, 0, 29, 135, $font_color, $font, strtoupper($banner_item['map']));

    $src = @imagecreatefrompng($logo1_image);
    imagecopy($img_handle, $src, 26, ($height - 26), 0, 0, 132, 26);


    if ($height == 248 || $height == 354) {
        ImageRectangleWithRoundedCorners($img_handle, 25, ($height - 95), 156, ($height - 85), 2, $title_background);
        imagettftext($img_handle, 6, 0, 29, ($height - 87), $title_color, $font, 'PLAYERS       PAST  24H');

        // // ---- graph ------
        imagesetthickness ( $img_handle , 1 );
        imagefilledrectangle ( $img_handle , 26 , ($height - 83) , 157 , ($height - 36) , $graph_back_color);
        imageline($img_handle , 39 , ($height - 77) , 39 , ($height - 39) , $graph_axis_color); //vertical line Y
        imageline($img_handle , 64 , ($height - 42) , 64 , ($height - 39) , $graph_axis_color);
        imageline($img_handle , 89 , ($height - 42) , 89 , ($height - 39) , $graph_axis_color);
        imageline($img_handle , 114 , ($height - 42) , 114 , ($height - 39) , $graph_axis_color);
        imageline($img_handle , 139 , ($height - 42) , 139 , ($height - 39) , $graph_axis_color);

        imageline($img_handle , 36 , ($height - 42) , 151 , ($height - 42) , $graph_axis_color); //horizantal line X
        imageline($img_handle , 36 , ($height - 76) , 39 , ($height - 76) , $graph_axis_color); //first rule
        imageline($img_handle , 36 , ($height - 59) , 39 , ($height - 59) , $graph_axis_color); //second rule
        imageline($img_handle , 39 , ($height - 76) , 152 , ($height - 76) , $graph_temp_color);
        imageline($img_handle , 39 , ($height - 59) , 152 , ($height - 59) , $graph_temp_color);
        imagettftext($img_handle, 7, 0, 29, ($height - 37), $graph_axis_color, $font, '0'); // 0
        


        if(count($real_time_data) > 0){
            $x_number = 100/count($real_time_data);
            $x1 = null;
            $y1 = null;
            $temp = 0;
            foreach ($real_time_data as $value) {
                if ($value[0] > $temp) {
                    $temp = $value[0];
                    continue;
                }
            }
            $y_number = 34/$temp;
            $i = 0;
            foreach ($real_time_data as $value) {
                if ($x1===null && $y1===null) {
                    $x1 = 40 + $i*$x_number;
                    $y1 = ($height - 41) - $y_number*$value[0];
                }else{
                    $x2 = 40 + $i*$x_number;
                    $y2 = ($height - 41) - $y_number*$value[0];
                    imageline ( $img_handle , $x1 , $y1 , $x2 , $y2 , $graph_line_color);
                    $x1 = $x2;
                    $y1 = $y2;
                }
                $i++;
            }
            imagettftext($img_handle, 7, 0, 26, ($height - 71), $graph_axis_color, $font, $temp); // 2
        }else{
            // imagettftext($img_handle, 8, 0, 450, 45, $graph_temp_color, $font, 'NO DATA AVAILABLE');
            imagettftext($img_handle, 7, 0, 26, ($height - 54), $graph_axis_color, $font, '10'); // 1
            imagettftext($img_handle, 7, 0, 26, ($height - 71), $graph_axis_color, $font, '20'); // 2
        }

    }
    if ($height == 354 || $height == 288) {
        $src = @imagecreatefromjpeg($map_image);
        imagecopy($img_handle, $src, 25,142 , 0, 0, 130, 110);
    }
    return $img_handle;
}

function banner240($banner_item, $game_image2, $logo2_image, $flag_image, $map_image,$height, $width, $html_background_color, $html_font_color, $html_title_background_color, $html_title_color, $html_border_color, $html_link_color, $html_border_link_color) {
    $img_handle  = imagecreatetruecolor($width, $height);
    $background_color = substr($html_background_color,1,6);
    $font_color = substr($html_font_color,1,6);
    $title_background_color = substr($html_title_background_color,1,6);
    $title_color = substr($html_title_color,1,6);
    $border_color = substr($html_border_color,1,6);
    $link_color = substr($html_link_color,1,6);
    $border_link_color = substr($html_border_link_color,1,6);
    $background_color = imagecolorallocate($img_handle, hexdec(substr($background_color,0,2)), hexdec(substr($background_color,2,2)), hexdec(substr($background_color,4,2)));
    $font_color = imagecolorallocate($img_handle, hexdec(substr($font_color,0,2)), hexdec(substr($font_color,2,2)), hexdec(substr($font_color,4,2)));
    $title_background_color = imagecolorallocate($img_handle, hexdec(substr($title_background_color,0,2)), hexdec(substr($title_background_color,2,2)), hexdec(substr($title_background_color,4,2)));
    $title_color = imagecolorallocate($img_handle, hexdec(substr($title_color,0,2)), hexdec(substr($title_color,2,2)), hexdec(substr($title_color,4,2)));
    $border_color = imagecolorallocate($img_handle, hexdec(substr($border_color,0,2)), hexdec(substr($border_color,2,2)), hexdec(substr($border_color,4,2)));
    $link_color = imagecolorallocate($img_handle, hexdec(substr($link_color,0,2)), hexdec(substr($link_color,2,2)), hexdec(substr($link_color,4,2)));
    $border_link_color = imagecolorallocate($img_handle, hexdec(substr($border_link_color,0,2)), hexdec(substr($border_link_color,2,2)), hexdec(substr($border_link_color,4,2)));
    $offline_color = imagecolorallocate($img_handle, 167, 51, 44);
    $active_color = imagecolorallocate($img_handle, 73, 182, 48);
    $font = realpath("font\TrebuchetMS.ttf");
    imagefilledrectangle($img_handle, 0, 0, $width, $height, $background_color);
    imagefilledrectangle($img_handle, 0, 0, $width, 20, $border_color);
    $src = @imagecreatefromgif($flag_image);
    imagecopy($img_handle, $src, 2, 5, 0, 0, 16, 11);
    $src = @imagecreatefrompng($game_image2);
    imagecopy($img_handle, $src, ($width - 20), 2, 0, 0, 16, 16);
    imagettftext($img_handle, 8, 0, ($width - 60), 14, $border_link_color, $font, $banner_item['game']);
    imagerectangle ( $img_handle , 0 , 21 , ($width - 1) , ($height - 1) , $border_color );
    imagerectangle ( $img_handle , 2 , 24 , ($width - 3) , 45 , $border_color );
    imagefilledrectangle($img_handle, 3, 25, ($width - 4), 44, $title_background_color);
    imagettftext($img_handle, 8, 0, 5, 38, $title_color, $font, '['.strtoupper($banner_item['country']).']  '.$banner_item['servername'].'  ['.strtoupper($banner_item['country']).']');
    imagettftext($img_handle, 8, 0, 4, 58, $font_color, $font, 'IP');
    imagettftext($img_handle, 8, 0, ($width - 110), 58, $link_color, $font, $banner_item['severip'].':'.$banner_item['severport']);
    if ($banner_item['status']) {
        ImageRectangleWithRoundedCorners($img_handle, ($width - 123), 50, ($width - 115), 58, 4, $active_color);
    }else{
        ImageRectangleWithRoundedCorners($img_handle, ($width - 123), 50, ($width - 115), 58, 4, $offline_color);
    }
    
    imagettftext($img_handle, 8, 0, 4, 75, $font_color, $font, 'Players');
    imagettftext($img_handle, 8, 0, ($width - 22), 75, $font_color, $font, '0/0');
    imagettftext($img_handle, 8, 0, 4, 92, $font_color, $font, 'Rank');
    imagettftext($img_handle, 8, 0, ($width - 22), 92, $font_color, $font, $banner_item['rank']);
    imagettftext($img_handle, 8, 0, 4, 109, $font_color, $font, 'Map');
    imagettftext($img_handle, 8, 0, ($width - 42), 109, $font_color, $font, strtoupper($banner_item['map']));
    if ($height > 280) {
        $src = @imagecreatefromjpeg($map_image);
        imagecopy($img_handle, $src, ($width - 160)/2, 120, 0, 0, 160, 120);
    }
    imagefilledrectangle($img_handle, 0, ($height - 20), $width, $height , $border_color);
    $src = @imagecreatefrompng($logo2_image);
    imagecopy($img_handle, $src, ($width - 22), ($height - 20), 0, 0, 22, 20);
    imagettftext($img_handle, 8, 0, ($width - 115), ($height - 7), $border_link_color, $font, 'GameTracker.com');
    return $img_handle;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners</title>
    <style>
        body {
            background-color: black;
            background-repeat: no-repeat;
            background-position: top center;
            font-family: "Trebuchet MS", Trebuchet, Tahoma, Arial, Helvetica, sans-serif;
        }
        body, textarea, select, input[type=text], input[type=date], input[type=email], input[type=password] {
            color: #f0f0f0;
        }
        .item_display_none {
            display: none;
        }
        .item_float_left {
            float: left;
        }
        .left_back {
            background-image: url('https://www.gametracker.com/images/global/body_background_3_left.jpg');
            position: fixed;
            top: 0px;
            left: 50%;
            margin-left: -800px;
            width: 303px;
            height: 1600px;
            background-repeat: no-repeat;
        }
        .right_back {
            background-image: url('https://www.gametracker.com/images/global/body_background_3_right.jpg');
            position: fixed;
            top: 0px;
            right: 50%;
            margin-right: -800px;
            width: 303px;
            height: 1600px;
            background-repeat: no-repeat;
        }
        .page_content {
            width: 980px;
            margin: 0px auto;
            position: relative;
            padding-top: 50px;
            min-height: 650px;
            height: auto;
        }
        div.blocknew805 {
            width: 959px;
            padding: 9px;
        }
        .item_560x95 {
            width: 560px;
            height: 95px;
        }
        div.blocknew {
            position: relative;
            margin-bottom: 10px;
            border-radius: 7px;
            border-width: 1px;
            border-style: solid;
            background-repeat: repeat-x;
            background-position: 0px -1px;
            background-image: url('https://www.gametracker.com/images/global/block/bgt_ffffff33_tm.png');
        }
        span.blocknewtabon, span.blocknewtaboff, div.blocknew {
            border-color: #343434;
            background-color: #1a1a1a;
        }
        div.blocknewhdr {
            border-bottom-width: 1px;
            border-bottom-style: solid;
            height: 18px;
            line-height: 18px;
            font-size: 18px;
            padding-bottom: 9px;
            margin: 0px 0px 9px 0px;
            color: #C0B08B;
            text-shadow: 2px 2px 0px #000000;
            border-bottom-color: #343434;
        }
        .item_float_clear {
            clear: both;
            height: 0px;
            font-size: 0px;
            line-height: 0px;
        }
        table.table_frm_ban {
            width: 1px;
            table-layout: fixed;
        }
        table.table_frm {
            border-width: 1px;
            border-style: solid;
            border-spacing: 0px 0px;
            border-collapse: separate;
            padding: 0px;
            margin: 0px;
            width: 100%;
            font-size: 12px;
            line-height: 20px;
        }
        table.table_lst, table.table_frm {
            border-color: #343434;
        }
        table.table_frm tr:first-child td {
            border-top-width: 1px;
        }
        table.table_frm_ban td:nth-child(1) {
            width: 130px;
            padding: 3px 6px;
        }
        table.table_frm_ban td:nth-child(2) {
            width: 360px;
            padding: 3px 6px;
        }
        table.table_frm td:first-child {
            border-left-width: 1px;
        }
        table.table_frm td.col_h {
            font-weight: bold;
            color: #DCCB7B;
        }
        table.table_lst td.col_h, table.table_frm td.col_h {
            background-color: #101010;
        }
        table.table_frm td {
            border-right-width: 1px;
            border-width: 0px 0px 1px 0px;
            border-style: solid;
            overflow: hidden;
            border-color: #000000;
        }
        input[type=text], input[type=email], input[type=date] {
            border-width: 1px;
            border-style: solid;
            font-size: 11px;
            padding: 0px 3px 0px 3px;
            height: 17px;
            line-height: 17px;
        }
        textarea, select, input[type=text], input[type=date], input[type=email], input[type=password] {
            border-color: #444444;
            background-color: #000000;
        }
        .item_w40 {
            width: 40px;
        }
        select {
            border-width: 1px;
            border-style: solid;
            font-size: 11px;
            padding: 0px 0px 0px 3px;
            height: 19px;
            border-color: #444444;
            background-color: #000000;
            color: #f0f0f0;
        }
        span.blocknewtabon {
            z-index: 100;
            position: relative;
            bottom: -1px;
            display: inline-block;
            border-radius: 7px 7px 0px 0px;
            padding: 4px 8px;
            border-width: 1px;
            border-style: solid;
        }
        span.blocknewtabon {
            border-bottom-color: transparent;
        }
        span.blocknewtabon, span.blocknewtaboff, div.blocknew {
            border-color: #343434;
            background-color: #1a1a1a;
        }
        span.blocknewtaboff {
            z-index: 100;
            position: relative;
            bottom: -1px;
            display: inline-block;
            border-radius: 7px 7px 0px 0px;
            padding: 4px 8px;
            border-width: 1px;
            border-style: solid;
            cursor: pointer;
            color: #FF9900;
        }
        div.blocknewtabcontent {
            border-radius: 0px 0px 7px 7px;
            background-image: none;
        }
        div.blocknew498 {
            width: 478px;
            padding: 9px;
        }
        textarea.textarea_500x60 {
            width: 467px;
            height: 76px;
        }
        textarea {
            margin: 1px;
            padding: 2px 3px 2px 3px;
            border-width: 1px;
            border-style: solid;
            font-size: 12px;
            border-color: #444444;
            background-color: #000000;
            color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="left_back"></div>
    <div class="right_back"></div>
    <div class="page_content" base_url="<?php echo $base_url; ?>" server_url="<?php echo $server_url; ?>" ip="<?php echo $banner_item['severip']; ?>" port="<?php echo $banner_item['severport']; ?>">
        <h2 style="text-align:center;color: #C0B08B;">Game Server Banners</h2>
        <div class="blocknew blocknew805">
            <div class="blocknewhdr">
                560×95 SERVER IMAGE BANNERS
            </div>
            <div class="item_float_left">
                <div id="serverBanner560x95Preview" class="item_560x95">
                    <img id="serverBanner560x95image" class="item_560x95" src="" alt="Loading">
                </div>
                <div class="item_h05">
                </div>
            </div>

            <div class="item_float_left">                
                <div class="item_h15">
                </div>
                <span class="blocknewtabon" onclick="processTabClick(this);">
                    WebSite/Blog
                </span>
                <span class="blocknewtaboff" onclick="processTabClick(this);">
                    Forum
                </span>
                <div class="blocknew blocknew498 blocknewtabcontent">
                    <textarea class="textarea_500x60" id="serverBanner560x95WebCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
                <div class="blocknew blocknew498 blocknewtabcontent item_display_none">
                    <textarea class="textarea_500x60" id="serverBanner560x95ForumCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
            </div>
            
            <div class="item_float_clear">
            </div>
        </div>

        <div class="blocknew blocknew805">
            <div class="blocknewhdr">
                350×20 SERVER IMAGE BANNERS
            </div>
            <div class="item_float_left">
                <img id="serverBanner350x20Preview" src="" alt="Loading">
                <div class="item_h05">
                </div>
            </div>
            <div class="item_float_left">
                <table class="table_frm table_frm_ban">
                    <tbody>
                        <tr>
                            <td class="col_h">
                                Pick a Theme
                            </td>
                            <td>
                                <select name="serverBanner350x20Theme" onchange="serverBanner350x20ShowCode(this);" onkeyup="if(event.keyCode == 9) return; serverBanner350x20ShowCode(this);">
                                    <option value="gt_red">GameTracker Red</option>
                                    <option value="gt_orange">GameTracker Orange</option>
                                    <option value="gt_green">GameTracker Green</option>
                                    <option value="gs">GameServers</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Top Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner350x20Color1" value="#692108" id="serverBanner350x20Color1" placeholder="" size="7" maxlength="7" oninput="serverBanner350x20ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Font Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner350x20Color2" value="#ffffff" id="serverBanner350x20Color2" placeholder="" size="7" maxlength="7" oninput="serverBanner350x20ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Bottom Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner350x20Color3" value="#381007" id="serverBanner350x20Color3" placeholder="" size="7" maxlength="7" oninput="serverBanner350x20ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Border Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner350x20Color4" value="#000000" id="serverBanner350x20Color4" placeholder="" size="7" maxlength="7" oninput="serverBanner350x20ShowCode();">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="item_h15">
                </div>
                <span class="blocknewtabon" onclick="processTabClick(this);">
                    WebSite/Blog
                </span>
                <span class="blocknewtaboff" onclick="processTabClick(this);">
                    Forum
                </span>
                <div class="blocknew blocknew498 blocknewtabcontent">
                    <textarea class="textarea_500x60" id="serverBanner350x20WebCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
                <div class="blocknew blocknew498 blocknewtabcontent item_display_none">
                    <textarea class="textarea_500x60" id="serverBanner350x20ForumCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
            </div>
            <div class="item_float_clear">
            </div>
        </div>

        <div class="blocknew blocknew805">
            <div class="blocknewhdr">
                160-PIXEL WIDE ARMA 3 SERVER IMAGE BANNERS
            </div>
            <div class="item_float_left">
                <img id="serverBanner160Preview" src="" alt="Loading">
                <div class="item_h15">
                </div>
            </div>
            <div class="item_float_left">
                &nbsp;&nbsp;&nbsp;&nbsp;
            </div>
            <div class="item_float_left" style="width: 600px;">
                <table class="table_frm table_frm_ban">
                    <tbody>
                        <tr>
                            <td class="col_h">
                                Pick a Theme
                            </td>
                            <td>
                                                                
                                <select name="serverBanner160Theme" onchange="serverBanner160x400ShowCode(this);" onkeyup="if(event.keyCode == 9) return; serverBanner160x400ShowCode(this);">
                                    <option value="1">Gray</option>
                                    <option value="2">Red</option>
                                    <option value="0">Custom</option>
                                </select>
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Background Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner160Color1" value="#000000" id="serverBanner160Color1" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBanner160x400ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Font Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner160Color2" value="#ffffff" id="serverBanner160Color2" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBanner160x400ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Title Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner160Color3" value="#c5c5c5" id="serverBanner160Color3" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBanner160x400ShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Game Name Color
                            </td>
                            <td>
                                <input type="color" name="serverBanner160Color4" value="#ffffff" id="serverBanner160Color4" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBanner160x400ShowCode();">
                            </td>
                        </tr>
                        <tr>
                            <td class="col_h">
                                Show player graph
                            </td>
                            <td>
                                <input type="checkbox" id="serverBanner160ShowGraph" checked="checked" value="y" onclick="javascript:serverBanner160x400ShowCode()">
                            </td>
                        </tr>
                        <!-- <tr>
                            <td class="col_h">
                                Show top players
                            </td>
                            <td>
                                <input type="checkbox" id="serverBanner160ShowTopPlayers" onclick="javascript:serverBanner160x400ShowCode()">
                            </td>
                        </tr> -->
                        <tr>
                            <td class="col_h">
                                Show map screenshot
                            </td>
                            <td>
                                <input type="checkbox" id="serverBanner160ShowMap" onclick="javascript:serverBanner160x400ShowCode()">
                            </td>
                        </tr>
                        <input type="hidden" id="serverBanner160ShowMapTitle" value="yes">
                    </tbody>
                </table>
                <div class="item_h15">
                </div>
                <span class="blocknewtabon" onclick="processTabClick(this);">
                    WebSite/Blog
                </span>
                <span class="blocknewtaboff" onclick="processTabClick(this);">
                    Forum
                </span>
                <div class="blocknew blocknew498 blocknewtabcontent">
                    <textarea class="textarea_500x60" id="serverBanner160WebCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
                <div class="blocknew blocknew498 blocknewtabcontent item_display_none">
                    <textarea class="textarea_500x60" id="serverBanner160ForumCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
            </div>
            <div class="item_float_clear">
            </div>
        </div>

        <div class="blocknew blocknew805">
            <div class="blocknewhdr">
                VERTICAL ARMA 3 SERVER HTML BANNERS
            </div>
            <div class="item_float_left">
                <div id="serverBannerHTMLPreview">
                    <img src="" alt="Loading" id="serverBanner240Preview">
                </div>
                <div class="item_h05">
                </div>
            </div>
            <div class="item_float_left">
                &nbsp;&nbsp;&nbsp;&nbsp;
            </div>
            <div class="item_float_left" style="width:600px;">
                <table class="table_frm table_frm_ban">
                    <tbody>
                        <tr>
                            <td class="col_h">
                                Pick a Theme
                            </td>
                            <td>
                                <input type="hidden" id="serverBannerHTMLLink" value="//cache.gametracker.com/components/html0/?host=179.191.208.85:2302">
                                
                                <select name="serverBannerHTMLTheme" onchange="serverBannerHTMLShowCode(this);" onkeyup="if(event.keyCode == 9) return; serverBannerHTMLShowCode(this);">
                                    <option value="1">Gray</option>
                                    <option value="2">Blue</option>
                                    <option value="3">Orange</option>
                                    <option value="4">White</option>
                                    <option value="5">Camo</option>
                                    <option value="0">Custom</option>
                                </select>
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Background Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor1" value="#333333" id="serverBannerHTMLColor1" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Font Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor2" value="#CCCCCC" id="serverBannerHTMLColor2" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Title Background Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor3" value="#222222" id="serverBannerHTMLColor3" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Title Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor4" value="#FF9900" id="serverBannerHTMLColor4" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Border Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor5" value="#555555" id="serverBannerHTMLColor5" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Link Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor6" value="#FFCC00" id="serverBannerHTMLColor6" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr class="color_input" style="display: none;">
                            <td class="col_h">
                                Border Link Color
                            </td>
                            <td>
                                <input type="color" name="serverBannerHTMLColor7" value="#222222" id="serverBannerHTMLColor7" placeholder="#HHHHHH" size="7" maxlength="7" oninput="serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <tr>
                            <td class="col_h">
                                Width (pixels)
                            </td>
                            <td>
                                <input type="text" class="item_w40" name="serverBannerHTMLWidth" value="240" id="serverBannerHTMLWidth" onchange="javascript:serverBannerHTMLShowCode();">&nbsp;(Minimum=144)
                            </td>
                        </tr>
                        <tr>
                            <td class="col_h">
                                Show map screenshot
                            </td>
                            <td>
                                <input type="checkbox" id="serverBannerHTMLShowMap" value="y" checked="checked" onclick="javascript:serverBannerHTMLShowCode();">
                            </td>
                        </tr>
                        <!-- <tr>
                            <td class="col_h">
                                Show online players
                            </td>
                            <td>
                                <input type="checkbox" id="serverBannerHTMLShowCurrPlayers" value="y" checked="checked" onclick="javascript:serverBannerHTMLShowCode();">
                                Height (pixels):
                                <input type="text" class="item_w40" name="serverBannerHTMLCurrPlayersHeight" value="100" id="serverBannerHTMLCurrPlayersHeight" onchange="javascript:serverBannerHTMLShowCode();">&nbsp;(Minimum=100)
                            </td>
                        </tr> -->
                                        
                    </tbody>
                </table>
                <div class="item_h15">
                </div>
                <span class="blocknewtabon">
                    WebSite/Blog
                </span>
                <div class="blocknew blocknew498 blocknewtabcontent">
                    <textarea class="textarea_500x60" id="serverBannerHTMLWebCode" onfocus="javascript:this.select()" readonly="readonly"></textarea>
                </div>
            </div>
            <div class="item_float_clear">
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            var ip = $('.page_content').attr('ip');
            var port = $('.page_content').attr('port');
            var baseurl = $('.page_content').attr('base_url');
            var serverurl = $('.page_content').attr('server_url');
            var myserver = serverurl + ip + ':' + port + '/';
            
            $.ajax({
                url: '/',
                type: 'post',
                data: {banner560_95:'a'},
                success: function(data) {
                    $('#serverBanner560x95image').attr('src', data);
                    $('#serverBanner560x95WebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    $('#serverBanner560x95ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
            let top_color = $('#serverBanner350x20Color1').val()
            let bottom_color = $('#serverBanner350x20Color3').val()
            let font_color = $('#serverBanner350x20Color2').val()
            
            $.ajax({
                url: '/',
                type: 'post',
                data: {banner350_20:'a',top_color: top_color, bottom_color: bottom_color, font_color: font_color},
                success: function(data) {
                    $('#serverBanner350x20Preview').attr('src', data);
                    $('#serverBanner350x20WebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    $('#serverBanner350x20ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
            let background_color = $('#serverBanner160Color1').val()
            let font_color1 = $('#serverBanner160Color2').val()
            let title_color = $('#serverBanner160Color3').val()
            let game_name_color = $('#serverBanner160Color4').val()

            $.ajax({
                url: '/',
                type: 'post',
                data: {banner160:'a',height: 248, background_color: background_color, font_color1: font_color1, title_color: title_color, game_name_color: game_name_color},
                success: function(data) {
                    $('#serverBanner160Preview').attr('src', data);
                    $('#serverBanner160WebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    $('#serverBanner160ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })

            let html_background_color = $('#serverBannerHTMLColor1').val()
            let html_font_color = $('#serverBannerHTMLColor2').val()
            let html_title_background_color = $('#serverBannerHTMLColor3').val()
            let html_title_color = $('#serverBannerHTMLColor4').val()
            let html_border_color = $('#serverBannerHTMLColor5').val()
            let html_link_color = $('#serverBannerHTMLColor6').val()
            let html_border_link_color = $('#serverBannerHTMLColor7').val()
            let html_width = $('#serverBannerHTMLWidth').val()
            let html_height = 288

            $.ajax({
                url: '/',
                type: 'post',
                data: {banner240:'a',html_height: html_height, html_width: html_width, html_background_color: html_background_color, html_font_color: html_font_color, html_title_background_color: html_title_background_color, html_title_color: html_title_color, html_border_color: html_border_color, html_link_color: html_link_color, html_border_link_color: html_border_link_color},
                success: function(data) {
                    $('#serverBanner240Preview').attr('src', data);
                    $('#serverBannerHTMLWebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    // $('#serverBanner160ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
        })
        function serverBannerHTMLShowCode()
        {
            let map_flag = document.getElementById('serverBannerHTMLShowMap').checked
            let html_height = 288
            if($('select[name=serverBannerHTMLTheme]').length <=0)
                return;
            var theme = $('select[name=serverBannerHTMLTheme]').val();
            if(theme == '1')
            {
                $('#serverBannerHTMLColor1').val('#333333');
                $('#serverBannerHTMLColor2').val('#CCCCCC');
                $('#serverBannerHTMLColor3').val('#222222');
                $('#serverBannerHTMLColor4').val('#FF9900');
                $('#serverBannerHTMLColor5').val('#555555');
                $('#serverBannerHTMLColor6').val('#FFCC00');
                $('#serverBannerHTMLColor7').val('#222222');
                $('#serverBannerHTMLColor1').closest('tr').hide();
                $('#serverBannerHTMLColor2').closest('tr').hide();
                $('#serverBannerHTMLColor3').closest('tr').hide();
                $('#serverBannerHTMLColor4').closest('tr').hide();
                $('#serverBannerHTMLColor5').closest('tr').hide();
                $('#serverBannerHTMLColor6').closest('tr').hide();
                $('#serverBannerHTMLColor7').closest('tr').hide();
            }
            else if(theme == '2')
            {
                $('#serverBannerHTMLColor1').val('#1F2642');
                $('#serverBannerHTMLColor2').val('#8790AE');
                $('#serverBannerHTMLColor3').val('#11172D');
                $('#serverBannerHTMLColor4').val('#FFFFFF');
                $('#serverBannerHTMLColor5').val('#333333');
                $('#serverBannerHTMLColor6').val('#FF9900');
                $('#serverBannerHTMLColor7').val('#999999');
                $('#serverBannerHTMLColor1').closest('tr').hide();
                $('#serverBannerHTMLColor2').closest('tr').hide();
                $('#serverBannerHTMLColor3').closest('tr').hide();
                $('#serverBannerHTMLColor4').closest('tr').hide();
                $('#serverBannerHTMLColor5').closest('tr').hide();
                $('#serverBannerHTMLColor6').closest('tr').hide();
                $('#serverBannerHTMLColor7').closest('tr').hide();
            }
            else if(theme == '3')
            {
                $('#serverBannerHTMLColor1').val('#FF9900');
                $('#serverBannerHTMLColor2').val('#000000');
                $('#serverBannerHTMLColor3').val('#FF7700');
                $('#serverBannerHTMLColor4').val('#000000');
                $('#serverBannerHTMLColor5').val('#000000');
                $('#serverBannerHTMLColor6').val('#06126A');
                $('#serverBannerHTMLColor7').val('#FF7700');
                $('#serverBannerHTMLColor1').closest('tr').hide();
                $('#serverBannerHTMLColor2').closest('tr').hide();
                $('#serverBannerHTMLColor3').closest('tr').hide();
                $('#serverBannerHTMLColor4').closest('tr').hide();
                $('#serverBannerHTMLColor5').closest('tr').hide();
                $('#serverBannerHTMLColor6').closest('tr').hide();
                $('#serverBannerHTMLColor7').closest('tr').hide();
            }
            else if(theme == '4')
            {
                $('#serverBannerHTMLColor1').val('#FFFFFF');
                $('#serverBannerHTMLColor2').val('#333333');
                $('#serverBannerHTMLColor3').val('#FFFFFF');
                $('#serverBannerHTMLColor4').val('#000000');
                $('#serverBannerHTMLColor5').val('#BBBBBB');
                $('#serverBannerHTMLColor6').val('#091858');
                $('#serverBannerHTMLColor7').val('#5C5C5C');
                $('#serverBannerHTMLColor1').closest('tr').hide();
                $('#serverBannerHTMLColor2').closest('tr').hide();
                $('#serverBannerHTMLColor3').closest('tr').hide();
                $('#serverBannerHTMLColor4').closest('tr').hide();
                $('#serverBannerHTMLColor5').closest('tr').hide();
                $('#serverBannerHTMLColor6').closest('tr').hide();
                $('#serverBannerHTMLColor7').closest('tr').hide();
            }
            else if(theme == '5')
            {
                $('#serverBannerHTMLColor1').val('#373E28');
                $('#serverBannerHTMLColor2').val('#D2E1B5');
                $('#serverBannerHTMLColor3').val('#2E3225');
                $('#serverBannerHTMLColor4').val('#FFFFFF');
                $('#serverBannerHTMLColor5').val('#3E4433');
                $('#serverBannerHTMLColor6').val('#889C63');
                $('#serverBannerHTMLColor7').val('#828E6B');
                $('#serverBannerHTMLColor1').closest('tr').hide();
                $('#serverBannerHTMLColor2').closest('tr').hide();
                $('#serverBannerHTMLColor3').closest('tr').hide();
                $('#serverBannerHTMLColor4').closest('tr').hide();
                $('#serverBannerHTMLColor5').closest('tr').hide();
                $('#serverBannerHTMLColor6').closest('tr').hide();
                $('#serverBannerHTMLColor7').closest('tr').hide();
            }
            else
            {
                $('#serverBannerHTMLColor1').closest('tr').show();
                $('#serverBannerHTMLColor2').closest('tr').show();
                $('#serverBannerHTMLColor3').closest('tr').show();
                $('#serverBannerHTMLColor4').closest('tr').show();
                $('#serverBannerHTMLColor5').closest('tr').show();
                $('#serverBannerHTMLColor6').closest('tr').show();
                $('#serverBannerHTMLColor7').closest('tr').show();
            }

            if (map_flag) {
                html_height = 288
            }else{
                html_height = 164
            }

            let ip = $('.page_content').attr('ip');
            let port = $('.page_content').attr('port');
            let baseurl = $('.page_content').attr('base_url');
            let serverurl = $('.page_content').attr('server_url');
            let myserver = serverurl + ip + ':' + port + '/';

            let html_background_color = $('#serverBannerHTMLColor1').val()
            let html_font_color = $('#serverBannerHTMLColor2').val()
            let html_title_background_color = $('#serverBannerHTMLColor3').val()
            let html_title_color = $('#serverBannerHTMLColor4').val()
            let html_border_color = $('#serverBannerHTMLColor5').val()
            let html_link_color = $('#serverBannerHTMLColor6').val()
            let html_border_link_color = $('#serverBannerHTMLColor7').val()
            let html_width = $('#serverBannerHTMLWidth').val()
            

            $.ajax({
                url: '/',
                type: 'post',
                data: {banner240:'a',html_height: html_height, html_width: html_width, html_background_color: html_background_color, html_font_color: html_font_color, html_title_background_color: html_title_background_color, html_title_color: html_title_color, html_border_color: html_border_color, html_link_color: html_link_color, html_border_link_color: html_border_link_color},
                success: function(data) {
                    $('#serverBanner240Preview').attr('src', data + '?dt=' + (new Date().getMilliseconds()));
                    $('#serverBannerHTMLWebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    // $('#serverBanner160ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
            
        }

        function serverBanner350x20ShowCode(element)
        {
            if($('select[name=serverBanner350x20Theme]').length <= 0)
                return;
            var theme = $('select[name=serverBanner350x20Theme]').val();
            if(theme == 'gt_red')
            {
                $('#serverBanner350x20Color1').val('#692108');
                $('#serverBanner350x20Color3').val('#381007');
                $('#serverBanner350x20Color2').val('#FFFFFF');
                $('#serverBanner350x20Color4').val('#000000');
                $('#serverBanner350x20Color1').closest('tr').hide();
                $('#serverBanner350x20Color2').closest('tr').hide();
                $('#serverBanner350x20Color3').closest('tr').hide();
                $('#serverBanner350x20Color4').closest('tr').hide();
            }
            else if(theme == 'gt_orange')
            {
                $('#serverBanner350x20Color1').val('#FFAD41');
                $('#serverBanner350x20Color3').val('#E98100');
                $('#serverBanner350x20Color2').val('#000000');
                $('#serverBanner350x20Color4').val('#591F11');
                $('#serverBanner350x20Color1').closest('tr').hide();
                $('#serverBanner350x20Color2').closest('tr').hide();
                $('#serverBanner350x20Color3').closest('tr').hide();
                $('#serverBanner350x20Color4').closest('tr').hide();
            }
            else if(theme == 'gt_green')
            {
                $('#serverBanner350x20Color1').val('#5A6C3E');
                $('#serverBanner350x20Color3').val('#383F2D');
                $('#serverBanner350x20Color2').val('#D2E1B5');
                $('#serverBanner350x20Color4').val('#2E3226');
                $('#serverBanner350x20Color1').closest('tr').hide();
                $('#serverBanner350x20Color2').closest('tr').hide();
                $('#serverBanner350x20Color3').closest('tr').hide();
                $('#serverBanner350x20Color4').closest('tr').hide();
            }
            else if(theme == 'gs')
            {
                $('#serverBanner350x20Color1').val('#323957');
                $('#serverBanner350x20Color3').val('#202743');
                $('#serverBanner350x20Color2').val('#F19A15');
                $('#serverBanner350x20Color4').val('#111111');
                $('#serverBanner350x20Color1').closest('tr').hide();
                $('#serverBanner350x20Color2').closest('tr').hide();
                $('#serverBanner350x20Color3').closest('tr').hide();
                $('#serverBanner350x20Color4').closest('tr').hide();
            }
            else
            {
                $('#serverBanner350x20Color1').closest('tr').show();
                $('#serverBanner350x20Color2').closest('tr').show();
                $('#serverBanner350x20Color3').closest('tr').show();
                $('#serverBanner350x20Color4').closest('tr').show();
            }
            let ip = $('.page_content').attr('ip');
            let port = $('.page_content').attr('port');
            let baseurl = $('.page_content').attr('base_url');
            let serverurl = $('.page_content').attr('server_url');
            let myserver = serverurl + ip + ':' + port + '/';
            console.log($('#serverBanner350x20Color2').val())
            let top_color = $('#serverBanner350x20Color1').val()
            let bottom_color = $('#serverBanner350x20Color3').val()
            let font_color = $('#serverBanner350x20Color2').val()
            $.ajax({
                url: '/',
                type: 'post',
                data: {banner350_20:'a',top_color: top_color, bottom_color: bottom_color, font_color: font_color},
                success: function(data) {
                    $('#serverBanner350x20Preview').attr('src', '');
                    $('#serverBanner350x20Preview').attr('src', data + '?dt=' + (new Date().getMilliseconds()));
                    $('#serverBanner350x20WebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    $('#serverBanner350x20ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
        }

        function serverBanner160x400ShowCode()
        {
            let map_flag = document.getElementById('serverBanner160ShowMap').checked
            let graph_flag = document.getElementById('serverBanner160ShowGraph').checked
            let imageHeight = 0;
            // let map_flag = $('#serverBanner160ShowMap').prop();
            // let graph_flag;
            if($('select[name=serverBanner160Theme]').length <=0)
                return;
            var theme = $('select[name=serverBanner160Theme]').val();
            if(theme == '0')
            {
                $('#serverBanner160Color1').closest('tr').show();
                $('#serverBanner160Color2').closest('tr').show();
                $('#serverBanner160Color3').closest('tr').show();
                $('#serverBanner160Color4').closest('tr').show();
            }
            else if(theme == '1')
            {
                $('#serverBanner160Color1').val('#000000');
                $('#serverBanner160Color2').val('#ffffff');
                $('#serverBanner160Color3').val('#c5c5c5');
                $('#serverBanner160Color4').val('#ffffff');
                $('#serverBanner160Color1').closest('tr').hide();
                $('#serverBanner160Color2').closest('tr').hide();
                $('#serverBanner160Color3').closest('tr').hide();
                $('#serverBanner160Color4').closest('tr').hide();
            }
            else// if(theme == '1')
            {
                $('#serverBanner160Color1').val('#000000');
                $('#serverBanner160Color2').val('#ffffff');
                $('#serverBanner160Color3').val('#c5c5c5');
                $('#serverBanner160Color4').val('#ff9900');
                $('#serverBanner160Color1').closest('tr').hide();
                $('#serverBanner160Color2').closest('tr').hide();
                $('#serverBanner160Color3').closest('tr').hide();
                $('#serverBanner160Color4').closest('tr').hide();
            }
            
            if (map_flag && graph_flag) {
                imageHeight = 354;
            }else if(map_flag){
                imageHeight = 288;
            }else if(graph_flag){
                imageHeight = 248;
            }else{
                imageHeight = 182;
            }

            let ip = $('.page_content').attr('ip');
            let port = $('.page_content').attr('port');
            let baseurl = $('.page_content').attr('base_url');
            let serverurl = $('.page_content').attr('server_url');
            let myserver = serverurl + ip + ':' + port + '/';
            let background_color = $('#serverBanner160Color1').val()
            let font_color1 = $('#serverBanner160Color2').val()
            let title_color = $('#serverBanner160Color3').val()
            let game_name_color = $('#serverBanner160Color4').val()

            $.ajax({
                url: '/',
                type: 'post',
                data: {banner160:'a',height: imageHeight, background_color: background_color, font_color1: font_color1, title_color: title_color, game_name_color: game_name_color},
                success: function(data) {
                    $('#serverBanner160Preview').attr('src', data + '?dt=' + (new Date().getMilliseconds()));
                    $('#serverBanner160WebCode').val('<a href="' + myserver + '" target="_blank"><img src="' + baseurl + data + ' alt=""/></a>')
                    $('#serverBanner160ForumCode').val('[url=' + myserver + '][img]' + baseurl + data + '[/img][/url]')
                }
            })
        }
        function processTabClick(element)
        {
            var count = 0;
            var selected = null;
            var lastElem = null;
            $(element.parentNode).children('.blocknewtaboff, .blocknewtabon').each(
                function()
                {
                    if(element == $(this).get(0))
                    {
                        selected = count;
                        $(this).removeClass('blocknewtaboff');
                        $(this).addClass('blocknewtabon');

                    }
                    else
                    {
                        $(this).addClass('blocknewtaboff');
                        $(this).removeClass('blocknewtabon');
                    }
                    ++count;
                    lastElem = this;
                }
            );
            count=0;
            $(lastElem).nextAll().each(
                function()
                {
                    if(count == selected)
                        $(this).show();
                    else
                        $(this).hide();
                    ++count;
                }
            );
            return;
        }
    </script>
</body>
</html>