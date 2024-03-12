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

    public function getContentFromCacheAndDownloadIfNecessary($filename, $url, $cacheDurationInDays, $forceUpdate, $afterDownload = null) {
        if ($forceUpdate === true)
            return $this->download($filename, $url, $afterDownload);
        $path = $this->getPath($filename);
        if (file_exists($path) === false)
            return $this->download($filename, $url, $afterDownload);
        $s = stat($path);
        if ($s === false)
            return $this->download($filename, $url, $afterDownload);
        if ($s['mtime'] + $cacheDurationInDays * 86400 < time())
            return $this->download($filename, $url, $afterDownload);
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
        $path = $this->getPath($filename);
        if ($afterDownload === null) {
            file_put_contents($path, $response);
            return $response;
        }
        $data = $afterDownload($response);
        file_put_contents($path, $data);
        return $data;
    }
}
