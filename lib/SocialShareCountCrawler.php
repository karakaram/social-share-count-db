<?php

/*
 * This file is part of the Social Share Count DB package.
 */

class SocialShareCountCrawler
{
    /**
     * @var int
     */
    protected $timeout;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->timeout = 10;
    }

    public function requestTwitter($url)
    {
        $postUrl = rawurlencode($url);
        $requestUrl = 'http://jsoon.digitiminimi.com/twitter/count.json?url=' . $postUrl;
        $json = $this->getContentsWithCurl($requestUrl);
        $twitter = json_decode($json, true);
        return (isset($twitter['count'])) ? (int)$twitter['count'] : 0;
    }

    public function requestFacebook($url)
    {
        $postUrl = rawurlencode($url);
        # FIXME
        $url = 'https://graph.facebook.com/v2.12?fields=engagement&id=' . $postUrl . '&access_token=appid|appsecret';
        $json = $this->getContentsWithCurl($url);
        $facebook = json_decode($json, true);
        return isset($facebook['engagement']) ? array_sum($facebook['engagement']) : 0;
    }

    public function requestHatenaBookmark($url)
    {
        $postUrl = rawurlencode($url);
        $url = 'http://api.b.st-hatena.com/entry.count?url=' . $postUrl;
        $count = $this->getContentsWithCurl($url);
        return (int)$count;
    }

    /**
     * @param string $url
     * @return mixed
     */
    protected function getContentsWithCurl($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Social Share Count Crawler');
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $curl_results = curl_exec($curl);

        if (false === $curl_results) {
            error_log('[ERROR] ' . __FILE__ . ' ' . __FUNCTION__ . ' ' . $url . ' ' . curl_error($curl));
        }

        curl_close ($curl);

        return $curl_results;
    }
}
