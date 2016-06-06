<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/3/15
 * Time: 上午8:18
 */
class Baidu extends Base
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

    //类型
    public $type = 'pc'; //pc mobile

    //xml对象
    public $xmlObj = null;

    //xml文档数据标签
    public $label = 'urlset';

    //索引文件xml文档标签
    public $indexLabel = 'sitemapindex';

    //索引文件数据模型
    public $indexDataModel = array();

    public function createXML()
    {
        //确定当前使用的文件
        $this->xmlObj = new Xml();
        if ($this->type == 'mobile') {
            $this->xmlObj->create($this->useFile, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.baidu.com/schemas/sitemap-mobile/1/"></urlset>');
        } else {
            $this->xmlObj->create($this->useFile, "<urlset></urlset>");
        }
    }

    //结束
    public function afterClose()
    {
        //移动站替换
        $str = file_get_contents($this->useFile);
        if ($this->type == 'mobile') {
            $str = preg_replace('/<mobile>mobile<\/mobile>/', '<mobile:mobile type="mobile"/>', $str);
        }
        $this->file_put_contents($this->useFile, $str);
        //压缩处理
        if ($this->isCompress) {
            $this->compress($this->useFile, true);
        }

    }

    //索引文件 设定索引文件数据格式
    public function setIndexDataModel()
    {
        //索引文件数据内容节点
        $this->indexLabel = "sitemapindex";

        //索引文件数据模型
        $this->indexDataModel = array(
            'sitemap' => array(
                'loc'     => 'file', //file, 为通用替换文件的通用字符
                'lastmod' => 'lastmod',
            ),
        );
    }

    //获得最后节点的url
    public function getLastUrl()
    {
        $xmlObj = $this->getLastNode();
        if (is_object($xmlObj)) {
            return $xmlObj->loc;
        } else {
            return '';
        }
    }
}
