<?php
$file = 'C:\\xampp\\htdocs\\fmc_systems\\tcpdf\\fonts\\iskpota.ttf';
if (file_exists($file)) {
    echo "File found at $file!";
} else {
    echo "File not found at $file. Check the path or file.";
}
?>