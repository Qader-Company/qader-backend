<?php

return [
    'portals' => [
        'admin' => 'Admin',
        'talent' => 'Talent',
        'client' => 'Client',
    ],

    'sections' => [
        'access-control' => 'Access control',
        'admins' => 'Admins',
        'countries' => 'Countries',
        'cities' => 'Cities',
        'languages' => 'Languages',
        'professional-fields' => 'Professional fields',
        'job-titles' => 'Job titles',
        'skills' => 'Skills',
        'tools' => 'Tools',
        'currencies' => 'Currencies',
        'industries' => 'Industries',
        'workspace' => 'Workspace',
        'dashboard' => 'Dashboard',
        'members' => 'Team members',
        'roles' => 'Roles',
        'marketplace' => 'Talent marketplace',
        'talent-selections' => 'Selected talents',
        'talent-applications' => 'Talent applications',
        'interviews' => 'Interviews',
        'talents' => 'Talents',
        'job-forms' => 'Job forms',
        'job-form-submissions' => 'Job form submissions',
        'platform' => 'Platform',
    ],

    'admin' => [
        'access-control' => [
            'view' => 'View access control',
            'manage-roles' => 'Manage roles',
        ],
        'admins' => [
            'view' => 'View admins',
            'manage' => 'Manage admins',
        ],
        'countries' => [
            'view' => 'View countries',
            'manage' => 'Manage countries',
        ],
        'cities' => [
            'view' => 'View cities',
            'manage' => 'Manage cities',
        ],
        'languages' => [
            'view' => 'View languages',
            'manage' => 'Manage languages',
        ],
        'professional-fields' => [
            'view' => 'View professional fields',
            'manage' => 'Manage professional fields',
        ],
        'job-titles' => [
            'view' => 'View job titles',
            'manage' => 'Manage job titles',
        ],
        'skills' => [
            'view' => 'View skills',
            'manage' => 'Manage skills',
        ],
        'tools' => [
            'view' => 'View tools',
            'manage' => 'Manage tools',
        ],
        'currencies' => [
            'view' => 'View currencies',
            'manage' => 'Manage currencies',
        ],
        'industries' => ['view' => 'View industries', 'manage' => 'Manage industries'],
        'talent-applications' => [
            'view' => 'View talent applications',
            'assign-reviewer' => 'Assign talent application reviewers',
            'review' => 'Review talent applications',
            'decide' => 'Make talent qualification decisions',
        ],
        'interviews' => [
            'view' => 'View interviews',
            'manage' => 'Manage interview progress and schedules',
            'evaluate' => 'Evaluate interviews',
            'decide' => 'Make final interview decisions',
        ],
        'talents' => [
            'view' => 'View accepted, rejected, and blacklisted talents',
            'manage-blacklist' => 'Add and remove talents from the blacklist',
            'manage-reapply' => 'Manage talent reapplication dates',
        ],
        'job-forms' => ['view' => 'View job forms', 'manage' => 'Manage job forms'],
        'job-form-submissions' => ['view' => 'View job form submissions', 'delete' => 'Delete job form submissions', 'start-hiring' => 'Start hiring from submissions'],
    ],

    'talent' => [
        'platform' => [
            'access' => 'Access the talent platform',
        ],
    ],
    'client' => [
        'workspace' => ['view' => 'View workspace', 'update' => 'Update workspace'],
        'dashboard' => ['view' => 'View dashboard'],
        'members' => ['view' => 'View team members', 'create' => 'Create team members', 'manage' => 'Manage team members'],
        'roles' => ['view' => 'View roles', 'manage' => 'Manage roles'],
        'marketplace' => ['view' => 'View the talent marketplace'],
        'talent-selections' => ['view' => 'View selected talents', 'manage' => 'Manage selected talents'],
    ],
];
