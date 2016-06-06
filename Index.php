<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/3/21
 * Time: 下午5:09
 */
class Index
{
    public $startCid = 0;

    public $startCaijiCid = 0;

    public $baiduPcObj = null;

    public $baiduMobileObj = null;

    public $haosouObj = null;

    public $haosouMobileObj = null;

    public $sougouObj = null;

    public $sougouMobileObj = null;

    public $googleObj = null;

    public $cidfile = '/data/siteroot/sitemap/bdsitemap/index/cid.txt';


    //初始化
    public function init()
    {
        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/sitemap/bdsitemap/index/',
            //站点地图访问url
            'url'        => 'http://company.gongchang.com/bdsitemap/index/',

        );
        //百度pc
        $this->baiduPcObj = new Baidu($config);

        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/msitemap/bdsitemap/index/',
            //站点地图访问url
            'url'        => 'http://m-c.gongchang.com/bdsitemap/index/',
            //类型
            'type'       => 'mobile',
        );
        //百度mobile
        $this->baiduMobileObj = new Baidu($config);

        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/sitemap/hssitemap/index/',
            //站点地图访问url
            'url'        => 'http://company.gongchang.com/hssitemap/index/',
        );
        //好搜pc
        $this->haosouObj = new Haosou($config);
        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/msitemap/hssitemap/index/',
            //站点地图访问url
            'url'        => 'http://m-c.gongchang.com/hssitemap/index/',
            //类型
            'type'       => 'mobile',
        );
        //好搜mobile
        $this->haosouMobileObj = new Haosou($config);

        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/sitemap/sgsitemap/index/',
            //站点地图访问url
            'url'        => 'http://company.gongchang.com/sgsitemap/index/',
        );
        //搜狗pc
        $this->sougouObj = new Sougou($config);
        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/msitemap/sgsitemap/index/',
            //站点地图访问url
            'url'        => 'http://m-c.gongchang.com/sgsitemap/index/',
            //类型
            'type'       => 'mobile',
        );
        //搜狗mobile
        $this->sougouMobileObj = new Sougou($config);

        //配置文件
        $config = array(
            //是否采用煞所  默认false
            'isCompress' => true,
            //文件生成的目录
            'path'       => '/data/siteroot/sitemap/ggsitemap/index/',
            //站点地图访问url
            'url'        => 'http://company.gongchang.com/ggsitemap/index/',
        );
        //谷歌pc
        $this->googleObj = new Google($config);

        //控制起始
        if (file_exists($this->cidfile)) {
            $str            = file_get_contents($this->cidfile);
            $this->startCid = intval($str);
        }

    }

    public function indexAction()
    {
        //初始化
        $this->init();

        while (true) {
            //获取数据
            $comArr = array(
              array('cid' => 1),
              array('cid' => 2),
              array('cid' => 3),
              array('cid' => 4),
              array('cid' => 5),
              array('cid' => 6),
              array('cid' => 7),
            );

            //如果有数据
            if (is_array($comArr) && !empty($comArr)) {
                foreach ($comArr as $value) {

                    $temp = array(
                        'url' => array(
                            'loc'     => $this->createMobileUrl($value['cid']),
                            'lastmod' => date('Y-m-d'),
                            'mobile'  => 'mobile',
                        ),
                    );
                    try{//防止有异常导致，异常中断，导致数据没有保存
                      //百度mobile版本
                      $this->baiduMobileObj->add($temp);

                      unset($temp['url']['mobile']);
                      //好搜
                      $this->haosouMobileObj->add($temp);
                      //搜狗
                      $this->sougouMobileObj->add($temp);

                      //构造数据
                      $temp = array(
                          'url' => array(
                              'loc'     => $this->createUrl($value['cid']),
                              'lastmod' => date('Y-m-d'),
                          ),
                      );
                      //百度pc版
                      $res = $this->baiduPcObj->add($temp);
                      //好搜
                      $this->haosouObj->add($temp);
                      //搜狗
                      $this->sougouObj->add($temp);
                      //谷歌
                      $this->googleObj->add($temp);
                    } catch(Exception $e) {
                      //最后关闭文件
                      $this->baiduPcObj->close();
                      $this->baiduMobileObj->close();
                      $this->haosouObj->close();
                      $this->haosouMobileObj->close();
                      $this->sougouObj->close();
                      $this->sougouMobileObj->close();
                      $this->googleObj->close();
                    }

                    if (!$res) {
                        //保存不成功，跳出while循环
                        break 2;
                    } else {
                        $this->startCid = $value['cid'];
                        file_put_contents($this->cidfile, $this->startCid);
                        chmod($this->cidfile, 0777);
                    }
                }
            } else {
                //没有数据,跳出循环
                break;
            }
        }
        //最后写入
        file_put_contents($this->cidfile, $this->startCid);
        chmod($this->cidfile, 0777);

        //最后关闭文件
        $this->baiduPcObj->close();
        $this->baiduMobileObj->close();
        $this->haosouObj->close();
        $this->haosouMobileObj->close();
        $this->sougouObj->close();
        $this->sougouMobileObj->close();
        $this->googleObj->close();

    }

    //生成url
    public function createUrl($cid)
    {
        return "http://abc.com/info/{$cid}/";
    }

    //移动版url
    public function createMobileUrl($cid)
    {
        return "http://m-abc.com/info/{$cid}/";
    }

    //根绝最后节点,获取最大cid,预留功能
    public function getMaxCidFromUrl($baiduObj)
    {
        $url = $baiduObj->getLastUrl();
        $p   = '/http:\/\/abc\.com\/info\/(\d+)_\w+\//';
        preg_match($p, $url, $match);
        if (isset($match[1])) {
            return $match[1];
        } else {
            return 0;
        }
    }

}
