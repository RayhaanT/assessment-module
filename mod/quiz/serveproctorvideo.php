<?php

require_once(__DIR__ . '/../../config.php');

$id = required_param('attemptid', PARAM_INT); // Attempt id
$stream = optional_param('stream', false, PARAM_BOOL);
$PAGE->set_url('/mod/quiz/serveproctorvideo.php', array('attemptid' => $id));

$filepath = $CFG->dataroot . "/filedir/proctor/" . $id . '/compiled.webm';
$filename = 'compiled.webm';

if(!$stream) {
    if(!file_exists($filepath)) {
        die("Video not found.");
    }

    if ($fd = fopen($filepath, "rb")) {
        $fsize = filesize($filepath);
        header("Content-Type: video/webm");
        header("Content-Disposition: inline; filename=\"" . $filename . "\"");
        header('Content-Transfer-Encoding: binary');
        header("Content-Length: $fsize");
        fpassthru($fd);
    } else {
        die('file not found');
    }
}
else {
    // File serving code from: https://stackoverflow.com/questions/35311593/php-serve-mp4-instantly-before-loading-everything
    if ($fp = fopen($filepath, "rb")) {
        $size = filesize($filepath); 
        $length = $size;
        $start = 0;  
        $end = $size - 1; 
        header('Content-type: video/webm');
        header("Accept-Ranges: 0-$length");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: ".$length);
        $buffer = 1024 * 8;
        while(!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }
        fclose($fp);
        exit();
    } else {
        die('file not found');
    }
}