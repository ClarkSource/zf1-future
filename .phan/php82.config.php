<?php

return [
    'target_php_version' => '8.2',
    'minimum_severity' => \Phan\Issue::SEVERITY_CRITICAL,
    'suppress_issue_types' => [],
    'directory_list' => ['library', 'test', 'vendor'],
    'exclude_analysis_directory_list' => ['vendor/'],
];
