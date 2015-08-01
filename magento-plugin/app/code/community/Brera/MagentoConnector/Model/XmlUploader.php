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
     * @var resource
     */
    private $stream;

    /**
     * @param string $target
     */
    function __construct($target)
    {
        $this->checkTarget($target);
        $this->target = $target;
    }

    public function upload($xmlString)
    {
        file_put_contents($this->target, $xmlString);
    }

    /**
     * @return resource
     */
    public function getUploadStream()
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
