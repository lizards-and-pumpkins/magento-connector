<?php

class Brera_MagentoConnector_Test_Model_XmlUploader extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getAllowedProtocols
     * @param string $protocol
     */
    public function testAllowProtocols($protocol)
    {
        $string = '<xml version="1.0"?><root />';

        $target = $protocol . 'some/path';
        $uploader = new Brera_MagentoConnector_Model_XmlUploader($string, $target);
        $this->assertInstanceOf(Brera_MagentoConnector_Model_XmlUploader::class, $uploader);
    }

    /**
     * @dataProvider getDisallowedProtocols
     * @param string $protocol
     */
    public function testDisallowedProtocols($protocol)
    {
        $this->setExpectedException(Mage_Core_Exception::class);
        $string = '<xml version="1.0"?><root />';

        $target = $protocol . 'some/path';
        new Brera_MagentoConnector_Model_XmlUploader($string, $target);
    }

    /**
     * @return string[][]
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
     * @return string[][]
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
}
