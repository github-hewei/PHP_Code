<?php  //CODE BY ZMZ
// 汉字转拼音类
define("MEM_KEY", "Global_Pinyin_Data_In_Memcache");

class CPinyin {
    private $pytab;

    public function __construct( $memcache = null ) {
        $fn = dirname( __FILE__ ) . "/pytable.serialize.data.php";
        $pytab = null;
        if( $memcache ) {
            try{
                $pytab = $memcache->get( MEM_KEY );
            } catch ( Exception $e ) {
                $pytab = null;
            }

        }
        if( !$pytab ) {
            $_tmp = explode( "\n", file_get_contents( $fn ) );
            if( isset( $_tmp[1] ) ) {
                $pytab = unserialize( trim( $_tmp[1] ) );
                if( $pytab === false ) {
                    $pytab = null;
                } else {
                    if( $memcache ) {
                        $memret = $memcache->set( MEM_KEY, $pytab, 0, 0 );
                    }
                }
            }
            //echo 'use file' . "\n";
        }  else {
            //echo 'use memcache' . "\n";
        }

        if( !$pytab ) {
            exit( 'construct class CPinyin fail' );
        }
        $this->pytab = $pytab;
    }
    //------------------------------------------------------------------------------------

    /**
     * @desc 获取字符串的首字母
     * @param $string 要转换的字符串
     * @param $isOne 是否取首字母
     * @param $upper 是否转换为大写
     * @return string
     *
     * 例如：getChineseFirstChar('我是作者') 首字符全部字母+小写
     * return "wo"
     *
     * 例如：getChineseFirstChar('我是作者',true) 首字符首字母+小写
     * return "w"
     *
     * 例如：getChineseFirstChar('我是作者',true,true) 首字符首字母+大写
     * return "W"
     *
     * 例如：getChineseFirstChar('我是作者',false,true) 首字符全部字母+大写
     * return "WO"
     */
    public function getChineseFirstChar( $string, $isOne=false, $upper=false) {
        $spellArray = $this->pytab;
        $str_arr = $this->utf8_str_split($string,1); //将字符串拆分成数组

        if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$str_arr[0])) { //判断是否是汉字
            $chinese = $spellArray[$str_arr[0]];
            $result = $chinese[0];
        }else {
            $result = $str_arr[0];
        }

        $result = $isOne ? substr($result,0,1) : $result;

        return $upper?strtoupper($result):$result;
    }

    /**
     * @desc 将字符串转换成拼音字符串
     * @param $string 汉字字符串
     * @param $upper 是否大写
     * @return string
     *
     * 例如：getChineseChar('我是作者'); 全部字符串+小写
     * return "wo shi zuo zhe"
     *
     * 例如：getChineseChar('我是作者',true); 首字母+小写
     * return "w s z z"
     *
     * 例如：getChineseChar('我是作者',true,true); 首字母+大写
     * return "W S Z Z"
     *
     * 例如：getChineseChar('我是作者',false,true); 首字母+大写
     * return "WO SHI ZUO ZHE"
     */
    public function getChineseChar($string,$isOne=false,$upper=false) {
        $spellArray = $this->pytab;
        $str_arr = $this->utf8_str_split($string,1); //将字符串拆分成数组
        $result = array();
        foreach($str_arr as $char)
        {
            if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$char))
            {
                $chinese = $spellArray[$char];
                $chinese  = $chinese[0];
            }else{
                $chinese=$char;
            }
            $chinese = $isOne ? substr($chinese,0,1) : $chinese;
            $result[] = $upper ? strtoupper($chinese) : $chinese;
        }
        return implode('',$result);
    }

    /**
     * @desc 将字符串转换成数组
     * @param $str 要转换的数组
     * @param $split_len
     * @return array
     */
    private function utf8_str_split($str,$split_len=1) {

        if(!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1) {
            return FALSE;
        }

        $len = mb_strlen($str, 'UTF-8');

        if ($len <= $split_len) {
            return array($str);
        }
        preg_match_all('/.{'.$split_len.'}|[^\x00]{1,'.$split_len.'}$/us', $str, $ar);

        return $ar[0];
    }
};


?>