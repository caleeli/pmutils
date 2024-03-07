<?php

namespace David\PmUtils\Jira;

use Exception;

class Jira
{
    private $jiraUsername = 'david.callizaya@processmaker.com';
    protected $jiraApiToken;
    private $jiraUrl = 'https://processmaker.atlassian.net';

    public static function getInstance(): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->jiraApiToken = getenv('JIRA_API_TOKEN');
    }

    public function makeJiraRequest($endpoint, $method = 'GET', $data = null)
    {
        $ch = curl_init();
        $url = $this->jiraUrl . $endpoint;

        $httpHeader = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->jiraUsername . ':' . $this->jiraApiToken),
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->jiraUsername . ':' . $this->jiraApiToken,
            CURLOPT_HTTPHEADER => $httpHeader,
        ];

        if ($method === 'POST' && $data !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        } else {
            return json_decode($response, true);
        }
    }

    public function getEpicPipeline($epic, $print = true)
    {
        $jql = '"Epic Link"=' . $epic . ' OR "Parent Link"=' . $epic . ' ORDER BY rank';
        return $this->getJqlPipeline($jql, $print);
    }

    public function getJqlPipeline($jql): array
    {
        $searchEndpoint = '/rest/api/3/search';
        $searchEndpoint .= '?jql=' . urlencode($jql);
        $result = $this->makeJiraRequest($searchEndpoint);
        $pipeline = [
            'PENDING' => [],
            'DONE' => [],
        ];
        foreach($result['issues'] as $res) {
            $issue = new Issue($res);
            if ($issue->fields->status->statusCategory->key === 'done') {
                $pipeline['DONE'][] = $issue;
            } else {
                $assignee = $issue->fields->assignee?->displayName ?? 'Unassigned';
                $pipeline['PENDING'][$assignee][] = $issue;
            }
        }
        return $pipeline;
    }

    public function getFilterPipeline($filterId, string $extraJql = ''): array
    {
        $result = $this->makeJiraRequest('/rest/api/3/filter/' . $filterId);
        $jql = $result['jql'];
        if ($extraJql) {
            $jql = $extraJql . ' AND ' . $jql;
        }
        return $this->getJqlPipeline($jql);
    }

    public function getBoardPipeline($id): array
    {
        // $searchEndpoint = '/rest/agile/1.0/board/' . $id . '/issue';
        // return $this->getJqlPipeline($searchEndpoint);

        // Get filter from board
        // $result = $this->makeJiraRequest('/rest/agile/1.0/board/' . $id . '/configuration');
        // $filterId = $result['filter']['id'];

        // Get active sprint(s) in board
        $result = $this->makeJiraRequest('/rest/agile/1.0/board/' . $id . '/sprint?state=active');
        $sprints = $result['values'];
        $ids = [];
        foreach($sprints as $sprint) {
            $ids[] = $sprint['id'];
        }
        // In sprint and not in Triage
        $jql = 'sprint in (' . implode(',', $ids) . ') AND status != Triage';
        // return $this->getFilterPipeline($filterId, $jql);
        // Get jql from filter
         return $this->getJqlPipeline($jql);
    }
}
