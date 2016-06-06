<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/3/16
 * Time: 下午3:16
 */
class Base
{
    //是否采用压缩
    public $isCompress = true;

    //开启压缩的后缀
    public $compSuffix = '.gz';

    //最大子文件数目
    public $maxFileNum = 100;

    //单个文件最多url数目
    public $maxUrlNum = 45000;

    //每天生成最大连接数
    public $maxPreDay = 450000;

    //url计数
    public $urlNum = 0;

    //当天url计数
    public $urlDayNum = 0;

    //文件计数
    public $fileNum = 1;

    //单个文件尺寸  单位Mb
    public $fileSize = 10;

    //文件生成目录
    public $path = '';

    //索引文件
    public $indexFile = 'index.xml';

    //站点地图访问url
    public $url = '';

    //子文件前缀
    public $itemPre = 'p-';

    //当前使用的xml
    public $useFile = '';

    //类型
    public $type = 'pc'; //pc mobile

    //xml对象
    public $xmlObj = null;

    //普通xml文档数据标签
    public $label = 'urlset';

    //普通xml文档数据标签中的url
    public $labelUrl = '';

    //索引文件xml文档标签前的内容
    public $indexUrl = '';

    //索引文件xml文档标签
    public $indexLabel = 'sitemapindex';

    //索引文件数据模型
    public $indexDataModel = array();

    public function __construct($config = array())
    {
        //自定义参数
        if (!empty($config) && is_array($config)) {
            foreach ($config as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }

        //生成数据目录文件不存在,自动创建
        $this->dataDir();

        //获取当天已经处理的数目
        $this->getDayNum();

        //确定当前活跃的编号
        $this->fileNum = $this->findFile('max');

        //索引文件数据模型设置
        $this->setIndexDataModel();

    }

    public function createXML()
    {
        //确定当前使用的文件
        $this->xmlObj = new Xml();
        $this->xmlObj->create($this->useFile, "<urlset{$this->labelUrl}></urlset>");
    }

    //开始初始化
    public function start()
    {
        //当前使用文件重置
        $this->useFile = $this->path . $this->itemPre . $this->fileNum . '.xml';

        //如果采用压缩,先把最后的压缩文件解压
        if ($this->isCompress) {
            $this->unCompress($this->useFile . $this->compSuffix);
        }

        if (!file_exists($this->useFile)) {
            //文件不存在创建
            $this->createXML();

            //单个文件url数目重置
            $this->urlNum = 0;

        } else {
            //文件存在,使用
            $this->xmlObj = new Xml();
            $this->xmlObj->open($this->useFile);
        }

        //判断文件数目, 超过删除
        while (true) {
            $num = $this->findFile('num');
            if ($num > $this->maxFileNum) {
                $this->removeMinFile();
            } else {
                break;
            }
        }
    }

    //每天运行一次,单个文件45000个URL,每天跑450000
    public function add($data)
    {
        //数据格式合法验证,先忽略

        //如果超过当天总数
        if ($this->urlDayNum >= $this->maxPreDay) {
            echo "超过当天最大数目:" . date('Y-m-d') . "\r\n";
            return false;
        }
        //如果对象不存在,新建对象
        if (!is_object($this->xmlObj)) {
            $this->start();
        }

        $this->xmlObj->addItem($data);

        //单个文件url数目+1
        $this->urlNum++;

        //当天总url数目+1
        $this->urlDayNum++;

        //写入当天计数
        $this->writeDayNum();

        //如果超过单个文件最大数
        if ($this->urlNum >= $this->maxUrlNum) {

            //写入旧文件
            $this->close();

            //开始新文件
            $this->fileNum++;
        }
        return true;
    }

    //结束
    public function close()
    {
        if (!is_object($this->xmlObj)) {
            return '';
        }
        $this->xmlObj->save();
        $this->xmlObj = null;

        //格式处理
        $str = file_get_contents($this->useFile);
        $str = preg_replace('/></', ">\n<", $str);
        $this->file_put_contents($this->useFile, $str);

        //文件保存后处理
        $this->afterClose();

        //重建索引
        $this->addIndex();
    }

    //文件保存后处理
    public function afterClose()
    {}

    //
    public function setIndexDataModel()
    {}

    //索引url文件
    public function addIndex()
    {
        $xmlObj = new Xml();
        $xmlObj->create($this->path . $this->indexFile, "<{$this->indexLabel}{$this->indexUrl}></{$this->indexLabel}>");

        $fileArr = $this->getFileOrderByTime();
        if (is_array($fileArr) && !empty($fileArr)) {
            foreach ($fileArr as $file) {
                //数据模型
                $data = $this->indexDataModel;
                //获取文件最后修改时间
                $a    = filemtime($this->path . $file);
                $data = $this->replaceArr($data, 'lastmod', date('Y-m-d', $a));
                $data = $this->replaceArr($data, 'file', $this->url . $file);
                //替换文件
                $xmlObj->addItem($data);
            }
        }
        $xmlObj->save();

        //格式处理
        $str = file_get_contents($this->path . $this->indexFile);
        $str = preg_replace('/></', ">\n<", $str);
        $this->file_put_contents($this->path . $this->indexFile, $str);
    }

    public function getFileOrderByTime()
    {
        $dh        = opendir($this->path);
        $fileArr   = array();
        $fileCTArr = array();
        while ($file = readdir($dh)) {
            if ($file != '.' && $file != '..' && $file != $this->indexFile && pathinfo($file, PATHINFO_EXTENSION) != 'txt') {
                $fileArr[]   = $file;
                $fileCTArr[] = filemtime($this->path . $file);
            }
        }
        array_multisort($fileCTArr, SORT_ASC, SORT_STRING, $fileArr);
        return $fileArr;

    }

    //根据数组的值,替换数组值
    public function replaceArr($arr, $search, $replace)
    {
        if (!is_array($arr) || empty($arr)) {
            return $arr;
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->replaceArr($value, $search, $replace);
            } else {
                if ($value == $search) {
                    $arr[$key] = $replace;
                }
            }
        }
        return $arr;
    }

