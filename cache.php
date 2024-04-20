<?php

class Cache {
    public function __construct() {

    }

    private function getPath($filename) {
        return __DIR__.'/cache/'.$filename;
    }

    public function getCacheStatus($filename) {
        $path = $this->getPath($filename);
        if (file_exists($path) === false)
            return 'non présent';
        $s = stat($path);
        if ($s === false)
            return 'problème de droit';
        return $s['mtime'];
    }

    // true => le cache a expiré
    // false => le cache n'a pas expiré
    public function doesCacheIsExpired($filename, $cacheDurationInDays, $forceUpdate) {
        if ($forceUpdate === true)
            return true;
        $path = $this->getPath($filename);
        if (file_exists($path) === false)
            return true;
        $s = stat($path);
        if ($s === false)
            return true;
        if ($s['mtime'] + $cacheDurationInDays * 86400 < time())
            return true;
        return false;
    }

    public function getContentFromCacheAndDownloadIfNecessary($filename, $url, $cacheDurationInDays, $forceUpdate, $afterDownload = null) {
        if ($this->doesCacheIsExpired($filename, $cacheDurationInDays, $forceUpdate))
            return $this->download($filename, $url, $afterDownload);
        $path = $this->getPath($filename);
        return file_get_contents($path);
    }

    public function getContentFromCacheNoDownload($filename) {
        $path = $this->getPath($filename);
        return file_get_contents($path);
    }

    private function download($filename, $url, $afterDownload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));
            
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("Impossible de télécharger le fichier");
        if ($afterDownload === null) {
            $this->writeCacheFile($filename, $response);
            return $response;
        }
        $data = $afterDownload($response);
        $this->writeCacheFile($filename, $data);
        return $data;
    }

    public function writeCacheFile($filename, $data) {
        $path = $this->getPath($filename);
        @file_put_contents($path, $data);
    }
}
