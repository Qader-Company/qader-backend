<?php

namespace App\Support\ApiDocumentation;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class EndpointNames
{
    public static function for(Route $route): string
    {
        $parts = array_slice(explode('/', $route->uri()), 2);
        $method = $route->methods()[0];
        $isPublicJobs = ($parts[0] ?? null) === 'public' && ($parts[1] ?? null) === 'jobs';

        if ($parts[0] === 'auth') {
            return match ($route->getActionMethod()) {
                'register' => 'Register',
                'login' => 'Login',
                'socialLogin' => 'Social Login',
                'logout' => 'Logout',
                'me' => 'Get Current User',
                'refresh' => 'Refresh Token',
                'sendOtp' => 'Send OTP',
                'verifyEmail' => 'Verify Email',
                'verifyPasswordReset' => 'Verify Password Reset OTP',
                'resetPassword' => 'Reset Password',
                default => Str::headline($route->getActionMethod()),
            };
        }

        array_shift($parts); // Portal.
        $module = $parts[0];

        if ($module === 'profile') {
            if (($parts[1] ?? '') === 'media') {
                return 'Download Profile Media';
            }

            return $method === 'GET' ? 'Get Profile' : 'Update Profile';
        }

        if ($module === 'onboarding') {
            return match ($parts[1] ?? '') {
                '' => 'Get Onboarding Progress',
                'form-fields' => 'Get Onboarding Form Fields',
                'professional-field' => 'Select Professional Field',
                'application' => 'Get Application',
                default => 'Save '.Str::headline($parts[1]),
            };
        }

        if ($module === 'application') {
            return match ($route->getActionMethod()) {
                'show' => 'Get Talent Application Status',
                'selectInterviewSlot' => 'Confirm Interview Slot',
                'rejectInterviewSlots' => 'Reject All Interview Slots',
                'cancelInterview' => 'Cancel Interview and Request New Slots',
                default => Str::headline($route->getActionMethod()),
            };
        }

        if ($module === 'talent-applications') {
            return match ($route->getActionMethod()) {
                'scopes' => 'List Talent Application Scopes',
                'sortOptions' => 'List Talent Application Sort Options',
                'statuses' => 'List Talent Application Statuses',
                'evaluationTipPriorities' => 'List Evaluation Tip Priorities',
                'reapplyPolicies' => 'List Reapply Policies',
                'interviewTypes' => 'List Interview Types',
                'interviewModes' => 'List Interview Modes',
                'interviewStatuses' => 'List Interview Statuses',
                'interviewSlotStatuses' => 'List Interview Slot Statuses',
                'index' => 'List Talent Applications',
                'reviewers' => 'List Available Reviewers',
                'show' => 'Get Talent Application',
                'downloadCv' => 'Download Submitted CV',
                'reviewerHistory' => 'Get Reviewer Assignment History',
                'activity' => 'Get Talent Application Activity',
                'assignReviewer' => 'Assign Talent Application Reviewer',
                'startReview' => 'Start Talent Application Review',
                'saveEvaluation' => 'Save Talent Evaluation',
                'reject' => 'Reject Talent Application',
                'scheduleInterview' => 'Schedule Talent Interview',
                'replaceInterviewSlots' => 'Offer Replacement Interview Slots',
                default => Str::headline($route->getActionMethod()),
            };
        }

        if ($module === 'talent-review-faqs') {
            return match ($route->getActionMethod()) {
                'screenStates' => 'List Talent Review FAQ Screen States',
                'index' => 'List Talent Review FAQs',
                'show' => 'Get Talent Review FAQ',
                'store' => 'Create Talent Review FAQ',
                'update' => 'Update Talent Review FAQ',
                'destroy' => 'Delete Talent Review FAQ',
                default => Str::headline($route->getActionMethod()),
            };
        }

        if ($module === 'job-forms' || $isPublicJobs) {
            $isSubmissionRoute = str_contains($route->uri(), '/submissions');

            return match ($route->getActionMethod()) {
                'publishingStatuses' => 'List Job Form Publishing Statuses',
                'submissionStatuses' => 'List Job Form Submission Statuses',
                'workTypes' => 'List Job Work Types',
                'workModes' => 'List Job Work Modes',
                'experienceRanges' => 'List Job Experience Ranges',
                'index' => $isPublicJobs
                    ? 'Explore Open Positions'
                    : ($isSubmissionRoute ? 'List Job Form Submissions' : 'List Job Forms'),
                'store' => 'Create Job Form',
                'show' => $isPublicJobs
                    ? 'Get Public Job Details'
                    : ($isSubmissionRoute ? 'Get Job Form Submission' : 'Get Job Form'),
                'update' => 'Update Job Form', 'publish' => 'Publish Job Form', 'activation' => 'Update Job Form Activation',
                'applicationForm' => 'Get Public Job Application Form', 'submit' => 'Submit Public Job Application',
                'export' => 'Export Job Form Submissions', 'downloadCv' => 'Download Applicant CV', 'startHiring' => 'Start Hiring Process',
                'destroy' => str_contains($route->uri(), 'submissions') ? 'Delete Job Form Submission' : 'Delete Job Form',
                default => Str::headline($route->getActionMethod()),
            };
        }

        if ($module === 'fields' && ($parts[1] ?? '') === 'form-fields') {
            return 'Get Professional Field Form Configuration';
        }

        $resource = in_array($module, ['master-data', 'access-control'], true) ? $parts[1] : $module;
        $resource = $resource === 'fields' ? 'professional-fields' : $resource;
        $singular = Str::headline(Str::singular($resource));

        if (end($parts) === 'status') {
            return 'Update '.$singular.' Status';
        }

        return match ($method) {
            'GET' => str_contains($route->uri(), '{') ? 'Get '.$singular : 'List '.Str::headline($resource),
            'POST' => 'Create '.$singular,
            'PUT', 'PATCH' => 'Update '.$singular,
            'DELETE' => 'Delete '.$singular,
            default => Str::headline($route->getActionMethod()),
        };
    }
}
