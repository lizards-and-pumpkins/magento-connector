<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Container_Session_Info
    extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _saveCache($data, $id, $tags = [], $lifetime = null)
    {
        return false;
    }
}
