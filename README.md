#项目简介
该类库用于快速生成网站地图，目前支持包括谷歌、百度、神马、好搜，搜狗这几种搜索引擎。
* * *
#使用简介
* * *
#类库简介
##类文件简介
* Xml.php  操作xml的简单类库
* Base.php 生成站点地图的基本类库
* Google.php 谷歌站点地图类库
* Baidu.php 百度站点地图类库
* Haosou.php 好搜站点地图类库
* Shenma.php 神马站点地图类库
* Sougou.php 搜索站点地图类库
***
##配置参数简介
* $isCompress = true;           //是否采用压缩
* $compSuffix = '.gz';          //开启压缩的后缀
* $maxFileNum = 100;            //最大子文件数目
* $maxUrlNum = 45000;           //单个文件最多url数目
* $maxPreDay = 450000;          //每天生成最大连接数
* $urlNum = 0;                  //当前文件url计数
* $urlDayNum = 0;               //当天url计数
* $fileNum = 1;                 //文件计数
* $fileSize = 10;               //单个文件尺寸  单位Mb
* $path = '';                   //文件生成目录
* $indexFile = 'index.xml';     //索引文件
* $url = '';                    //站点地图访问url
* $itemPre = 'p-';              //子文件前缀
* $useFile = '';                //当前使用的xml
* $type = 'pc';                 //类型pc mobile
* $xmlObj = null;               //xml对象
* $label = 'urlset';            //普通xml文档数据标签
* $labelUrl = '';               //普通xml文档数据标签中的url
* $indexUrl = '';               //索引文件xml文档标签前的内容
* $indexLabel = 'sitemapindex'; //索引文件xml文档标签
* $indexDataModel = array();    //索引文件数据模型
