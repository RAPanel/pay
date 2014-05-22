<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Semyon
 * Date: 31.03.13
 * Time: 22:29
 * To change this template use File | Settings | File Templates.
 */
class pay2pay
{

    public $test = 1;

    public static function get()
    {
        return new self;
    }

    public function getConfig()
    {
        return Yii::app()->controller->module->config[__CLASS__];
    }

    public function getSettings()
    {
        $config = $this->getConfig();
        return array(
            'version' => '1.3',
            'merchant_id' => $config['merchant_id'],
            'language' => 'ru',
            'order_id' => '0',
            'amount' => '0',
            'currency' => 'RUB',
            'description' => 'Заказ',
            'test_mode' => $this->test,
        );
    }

    public function getRequest($data = array())
    {
        $settings = $this->getSettings();
        $data = array_merge($settings, $data);
        $result = $this->array2xml($data, new SimpleXMLElement('<request/>'));
        return $result->asXML();
    }

    public function array2xml($data, $xml)
    {
        foreach ($data as $key => $val)
            if (is_array($val)) $this->array2xml($val, $xml->addChild($key));
            else $xml->addChild($key, (string)$val);
        return $xml;
    }

    public function getResult($message = '', $status = 0)
    {
        $result = new SimpleXMLElement('<result/>');
        $result->addChild('status', $status ? 'yes' : 'no');
        $result->addChild('error_msg', $message);
        return $result->asXML();
    }

    public function getForm($order)
    {
        $data = array(
            'order_id' => $order->id,
            'amount' => $order->total,
            'description' => CHtml::encode($order->items),
        );
        if (!empty($order->pay_info['type'])) $data['paymode']['code'] = $order->pay_info['type'];
        return $this->form($data);
    }

    public function form($data)
    {
        $config = $this->getConfig();
        $xml = $this->getRequest($data);
        $sign = md5($config['secret_key'] . $xml . $config['secret_key']);

        $result = CHtml::form('https://merchant.pay2pay.com/?page=init', 'post', array('id' => 'order_form'));
        $result .= CHtml::hiddenField('xml', base64_encode($xml));
        $result .= CHtml::hiddenField('sign', base64_encode($sign));
        $result .= CHtml::submitButton('Перейти к оплате');
        $result .= CHtml::endForm();
        $result .= '<script>document.getElementById("order_form").submit();</script>';

        return $result;
    }

    public function testPay($data)
    {
        $config = $this->getConfig();
        $xml = base64_decode(str_replace(' ', '+', $data['xml']));
        $sign = base64_decode(str_replace(' ', '+', $data['sign']));

        $data = simplexml_load_string($xml);

        if ($data->status != 'success') return $this->getResult('Incorrect pay status');
        if (empty($data->order_id)) return $this->getResult('Incorrect order_id');
        if (!$order = Order::model()->findByPk($data->order_id)) return $this->getResult('Unknown order_id');
        if ($order->pay_status != 0) return $this->getResult('Order is not ready to pay');
        if ($sign != md5($config['hidden_key'] . $xml . $config['hidden_key'])) return $this->getResult('Security check failed');
        if ((int)$order->total != (int)$data->amount) return $this->getResult('Amount check failed');
        $order->pay_status = 1;
        if (!$order->save(false, array('pay_status'))) return $this->getResult('Status save failed');
        return $this->getResult('ok', 1);
    }

    public function getApi($type)
    {
        $config = $this->getConfig();
        $function = 'get' . ucfirst($type) . 'Xml';
        $ch = curl_init("https://merchant.pay2pay.com/output/?module=xml");
        $xml = $this->{$function}(); //xml-документ
        $api_key = $config['api_key']; //ключ API-интерфейса
        $sign = md5($api_key . $xml . $api_key);
        $params = "xml=" . base64_encode($xml) . "&sign=" . base64_encode($sign);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        $data = new SimpleXMLElement($response);

        return current($data->results->paymode_list);
    }

    protected function getPaymodeXml()
    {
        $config = $this->getConfig();
        $result = new SimpleXMLElement('<result/>');
        $result->addChild('type', 'Paymode');
        $result->addChild('version', 1);
        $result->addChild('merchant_id', $config['merchant_id']);
        return $result->asXML();
    }
}