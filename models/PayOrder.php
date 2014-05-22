<?php

class PayOrder extends Order
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}