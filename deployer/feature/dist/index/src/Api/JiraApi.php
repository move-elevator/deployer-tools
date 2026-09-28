<?php

namespace MoveElevator\FeatureIndex\Api;

use MoveElevator\FeatureIndex\Model\Entry;

class JiraApi extends AbstractApi
{
    const CACHE_PATH = __DIR__ . '/../../var/cache/jira/';
    const CACHE_LIFETIME = 300;
    const REQUEST_TIMEOUT = 5;
    const FIELDS = 'summary,issuetype,assignee,status';

    protected string $url;
    protected string $auth;
    public function __construct(string $url, string $auth) {
        $this->url = $url;
        $this->auth = $auth;
    }

    /**
     * @param Entry[] $entries
     */
    public function loadIssues(array $entries): void
    {
        if ($this->url === '') return;

        $uncached = [];
        foreach ($entries as $entry) {
            if ($entry->getIssue() === '') continue;

            $response = $this->getCache($entry->getIssue());
            if ($response === null) {
                $uncached[$entry->getIssue()][] = $entry;
                continue;
            }
            $this->applyIssueData($entry, $response);
        }

        foreach ($this->requestAll(array_keys($uncached)) as $issue => $response) {
            foreach ($uncached[$issue] as $entry) {
                $this->applyIssueData($entry, $response);
            }
        }
    }

    private function applyIssueData(Entry $entry, array $response): void
    {
        $fields = $response['fields'] ?? null;
        if (!is_array($fields)) return;

        $entry->setIssueData([
            'summary' => $fields['summary'] ?? '',
            'type' => [
                'name' => $fields['issuetype']['name'] ?? '',
                'icon' => $fields['issuetype']['iconUrl'] ?? '',
            ],
            'assignee' => [
                'name' => $fields['assignee']['displayName'] ?? 'NA',
            ],
            'status' => [
                'name' => $fields['status']['name'] ?? '',
                'color' => $fields['status']['statusCategory']['colorName'] ?? '',
                // "new", "indeterminate" or "done"
                'category' => $fields['status']['statusCategory']['key'] ?? '',
            ],
        ]);
    }

    /**
     * Requests all issues in parallel, so the page load does not grow with every instance
     *
     * @param string[] $issues
     * @return array<string, array> successful responses by issue key
     */
    private function requestAll(array $issues): array
    {
        if ($issues === []) return [];

        $multiHandle = curl_multi_init();
        $handles = [];
        foreach ($issues as $issue) {
            $handles[$issue] = $this->createRequest($issue);
            curl_multi_add_handle($multiHandle, $handles[$issue]);
        }

        do {
            $status = curl_multi_exec($multiHandle, $running);
            // select() returns -1 immediately on some libcurl builds, avoid busy looping until the timeout
            if ($running && curl_multi_select($multiHandle) === -1) usleep(1000);
        } while ($running && $status === CURLM_OK);

        $responses = [];
        foreach ($handles as $issue => $handle) {
            $response = $this->parseResponse(curl_multi_getcontent($handle), curl_getinfo($handle, CURLINFO_RESPONSE_CODE));
            curl_multi_remove_handle($multiHandle, $handle);
            if ($response === null) continue;

            $this->setCache($issue, $response);
            $responses[$issue] = $response;
        }

        curl_multi_close($multiHandle);

        return $responses;
    }

    private function createRequest(string $issue): \CurlHandle
    {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->url . rawurlencode($issue) . '?fields=' . self::FIELDS);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($handle, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        // without credentials only public Jira instances answer, everything else is dropped below
        curl_setopt($handle, CURLOPT_HTTPHEADER, array_filter([
                'Accept: application/json',
                $this->auth !== '' ? 'Authorization: Basic ' . $this->auth : null,
        ]));

        return $handle;
    }

    /**
     * Error responses (e.g. 401 without valid credentials, 404 for unknown issues) are neither
     * rendered nor cached, otherwise their empty fields show up as issue data for five minutes
     */
    private function parseResponse(?string $result, int $status): ?array
    {
        if ($result === null || $status !== 200) return null;

        $response = json_decode($result, true);
        return is_array($response) ? $response : null;
    }
}
