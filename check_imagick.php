<?php
if (extension_loaded('imagick')) {
    $im = new Imagick();
    echo 'Imagick loaded. Formats: ' . (in_array('PDF', $im->queryFormats()) ? 'PDF Supported' : 'No PDF Support');
} else {
    echo 'Imagick not loaded';
}
