<?php

class PayController extends CController
{
    public function actionIndex($id, $type)
    {
        $base = Order::model()->findByPk($id);
        $model = new $type();
        $data = $model->getForm($base);
        $this->renderText($data);
    }

    public function actionResult($type)
    {
        $model = new $type;
        $result = $model->testPay($_REQUEST);
        echo $result;
    }

    public function actionSuccess()
    {
        $this->pageTitle = 'Успешная оплата';
        $this->text('Оплата успешно проведена.');
    }

    public function actionFail()
    {
        $this->pageTitle = 'Ошибка оплаты';
        Yii::app()->user->setFlash('info', '');
        $this->text('Произошла ошибка при оплате заказа.');
    }

    public function text($text)
    {
        $result = CHtml::tag('h1', array(), $this->pageTitle);
        $result .= CHtml::tag('div', array('class' => 'message'), $text);
        $this->renderText(CHtml::tag('div', array('class'=>'container'), $result));
    }
}