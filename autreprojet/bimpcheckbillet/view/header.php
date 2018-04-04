<?php

function printHeader($title, $arrayofjs) {
    print '<!DOCTYPE html>';
    print '<html>';

    print '<head>';
    print '<title>' . $title . '</title>';
// CSS
    print '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
// JS
    print '<script src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>';
    print '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    foreach ($arrayofjs as $jsfile) {
        print '<script type="text/javascript" src="' . $jsfile . '"></script>';
    }
    print '</head>';
}
