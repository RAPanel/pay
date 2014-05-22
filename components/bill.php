<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Semyon
 * Date: 31.03.13
 * Time: 22:29
 * To change this template use File | Settings | File Templates.
 */

class bill
{
    public function getForm($order)
    {
//        CVarDumper::dump($order,10,1);
//        die;
        Yii::app()->controller->widget('application.widgets.BillWidget.BillWidget', array(
            'payer'=>$order->pay_info,
            'order'=>$order,
        ));
        Yii::app()->end();
    }
}