<?php

namespace Iodev\Whois\Loaders;

use Iodev\Whois\Exceptions\ConnectionException;

class CurlLoader extends BaseLoader
{
    /** @var array */
    protected $options = [];

    /** @var resource */
    protected $curl = null;

    /** @var resource */
    protected $handle = null;

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $opts
     * @return $this
     */
    public function setOptions(array $opts)
    {
        $this->options = $opts;
        return $this;
    }

    /**
     * @param array $opts
     * @return $this
     */
    public function replaceOptions(array $opts)
    {
        $this->options = array_replace($this->options, $opts);
        return $this;
    }

    /**
     * @throws ConnectionException
     */
    protected function load()
    {
        $this->handle = fopen('php://temp','r+');
        if (!$this->handle) {
            throw new ConnectionException('Query stream not created');
        }
        fwrite($this->handle, $this->query);
        rewind($this->handle);

        $this->curl = curl_init();
        if (!$this->curl) {
            throw new ConnectionException('Curl not created');
        }
        curl_setopt_array($this->curl, array_replace($this->options, [
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROTOCOLS => CURLPROTO_TELNET,
            CURLOPT_URL => "telnet://{$this->whoisHost}:43",
            CURLOPT_INFILE => $this->handle,
        ]));

        $this->loadedText = curl_exec($this->curl);
        if ($this->loadedText === false) {
            throw new ConnectionException(curl_error($this->curl), curl_errno($this->curl));
        }
    }

    protected function teardown()
    {
        parent::teardown();
        if ($this->curl) {
            curl_close($this->curl);
        }
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
