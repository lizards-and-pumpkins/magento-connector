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
        $string = '<xml version="1.0"?><root />';

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
        $string = '<xml version="1.0"?><root />';

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
     * @return PHPUnit_Framework_MockObject_MockObject|LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private function getConfigStub($target)
    {
        $config = $this->getMock(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class);
        $config->method('getLocalPathForProductExport')->willReturn($target);
        $config->method('getLocalFilenameTemplate')->willReturn('magento.xml');
        return $config;
    }
}


if (!class_exists(Mage_Core_Exception::class)) {
    class Mage_Core_Exception extends Exception
    {
        protected $_messages = [];

        public function addMessage(Mage_Core_Model_Message_Abstract $message)
        {
            if (!isset($this->_messages[$message->getType()])) {
                $this->_messages[$message->getType()] = [];
            }
            $this->_messages[$message->getType()][] = $message;
            return $this;
        }

        public function getMessages($type = '')
        {
            if ('' == $type) {
                $arrRes = [];
                foreach ($this->_messages as $messageType => $messages) {
                    $arrRes = array_merge($arrRes, $messages);
                }
                return $arrRes;
            }
            return isset($this->_messages[$type]) ? $this->_messages[$type] : [];
        }

        /**
         * Set or append a message to existing one
         *
         * @param string $message
         * @param bool $append
         * @return Mage_Core_Exception
         */
        public function setMessage($message, $append = false)
        {
            if ($append) {
                $this->message .= $message;
            } else {
                $this->message = $message;
            }
            return $this;
        }
    }
}
