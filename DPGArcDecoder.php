<?php


class DPGArcDecoder
{
    private $byteArray;
    private $header = array();
    private $key = array();

    private $keyIndex = 0;
    private $dataIndex = 0;

    public static $version = '0.4';

    public function __construct( $dataContent ) {
        $this->createByteArrayFromString( $dataContent );
    }

    public function decode() {
        $this->determineIndexes();
        $data = $this->extractEncodedData();
        $data = $this->xorDecodeData( $data );
        return $this->uncompressData( $data );
    }

    /**
     * @param $dataContent
     */
    public function createByteArrayFromString( $dataContent ) {
        $this->byteArray = unpack( "C*", $dataContent );
    }

    public function determineIndexes() {
        foreach ($this->byteArray as $i => $byte) {
            if ( ! $this->header && $byte == 13 && $this->byteArray[$i + 1] == 10) {
                $this->header = array_slice( $this->byteArray, 0, $i - 1 );
                $this->byteArray = array_slice($this->byteArray,$i+1);
            }
        }
        foreach ($this->byteArray as $i => $byte) {
            if ( ! $this->header && $byte == 13 && $this->byteArray[$i + 1] == 10) {
                $this->header = array_slice( $this->byteArray, 0, $i - 1 );
            }

            if ($this->key && ( $i > $this->keyIndex + 5 )) {
                $found = TRUE;
                foreach ($this->key as $ii => $comp) {
                    if ($comp != $this->byteArray[$i + $ii]) {
                        $found = FALSE;
                        break;
                    }
                }
                if ($found) {
                    $this->dataIndex = $i + 4;
                    break;
                }
            }

            if ( ! $this->key && $byte == 26 ) {
                $this->key      = array_slice( $this->byteArray, $i+1, 4 );
                $this->keyIndex = $i;
            }
        }
    }

    /**
     * @return array
     */
    public function extractEncodedData() {
        $data = array_slice( $this->byteArray, $this->dataIndex );
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function xorDecodeData( array $data ) {
        foreach ($data as $dataI => $databyte) {
            $data[$dataI] = $this->key[$dataI % count( $this->key )] ^ $databyte;
        }
        return $data;
    }

    /**
     * @param array $data
     * @return string
     */
    public function uncompressData( array $data ) {
        return @zlib_decode( pack( 'C*', ...$data ) );
    }

}