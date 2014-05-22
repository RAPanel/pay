<?php

/**
 * @author ReRe Design studio
 * @email webmaster@rere-design.ru
 */
class PayModule extends CWebModule
{
    public $config = array();
    public function init()
    {
        YiiBase::setPathOfAlias('pay', YiiBase::getPathOfAlias('application.modules.pay'));

        $imports = array(
            'pay.models.*',
            'pay.components.*',
            'pay.controllers.*',
        );
        $this->setImport($imports);
        parent::init();
    }

}