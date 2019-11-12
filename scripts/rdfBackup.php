<?php
/**
 * Created by PhpStorm.
 * User: Zahen Malla Osman
 * Date: 12/09/17
 * Time: 14:40
 */

/**
 * This script allows to zip all rdf files in HAL
 */

// ----------
// Hal Header
// ----------

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

// -------
// Methods
// -------

// Method to zip the a directory and their sub directories
function zipDirectory($source, $destination)
{
    // Check that source exist
    if (!file_exists($source)) {
        println('', 'Source file does not exist !', 'red');
        exit;
    }
    // Initialize archive object
    $zip = new ZipArchive();

    // Open the archive folder to write or overwrite
    if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        println('', 'Cannot open the archive folder', 'red');
        exit;
    }

    // formatting the path of the RDF folder
    $source = str_replace('\\', '/', realpath($source));

    // For sub directories
    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if ( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) {
                continue;
            }

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    // For files
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    // Close the archive folder
    $zip->close();
    println('', 'Archiving with success', 'green');
}

// ---------------
// Main processing
// ---------------

// Get the date of today
$today = date("Y-m-d");

// RDF source folder on server
$sourceDirectory = __DIR__ . '/../cache/hal/' . APPLICATION_ENV . '/rdf';

// RDF archive folder (will be created after zipping)
$destinationDirectory = '/data/hal/production/data_ao/rdf_archive_' . $today . '.zip';

// Zipping the directory of RDF files
zipDirectory($sourceDirectory, $destinationDirectory);
