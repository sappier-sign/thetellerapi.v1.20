<?php

namespace App;

use App\Http\Controllers\TransactionController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class Functions extends Model
{
    protected $message;
    
    // Write international airtime response to logfile
    public static function writeIntAirtime( $header, $string )
    {
        $data = "$header\r\n";
        if ( is_array( $string ) )
        {
            foreach ( $string as $key => $value )
            {
                if ( is_array( $value ) )
                {
                    foreach ( $value as $k => $v )
                    {
                        $data .= date( 'Y-m-d H:i:s' ).' | '.$k.' : '.$v."\r\n";
                    }
                }
                else
                {
                    $data .= date( 'Y-m-d H:i:s' ).' | '.$key.' : '.$value."\r\n";
                }
            }
        }

        else
        {
            $data .= date( 'Y-m-d H:i:s' ).' | '."$string\r\n";
        }

        if ( isset( $data ) && $data != '' ) {
            $filePath = 'all.txt';
            $fopen = fopen( $filePath, 'a+' );
            fwrite( $fopen, "$data\r\n" );
            fclose( $fopen );
        }

        File::append(storage_path('logs/ttm_messages/'.Date('Ymd').'.txt'), "$data\n");
    }

    // Write MTN data to our logfile
    public static function writeMTN( $header, $params, $path, $message )
    {
        $match = array( 'username', 'password', 'apiKey', 'vendorID' );
        $message .= "\t<<<".Carbon::now()->toTimeString()." START $header\r\n";
        if ( is_object( $params ) ) {
            $params	=	get_object_vars( $params );
        }
        foreach ( $params as $key => $value ) {
            if ( is_array( $value ) ) {
                $message .= date( 'H:i:s' ).' | '."$key\r\n";
                $params = $value;
                foreach ( $params as $k => $v ) {

                    if ( in_array( $k, $match ) )
                    {
                        $message .= "\t\t".str_pad($k, 15, ' ', STR_PAD_RIGHT)." :\t".self::maskAm($v)."\r\n";
                    } else {
                        $message .= "\t\t".str_pad($k, 15, ' ', STR_PAD_RIGHT)." :\t".$v."\r\n";
                    }
                }
            } else {

                if ( is_object( $value ) ) {
                    $params	=	get_object_vars( $value	);
                    if ( is_array( $params ) ) {
                        $params = $value;
                        foreach ( $params as $k => $v ) {
                            if ( in_array( $k, $match ) )
                            {
                                $message .= "\t\t".str_pad($k, 15, ' ', STR_PAD_RIGHT)." :\t".self::maskAm($v)."\r\n";
                            } else {
                                $message .= "\t\t".str_pad($k, 15, ' ', STR_PAD_RIGHT)." :\t".$v."\r\n";
                            }
                        }
                    }
                }	else {
                    if ( in_array( $key, $match ) )
                    {
                        $message .= "\t\t".str_pad($key, 15, ' ', STR_PAD_RIGHT)." :\t".self::maskAm($value)."\r\n";
                    } else {
                        $message .= "\t\t".str_pad($key, 15, ' ', STR_PAD_RIGHT)." :\t".$value."\r\n";
                    }
                }
            }
        }
        $message .= "\t<<<".Carbon::now()->toTimeString()." END $header\r\n\r\n";
        self::writeRequestWithTimestamp( $path, $message );
    }

    public static function writeTigo( $header, $value )
    {
        $message = $header."\r\n";
        foreach ( $value as $array )
        {
            if ( isset( $array[ 'tag' ] ) && isset( $array[ 'value' ] ) && isset( $array[ 'type' ] ) && $array[ 'type' ] == 'complete' )
            {
                $point = 0;
                $current = '';
                $length = strlen( $array[ 'tag' ] );

                while ( $current != ':' && $point < $length )
                {
                    $current = substr( $array[ 'tag' ], $point, 1 );
                    $point++;
                }

                $array[ 'tag' ] = substr( $array[ 'tag' ], $point );
                $match = array( 'USERNAME', 'PASSWORD', 'CONSUMERID', 'WEBUSER', 'WEBPASSWORD', 'PARAMETERNAME', 'PARAMETERVALUE' );

                if ( !in_array( $array[ 'tag' ], $match ) )
                {
                    $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.$array[ 'value' ]."\r\n";
					$array[ 'value' ]	=	self::maskAm( $array[ 'value' ] );
                }
            }
        }
        self::writeRequestWithTimestamp( $message );
    }

    public static function maskAm( $value )
    {
        $length = strlen( $value );
        $new_value = '';
        $count = 0;

        while ( $count < $length )
        {
            $new_value .= '*';
            $count++;
        }
        return $new_value;
    }

    public static function writeAirtel( $header, $value )
    {
        $match = array('merchant_number');
        $message	=	"$header\r\n";
        foreach ( $value as $key => $val )
        {
            if ( in_array( $key, $match ) )
            {
                $message .= date( 'H:i:s' ).' | '.$key.' : '.self::maskAm( $val )."\r\n";
            } else {
                $message .= date( 'H:i:s' ).' | '."$key : $val\r\n";
            }
        }
        self::writeRequestWithTimestamp( $message );
    }

    public static function writeGhipss( $header, $params )
    {
        $credentials = array( 'merchantID', 'acquirerID', 'password' );
        $message = $header."\r\n";
        if ( is_array( $params ) ) {

            foreach ( $params as $key => $value ) {
                if ( is_array( $value ) ) {

                    $message	.=	date( 'H:i:s' ).' | '."$key\r\n";
                    $params		=	$value;

                    foreach ( $params as $key => $value ) {
                        if ( in_array( $key, $credentials ) ) {
                            $value	=	self::maskAm( $value );
                        }
                        $message	.=	date( 'H:i:s' ).' | '."$key	:	$value\r\n";
                    }
                }	else {
                    if ( in_array( $key, $credentials ) ) {
                        $value	=	self::maskAm( $value );
                    }
                    $message	.=	date( 'H:i:s' ).' | '."$key	:	$value\r\n";
                }
            }
        }

        else
        {
            $message	.=	date( 'H:i:s' ).' | '."$params\r\n";
        }

        self::writeRequestWithTimestamp( $message );
    }

    public static function writeZenith( $header, $messageToWrite )
    {
        $match = array('GlobalPayID');
        $message = $header."\r\n";
        if (!is_null($messageToWrite)){
            foreach ( $messageToWrite as $key => $value )
            {
                if ( is_array( $value ) )
                {
                    $message .= date( 'H:i:s' ). ' | ' ."$key\r\n";
                    $messageToWrite = $value;
                    foreach ( $messageToWrite as $key => $value )
                    {
                        if ( is_array( $value ) )
                        {
                            $message .= date( 'H:i:s' ). ' | ' ."$key\r\n";
                            $messageToWrite = $value;
                            foreach ( $messageToWrite as $k => $v )
                            {
                                if ($k === 'CardNumber'){
                                    $value = substr($v, 0, 6).'******'.substr($v, -4);
                                } elseif( $k === 'CardCvv'){
                                    $value = '***';
                                } else
                                    if ( $k === 'GlobalPayID'){
                                    $value = self::maskAm($v);
                                }
                                $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                            }

                        }

                        else
                        {
                            $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                        }
                    }

                }

                else
                {
                    $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                }
            }
        }

        // Format the data before writing to file
        return self::writeRequestWithTimestamp( $message );
    }

    public static function writeVodafone($header, $strings)
    {
        $message = $header."\r\n";
        if (isset($strings['array'])){
            foreach ($strings['array'] as $key => $value) {
                $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
            }
        } else {
            foreach ( $strings as $array )
            {
                if ( isset( $array[ 'tag' ] ) && isset( $array[ 'value' ] ) && isset( $array[ 'type' ] ) && $array[ 'type' ] == 'complete' )
                {
                    $match = array( 'TOKEN', 'VFPIN', 'VENDORCODE' );

                    if ( !in_array( $array[ 'tag' ], $match ) )
                    {
                        $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.$array[ 'value' ]."\r\n";
                    } else {
                        $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.self::maskAm( $array[ 'value' ] )."\r\n";
                    }
                }
            }
        }

        return self::writeRequestWithTimestamp($message);
    }

    // Writes all the request and response from theteller and the source of fund
    // to the message log files on the server with the timestamp
    public static function writeRequestWithTimestamp ( $path, $dataToWrite )
    {
        $file = fopen($path, 'a+');
        fwrite($file, $dataToWrite);
        fclose($file);
    }

    // Writes all the request and response from theteller and the source of fund
    // to the message log files on the server without the timestamp
    public static function writeRequest ( $filePath, $dataToWrite )
    {
        $fopen = fopen( $filePath, 'a+' );
        fwrite( $fopen, $dataToWrite );
        fclose( $fopen );
    }

    public static function toFloat($minor_unit){
        $float = ((int)$minor_unit)/100;
        return round((float)$float,2);
    }

    public static function logRequest(Request $request)
    {
        $message = date( "d-m-Y" ) . " | " . "MERCHANT TO TTLR REQUEST FOREIGN\r\n";
        foreach ($request->all() as $index => $value) {
            if ($index === 'pan'){
                $value = self::maskAm($value);
            } elseif ($index === 'cvv') {
                $value = '***';
            }
            $message .= Carbon::today()->toTimeString()." | $index:\t\t$value\r\n";
        }

        File::append(storage_path('logs_ttm_messages/'.Date('Ymd').'.txt'), "$message");
    }

    public static function writettlrResponse($response, $path)
    {
        $message = "\t<<<".Carbon::now()->toTimeString()." START TTLR TO MERCHANT RESPONSE\r\n";
        foreach ($response as $key => $value) {
            $message .= "\t\t".str_pad($key, 10, ' ', 1)." :\t $value\r\n";
        }
        $message .= "\t<<<".Carbon::now()->toTimeString()." END TTLR TO MERCHANT RESPONSE \r\n";

        $file = fopen($path, 'a');
        fwrite($file, $message);
        fclose($file);
    }
}