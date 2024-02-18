<?php

namespace David\PmUtils\Traits;

use DateTime;
use David\PmUtils\Jira\Issue;
use Exception;

trait JiraTrait
{
    private $jiraUsername = 'david.callizaya@processmaker.com';
    protected $jiraApiToken;
    private $jiraUrl = 'https://processmaker.atlassian.net';

    /**
     * Obtiene el listado de ticket asignado a mi usuario
     *
     * @return mixed
     */
    public function fetchAssignedTickets()
    {
        $jql = 'assignee = currentUser() AND status != Closed ORDER BY updated DESC';
        $this->searchTickets($jql);
    }

    private function makeJiraRequest($endpoint, $method = 'GET', $data = null)
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

    private function getContent($contentArray)
    {
        $description = "";
        foreach($contentArray as $content) {
            if ($content['type'] === 'paragraph') {
                foreach($content['content'] as $paragraph) {
                    if ($paragraph['type'] === 'text') {
                        $description .= $paragraph['text'] . "\n";
                    }
                    // inlineCard
                    if ($paragraph['type'] === 'inlineCard') {
                        $description .= $paragraph['attrs']['url'] . "\n";
                    }
                }
            }
        }
        return $description;
    }

    private function searchTickets($jql)
    {
        $searchEndpoint = '/rest/api/3/search';
        $jqlQuery = '?jql=' . urlencode($jql);
        $tickets = $this->makeJiraRequest($searchEndpoint . $jqlQuery)['issues'];
        foreach($tickets as $ticket) {
            $description = $this->getContent($ticket['fields']['description']['content']);
            $commentEndpoint = "/rest/api/3/issue/{$ticket['key']}/comment";
            $ticketComments = $this->makeJiraRequest($commentEndpoint);

            $comments = "";
            foreach($ticketComments['comments'] as $comment) {
                $body = $this->getContent($comment['body']['content']);
                $comments .= "{$comment['author']['displayName']} - {$comment['updated']}:\n{$body}\n";
            }
            $assignee = $ticket['fields']['assignee'] ?: ['displayName' => 'Unassigned'];

            echo <<<EOT
            Ticket: {$ticket['key']}
            Assignee: {$assignee['displayName']}
            Issue type: {$ticket['fields']['issuetype']['name']}
            Updated: {$ticket['fields']['updated']}
            Summary: {$ticket['fields']['summary']}
            Status: {$ticket['fields']['status']['name']}
            Description:
            {$description}

            Comments:
            {$comments}
            ---

            EOT;
        }
    }

    public function jira_comments($ticketKey)
    {
        $commentEndpoint = "/rest/api/3/issue/$ticketKey/comment";
        print_r($this->makeJiraRequest($commentEndpoint));
    }

    public function ultimos_tickets()
    {
        $yesterday = new DateTime('-1 day');
        $yesterday->setTime(0, 0, 0); // Comienza desde la medianoche del día anterior
        $jql = 'status != Closed AND updated >= "' . $yesterday->format('Y-m-d H:i') . '" AND (assignee = currentUser() OR comment ~ currentUser()) order by updated DESC';
        $this->searchTickets($jql);
    }

    public function jira_epic($epic, $print = true)
    {
        $jql = '"Epic Link"=' . $epic . ' OR "Parent Link"=' . $epic . ' ORDER BY rank';
        $jqlQuery = '/rest/api/3/search?jql=' . urlencode($jql);
        $result = $this->makeJiraRequest($jqlQuery);
        $pipeline = [
            'PENDING' => [],
            'DONE' => [],
        ];
        $logIssues = $print;
        foreach($result['issues'] as $res) {
            $issue = new Issue($res);
            if ($logIssues) {
                echo "-----\n";
                echo $issue->key, " ";
                echo $issue->fields->summary, "\n";
                echo $issue->fields->assignee?->displayName, "\n";
                echo $issue->fields->assignee?->avatarUrls['48x48'] ?? "", "\n";
                echo $issue->fields->status->name, "\n";
                echo $issue->fields->status->statusCategory->key, "\n";
                echo $issue->fields->status->statusCategory->colorName, "\n";
                foreach($res['fields'] as $k => $v) {
                    if (strpos($k, 'original') !== false) {
                        echo $k, "=", json_encode($v), "\n";
                    }
                }
                echo "\n";
            }
            if ($issue->fields->status->statusCategory->key === 'done') {
                $pipeline['DONE'][] = $issue;
            } else {
                $assignee = $issue->fields->assignee?->displayName ?? 'Unassigned';
                $pipeline['PENDING'][$assignee][] = $issue;
            }
        }
        if ($print) {
            // Show pending issues
            foreach($pipeline['PENDING'] as $assignee => $issues) {
                echo "-----\n";
                echo $assignee, ": ";
                foreach($issues as $issue) {
                    echo $issue->key, " ";
                }
                echo "\n";
            }
            // Show done issues
            echo "-----\n";
            echo "DONE: ";
            foreach($pipeline['DONE'] as $issue) {
                echo $issue->key, "({$issue->fields->timeoriginalestimate})";
            }
            echo "\n";
        }
        return $pipeline;
    }
}
