<?php

abstract class LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
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

    /**
     * @param string $target
     */
    protected function __construct($target)
    {
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
    public function writePartialXmlString($partialString)
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
     */
    private function checkTarget($target)
    {
        $protocol = strtok($target, self::PROTOCOL_DELIMITER) . self::PROTOCOL_DELIMITER;
        if (!in_array($protocol, $this->getAllowedProtocols())) {
            $message = sprintf('"%s" is not one of the allowed protocols: "%s"', $protocol,
                implode(', ', $this->getAllowedProtocols()));

            throw new Mage_Core_Exception($message);
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedProtocols()
    {
        return [
            'ssh2.scp://',
            'ssh2.sftp://',
            'file://',
            'php://',
        ];
    }
}
