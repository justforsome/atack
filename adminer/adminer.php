<?php

$adminer = new Adminer();
$adminer->execute();

class Adminer
{
    private $version;

    private $printedLines;

    private $screenWidth, $screenHeight;

    private $stdin;

    public function __construct()
    {
        $config = require(__DIR__ . '/../config.php');
        $this->version = $config['version'];

        $this->stdin = fopen('php://stdin', 'r');
        stream_set_blocking($this->stdin, 0);
        system('stty cbreak -echo');

        $this->printedLines = null;

        $this->screenWidth = exec('tput cols');
        $this->screenHeight = exec('tput lines');
    }

    public function __destruct()
    {
        // Reset the tty back to the original configuration
        system('stty -cbreak echo');
//        system("stty '" . $this->ttyTerm . "'");
    }

    public function execute()
    {
        echo "\r\n";

        while(true) {
            $this->displayStats();

            for($i=0;$i<50;$i++) {
                usleep(100000);
                $keypress = fgets($this->stdin);
                switch($keypress) {
                    case 'x':
                    case 'X':
                    case 'ч':
                    case 'Ч':
                        break (3);
                }
            }
        }
    }

    private function displayStats()
    {
        $oldPrintedLines = $this->printedLines ?: $this->screenHeight;
        // Move to first line
        for ($i = 0; $i < $this->printedLines; $i++) {
            echo "\r\033[K\033[1A\r\033[K\r";
//            echo "\r\033[1A\r";
        }

        $runtimeDir = __DIR__ . '/../runtime/';
        if (!file_exists($runtimeDir)) {
            echo "\033[41m Stats are not available \033[0m\r\n";
            $this->printedLines = 1;
        } else {
            $files = scandir($runtimeDir);
            $urlInfo = [];
            $numeratorTotal = .0;
            $denominatorTotal = .0;
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                try {
                    $data = unserialize(file_get_contents($runtimeDir . '/' . $file));

                    if(!isset($data['url'], $data['requestLog']) || !is_iterable($data['requestLog'])) {
                        continue;
                    }

                    if(isset($data['startTime'], $data['endTime'])) {
                        $diff = $data['startTime']->diff($data['endTime']);
                        $diffSec = (float)($diff->i * 60 + $diff->s);
                    } else {
                        $diffSec = 0;
                    }

                    $url = $data['url'];

                    if (!isset($urlInfo[$url])) {
                        $urlInfo[$url] = [
                            'numerator' => .0,
                            'denominator' => .0,
                            'totalResponseTime' => .0,
                            'countRequests' => 0,
                        ];
                    }
                    $numeratorCurrent = (float) count($data['requestLog']) * $diffSec;
                    $urlInfo[$url]['numerator'] += (float) $numeratorCurrent;
                    $numeratorTotal += (float) $numeratorCurrent;

                    $urlInfo[$url]['denominator'] += $diffSec;
                    $denominatorTotal += $diffSec;

                    foreach ($data['requestLog'] as $requestLogItem) {
                        $urlInfo[$url]['totalResponseTime'] += $requestLogItem['responseTime'];
                        if (!isset($urlInfo[$url]['responseCodes'][$requestLogItem['httpCode']])) {
                            $urlInfo[$url]['responseCodes'][$requestLogItem['httpCode']] = 0;
                        }
                        $urlInfo[$url]['responseCodes'][$requestLogItem['httpCode']]++;
                    }

                    $urlInfo[$url]['countRequests'] += count($data['requestLog']);

                    arsort($urlInfo[$url]['responseCodes']);
                } catch (\Exception $ex) {
                    continue;
                }
            }

            $versionString = 'v' . $this->version;

            echo "\033[42mTotal requests per second: " .
                str_pad(
                    number_format($denominatorTotal > 0 ? $numeratorTotal / $denominatorTotal : 0, 2),
                    $this->screenWidth - 27 - strlen($versionString), ' '
                ) . $versionString . "\r\n";
            echo str_repeat('-', $this->screenWidth) . "\033[0m";

            // URL | Response |    RPS |  Time
            // url | 200,404> | 100.00 | 004.3s

            // calculating cols width
            $availableUrlWidth = $this->screenWidth - 29;
            $maxUrlWidth = 4;
            foreach ($urlInfo as $url => &$urlInfoItem) {
                $urlLength = strlen($url);
                if ($urlLength > $availableUrlWidth) {
                    $urlInfoItem['url'] = substr($url, 0, $availableUrlWidth - 3) . '...';
                    $maxUrlWidth = $availableUrlWidth;
                    continue;
                } elseif ($maxUrlWidth < $urlLength) {
                    $maxUrlWidth = $urlLength;
                }

                $urlInfoItem['url'] = $url;
            }
            unset($urlInfoItem);

            // Header
            echo 'URL' . str_repeat(' ', $maxUrlWidth - 3) . " | Response |    RPS |  Time\r\n";

            foreach ($urlInfo as $urlInfoItem) {
                // URL
                echo str_pad($urlInfoItem['url'], $maxUrlWidth + 2, ' ');

                // Response
                $responseCodeCol = ' ';
                $responseCodeCount = 0;
                foreach ($urlInfoItem['responseCodes'] as $responseCode => $responseCount) {
                    switch(++$responseCodeCount) {
                        case 2:
                            $responseCodeCol .= ',';
                            break;
                        case 3:
                            $responseCodeCol .= '>';
                            break(2);
                    }
                    $responseCodeCol .= $responseCode;
                }
                echo str_pad($responseCodeCol, 10, ' ', STR_PAD_RIGHT);

                // RPS
                if ($urlInfoItem['denominator'] > 0) {
                    $itemRps = $urlInfoItem['numerator'] / $urlInfoItem['denominator'];
                } else {
                    $itemRps = .0;
                }
                echo str_pad(
                    number_format($itemRps, 2), 8, ' ', STR_PAD_LEFT);

                // Time
                if((float) $urlInfoItem['countRequests'] > 0) {
                    $itemTime = $urlInfoItem['totalResponseTime'] / (float) $urlInfoItem['countRequests'];
                } else {
                    $itemTime = .0;
                }
                echo str_pad(
                    number_format($itemTime,1, '.', '') . 's',
                    8, ' ', STR_PAD_LEFT);

                echo "\r\n";
            }

            echo "\r\n\033[42m Press [Ctrl+C] or [x] to exit \033[0m\r\n";

            $this->printedLines = count($urlInfo) + 5;
        }

        if($oldPrintedLines > $this->printedLines) {
            for($i=$oldPrintedLines;$i<$this->printedLines;$i++) {
                echo "\r\033[K\033[1A\r";
            }
        }
    }
}
