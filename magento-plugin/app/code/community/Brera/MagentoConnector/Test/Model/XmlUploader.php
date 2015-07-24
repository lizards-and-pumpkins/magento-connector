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
        return array(
            array('ssh2.scp://'),
            array('ssh2.sftp://'),
            array('file://'),
        );
    }

    /**
     * @return string[][]
     */
    public function getDisallowedProtocols()
    {
        return array(
            array('http://'),
            array('ftp://'),
            array('php://'),
            array('zlib://'),
            array('data://'),
            array('glob://'),
            array('phar://'),
            array('ssh2://'),
            array('rar://'),
            array('ogg://'),
            array('expect://'),
            array('compress.zlib://'),
            array('compress.bzip2://'),
            array('zip://'),
            array('ssh2.shell://'),
            array('ssh2.exec://'),
            array('ssh2.tunnel://'),
        );

    }
}
