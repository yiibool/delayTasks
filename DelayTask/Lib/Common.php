<?php
namespace DelayTask\Lib;

use DelayTask\Exception\RuntimeException;

/**
 * 公用方法
 */
class Common
{
    /**
     * 数据返回标准格式
     * @param   integer $code    0 代表成功；其他值代表各类错误编码
     * @param   string  $message 出错信息
     * @param   array   $data    返回的数据部分
     * @return  array
     */
    public static function formatReturn($code = 0, $message = '', $data = [])
    {
        return self::errCodeMsg($code, $message, $data);
    }

    /**
     * 标准返回错误码 和 错误信息
     * @param  integer  $code
     * @param  string   $message
     * @param  array    $data
     * @return array
     */
    public static function errCodeMsg($code = 0, $message = '', $data = [])
    {
       return [
            'code'      => $code,
            'message'   => $message ? $message : ($code ? 'fail' : 'success'),
            'data'      => $data
        ];
    }

    /**
     * 读取文件内容
     * @param  string $filePath 文件路径
     * @return string
     * @throws RuntimeException
     */
    public static function getFileContent($filePath)
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(RuntimeException::CODE_FILE_NOT_EXISTS, '', [$filePath]);
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) {
            throw new RuntimeException(RuntimeException::CODE_FILE_OPEN_FAILED, '', [$filePath]);
        }

        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            throw new RuntimeException(RuntimeException::CODE_FILE_LOCK_FAILED, '', [$filePath]);
        }

        $content = '';
        while(!feof($fp)) {
            $content .= fread($fp, 8192);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
        return $content;
    }

    /**
     * 写入文件
     * @param  string $filePath 文件路径
     * @param  string $content  待写入文件内容
     * @return boolean
     * @throws RuntimeException
     */
    public static function writeToFile($filePath, $content)
    {
        $dirPath = dirname($filePath);
        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath, 0777, true)) {
                throw new RuntimeException(RuntimeException::CODE_DIRPATH_CREATE_FAILED, '', [$dirPath]);
            }
        }

        $fp = fopen($filePath, 'w+');
        if (!$fp) {
            throw new RuntimeException(RuntimeException::CODE_FILE_OPEN_FAILED, '', [$filePath]);
        }

        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            throw new RuntimeException(RuntimeException::CODE_FILE_LOCK_FAILED, '', [$filePath]);
        }

        if (!fwrite($fp, $content)) {
            throw new RuntimeException(RuntimeException::CODE_FILE_WRITE_FAILED, '', [$filePath]);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    /**
     * 移动文件
     * @param $file
     * @param $target
     * @return bool
     * @throws RuntimeException
     */
    public static function moveFile($file, $target)
    {
        $dirPath = dirname($target);
        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath, 0777, true)) {
                throw new RuntimeException(RuntimeException::CODE_DIRPATH_CREATE_FAILED, '', [$dirPath]);
            }
        }

        if ( !rename($file, $target) ) {
            throw new RuntimeException(RuntimeException::CODE_FILE_RENAME_FAILED, '', [$file, $target]);
        }

        return true;
    }

    /**
     * 遍历目录
     * @param $dirPath
     * @return array
     * @throws RuntimeException
     */
    public static function recurseDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new RuntimeException(RuntimeException::CODE_DIR_NOT_EXISTS, '', [$dirPath]);
        }

        $entries = [];
        $r = dir($dirPath);
        while ( false !== ($entry = $r->read()) )
        {
            if (!in_array($entry, ['.', '..'])) {
                $entries[] = $entry;
            }
        }
        $r->close();

        return $entries;
    }

}
