<?php

declare(strict_types=1);

class LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkins_VersionController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