    //获得最后文件的节点数据
    public function getLastNode()
    {
        $num  = $this->findFile('max');
        $file = $this->path . $this->itemPre . $num . '.xml';

        //压缩文件先解压
        $compreFile = '';
        if ($this->isCompress) {
            //解压
            $this->unCompress($file . $this->compSuffix, true);
        }

        //获取最后的节点
        $xmlObj = new Xml();
        $xmlObj->open($file);
        if (!is_object($xmlObj->xmlObj)) {
            return null;
        }
        $temp = $xmlObj->xmlObj->children();
        $temp = $temp[count($temp) - 1];
        unset($xmlObj);

        //删除解压的文件
        if ($this->isCompress) {
            unlink($file);
        }
        return $temp;
    }

    //
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            return '';
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    //获取当天计数,存储格式 2016-03-17|1|2016-03-17|10   前部分为单个文件计数,后面为当天技术
    public function getDayNum()
    {
        //文件不存在创建,防止报错
        if (!file_exists($this->path . 'num.txt')) {
            $this->file_put_contents($this->path . 'num.txt', '0');
        }
        $str = file_get_contents($this->path . 'num.txt');
        $arr = explode('|', $str);
        //单个文件总url数目, 不用日期有效判断
        $this->urlNum = isset($arr[1]) ? intval($arr[1]) : 0;

        //当天总生成url数目,判断日期,不是当天的清0
        if (isset($arr[2]) && $arr[2] == date('Y-m-d')) {
            $this->urlDayNum = isset($arr[3]) ? intval($arr[3]) : 0;
        } else {
            $this->urlDayNum = 0;
        }
    }
    //写入当天计数
    public function writeDayNum()
    {
        $str = date('Y-m-d') . '|' . $this->urlNum . '|' . date('Y-m-d') . '|' . $this->urlDayNum;
        $this->file_put_contents($this->path . 'num.txt', $str);
    }

