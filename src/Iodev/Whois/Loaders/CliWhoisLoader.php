<?php

namespace Iodev\Whois\Loaders;

use Iodev\Whois\Exceptions\ConnectionException;

class CliWhoisLoader extends BaseLoader
{
    public function __construct(string $cmdPath = '/usr/bin/whois',int $timeout = 60)
    {
        parent::__construct($timeout);
        $this->cmdPath = $cmdPath;
    }

    /** @var resource */
    protected $handle;

    /** @var string */
    protected $cmdPath;

    /**
     * @throws ConnectionException
     */
    protected function load()
    {
        $cmd = $this->buildCommand();
        $this->handle = popen($cmd, 'r');
        if (!$this->handle) {
            throw new ConnectionException("Cannot create handle for popen '$cmd'");
        }
        stream_set_timeout($this->handle, $this->timeout);
        $this->loadedText = "";
        while (!feof($this->handle)) {
            $chunk = fread($this->handle, 8192);
            if (false === $chunk) {
                throw new ConnectionException("Response chunk cannot be read");
            }
            $this->loadedText .= $chunk;
        }
    }

    /**
     * @return string
     */
    protected function buildCommand() {
        return "{$this->cmdPath} -h {$this->whoisHost} {$this->query}";
    }

    protected function teardown()
    {
        parent::teardown();
        if ($this->handle) {
            pclose($this->handle);
            $this->handle = null;
        }
    }
}
