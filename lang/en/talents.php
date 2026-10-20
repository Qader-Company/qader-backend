<?php

return [
    'application' => [
        'not_submitted' => 'No application has been submitted yet.',
    ],
    'review' => [
        'invalid_applications' => 'One or more selected applications are invalid.',
        'assigned_to_another_reviewer' => 'This application is assigned to another reviewer. Reassign it before evaluating.',
        'evaluation_not_editable' => 'The evaluation can no longer be changed.',
        'invalid_interviewers' => 'Every interviewer must be an active admin.',
        'duplicate_slots' => 'Interview slots must have different start times.',
        'interviewer_time_conflict' => 'One or more interviewers are unavailable during the selected time.',
        'invalid_reviewer' => 'The selected reviewer must be active and have the review permission.',
        'application_closed' => 'This application can no longer be reviewed or reassigned.',
        'current_reviewer_required' => 'Only the currently assigned reviewer can perform this action.',
        'decision_not_allowed' => 'A decision cannot be recorded for the application in its current status.',
        'evaluation_required' => 'Complete the talent evaluation before recording a decision.',
        'decision_already_recorded' => 'A decision has already been recorded for this application.',
        'reviewer_assigned' => 'Reviewer assigned successfully.',
        'evaluation_saved' => 'Talent evaluation saved successfully.',
        'application_rejected' => 'Talent application rejected successfully.',
        'interview_scheduled' => 'Interview details and available times saved successfully.',
        'cv_not_found' => 'The submitted CV was not found.',
    ],
    'onboarding' => [
        'previous_step_required' => 'Complete the :step step before continuing.',
        'steps' => [
            'personal-information' => 'Personal Information',
            'skills-and-experiences' => 'Skills and Experiences',
            'work-preferences' => 'Work Preferences',
        ],
    ],
];
