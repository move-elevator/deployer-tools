<?php

namespace MoveElevator\FeatureIndex\Api;

use MoveElevator\FeatureIndex\Model\Entry;

class JiraApi extends AbstractApi
{
    const CACHE_PATH = __DIR__ . '/../../var/cache/jira/';
    const CACHE_LIFETIME = 300;
    const REQUEST_TIMEOUT = 5;
    const FIELDS = 'summary,issuetype,priority,assignee,status';

    protected string $url;
    protected string $auth;
    public function __construct(string $url, string $auth) {
        $this->url = $url;
        $this->auth = $auth;
    }

    public function checkIssue(Entry $entry): void
    {
        if ($entry->getIssue() === '' || $this->url === '') return;

        $fields = $this->request($entry->getIssue())['fields'] ?? null;
        if (!is_array($fields)) return;

        $entry->setIssueData([
            'summary' => $fields['summary'] ?? '',
            'type' => [
                'name' => $fields['issuetype']['name'] ?? '',
                'icon' => $fields['issuetype']['iconUrl'] ?? '',
            ],
            'priority' => [
                'name' => $fields['priority']['name'] ?? '',
                'icon' => $fields['priority']['iconUrl'] ?? '',
            ],
            'assignee' => [
                'name' => $fields['assignee']['displayName'] ?? 'NA',
            ],
            'status' => [
                'name' => $fields['status']['name'] ?? '',
                'icon' => $fields['status']['iconUrl'] ?? '',
                'color' => $fields['status']['statusCategory']['colorName'] ?? '',
            ],
        ]);
    }

    private function request(string $issue): ?array
    {
        $cached = $this->getCache($issue);
        if ($cached !== null) return $cached;

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->url . rawurlencode($issue) . '?fields=' . self::FIELDS);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl_session, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        // without credentials only public Jira instances answer, everything else is dropped below
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, array_filter([
                'Accept: application/json',
                $this->auth !== '' ? 'Authorization: Basic ' . $this->auth : null,
        ]));
        $result = curl_exec($curl_session);
        $status = curl_getinfo($curl_session, CURLINFO_RESPONSE_CODE);

        // error responses (e.g. 401 without valid credentials, 404 for unknown issues) are neither
        // rendered nor cached, otherwise their empty fields show up as issue data for five minutes
        if (!is_string($result) || $status !== 200) return null;

        $data = json_decode($result, true);
        if (!is_array($data)) return null;

        $this->setCache($issue, $data);
        return $data;
    }
}
