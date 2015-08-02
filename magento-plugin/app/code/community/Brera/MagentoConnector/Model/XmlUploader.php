<?php

class Brera_MagentoConnector_Model_XmlUploader
{
    const PROTOCOL_DELIMITER = '://';

    /**
     * @var string
     */
    private $target;

    /**
     * @var resource
     */
    private $stream;

    function __construct()
    {
        $target = Mage::getStoreConfig('brera/magentoconnector/product_xml_target');
        $this->checkTarget($target);
        $this->target = $target;
    }

    /**
     * @param string $xmlString
     */
    public function upload($xmlString)
    {
        file_put_contents($this->target, $xmlString);
    }

    /**
     * @param string $partialString
     * @return int
     */
    public function writePartialString($partialString)
    {
        return fwrite($this->getUploadStream(), $partialString);
    }

    /**
     * @return resource
     */
    private function getUploadStream()
    {
        if (!$this->stream) {
            $this->stream = fopen($this->target, 'w');
        }

        return $this->stream;
    }

    /**
     * @param string $target
     * @throws Mage_Core_Exception
     */
    private function checkTarget($target)
    {
        $protocol = strtok($target, self::PROTOCOL_DELIMITER) . self::PROTOCOL_DELIMITER;
        if (!in_array($protocol, $this->getAllowedProtocols())) {
            $message = sprintf('"%s" is not one of the allowed protocols: "%s"', $protocol,
                implode(', ', $this->getAllowedProtocols()));
            Mage::throwException($message);
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedProtocols()
    {
        return array(
            'ssh2.scp://',
            'ssh2.sftp://',
            'file://',
        );
    }
}
