<?php

$t = fn (string $title, string $body): array => ['title' => $title, 'body' => $body];

return [
    'marked_as_read' => 'Notification marked as read successfully.',
    'all_marked_as_read' => 'All notifications marked as read successfully.',

    // Hiring requests
    'hiring_request_submitted' => $t('New hiring request', 'A new hiring request :reference was submitted for :company.'),
    'hiring_request_assigned' => $t('Hiring request assigned to you', 'Hiring request :reference from :company was assigned to you.'),
    'hiring_offer_received' => $t('New job offer', 'You received a job offer from :company for :job_title.'),
    'hiring_offer_accepted' => $t('Offer accepted', 'Talent :talent_code accepted the offer for :job_title (:reference).'),
    'hiring_offer_rejected' => $t('Offer rejected', 'Talent :talent_code rejected the offer for :job_title (:reference).'),
    'hiring_counter_offer_received' => $t('Counter offer received', 'Talent :talent_code sent a counter offer for :job_title (:reference).'),
    'hiring_counter_offer_accepted' => $t('Counter offer accepted', ':company accepted the counter offer for :job_title (:reference).'),
    'hiring_offer_responded_on_behalf' => $t('Offer response recorded', 'Qader recorded the response ":decision" on the offer for :job_title (:reference).'),
    'hiring_offer_expired' => $t('Offer expired', 'The response time for the offer for :job_title (:reference) has ended.'),
    'hiring_offer_deadline_reminder' => $t('Offer response due soon', 'Less than an hour is left to respond to the offer for :job_title (:reference).'),
    'hiring_interview_proposed' => $t('Interview time proposed', 'An interview time was proposed for :job_title (:reference). Please respond.'),
    'hiring_interview_rescheduled' => $t('Interview rescheduled', 'A new interview time was proposed for :job_title (:reference). Please respond.'),
    'hiring_interview_response_received' => $t('Interview schedule response', 'A response ":response" was received on the interview for :job_title (:reference).'),
    'hiring_interview_confirmed' => $t('Interview confirmed', 'The interview for :job_title (:reference) is confirmed.'),
    'hiring_interview_reminder' => $t('Interview in one hour', 'The interview for :job_title (:reference) starts in one hour.'),
    'hiring_interview_starting' => $t('Interview starting now', 'The interview for :job_title (:reference) is starting now.'),
    'hiring_decision_required' => $t('Hiring decision required', 'The interview for :job_title (:reference) is completed. Please submit your decision.'),
    'hiring_decision_deadline_reminder' => $t('Hiring decision due soon', 'Less than an hour is left to submit your decision for :job_title (:reference).'),
    'hiring_decision_made' => $t('Hiring decision', 'The hiring decision for :job_title (:reference) is ":decision".'),
    'hiring_decision_withdrawn' => $t('Hiring decision withdrawn', 'The hiring decision for :job_title (:reference) was withdrawn.'),
    'hiring_item_on_hold' => $t('Hiring paused', 'Hiring for :job_title (:reference) was put on hold.'),
    'hiring_item_resumed' => $t('Hiring resumed', 'Hiring for :job_title (:reference) was resumed.'),
    'hiring_item_cancelled' => $t('Hiring cancelled', 'Hiring for :job_title (:reference) was cancelled.'),

    // Talent applications
    'talent_application_assigned' => $t('Application assigned to you', 'The application of :talent (:talent_code) was assigned to you for review.'),
    'talent_interview_slots_available' => $t('Choose your interview time', 'Interview times are available for your application. Please choose one.'),
    'talent_interview_slot_selected' => $t('Interview time selected', ':talent (:talent_code) selected an interview time.'),
    'talent_interview_slots_rejected' => $t('Interview times rejected', ':talent (:talent_code) rejected the offered interview times and needs new ones.'),
    'talent_interview_cancelled' => $t('Interview cancelled by talent', ':talent (:talent_code) cancelled the confirmed interview and needs new times.'),
    'talent_interview_reminder' => $t('Interview in one hour', 'The application interview :talent_code starts in one hour.'),
    'talent_interview_starting' => $t('Interview starting now', 'The application interview :talent_code is starting now.'),
    'talent_application_accepted' => $t('Application accepted', 'Congratulations! Your application was accepted.'),
    'talent_application_rejected' => $t('Application result', 'Your application was not accepted this time.'),

    // Help requests
    'help_request_assigned' => $t('Help request assigned to you', 'Help request :reference (:help_type) was assigned to you.'),
    'help_request_in_progress' => $t('Help request in progress', 'Qader started working on your help request :reference.'),
    'help_request_closed' => $t('Help request closed', 'Your help request :reference was closed.'),

    // Workspaces
    'workspace_deactivated' => $t('Workspace deactivated', 'The workspace :workspace was deactivated. Hiring activity is paused.'),
    'workspace_activated' => $t('Workspace activated', 'The workspace :workspace was activated again.'),

    // Record activity (admins -> super admins, team members -> workspace owner)
    'record_created' => $t('New :record added', ':actor added :record ":name".'),
    'record_deleted' => $t(':Record deleted', ':actor deleted :record ":name".'),
    'record_restored' => $t(':Record restored', ':actor restored :record ":name" from trash.'),
    'record_removed' => $t(':Record removed permanently', ':actor permanently removed :record ":name" from trash.'),

    'record' => 'record',
    'records' => [
        'job-forms' => 'job form',
        'form-submissions' => 'form submission',
        'talents' => 'talent',
        'admins' => 'admin',
        'roles' => 'role',
        'help-requests' => 'help request',
        'clients' => 'client',
        'countries' => 'country',
        'cities' => 'city',
        'currencies' => 'currency',
        'languages' => 'language',
        'industries' => 'industry',
        'professional-fields' => 'professional field',
        'job-titles' => 'job title',
        'skills' => 'skill',
        'tools' => 'tool',
        'workspaces' => 'workspace',
        'team' => 'team member',
        'workspace-roles' => 'workspace role',
    ],
];
