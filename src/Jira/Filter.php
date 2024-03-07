<?php

namespace David\PmUtils\Jira;

use David\PmUtils\Console;

class Filter
{
    public $self;
    public $id;
    public $name;
    public $description;
    public $owner;
    public $jql;
    public $viewUrl;
    public $searchUrl;
    public $favourite;
    public $favouritedCount;
    public $sharePermissions;
    public $editPermissions;
    public $isWritable;
    public $sharedUsers;
    public $subscriptions;

    public function __construct(array $data)
    {
        $this->self = $data['self'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->owner = isset($data['owner']) ? new Reporter($data['owner']) : null;
        $this->jql = $data['jql'] ?? null;
        $this->viewUrl = $data['viewUrl'] ?? null;
        $this->searchUrl = $data['searchUrl'] ?? null;
        $this->favourite = $data['favourite'] ?? null;
        $this->favouritedCount = $data['favouritedCount'] ?? null;
        $this->sharePermissions = $data['sharePermissions'] ?? null;
        $this->editPermissions = $data['editPermissions'] ?? null;
        $this->isWritable = $data['isWritable'] ?? null;
        $this->sharedUsers = $data['sharedUsers'] ?? null;
        $this->subscriptions = $data['subscriptions'] ?? null;
    }

    public static function find($id): self
    {
        $console = new Console(__DIR__);
        return new self($console->jira_filter($id));
    }
}
