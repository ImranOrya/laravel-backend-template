<?php

namespace App\Http\Controllers\v1\template;

use Carbon\Carbon;
use App\Models\Ngo;
use App\Models\User;
use App\Models\Donor;
use App\Models\Setting;
use App\Models\Approval;
use App\Models\Document;
use App\Models\Agreement;
use App\Traits\FilterTrait;
use Illuminate\Http\Request;
use App\Models\AgreementStatus;
use App\Models\ApprovalDocument;
use App\Models\AgreementDocument;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Enums\Types\ApprovalTypeEnum;
use App\Repositories\Approval\ApprovalRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;

class ApprovalController extends Controller
{
    // use HelperTrait;
    use FilterTrait;
    protected $approvalRepository;

    public function __construct(
        ApprovalRepositoryInterface $approvalRepository,
    ) {
        $this->approvalRepository = $approvalRepository;
    }
    public function approval(Request $request, $approval_id)
    {
        $approval =  DB::table('approvals as a')
            ->where('a.id', $approval_id)->first();
        if (!$approval) {
            return response()->json([
                'message' => __('app_translation.approval_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $tr = [];
        if ($approval->requester_type == User::class) {
            $tr = $this->approvalRepository->userApproval($approval_id);
        }
        return response()->json($tr, 200, [], JSON_UNESCAPED_UNICODE);
    }
    // public function approvalSubmit(Request $request)
    // {
    //     $request->validate([
    //         "approved" => "required",
    //         "approval_id" => "required",
    //     ]);
    //     $approval_id = $request->approval_id;
    //     $approval =  Approval::find($approval_id);
    //     if (!$approval) {
    //         return response()->json([
    //             'message' => __('app_translation.approval_not_found'),
    //         ], 404, [], JSON_UNESCAPED_UNICODE);
    //     }
    //     DB::beginTransaction();
    //     $authUser = $request->user();
    //     if ($approval->requester_type === Ngo::class) {
    //         if ($approval->notifier_type_id == NotifierEnum::ngo_submitted_register_form->value) {
    //             $agreement = Agreement::where('ngo_id', $approval->requester_id)
    //                 ->latest("end_date")
    //                 ->first();
    //             if (!$agreement) {
    //                 return response()->json(
    //                     [
    //                         'message' => __('app_translation.agreement_not_exists')
    //                     ],
    //                     500,
    //                     [],
    //                     JSON_UNESCAPED_UNICODE
    //                 );
    //             }
    //             // 1. Find NGO
    //             $ngo = Ngo::where('id', $approval->requester_id)->first();
    //             if (!$ngo) {
    //                 return response()->json([
    //                     'message' => __('app_translation.ngo_not_found'),
    //                 ], 404, [], JSON_UNESCAPED_UNICODE);
    //             }
    //             if ($request->approved == true) {
    //                 $approval->notifier_type_id = NotifierEnum::ngo_register_form_accepted->value;
    //                 $approval->approval_type_id = ApprovalTypeEnum::approved->value;
    //                 $approval->respond_date = Carbon::now();
    //                 $approval->responder_id = $authUser->id;
    //                 $approval->respond_comment = $request->respond_comment;
    //                 $approval->completed = true;

    //                 AgreementStatus::where('agreement_id', $agreement->id)->update(['is_active' => false]);
    //                 $agreementStatus = AgreementStatus::create([
    //                     'agreement_id' => $agreement->id,
    //                     'userable_id' => $authUser->id,
    //                     'userable_type' => $this->getModelName(get_class($authUser)),
    //                     "is_active" => true,
    //                     'status_id' => StatusEnum::registered->value,
    //                 ]);

    //                 $this->storeNgoAgreementWithTrans($agreementStatus->id, [
    //                     'pashto' => 'لاسلیک شوی د نوم لیکنې فورمه تصویب شوه.',
    //                     'farsi' => 'فرم ثبت امضا شده تایید شد.',
    //                     'english' => 'Signed Register Form Approved.'
    //                 ]);

    //                 // 1. Assign Approval Document value to agreement and document
    //                 $approvalDocuments = ApprovalDocument::where('approval_id', $approval_id)
    //                     ->get();
    //                 foreach ($approvalDocuments as $document) {
    //                     $document = Document::create([
    //                         'actual_name' => $document->actual_name,
    //                         'size' => $document->size,
    //                         'path' => $document->path,
    //                         'type' => $document->type,
    //                         'check_list_id' => $document->check_list_id,
    //                     ]);

    //                     AgreementDocument::create([
    //                         'document_id' => $document->id,
    //                         'agreement_id' => $agreement->id,
    //                     ]);
    //                 }

    //                 // 1. Validate date
    //                 $expirationDate = Setting::where('id', SettingEnum::registeration_expire_time->value)
    //                     ->select('id', 'value as days')
    //                     ->first();
    //                 if (!$expirationDate) {
    //                     return response()->json(
    //                         [
    //                             'message' => __('app_translation.setting_record_not_found'),
    //                         ],
    //                         404,
    //                         [],
    //                         JSON_UNESCAPED_UNICODE
    //                     );
    //                 }
    //                 $agreement = Agreement::where('ngo_id', $ngo->id)
    //                     ->where('end_date', null) // Order by end_date descending
    //                     ->first();           // Get the first record (most recent)
    //                 if (!$agreement) {
    //                     return response()->json(
    //                         [
    //                             'message' => __('app_translation.doc_already_submitted'),
    //                         ],
    //                         500,
    //                         [],
    //                         JSON_UNESCAPED_UNICODE
    //                     );
    //                 }
    //                 $end_date = Carbon::parse($agreement->start_date)->addDays((int)$expirationDate->days);
    //                 $agreement->end_date = $end_date;
    //                 $agreement->save();
    //                 // Allow project permission
    //                 $this->approvedNgoPermissions($ngo->id);

    //                 $this->notificationRepository->SendNotification($request, [
    //                     "userable_type" => User::class,
    //                     "notifier_type_id" => NotifierEnum::ngo_register_form_accepted->value,
    //                     "message" => ""
    //                 ]);
    //             } else {
    //                 $approval->approval_type_id = ApprovalTypeEnum::rejected->value;
    //                 // 1. set current agreement start_date and end_date to null
    //                 $agreement->end_date = null;
    //                 $agreement->start_date = null;
    //                 // 2. Update Approval
    //                 $approval->approval_type_id = ApprovalTypeEnum::rejected->value;
    //                 $approval->respond_date = Carbon::now();
    //                 $approval->responder_id = $authUser->id;
    //                 $approval->respond_comment = $request->respond_comment;
    //                 $approval->completed = true;

    //                 AgreementStatus::where('agreement_id', $agreement->id)->update(['is_active' => false]);
    //                 $agreementStatus = AgreementStatus::create([
    //                     'agreement_id' => $agreement->id,
    //                     'userable_id' => $authUser->id,
    //                     'userable_type' => $this->getModelName(get_class($authUser)),
    //                     "is_active" => true,
    //                     'status_id' => StatusEnum::document_upload_required->value,
    //                 ]);

    //                 $this->storeNgoAgreementWithTrans($agreementStatus->id, [
    //                     'pashto' => 'لاسلیک شوی د نوم لیکنې فورمه رد شوه.',
    //                     'farsi' => 'فرم ثبت امضا شده رد شد.',
    //                     'english' => 'Signed Register Form rejected.'
    //                 ]);

    //                 // 3. Send Notification
    //                 $this->notificationRepository->SendNotification($request, [
    //                     "userable_type" => User::class,
    //                     "notifier_type_id" => NotifierEnum::ngo_submitted_register_form->value,
    //                     "message" => ""
    //                 ]);
    //             }
    //         }
    //     }
    //     $approval->save();

    //     DB::commit();
    //     return response()->json([
    //         'message' => __('app_translation.success'),
    //     ], 200, [], JSON_UNESCAPED_UNICODE);
    // }
    // User
    public function pendingUserApproval(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        $query = $this->approvalRepository->getByNotifierTypeAndRequesterType(
            ApprovalTypeEnum::pending->value,
            User::class
        );
        $this->applySearch($query, $request, [
            'id' => 'a.id',
            'requester' => 'usr.username',
        ]);
        $approvals = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($approvals, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function approvedUserApproval(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        $query = $this->approvalRepository->getByNotifierTypeAndRequesterType(
            ApprovalTypeEnum::approved->value,
            User::class
        );
        $this->applySearch($query, $request, [
            'id' => 'a.id',
            'requester' => 'usr.username',
        ]);
        $approvals = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($approvals, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function rejectedUserApproval(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        $query = $this->approvalRepository->getByNotifierTypeAndRequesterType(
            ApprovalTypeEnum::rejected->value,
            User::class
        );
        $this->applySearch($query, $request, [
            'id' => 'a.id',
            'requester' => 'usr.username',
        ]);
        $approvals = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($approvals, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
