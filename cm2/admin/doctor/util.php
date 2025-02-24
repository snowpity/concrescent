<?php

function failed(string $target, string $message): void
{
    echo row($target, $message, 'FAILED');
}

function notice(string $target, string $message): void
{
    echo row($target, $message, 'NOTICE');
}

function passed(string $target, string $message): void
{
    echo row($target, $message, 'PASSED');
}

function checking(string $target, string $message): void
{
    echo row($target, $message, 'CHECKING');
}

function row(string $target, string $message, string $level): string
{
    return <<<HEREDOC
    <th><form method="get" action="$target.php" target="_self"
        hx-get="$target.php"
        hx-target="closest tr"
    >
    <input type="submit" value="&#10227;"/>
    </form></th>
    <td class="level $level">$level</td>
    <td>$message</td>
HEREDOC;

}
