<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Semyon
 * Date: 31.03.13
 * Time: 22:29
 * To change this template use File | Settings | File Templates.
 */

class robokassa
{

    public $test = 0;

    public static function get()
    {
        return new self;
    }

    public function getConfig()
    {
        return Yii::app()->params['payMethods'][__CLASS__];
    }

    public function getForm($order)
    {
        $config = $this->getConfig();
        if ($this->test) $url = "http://test.robokassa.ru/Index.aspx";
        else $url = "https://auth.robokassa.ru/Merchant/Index.aspx";
        $crc = md5("{$config['user']}:{$order->total}:{$order->id}:{$config['pass1']}");
        $item = preg_replace('/\s+/', ' ', implode(' ', $order->items));
        $type = $order->pay_info['type'];
        Yii::app()->controller->redirect($url . "?" .
        "MrchLogin={$config['user']}&OutSum={$order->total}&InvId={$order->id}&IncCurrLabel={$order->pay_info['type']}" .
        "&Desc={$item}&SignatureValue={$crc}&Encoding=utf-8");

        return true;
    }

    public function testPay($data)
    {
        $config = $this->getConfig();

        $out_summ = $data["OutSum"];
        $inv_id = $data["InvId"];
        $crc = strtoupper($data["SignatureValue"]);
        $my_crc = strtoupper(md5("{$out_summ}:{$inv_id}:{$config['pass2']}"));

        if ($my_crc != $crc) return $this->getResult('Security check failed');
        if (empty($inv_id)) return $this->getResult('Incorrect order_id');
        if (!$order = Order::model()->findByPk($inv_id)) return $this->getResult('Unknown order_id');
        if ($order->status_id != 0) return $this->getResult('Order is not ready to pay');
        if ((int)$order->total != (int)$out_summ) return $this->getResult('Amount check failed');
        $order->status_id = 2;
        if (!$order->save(false, array('pay_status'))) return $this->getResult('Status save failed');
        return $this->getResult('ok' . $inv_id, 1);
    }

    public function getApi($type)
    {
        $function = 'get' . ucfirst($type) . 'Url';
        $url = $this->{$function}();
        $data = new SimpleXMLElement($url, 0, 1);

//      CVarDumper::dump($data,10,1);die;

        $result = array();
        foreach ($data->Groups->Group as $row) {
            foreach ($row->Items->Currency as $val)
                $result[] = array(
                    'name' => (string)$val['Name'],
                    'code' => (string)$val['Label'],
                    'type' => (string)$row['Description'],
                );
        }

        return $result;
    }

    protected function getPaymodeUrl()
    {
        $config = $this->getConfig();
        return "https://merchant.roboxchange.com/WebService/Service.asmx/GetCurrencies?MerchantLogin={$config['user']}&Language=" . Yii::app()->language;
    }

    public function getResult($data, $result = 0)
    {
        exit("{$data}\n");
        return $result;
    }

}