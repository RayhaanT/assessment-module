<?php

require_once(__DIR__ . '/../../config.php');

$id = required_param('attemptid', PARAM_INT); // Attempt id

$filepath = $CFG->dataroot . "/filedir/proctor/" . $id . '/';
$compiled = $filepath . 'compiled.webm';
$compilable = $filepath . 'compilable.webm';

if(!is_dir($CFG->dataroot . '/filedir/proctor')) {
    mkdir($CFG->dataroot . '/filedir/proctor', $CFG->directorypermissions, true);
}

global $USER, $DB;

require_login();
$PAGE->set_url('/mod/quiz/savevideo.php', array('attemptid' => $id));

$filecount = 0;
$files = glob($filepath . "*");
if ($files) {
    $filecount = count($files);
}

if ($_FILES["video"]["type"] == "video/webm") {
    if ($_FILES["video"]["error"] > 0) {
        echo "Error Code: " . $_FILES["video"]["error"] . "<br />";
    }

    else {
        if (!is_dir($CFG->dataroot . "/filedir/proctor/" . $id)) {
            mkdir($CFG->dataroot . "/filedir/proctor/" . $id, $CFG->directorypermissions, true);
        }
        
        $targetpath = '';
        if($filecount == 0) {
            $targetpath = $compiled;
        }
        else {
            $filename = $filecount . ".webm";
            $targetpath = $filepath . $filename;
        }

        move_uploaded_file(
            $_FILES["video"]["tmp_name"],
            $targetpath
        );

        if($filecount > 0) {
            // Rename to avoid name conflict
            rename($compiled, $compilable);
            exec('mkvmerge -o ' . $compiled . ' -w ' . $compilable . ' + ' . $targetpath, $output, $return);

            // Return will return non-zero upon an error
            if (!$return) {
                // If no error, delete the individual clip
                unlink($targetpath);
                unlink($compilable);
            }
        }
    }
}
