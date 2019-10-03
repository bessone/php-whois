<?php

namespace Iodev\Whois\Loaders;

use Iodev\Whois\Exceptions\ConnectionException;
use Iodev\Whois\Exceptions\WhoisException;
use Iodev\Whois\Helpers\TextHelper;

abstract class BaseLoader implements ILoader
{
    public function __construct($timeout = 60)
    {
        $this->setTimeout($timeout);
    }

    /** @var int */
    protected $timeout;

    /** @var string|bool */
    protected $origEnv = false;

    /** @var string  */
    protected $whoisHost = '';

    /** @var string  */
    protected $query = '';

    /** @var string  */
    protected $loadedText = '';

    abstract protected function load();

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = max(0, (int)$seconds);
        return $this;
    }

    /**
     * @param string $whoisHost
     * @param string $query
     * @return string
     * @throws ConnectionException
     * @throws WhoisException
     */
    public function loadText($whoisHost, $query)
    {
        $this->whoisHost = (string)$whoisHost;
        $this->query = (string)$query;
        try {
            $this->setup();
            $this->validateWhoisHost();
            $this->load();
            $this->postprocess();
            $this->validateResponse();
            return $this->loadedText;
        } finally {
            $this->teardown();
        }
    }

    protected function setup()
    {
        $this->loadedText = '';
        $this->origEnv = getenv('RES_OPTIONS');
        putenv("RES_OPTIONS=retrans:1 retry:1 timeout:{$this->timeout} attempts:1");
    }

    protected function teardown()
    {
        $this->origEnv === false ? putenv("RES_OPTIONS") : putenv("RES_OPTIONS={$this->origEnv}");
    }

    protected function postprocess() {
        $this->loadedText = TextHelper::toUtf8($this->loadedText);
    }

    /**
     * @throws ConnectionException
     */
    protected function validateWhoisHost() {
        if (!gethostbynamel($this->whoisHost)) {
            throw new ConnectionException("Host is unreachable: {$this->whoisHost}");
        }
    }

    /**
     * @throws WhoisException
     */
    protected function validateResponse()
    {
        if (preg_match('~^WHOIS\s+.*?LIMIT\s+EXCEEDED~ui', $this->loadedText, $m)) {
            throw new WhoisException($m[0]);
        }
    }
}
