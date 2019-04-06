<?php

if (php_sapi_name() != "cli") {
    exit;
}

include_once './DPGArcDecoder.php';
include_once './ByteUtil.php';
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
$contents = ByteUtil::createByteArrayFromString($contents);

$decoder = new DPGArcDecoder( $contents );
$decoded = $decoder->decode()->getUncompressedData();

$outputFileHandle = @fopen( $outputFileName, "wb" ) or die( 'Could not create output file' . PHP_EOL );
fwrite($outputFileHandle,$decoded);
fclose($outputFileHandle);

foreach ($decoder->getSegments() as $i=>$segment) {
    $outputFileHandle = @fopen( $outputFileName.'_'.$i, "wb" ) or die( 'Could not create output file' . PHP_EOL );
    fwrite($outputFileHandle,$segment);
    fclose($outputFileHandle);
}




