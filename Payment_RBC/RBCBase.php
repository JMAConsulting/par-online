<?php
class RBCBase
{

    /**
    * Constructor.
    */
    function __construct()
    {
        $this->invalidString_regexp = '/[^A-Z0-9 \.,&\-\/\+\*\$%]/';
        $this->account_file_sender  = array();
        $this->transactions         = array();
        $this->timestamp            = time();
        $this->sum_amounts          = 0;
        $this->allerrors            = array();
        $this->recordNumber         = 1;
    }


    /**
    * Makes the given string valid for DTA files.
    * Some diacritics, especially German umlauts become uppercase,
    * all other chars not allowed are replaced with space.
    *
    * @param string $string String that should made valid.
    *
    * @access public
    * @return string
    */
    function makeValidString($string)
    {
        $special_chars = array(
            'á' => 'a',
            'à' => 'a',
            'ä' => 'ae',
            'â' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ā' => 'a',
            'ă' => 'a',
            'ą' => 'a',
            'ȁ' => 'a',
            'ȃ' => 'a',
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'Ae',
            'Â' => 'A',
            'Ã' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ā' => 'A',
            'Ă' => 'A',
            'Ą' => 'A',
            'Ȁ' => 'A',
            'Ȃ' => 'A',
            'ç' => 'c',
            'ć' => 'c',
            'ĉ' => 'c',
            'ċ' => 'c',
            'č' => 'c',
            'Ç' => 'C',
            'Ć' => 'C',
            'Ĉ' => 'C',
            'Ċ' => 'C',
            'Č' => 'C',
            'ď' => 'd',
            'đ' => 'd',
            'Ď' => 'D',
            'Đ' => 'D',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ē' => 'e',
            'ĕ' => 'e',
            'ė' => 'e',
            'ę' => 'e',
            'ě' => 'e',
            'ȅ' => 'e',
            'ȇ' => 'e',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ē' => 'E',
            'Ĕ' => 'E',
            'Ė' => 'E',
            'Ę' => 'E',
            'Ě' => 'E',
            'Ȅ' => 'E',
            'Ȇ' => 'E',
            'ĝ' => 'g',
            'ğ' => 'g',
            'ġ' => 'g',
            'ģ' => 'g',
            'Ĝ' => 'G',
            'Ğ' => 'G',
            'Ġ' => 'G',
            'Ģ' => 'G',
            'ĥ' => 'h',
            'ħ' => 'h',
            'Ĥ' => 'H',
            'Ħ' => 'H',
            'ì' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ĩ' => 'i',
            'ī' => 'i',
            'ĭ' => 'i',
            'į' => 'i',
            'ı' => 'i',
            'ĳ' => 'ij',
            'ȉ' => 'i',
            'ȋ' => 'i',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ĩ' => 'I',
            'Ī' => 'I',
            'Ĭ' => 'I',
            'Į' => 'I',
            'İ' => 'I',
            'Ĳ' => 'IJ',
            'Ȉ' => 'I',
            'Ȋ' => 'I',
            'ĵ' => 'j',
            'Ĵ' => 'J',
            'ķ' => 'k',
            'Ķ' => 'K',
            'ĺ' => 'l',
            'ļ' => 'l',
            'ľ' => 'l',
            'ŀ' => 'l',
            'ł' => 'l',
            'Ĺ' => 'L',
            'Ļ' => 'L',
            'Ľ' => 'L',
            'Ŀ' => 'L',
            'Ł' => 'L',
            'ñ' => 'n',
            'ń' => 'n',
            'ņ' => 'n',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ņ' => 'N',
            'Ň' => 'N',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'oe',
            'ô' => 'o',
            'õ' => 'o',
            'ø' => 'o',
            'ō' => 'o',
            'ŏ' => 'o',
            'ő' => 'o',
            'œ' => 'oe',
            'ȍ' => 'o',
            'ȏ' => 'o',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'Oe',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ø' => 'O',
            'Ō' => 'O',
            'Ŏ' => 'O',
            'Ő' => 'O',
            'Œ' => 'OE',
            'Ȍ' => 'O',
            'Ȏ' => 'O',
            'ŕ' => 'r',
            'ř' => 'r',
            'ȑ' => 'r',
            'ȓ' => 'r',
            'Ŕ' => 'R',
            'Ř' => 'R',
            'Ȑ' => 'R',
            'Ȓ' => 'R',
            'ß' => 'ss',
            'ś' => 's',
            'ŝ' => 's',
            'ş' => 's',
            'š' => 's',
            'ș' => 's',
            'Ś' => 'S',
            'Ŝ' => 'S',
            'Ş' => 'S',
            'Š' => 'S',
            'Ș' => 'S',
            'ţ' => 't',
            'ť' => 't',
            'ŧ' => 't',
            'ț' => 't',
            'Ţ' => 'T',
            'Ť' => 'T',
            'Ŧ' => 'T',
            'Ț' => 'T',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'ue',
            'û' => 'u',
            'ũ' => 'u',
            'ū' => 'u',
            'ŭ' => 'u',
            'ů' => 'u',
            'ű' => 'u',
            'ų' => 'u',
            'ȕ' => 'u',
            'ȗ' => 'u',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'Ue',
            'Û' => 'U',
            'Ũ' => 'U',
            'Ū' => 'U',
            'Ŭ' => 'U',
            'Ů' => 'U',
            'Ű' => 'U',
            'Ų' => 'U',
            'Ȕ' => 'U',
            'Ȗ' => 'U',
            'ŵ' => 'w',
            'Ŵ' => 'W',
            'ý' => 'y',
            'ÿ' => 'y',
            'ŷ' => 'y',
            'Ý' => 'Y',
            'Ÿ' => 'Y',
            'Ŷ' => 'Y',
            'ź' => 'z',
            'ż' => 'z',
            'ž' => 'z',
            'Ź' => 'Z',
            'Ż' => 'Z',
            'Ž' => 'Z',
        );

        if (strlen($string) == 0) {
            return "";
        }
        // ensure UTF-8, for single-byte-encodings use either
        //     the internal encoding or assume ISO-8859-1
        $utf8string = mb_convert_encoding(
            $string,
            "UTF-8",
            array("UTF-8", mb_internal_encoding(), "ISO-8859-1")
        );

        // replace known special chars
        $result = strtr($utf8string, $special_chars);
        // upper case
        $result = strtoupper($result);
        // make sure every special char is replaced by one space, not two or three
        $result = mb_convert_encoding($result, "ASCII", "UTF-8");
        $result = preg_replace($this->invalidString_regexp, ' ', $result);

        return $result;
    }

    function formatFileNumber( $rbcFileId ){
        $length = strlen($rbcFileId);
        if( $length > 4 ){
            $rbcFileId = substr( $rbcFileId, $length-4 );
        }
        return str_pad( $rbcFileId, 4, '0', STR_PAD_LEFT);
    }
 
}
