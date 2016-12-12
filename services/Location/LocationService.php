<?php
/**
 * Created by PhpStorm.
 * User: yy
 * Date: 2016/12/7
 * Time: 15:20
 */

namespace App\Services\Location;

use App\Models\Area;

class LocationService
{
    /**
     * 对高德地图的街道信息处理
     *
     * @param array $keywords
     *
     * @return array
     */
    public function handleLocationInfo(Array $keywords)
    {
        // 查询是否已经有中国和四川的数据
        $reQueryC = $this->queryPid('中国');
        $reQueryS = $this->queryPid('四川省');
        if(is_null($reQueryC)){
            // 插入中国的数据
            Area::insert([ 'no'=> '1','name'=>'中国','level'=> 0,'adcode'=>100000]);
        }
        if(is_null($reQueryS)){
            // 查询得到中国的id，插入四川的数据
            $chinaInfo = $this->queryPid('中国')->id;
            Area::insert([
                'no'    => '1',
                'pid'   =>$chinaInfo,
                'node'  =>$chinaInfo,
                'name'  =>'四川省',
                'level' => 10,
                'adcode'=>510000,
            ]);
        }
        $key    = '';			// 此处为购买高德产品-->key
        $subdistrict    = '1';
        $showbiz        = 'false';
        $output         = 'JSON';
        // 将高德API返回信息处理之后写入数据库
        foreach($keywords as $keyword)
        {
        // 获取高德地图所有街道信息
        $streetInfo = $this->getLocationInfo($key,$keyword,$subdistrict,$showbiz,$output);
        // 转化成数组
        $preData = json_decode($streetInfo,true);
        // 获得所需数组
        $preData = $preData['districts']['0'];
        // 处理获得数组
        $handleInfo = $this->handleInfo($preData);
        // 写入数据库
        $this->insertMysql($handleInfo);
        }
    }

    /**
     * 对高德地图的街道信息处理
     *
     * @param $key              用户购买的高德key
     * @param $keywords         父级区域的关键信息
     * @param $subdistrict      下级区域范围
     * @param $showbiz          是否显示行政区
     * @param $output           返回参数形式
     *
     * @return array
     */
    public function getLocationInfo($key,$keywords,$subdistrict,$showbiz,$output)
    {
        $url = 'http://restapi.amap.com/v3/config/district?';
        $data['key']            = $key;
        $data['keywords']       = $keywords;
        $data['subdistrict']    = $subdistrict;
        $data['showbiz']        = $showbiz;
        $data['output']         = $output;

        // 拼凑url后面的信息
        $handle = http_build_query($data);
        // 拼接整个的url
        $url = $url.$handle;
        // 初始化一个url句柄
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        $reInfo = curl_exec($ch);;
        curl_close($ch);

        return $reInfo;
    }

    /**
     * 对高德数据进行处理
     *
     * @param $inData
     *
     * @return array
     */
    public function handleInfo($inData)
    {
        $a = 1;
        $reData[] = [];
        $LocationData = [];
        // 父级名字
        $pname = $inData['name'];
        // 获取当前区域的父级id
        $pinfo = $this->queryPid($pname);
        $LocationData['pid'] = $pinfo['id'];
        // 拼凑node
        $LocationData['node'] = $pinfo['node'].','.$pinfo['id'];
        // 定义
        $streetInfos = $inData['districts'];
        // foreach循环得到所有的街道信息
        foreach($streetInfos as $streetInfo)
        {
            // 获取当前区域的no
            $LocationData['no'] = $a;
            // 获取当前区域的名字
            $LocationData['name']   = $streetInfo['name'];
            $LocationData['adcode'] = $streetInfo['adcode'];
            //获取当前区域的级别
            // 区域等级 0:国家 10: 省级 20: 市级: 30: 区级 40: 街道
            switch ($streetInfo['level'])
            {
                case 'country':
                    $LocationData['level'] = 0;
                    break;
                case 'province':
                    $LocationData['level'] = 10;
                    break;
                case 'city':
                    $LocationData['level'] = 20;
                    break;
                case 'district':
                    $LocationData['level'] = 30;
                    break;
                case 'street':
                    $LocationData['level'] = 40;
                    break;
            }
            $reData[] =[
                'no'    => $LocationData['no'],
                'pid'   => $LocationData['pid'],
                'node'  => $LocationData['node'],
                'name'  => $LocationData['name'],
                'level' => $LocationData['level'],
                'adcode'=> $LocationData['adcode'],
            ];
            $a++;
        }
        unset($reData['0']);
        return $reData;
    }

    /**
     * 查询父级信息
     *
     * @param $localName
     */
    public function queryPid($localName)
    {
        $reData = Area::where('name',$localName)
            ->first();
        return $reData;
    }

    /**
     * 写入数据库
     *
     * @param $arrayData
     */
    public function insertMysql($arrayData)
    {
        foreach($arrayData as $data)
        {
            // 判断是否在数据库中已经存在相同的街道信息
            $reData = Area::where('adcode',$data['adcode'])
                ->where('name',$data['name'])
                ->first();
            if(!is_null($reData))
            {
                break;
            }
            Area::insert([
                'no'    => $data['no'],
                'pid'   => $data['pid'],
                'node'  => $data['node'],
                'name'  => $data['name'],
                'level' => $data['level'],
                'adcode'=> $data['adcode'],
            ]);
        }
    }
}