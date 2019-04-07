<?php

class DPGArcDecoder
{
    private $byteArray;
    private $header = array();
    private $key = array();
    private $folders = array();

    private $dataIndex = 12;

    private $compressedData;
    private $uncompressedData;

    public function __construct(array $byteArray) {
        $this->byteArray = $byteArray;
    }

    /**
     * @return $this
     */
    public function decode() {
        $this->removeTextHeader()
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
        $header = array_slice($this->byteArray,0,27);
        $this->key = array_slice($header,1,4);

        $firstFolderOffset = array_slice($header,15,4);
        $firstFolderOffset = unpack('V',pack('C*',...$firstFolderOffset));

        $dataWithoutHeader = pack('C*', ...(array_slice($this->byteArray,23)));
        $seek = $firstFolderOffset[1]+1;
        $segments = array();
        $test = unpack('V'.$seek,$dataWithoutHeader);
        $length = $test[$seek];
        $count = $test[$seek-1];
        $dataWithoutHeader = substr($dataWithoutHeader,$seek*4);
        $segments[] = substr($dataWithoutHeader,0,$length);
        $dataWithoutHeader = substr($dataWithoutHeader,$length);
        $seek = 1;

        while (count($segments)<$count) {
            $test = unpack('V'.$seek,$dataWithoutHeader);
            $length = $test[$seek];
            $dataWithoutHeader = substr($dataWithoutHeader,$seek*4);
            $segments[] = substr($dataWithoutHeader,0,$length);
            $dataWithoutHeader = substr($dataWithoutHeader,$length);
        }
        $this->folders = $segments;
        $this->byteArray = unpack('C*', $dataWithoutHeader);
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
    public function removeTextHeader() {
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