<?php 
/**
 * 上传文件类
 */
class Upload
{
    /**
     * 上传文件方法
     * @param string $key 资源标识
     * @param string $dir 目标目录
     * @return array('ret'=>0, 'msg'=>'失败原因')
     */
    public static function run($key, $dir)
    {
        if(!is_uploaded_file($_FILES[$key]['tmp_name'])) {
            $ret['ret'] = 0;
            $ret['msg'] = '没有上传文件';
            return $ret;
        }
        $dir = str_replace("\\", '', $dir);
        if( substr($dir,-1) != '/' ) {
            $dir .= '/';
        }
        if( !self::CreatePath( $dir ) ) {
            $ret['ret'] = 1;
            $ret['msg'] = '创建路径失败';
            return $ret;
        }
        $ext = pathinfo($_FILES[$key]['name'],PATHINFO_EXTENSION);
        $md5 = md5_file($_FILES[$key]['tmp_name']);
        $size = filesize($_FILES[$key]['tmp_name']);
        $fn = $dir . $md5 . '.' . $ext;
        if( file_exists($fn) ) {
            $ret['ret'] = 1;
            $ret['filepath'] = $fn;
            $ret['filename'] = basename( $fn );
            $ret['size'] = $size;
            return $ret;
        }
        if( !move_uploaded_file($_FILES[$key]['tmp_name'],$fn) ) {
            $ret['ret'] = 0;
            $ret['msg'] = '移动到目标目录失败';
            return $ret;
        }
        $ret['ret'] = 1;
        $ret['filepath'] = $fn;
        $ret['filename'] = basename( $fn );
        $ret['size'] = $size;
        return $ret;
    }

    /**
     * 创建路径方法
     * @param string $path 目录路径
     * @return bool 成功|失败
     */
    protected static function CreatePath($path)
    {
        if( !file_exists($path) ) {
            self::CreatePath( dirname($path) );
            if( !@mkdir($path, 0777) ) {
                return false;
            }
        }
        return true;
    }
}

// 测试
if(isset($_FILES['image'])) {
    $dir = $_SERVER['DOCUMENT_ROOT'].'/Upload/files/';
    $ret = Upload::run('image', $dir);
    var_dump($ret);
}