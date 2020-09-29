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
     * 拼接日志参数为长字符串
     * @param  array  $logParams 日志参数
     * @return string
     */
    public static function spliceLogParams(array $logParams)
    {
        if (empty($logParams)) {
            return '';
        }

        $keys = array_keys($logParams);
        return array_reduce($keys, function ($_carry, $_item) use ($logParams) {
            if ( !empty($logParams[$_item]) ) {
                $_carry .= $_item . ':' . $logParams[$_item] . '; ';
            }

            return $_carry;
        });
    }

    /**
     * 重设数据索引Map
     * @param array  $arr 目标数组
     * @param string $key 指定索引字段
     * @return array
     */
    public static function setMap(array $arr, $key)
    {
        $nArr = [];
        if (empty($arr)) {
            goto end;
        }

        foreach ($arr as $_v) {
            if (!isset($_v[$key])) {
                goto end;
            }

            $nArr[$_v[$key]] = $_v;
        }

        end:
        return $nArr;
    }

    /**
     * 验证收单订单格式是否正确
     * @param  array  $orders 业务收单订单列表
     * @return boolean
     */
    public static function validateOrders(array $orders)
    {
        if (empty($orders)) return false;

        $orderFields = ['appid', 'amount', 'title', 'notify'];
        $filterOrders = array_filter($orders, function ($_val, $_key) use ($orderFields) {
            // 第一层索引表示业务订单号
            if (!preg_match('/^[0-9A-Za-z]+$/', $_key)) return false;

            // 第一层值是订单信息字段数组
            if (!is_array($_val)) return false;

            // 校验必须字段
            $_availFields = array_filter($orderFields, function ($_k) use ($_val) {
                return isset($_val[$_k]);
            }, ARRAY_FILTER_USE_KEY);
            return empty(array_diff($orderFields, $_availFields));

        }, ARRAY_FILTER_USE_BOTH);
        return empty(array_diff_assoc($orders, $filterOrders));
    }

    /**
     * 计算订单总金额
     * @param  array   $orders 订单列表
     * @param  string  $field  金额字段名
     * @param  integer $retain 小数点保留位数
     * @return float
     */
    public static function calOrderAmount(array $orders, $field = 'amount', $retain = 2)
    {
        $retain = max($retain, 2);
        $amount = (float) bcadd(0, 0, $retain);
        if ( empty($orders) or $field === '' ) {
            return $amount;
        }

        array_walk($orders, function ($_v) use (&$amount, $field, $retain) {
            if (isset($_v[$field])) {
                if ( bccomp($_v[$field], 0) == 1 ) {
                    $amount = bcadd($amount, $_v[$field], $retain);
                }
            }
        });

        return $amount;
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

    /**
     * 下载远程文件
     * @param  string $url
     * @param  string $target
     */
    public static function saveRemoteFile($url, $target)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $content = curl_exec($ch);
        curl_close($ch);
        $downloadedFile = fopen($target, 'w');
        fwrite($downloadedFile, $content);
        fclose($downloadedFile);
    }

}
