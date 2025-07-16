<?php

namespace MoveElevator\FeatureIndex\Api;

use MoveElevator\FeatureIndex\Model\Entry;

class JiraApi extends AbstractApi
{
    const CACHE_PATH = __DIR__ . '/../../var/cache/jira/';
    const CACHE_LIFETIME = 300;

    protected string $url;
    protected string $auth;
    public function __construct(string $url, string $auth) {
        $this->url = $url;
        $this->auth = $auth;
    }

    /**
     * @param \MoveElevator\FeatureIndex\Model\Entry $entry
     * @return void
     */
    public function checkIssue(Entry &$entry) {
        if ($entry->getIssue() === '' || (!$this->url && !$this->auth)) return;

        $response = $this->request($entry->getIssue());

        if (is_null($response)) return;
        $issueData = [];
        $issueData['summary'] = $response['fields']['summary'];
        $issueData['type']['name'] =  $response['fields']['issuetype']['name'];
        $issueData['type']['icon'] =  $response['fields']['issuetype']['iconUrl'];
        $issueData['priority']['name'] =  $response['fields']['priority']['name'];
        $issueData['priority']['icon'] =  $response['fields']['priority']['iconUrl'];
        $issueData['assignee']['name'] =  $response['fields']['assignee'] ? $response['fields']['assignee']['displayName'] : 'NA';
        $issueData['status']['name'] =  $response['fields']['status']['name'];
        $issueData['status']['icon'] =  $response['fields']['status']['iconUrl'];
        $issueData['status']['color'] =  $response['fields']['status']['statusCategory']['colorName'];

        $entry->setIssueData($issueData);
    }

    /**
     * @param string $issue
     * @return mixed
     */
    private function request(string $issue)
    {
        if ($this->getCache($issue, self::CACHE_PATH, self::CACHE_LIFETIME)) return $this->getCache($issue, self::CACHE_PATH, self::CACHE_LIFETIME);

        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->url . $issue);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->auth
        ]);
        $result = curl_exec($curl_session );
        curl_close($curl_session );

        $this->setCache($issue, $result, self::CACHE_PATH, self::CACHE_LIFETIME);
        return json_decode($result, true);
    }
}