    //检测文件数目,移除最后的文件
    public function removeMinFile()
    {
        //找到最小文件 删除
        $num  = $this->findFile('min');
        $file = $this->path . $this->itemPre . $num . '.xml';
        if ($this->isCompress) {
            $file .= $this->compSuffix;
        }
        unlink($file);
    }

    //获取文件的编号, max最大数字编号 min最小数字编号, num获得数目
    public function findFile($type = 'max')
    {
        $dh     = opendir($this->path);
        $numArr = array();
        while ($file = readdir($dh)) {
            if ($file != '.' && $file != '..' && $file != $this->indexFile && pathinfo($file, PATHINFO_EXTENSION) != 'txt') {
                $p = "/{$this->itemPre}(\d+)\.xml/";
                preg_match($p, $file, $match);
                if (isset($match[1])) {
                    $numArr[] = intval($match[1]);
                }
            }
        }

        if ($type == 'max') {
            return (!is_array($numArr) || empty($numArr)) ? 1 : max($numArr);
        } elseif ($type == 'min') {
            return (!is_array($numArr) || empty($numArr)) ? 1 : min($numArr);
        } elseif ($type == 'num') {
            return count($numArr);
        }
    }

    //清空目录
    public function clear($path = '')
    {
        if ($path == '') {
            $path = $this->path;
        }
        $dh = opendir($path);
        while ($file = readdir($dh)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . $file)) {
                    $this->clear($path . $file);
                } else {
                    unlink($path . $file);
                }
            }
        }
        closedir($dh);
    }

    //检测数据存放目录
    public function dataDir()
    {
        if (!is_dir($this->path)) {
            $temp = explode('/', $this->path);
            $path = '/';
            foreach ($temp as $value) {
                if (!empty($value)) {
                    $path .= $value . '/';
                    if (!is_dir($path)) {
                        mkdir($path);
                        chmod($path, 0777);
                    }
                }
            }
        }
    }

    //销毁
    public function __destruct()
    {
        if (is_object($this->xmlObj)) {
            $this->close();
        }
    }

    //压缩函数
    public function compress($file, $isDelete = true)
    {
        if (!file_exists($file)) {
            return false;
        }
        //压缩文件存在,删除,避免生成不成功
        if (file_exists($file . $this->compSuffix)) {
            unlink($file . $this->compSuffix);
        }

        //压缩文件
        $fp = gzopen($file . $this->compSuffix, 'w9');
        gzwrite($fp, file_get_contents($file));
        chmod($file . $this->compSuffix, 0777);

        if ($isDelete) {
            unlink($file);
        }

    }

    //解压函数
    public function unCompress($file, $isDelete = false)
    {
        if (!file_exists($file)) {
            return false;
        }
        $unfile = str_replace($this->compSuffix, '', $file); //生成解压文件
        //解压文件存在删除
        if (file_exists($unfile)) {
            unlink($unfile);
        }

        $zd       = gzopen($file, "r");

        //To Get the size of the uncompressed file
        $FileRead = $file;
        $FileOpen = fopen($FileRead, "rb");
        fseek($FileOpen, -4, SEEK_END);
        $buf = fread($FileOpen, 4);
        $GZFileSize = end(unpack("V", $buf));
        fclose($FileOpen);
        //To Get the size of the zip file

        $contents = gzread($zd, $GZFileSize);
        gzclose($zd);
        $this->file_put_contents($unfile, $contents);
        chmod($unfile, 0777);

        if ($isDelete) {
            unlink($file);
        }
    }

    /**
     *
     */
    public function file_put_contents($file, $str)
    {
        file_put_contents($file, $str);
        chmod($file, 0777);
    }
}
