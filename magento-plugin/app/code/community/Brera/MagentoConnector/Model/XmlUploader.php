<?php

class Brera_MagentoConnector_Model_XmlUploader
{
    const PROTOCOL_DELIMITER = '://';

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $xmlString;

    /**
     * @param string $xmlString
     * @param string $target
     */
    function __construct($xmlString, $target)
    {
        $this->checkTarget($target);
        $this->target = $target;
        $this->xmlString = $xmlString;
    }

    public function upload()
    {
        file_put_contents($this->target, $this->xmlString);
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
