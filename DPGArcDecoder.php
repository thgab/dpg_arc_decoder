<?php

class DPGArcDecoder
{
    private $byteArray;
    private $header = array();
    private $key = array();

    private $keyIndex = 0;
    private $dataIndex = 0;

    public static $version = '0.4';
    private $compressedData;
    private $uncompressedData;

    public function __construct(array $byteArray) {
        $this->byteArray = $byteArray;
    }

    /**
     * @return $this
     */
    public function decode() {
        $this->removeHeader()
            ->determineIndexes()
            ->extractEncodedData()
            ->deCryptData()
            ->uncompressData();
        return $this;
    }

    /**
     * @return $this
     */
    public function determineIndexes() {
        foreach ($this->byteArray as $i => $byte) {
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

            if ( ! $this->key && $byte == 26) {
                $this->key      = array_slice( $this->byteArray, $i + 1, 4 );
                $this->keyIndex = $i;
            }
        }
        if ($this->key && ! $this->dataIndex) {
            foreach ($this->byteArray as $i => $byte) {
                if ($i > $this->keyIndex + 5) {
                    if ($byte == 13) {
                        $this->dataIndex = $i + 5;
                        break;
                    }
                }
            }

        }
        return $this;
    }

    /**
     * @return $this
     */
    public function extractEncodedData() {
        $this->compressedData = array_slice( $this->byteArray, $this->dataIndex );
        return $this;
    }

    /**
     * @return $this
     */
    public function uncompressData() {
        $this->uncompressedData = @zlib_decode( ByteUtil::createStringFromByteArray($this->compressedData) );
        return $this;
    }

    /**
     * @return $this
     */
    public function removeHeader() {
        foreach ($this->byteArray as $i => $byte) {
            if ( ! $this->header && $byte == 13 && $this->byteArray[$i + 1] == 10) {
                $this->header    = array_slice( $this->byteArray, 0, $i - 1 );
                $this->byteArray = array_slice( $this->byteArray, $i + 1 );
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function deCryptData() {
        $this->compressedData = ByteUtil::xorData( $this->compressedData, $this->key );
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUncompressedData() {
        return $this->uncompressedData;
    }

    /**
     * @return array
     */
    public function getSegments() {
        $decoded = $this->uncompressedData;
        $segments = array();
        while($decoded){
            $vdata=array_values(unpack( "V", $decoded ));
            $next = $vdata[0] + 12;
            $segments[] = substr($decoded,0,$next);
            $decoded = substr($decoded,$next);
        }
        return $segments;
    }

}