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
        $requestUrl = 'http://api.b.st-hatena.com/entry.count?url=' . $postUrl;
        $count = $this->getContentsWithCurl($requestUrl);
        return (int)$count;
    }

    public function requestFacebook($url)
    {
        $postUrl = rawurlencode($url);
        $url = 'http://graph.facebook.com/' . $postUrl;
        $json = $this->getContentsWithCurl($url);
        $facebook = json_decode($json, true);
        return isset($facebook['shares']) ? (int)$facebook['shares'] : 0;
    }


    public function requestGooglePlus($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $json = curl_exec($curl);

        if (curl_error($curl)) {
            error_log(__FUNCTION__ . $curl);
        }

        curl_close ($curl);

        $googlePlus = json_decode($json, true);

        return isset($googlePlus[0]['result']['metadata']['globalCounts']['count']) ? (int)$googlePlus[0]['result']['metadata']['globalCounts']['count'] : 0;
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
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $curl_results = curl_exec($curl);

        if (curl_error($curl)) {
            error_log(__FUNCTION__ . '' . $url . ' ' . curl_error($curl));
        }

        curl_close ($curl);

        return $curl_results;
    }
}