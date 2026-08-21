<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\LeaveRequest; use App\Models\LeaveType; use App\Models\SalaryComponent; use App\Models\SalaryStructureItem; use Illuminate\Http\Request;
class HrmDetailExportController extends Controller {
 public function leaveType(LeaveType $leaveType, Request $request){
    $rows=$this->leaveRows($leaveType,$request)->get();
    $summary=[
        'total_requests' => $rows->count(),
        'total_days' => $rows->sum('total_days'),
        'approved' => $rows->where('approval_status','approved')->count(),
        'pending' => $rows->where('approval_status','pending')->count(),
        'rejected' => $rows->where('approval_status','rejected')->count(),
    ];
    return view('admin.hrm-details.leave-type',compact('leaveType','rows','summary'));
 }
 public function leaveExport(LeaveType $leaveType, Request $request){return $this->csv('leave_type_'.$leaveType->id,$this->leaveRows($leaveType,$request)->get()->map(fn($r)=>[$r->employee->full_name,$r->start_date,$r->end_date,$r->total_days,$r->approval_status]),['Employee','Start','End','Days','Status'],$request);}
 public function component(SalaryComponent $salaryComponent, Request $request){
    $rows=$this->componentRows($salaryComponent,$request)->get();
    $summary=[
        'total_entries' => $rows->count(),
        'total_amount' => $rows->sum('amount'),
        'employee_count' => $rows->pluck('salaryStructure.employee_id')->unique()->count(),
    ];
    return view('admin.hrm-details.salary-component',compact('salaryComponent','rows','summary'));
 }
 public function componentExport(SalaryComponent $salaryComponent, Request $request){return $this->csv('salary_component_'.$salaryComponent->id,$this->componentRows($salaryComponent,$request)->get()->map(fn($r)=>[$r->salaryStructure->employee->full_name,$r->salaryStructure->effective_date,$r->amount]),['Employee','Effective date','Amount'],$request);}
 private function leaveRows($type,$r){$q=LeaveRequest::with('employee')->where('leave_type_id',$type->id);if($r->from)$q->whereDate('start_date','>=',$r->from);if($r->to)$q->whereDate('end_date','<=',$r->to);return $q->latest('start_date');}
 private function componentRows($component,$r){$q=SalaryStructureItem::with('salaryStructure.employee')->where('salary_component_id',$component->id);if($r->from)$q->whereHas('salaryStructure',fn($x)=>$x->whereDate('effective_date','>=',$r->from));if($r->to)$q->whereHas('salaryStructure',fn($x)=>$x->whereDate('effective_date','<=',$r->to));return $q->latest();}
 private function csv($name,$rows,$headers,$request){$mime=$request->format==='excel'?'application/vnd.ms-excel':'text/csv';return response()->streamDownload(function()use($rows,$headers){$out=fopen('php://output','w');fputcsv($out,$headers);foreach($rows as $row)fputcsv($out,$row);fclose($out);},$name.'.csv',['Content-Type'=>$mime]);}
}
