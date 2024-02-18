<?php

namespace David\PmUtils\Jira;

// implements Array interfaces
class IssueLink
{
    public $id;
    public $self;
    public IssueLinkType $type;
    public ?Issue $inwardIssue;
    public ?Issue $outwardIssue;

    public function __construct(array $link)
    {
        $this->id = $link['id'];
        $this->self = $link['self'];
        $this->type = new IssueLinkType($link['type']);
        $this->inwardIssue = isset($link['inwardIssue']) ? new Issue($link['inwardIssue']) : null;
        $this->outwardIssue = isset($link['outwardIssue']) ? new Issue($link['outwardIssue']) : null;
    }
}
