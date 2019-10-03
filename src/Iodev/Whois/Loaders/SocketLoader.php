<?php

namespace Iodev\Whois\Loaders;

use Iodev\Whois\Exceptions\ConnectionException;

class SocketLoader extends BaseLoader
{
    /** @var resource */
    protected $handle;

    /**
     * @throws ConnectionException
     */
    protected function load()
    {
        $errno = null;
        $errstr = null;
        $this->handle = @fsockopen($this->whoisHost, 43, $errno, $errstr, $this->timeout);
        if (!$this->handle) {
            throw new ConnectionException($errstr, $errno);
        }
        if (false === fwrite($this->handle, $this->query)) {
            throw new ConnectionException("Query cannot be written");
        }
        $this->loadedText = "";
        while (!feof($this->handle)) {
            $chunk = fread($this->handle, 8192);
            if (false === $chunk) {
                throw new ConnectionException("Response chunk cannot be read");
            }
            $this->loadedText .= $chunk;
        }
    }

    protected function teardown()
    {
        parent::teardown();
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

}
