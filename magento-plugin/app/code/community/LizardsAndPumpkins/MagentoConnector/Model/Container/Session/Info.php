<?php

class YourModule_YourCompany_Model_Container_Purchased extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _saveCache($data, $id, $tags = [], $lifetime = null)
    {
        return false;
    }
}
