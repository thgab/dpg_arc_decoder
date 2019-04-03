<?php

if (php_sapi_name() != "cli") {
    exit;
}

include './DPGArcDecoder.php';
echo 'DPG .arc decoder v' . DPGArcDecoder::$version . ' by thgab' . PHP_EOL;

if (count( $argv ) < 2) {
    echo 'usage: php ' . basename( __FILE__ ) . ' inputfile [outputfile]' . PHP_EOL;
    exit;
}


$inputFileName  = $argv[1];
$outputFileName = isset( $argv[2] ) ? $argv[2] : $inputFileName . '.extr';
$inputFileHandle = @fopen( $inputFileName, "rb" ) or die( 'Could not open input file' . PHP_EOL );
$inputFileSize = filesize( $inputFileName );
$contents      = fread( $inputFileHandle, $inputFileSize );
fclose( $inputFileHandle );

$decoder = new DPGArcDecoder( $contents );

$outputFileHandle = @fopen( $outputFileName, "wb" ) or die( 'Could not create output file' . PHP_EOL );
fwrite( $outputFileHandle, $decoder->decode() );
fclose( $outputFileHandle );




