<?php

namespace David\PmUtils\Jira;

class Assignee {
    public $self;
    public $accountId;
    public $emailAddress;
    public $avatarUrls;
    public $displayName;
    public $active;
    public $timeZone;
    public $accountType;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->accountId = $data['accountId'] ?? null;
        $this->emailAddress = $data['emailAddress'] ?? null;
        $this->avatarUrls = $data['avatarUrls'] ?? [];
        $this->displayName = $data['displayName'] ?? null;
        $this->active = $data['active'] ?? null;
        $this->timeZone = $data['timeZone'] ?? null;
        $this->accountType = $data['accountType'] ?? null;
    }
}
