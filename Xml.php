<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/3/15
 * Time: 上午8:16
 */
class Xml
{
    public $xmlObj = null;
    public $file   = '';

    public function __construct()
    {

    }

    //创建新的xml文件
    public function create($file, $body = "<data></data>")
    {
        $this->file = $file;
        $str = '<?xml version="1.0" encoding="utf-8"?>'.$body;
        file_put_contents($file, $str);
        $this->xmlObj= simplexml_load_file($file);
    }

    //打开已经存在的xml文件
    public function open($file)
    {
        $this->file = $file;
        if (!file_exists($file)) {
            echo "文件不存在";
            return;
        }
        $this->xmlObj = simplexml_load_file($file);
    }

    //追加节点
    public function addItem($data, $node = null)
    {
        //判断对象是否存在,不存在返回为空
        if (is_null($this->xmlObj)) {
            echo "对象不存在";
            return;
        }
        if ($node === null) {
            $node = $this->xmlObj;
        }
        //写入数据
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $this->addItem($value, $node->addChild($key));
                } else {
                    $node->addChild($key, $value);
                }
            }
        }
    }

    //保存
    public function save()
    {
        $this->xmlObj->asXML($this->file);
    }
}
