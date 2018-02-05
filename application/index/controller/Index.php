<?php
namespace app\index\controller;
use \think\Request;
use \think\Db;

class Index
{
    public function index()
    {
        /**
         * 逻辑 获取传来的参数
         * 
         * 年分 月份 金额
         * $year $month $money
         * 
         * 获取用户对象组
         * 获取商城对象组
         * 
         * 获取商城对象里面的每个产品。
         * 
         * 生成订单
         * 生成订单物品
         * 统计金额
         * 如果不够 就继续增加订单 如果够了就跳出循环
        
         * */
        $year=Request::instance()->get("year");
        $month=Request::instance()->get("month");
        $money=Request::instance()->get("money");
        
        $money=$money*10000;
      
        
        if(empty($year)||empty($month)||empty($money))
        {
            echo "year month money  请用get方式传入不能为空";
        }
        else
        {
            $userArr=$this->getCustomerArray();
            $storeArr=$this->getStoreArray();
            if(!empty($userArr))
            {
                if(!empty($storeArr))
                {
                 
                    $orderAllMoney=0;
                   
                    
                    while($orderAllMoney<$money)
                    {
                        $orderAllMoney+=(float)$this->bornOrder($userArr, $storeArr,$year,$month);
                    }
                    
                    echo "数据产生完毕，请检查数据".date("Y-m-d H:i:s");
                    
                }else
                {
                    echo "没有一个商铺";
                }
            }
            else
            {
                echo "没有一个用户";
            }
        }
       
    }
    
    /**
     * 传入用户组，和商城组随机产生一个订单
     * 
     *   // 随机函数
        mt_rand($min,$max);
        
                         返回的是订单金额
        
     * */
    public function bornOrder($userArr,$storeArr,$year,$month)
    {
        /**
         * 随机生成用户 商城 和商城中的货物
         * */
        $userlen=count($userArr);
        $storelen=count($storeArr);
        $userIndex=mt_rand(0,$userlen-1);
        $storeIndex=mt_rand(0,$storelen-1);
        
        
        $cuUser=$userArr[$userIndex];
        $cuStore=$storeArr[$storeIndex];
        
        $goodsLen=count($cuStore["goods"]);
        $goodsIndex=mt_rand(0,$goodsLen-1);
        $cugood=$cuStore["goods"][$goodsIndex];
        
        /*
         * 取1到当前月日的数据
         * */
        
        $cumonthHasDay=date("t",strtotime("$year"."-"."$month"));
        
        $day=mt_rand(1,$cumonthHasDay);
        if($day<10)
        {
            $day="0".$day;
        }
       
        /**组装订单数据 order_info*/
        //年月日五位随机数
        $orderSn=$year.$month.$day.$this->randomkeys(5);
        $province=$cuUser["address"]["province"];
        $city=$cuUser["address"]["city"];
        $district=$cuUser["address"]["district"];
        $address=$cuUser["address"]["address"];
        $mobile=$cuUser["address"]["mobile"];
        $email=$cuUser["email"];
        
        
        
        
        //一个订单中只有一个商品
        $good_amount=$order_amount=$inv_money=$cugood["shop_price"];//货物金额 //订单金额//发票金额
        
        //开始时间
        $startTime=strtotime($year."-".$month."-".$day);
        
        $add_time=$startTime;
        $confirm=$startTime+24*60*60;//一天后下单
        $paytime=$startTime+2*24*60*60;//七天后付款
        /*
         * 生成订单 订单都是522 已经收获的
         * */
        $orderData=array(
            "order_sn"=>$orderSn, 
            "user_id"=>$cuUser["user_id"],
            "order_status"=>5,
            "shipping_status"=>2,
            "pay_status"=>2,
            "province"=>$province,
            "city"=>$city,
            "district"=>$district,
            "address"=>$address,
            "mobile"=>$mobile,
            "email"=>$email,
            "best_time"=>"仅工作日送货",
            "shipping_id"=>3,
            "shipping_name"=>"顺丰速运",
            "pay_id"=>8,
            "pay_name"=>"微信扫码支付",
            "how_oos"=>"等待所有商品备齐后再发",
            "goods_amount"=>$good_amount,
            "order_amount"=>$order_amount,
            "inv_money"=>$inv_money,
            "referer"=>"网站自营",
            "add_time"=>$add_time,
            "confirm_time"=>$confirm,
            "pay_time"=>$paytime
        );
        $order_id=$this->addOrder($orderData);
       
        
        
        $goods_id=$cugood["goods_id"];
        $goods_name=$cugood["goods_name"];
        $goods_sn=$cugood["goods_sn"];
        $market_price=$cugood["market_price"];
        $goods_price=$cugood["shop_price"];
        
        
        /** **生成订单详情*
         * 组装物品数据
         * order_goods
         * */
        $orderGoods=array(
            "order_id"=>$order_id,
            "goods_id"=>$goods_id,
            "goods_name"=>$goods_name,
            "goods_sn"=>$goods_sn,
            "goods_number"=>1,
            "market_price"=>$market_price,
            "goods_price"=>$goods_price
        );
        $this->addOrderGoods($orderGoods);
        
        return $good_amount;
        
        
       
    }
    
    public function randomkeys($random_length)
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyz";
        $random_string = '';
        for ($i = 0; $i < $random_length; $i++) {
            $random_string .= $chars [mt_rand(0, strlen($chars) - 1)];
        }
        return $random_string;
        
    }
   /**
    * 插入数据返回订单id
    * 
    * @param unknown $data
    * @return number|string*/ 
    public function addOrder($data)
    {
        $order_ID=Db::name('order_info')->insertGetId($data);
        return $order_ID;
    }
    
    public function addOrderGoods($data)
    {
        $rec_ID=Db::name('order_goods')->insertGetId($data);
        return $rec_ID;
    }
    
    
    
    
    /**
     * 获取用户对象组
     * 
     * 用户数组 每个用户都有一个地址对象
     * 
     * */
    public function getCustomerArray()
    {
       $users= Db::name("users")->select();
       $newUser=array();
       
       //echo "count=".count($users);
       //echo "<br/>";
       for($i=0;$i<count($users);$i++)
       {
          // echo $i;
           
           //echo"<br/>";
          // var_dump($users[$i]);
           $userid=$users[$i]["user_id"];
           $address=Db::name("user_address")->where("user_id='$userid'")->select();
           //var_dump($address);
           if($address)
           {
               $address=$address[0];
               $users[$i]["address"]=$address;
               //
               $newUser[]=$users[$i];
           }
       }
       return $newUser;
    }
    
    /**
     * 获取商城对象组
     * 
     * 商城对象，每个对象有产品属性
     * 
     * */
    public function getStoreArray()
    {
        //找出供应商
        $supplierArray=Db::name('supplier')->where(" status='1' ") ->select();
        $newSupplierArray=array();
        for($i=0;$i<count($supplierArray);$i++)
        {
            $eachSupplider=$supplierArray[$i];
            $suppliderid=$eachSupplider['supplier_id'];
            //根据供应商获取商品列表
            $goods=Db::name('goods')->where("supplier_id ='$suppliderid' ")->select();
            if($goods)
            {
                $eachSupplider["goods"]=$goods;
                $newSupplierArray[]=$eachSupplider;
            }
        }
        //商城自营获取到后加入到 商城数组中去
        $supplier=["supplier_id"=>"0","supplier_name"=>"自营"];
        $goods=Db::name('goods')->where("supplier_id ='0' ")->select();
        $supplier["goods"]=$goods;
        $newSupplierArray[]=$supplier;
        return $newSupplierArray;
    }
}
