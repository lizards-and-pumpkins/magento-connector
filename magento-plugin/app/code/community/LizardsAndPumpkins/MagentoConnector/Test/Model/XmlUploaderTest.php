<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
 */
class LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploaderTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getAllowedProtocols
     * @param string $protocol
     */
    public function testAllowProtocols($protocol)
    {
        $target = $protocol . 'some/path';
        $config = $this->getConfigStub($target);
        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader($config);
        $this->assertInstanceOf(LizardsAndPumpkins_MagentoConnector_Model_XmlUploader::class, $uploader);
    }

    /**
     * @dataProvider getDisallowedProtocols
     * @param string $protocol
     */
    public function testDisallowedProtocols($protocol)
    {
        $this->setExpectedException(Mage_Core_Exception::class);

        $target = $protocol . 'some/path';
        $config = $this->getConfigStub($target);
        new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader($config);
    }

    /**
     * @return array[]
     */
    public function getAllowedProtocols()
    {
        return [
            ['ssh2.scp://'],
            ['ssh2.sftp://'],
            ['file://'],
        ];
    }

    /**
     * @return array[]
     */
    public function getDisallowedProtocols()
    {
        return [
            ['http://'],
            ['ftp://'],
            ['php://'],
            ['zlib://'],
            ['data://'],
            ['glob://'],
            ['phar://'],
            ['ssh2://'],
            ['rar://'],
            ['ogg://'],
            ['expect://'],
            ['compress.zlib://'],
            ['compress.bzip2://'],
            ['zip://'],
            ['ssh2.shell://'],
            ['ssh2.exec://'],
            ['ssh2.tunnel://'],
        ];

    }

    /**
     * @param string $target
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig|PHPUnit_Framework_MockObject_MockObject
     */
    private function getConfigStub($target)
    {
        $config = $this->getMock(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class);
        $config->method('getLocalPathForProductExport')->willReturn($target);
        $config->method('getLocalFilenameTemplate')->willReturn('magento.xml');
        return $config;
    }
}
