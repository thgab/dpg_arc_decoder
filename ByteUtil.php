<?php


class ByteUtil
{
    /**
     * @param $string
     * @return array|false
     */
    public static function createByteArrayFromString( $string ) {
        return unpack( "C*", $string );
    }

    /**
     * @param array $byteArray
     * @return false|string
     */
    public static function createStringFromByteArray(array $byteArray) {
        return pack( 'C*', ...$byteArray );
    }

    /**
     * @param array $data
     * @return array
     */
    public static function xorData( array $data, array $key ) {
        foreach ($data as $index => $byte) {
            $data[$index] = $key[$index % count( $key )] ^ $byte;
        }
        return $data;
    }

}