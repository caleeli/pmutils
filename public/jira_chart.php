<!doctype html>
<?php
use David\PmUtils\Console;
use David\PmUtils\Jira\Issue;
use David\PmUtils\Jira\Jira;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    foreach (parse_ini_file(__DIR__ . '/../.env') as $key=>$value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

ini_set('display_errors', 1);
$epic = $_GET['epic'] ?? null;
$filter = $_GET['filter'] ?? null;
$board = $_GET['board'] ?? null;
const ACTIVE_STATUSES = ['Scheduled', 'In Progress', 'In Review', 'Pull Request'];
const READY_STATUSES = ['In Backlog', 'Ready for Review'];

if ($epic) {
    $console = new Console(__DIR__);
    $pipeline = $console->jira_epic($epic, false);
} elseif ($filter) {
    $pipeline = Jira::getInstance()->getFilterPipeline($filter);
} elseif ($board) {
    $pipeline = Jira::getInstance()->getBoardPipeline($board);
} else {
    die('No ?epic= or ?filter= provided');
}

function sumTime(array $issues)
{
    $doneSum = 0;
    foreach($issues as $issue) {
        $doneSum += $issue->fields->timeoriginalestimate;
    }
    $doneSum = round($doneSum / 3600);
    return $doneSum;
}
function renderIssue(Issue $issue)
{
    $link = 'https://processmaker.atlassian.net/browse/' . $issue->key;
    $hours = round($issue->fields->timeoriginalestimate / 3600);
    $class = '';
    if (!$hours) {
        $class .= 'font-bold bg-orange-600 ';
    }
    echo "<a class='ticket ticket-{$issue->fields->status->statusCategory->colorName}'",
    "href='{$link}' target='_blank'>";
    echo $issue->key, "<small class='{$class}'>({$hours}h)</small>";
    echo "</a>";
}
function findBlockedBy(array $allIssues, Issue $issue)
{
    $blockedBy = [];
    foreach($allIssues as $issue2) {
        if ($issue2->fields->status->statusCategory->key === 'done') {
            continue;
        }
        if (strpos($issue2->fields->summary, 'blocked by ' . $issue->key) !== false) {
            $blockedBy[] = $issue2;
        }
    }
    return $blockedBy;
}
function selectActive(array $issues)
{
    $today = [];
    $rest = [];
    foreach($issues as $issue) {
        if (in_array($issue->fields->status->name, ACTIVE_STATUSES)) {
            $today[] = $issue;
        } else {
            $rest[] = $issue;
        }
    }
    return [$today, $rest];
}
/**
 * @param Issue[] $issues
 * @return array
 */
function selectReadyNotBlocked(array $issues)
{
    $ready = [];
    $rest = [];
    foreach($issues as $issue) {
        $blockedBy = $issue->fields->issuelinks->having('inwardIssue');
        if (count($blockedBy) === 0 && in_array($issue->fields->status->name, READY_STATUSES)) {
            $ready[] = $issue;
        } else {
            $rest[] = $issue;
        }
    }
    return [$ready, $rest];
}
function selectBlockedNotReady(array $issues)
{
    return [$issues, []];
}

// PENDING SUM
$pendingSum = 0;
foreach($pipeline['PENDING'] as $assignee => $issues) {
    $pendingSum += sumTime($issues);
}
// DONE SUM
$doneSum = sumTime($pipeline['DONE']);

?>
<html lang="en">

<head>
    <title>Jira Chart</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <style>
        table {
            border-spacing: 0px;
            border-collapse: collapse;
            width: 100%;
        }

        .bb {
            border-bottom: 1px solid black;
        }

        .ticket {
            border: 1px solid black;
            padding: 4px;
            margin: 4px;
            display: inline-block;
            border-radius: 4px;
        }

        .ticket-green {
            background-color: #d0f0c0;
            color: #006100;
        }

        .ticket-yellow {
            background-color: #fff2cc;
            color: #9f6000;
        }
        .ticket-blue-gray {
            background-color: #cfe2f3;
            color: #1f497d;
        }
    </style>
    <table border="0">
        <capFUNCTION>PROJECT PIPELINE</capFUNCTION tion>
        <tr>
            <th class="border-2 ticket-yellow" style="width:50%">PENDING
                (<?=$pendingSum?>h)</th>
            <th class="border-2 ticket-green" style="width:50%">DONE
                (<?=$doneSum?>h)</th>
        </tr>
        <tr>
<?php
$blockedSum = 0;
$readySum = 0;
$activeSum = 0;
foreach($pipeline['PENDING'] as $assignee => $issues) {
    list($active, $rest) = selectActive($issues);
    list($ready, $rest) = selectReadyNotBlocked($rest);
    list($blocked, $rest) = selectBlockedNotReady($rest);
    $blockedSum += sumTime($blocked);
    $readySum += sumTime($ready);
    $activeSum += sumTime($active);
}
?>
            <td class="border-2">
                <table class="border-2">
                    <tr class="border-2">
                        <th class='border-2' style="width:16rem">Assignee</th>
                        <th class='border-2'>Blocked (<?=$blockedSum?>h)</th>
                        <th class='border-2'>Ready (<?=$readySum?>h)</th>
                        <th class='border-2'>Active (<?=$activeSum?>h)</th>
                    </tr>
                    <?php
foreach($pipeline['PENDING'] as $assignee => $issues) {
    echo "<tr class='border-2'>";
    echo "<td class='border-2'>{$assignee} (" . sumTime($issues) . "h)</td>";
    echo "<td class='border-2'>";
    list($active, $rest) = selectActive($issues);
    list($ready, $rest) = selectReadyNotBlocked($rest);
    list($blocked, $rest) = selectBlockedNotReady($rest);
    foreach($blocked as $issue) {
        renderIssue($issue);
    }
    echo "</td>";
    echo "<td class='border-2'>";
    foreach($ready as $issue) {
        renderIssue($issue);
    }
    echo "</td>";
    echo "<td class='border-2'>";
    foreach($active as $issue) {
        renderIssue($issue);
    }
    echo "</td>";
    echo "</tr>";
}
    ?>
                </table>
            </td>
            <td class="border-2">
                <?php
foreach($pipeline['DONE'] as $issue) {
    renderIssue($issue);
}
    ?>
            </td>
        </tr>
    </table>
</body>

</html>