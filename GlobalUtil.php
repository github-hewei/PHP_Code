<?php // CODE BY HW 
//GLOBAL UTIL
class GU{
    /**
     * 发送curl请求
     * @param string $url 请求地址
     * @param null|array|string $post_data POST 参数，为null的话为 get请求
     * @param array $header http请求头
     * @param array $other_options 其他curl选项
     * @return string 返回结果
     */
    public static function cURL($url, $post_data = null, $header = null, $other_options = null) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        if( !is_null( $post_data ) ) {
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
        }
        if( is_array( $header ) ) {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        }
        if( is_array( $other_options ) ) {
            foreach( $other_options as $option => $value ) {
                curl_setopt( $ch, $option, $value );
            }
        }
        $response = curl_exec( $ch );
        curl_close($ch);
        if( CURLE_OK !== curl_errno( $ch ) ) {
            echo "cURL Error: " . curl_error( $ch );
            exit;
        }
        return $response;
    }

    /**
     * 根据数组拼接写入SQL字符串
     * @param string $table 数据表名
     * @param array $insert_data 数据
     * @param null|array $fields 字段
     * @return string 返回拼接好的SQL
     */
    public static function CInsertSQL($table, $insert_data, $fields = NULL) {
        if( count($insert_data) == 0 ) {
            return "";
        }
        if( is_null( $fields ) ) {
            $fields = array_keys( $insert_data[0] );
        }
        $tpl = "INSERT INTO `%s` ( `%s` ) VALUES %s;";
        $data = array();
        foreach( $insert_data as $item ) {
            $rows = array();
            foreach( $fields as $key ) {
                $value = "";
                if( isset( $item[$key] ) ) {
                    $value = addslashes( trim( $item[$key] ) );
                }
                array_push( $rows, $value );
            }
            array_push($data, sprintf("('%s')", implode("', '", $rows)));
        }
        $sql = sprintf( $tpl, $table, implode("`, `", $fields), implode(", ", $data) );
        return $sql;
    }

    /**
     * 创建路径
     * @param string $path 路径
     * @return string 路径
     */
    public static function CreatePath( $path ) {
        if(!is_dir($path) && !mkdir($path, 0777, true)) {
            echo "路径创建失败:{$path}<br>\n";
            exit;
        }
        return $path;
    }

    /**
     * 重新计算序列化数据字符串长度并解码
     * @param string $str serialize序列化的字符串
     * @return mixed 解码后的数据
     */
    public static function Unserialize( $str )
    {
        # s:12:"壳脂胶囊";
        $str = preg_replace_callback( '|s:(\d+):"(.*?)";|s', function($match) {
            return sprintf('s:%d:"%s";', strlen($match[2]), $match[2]);
        }, $str );
        return unserialize( $str );
    }

    /**
     * 连接数据库,创建一个PDO对象
     * @param string $user 用户
     * @param string $passwd 密码
     * @param string $dbname 数据库
     * @param string $host 数据库服务器地址
     * @param string $port 数据库端口
     * @return object 返回pdo对象
     */
    public static function Cpdo($user, $passwd, $dbname, $host=null, $port=null) {
        if( is_null($host) ) {
            $host = 'localhost';
        }
        if( is_null($port) ) {
            $port = 3306;
        }
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};port={$port};";
            $pdo = new PDO($dsn, $user, $passwd);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("set names utf8;");
        } catch (PDOException $e) {
            printf("数据库连接失败: %s<br>\n", $e->getMessage());
            exit;
        }
        return $pdo;
    }



}

