<?php

use GuzzleHttp\Client;
use \Ovh\Api;

class OVH {
    private $ovh;

    public function __construct($config) {
        $client = new Client([ 'timeout' => 1, 'connect_timeout' => 1 ]);
        $conn = new Api($config['api']['applicationKey'],
                        $config['api']['applicationSecret'],
                        $config['api']['endpoint'],
                        $config['api']['consumerKey'],
                        $client);
        $this->ovh = $conn;
    }

    public function addSubscriberToMailingList($domain, $listName, $email) {
        $url = implode('/', [ '/email/domain', $domain, 'mailingList', $listName, 'subscriber']);
        try {
            $this->ovh->post($url, [ 'email' => $email ]);
        }
        catch (Exception $e) {
            // si l'abonné existe déjà, pas grave, l'essentiel c'est qu'il soit inscrit
            if ($e->getMessage() == "This Subscriber already exists in this mailing list")
                return;
        }
    }

    public function getMailingList($domain) {
        $url = implode('/', [ '/email/domain', $domain, 'mailingList' ]);
        return $this->ovh->get($url);
    }

    public function getSubscribers($domain, $listName) {
        $url = implode('/', [ '/email/domain', $domain, 'mailingList', $listName, 'subscriber' ]);
        return $this->ovh->get($url);
    }

    public function removeSubscriberFromMailingList($domain, $listName, $email) {
        $url = implode('/', [ '/email/domain', $domain, 'mailingList', $listName, 'subscriber', $email]);
        return $this->ovh->delete($url);
    }
}
