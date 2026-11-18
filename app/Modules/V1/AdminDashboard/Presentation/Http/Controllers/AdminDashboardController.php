<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Application\Services\AdminDashboardService;
use App\Modules\V1\AdminDashboard\Presentation\Http\Requests\GetAdminDashboardRequest;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Response;

/**
 * @group Admin
 *
 * @subgroup Dashboard
 */
class AdminDashboardController extends Controller
{
    #[Response('{"success":true,"data":{"period":{"key":"month","from":"2026-11-01T00:00:00+02:00","to":"2026-11-30T23:59:59+02:00"},"metrics":{"new_applications":{"value":186,"awaiting_review":42},"interviews":{"value":18,"today":5},"talents":{"value":1000,"added_in_period":3},"active_clients":{"value":376,"joined_in_period":2}},"applications_and_offers":{"applications":[{"status":"submitted","label":"Submitted","count":186},{"status":"scheduled","label":"Scheduled","count":74},{"status":"accepted","label":"Accepted","count":32}],"offers":[{"status":"pending","label":"Pending","count":9},{"status":"countered","label":"Countered","count":3},{"status":"accepted","label":"Accepted","count":5},{"status":"rejected","label":"Rejected","count":2}]},"upcoming_interviews":[{"id":15,"source":"talent_evaluation","type":"technical","request_item_id":null,"application_id":41,"start":"2026-11-18T10:00:00+02:00","end":"2026-11-18T11:00:00+02:00","status":"confirmed","status_label":"Confirmed","talent":{"id":22,"full_name":"Ahmed Hassan"},"job_title":{"id":7,"name":"Backend Developer"},"workspace":null},{"id":9,"source":"hiring","type":"hiring","request_item_id":7,"application_id":null,"start":"2026-11-18T12:00:00+02:00","end":"2026-11-18T13:00:00+02:00","status":"awaiting_responses","status_label":"Awaiting Responses","talent":{"id":22,"full_name":"Ahmed Hassan"},"job_title":{"id":7,"name":"Backend Developer"},"workspace":{"id":4,"name":"Qader Technology"}}],"hiring_requests":{"open_count":24,"summary":{"in_review":6,"active":14,"on_hold":4},"latest":{"id":20,"reference":"HR-2026-MYY6WXEB","status":"active","workspace":{"id":4,"name":"Qader Technology"},"positions":[{"job_title":"Backend Developer","count":2}],"created_at":"2026-11-18T08:00:00+02:00"}},"latest_clients":[{"id":5,"full_name":"Mostafa Ali","initials":"MA","workspace":{"id":4,"name":"Qader Technology"},"industry":"Technology","registered_at":"2026-11-18T08:00:00+02:00"}],"help_requests":{"open_count":11,"unassigned_count":6,"summary":{"new":4,"in_progress":7,"closed_in_period":12},"latest":{"id":9,"reference":"REQ-1009","title":"Hiring Support","status":"new","status_label":"New","contact_method":"whatsapp","contact_method_label":"WhatsApp","workspace":{"id":4,"name":"Qader Technology"},"assigned_admin":null,"created_at":"2026-11-18T08:00:00+02:00"}}}}', 200, 'Dashboard data with both talent-evaluation and hiring interviews')]
    public function __invoke(GetAdminDashboardRequest $request, AdminDashboardService $service): JsonResponse
    {
        return ApiResponse::success($service->get($request->period()));
    }
}
