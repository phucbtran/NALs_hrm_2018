<?php

namespace App\Export;
use App\Models\Confirm;
use App\Models\Role;
use App\Models\Team;
use App\Service\SearchConfirmService;
use App\Service\SearchEmployeeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Created by PhpStorm.
 * User: Ngoc Quy
 * Date: 4/23/2018
 * Time: 4:47 PM
 */

class ConfirmExport implements FromCollection,WithEvents, WithHeadings
{
    use Exportable, RegistersEventListeners;
    private $searchConfirmService;
    protected $returnCollectionConfirm;
    /**
     * @var Request
     */
    private $request;


    /**
     * @var Request
     */

    public function __construct(SearchConfirmService $searchConfirmService, Request $request)
    {
        $this->request = $request;
        $this->$searchConfirmService = $searchConfirmService;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Confirm::select('confirms.*')->distinct('confirms.id');

        if (!isset($this->request['number_record_per_page'])) {
            $this->request['number_record_per_page'] = config('settings.paginate');
        }

        $params['search'] = [
            'po_id' => !empty($this->request->po_id) ? $this->request->po_id : '',
            'employee_name' => !empty($this->request->employee_name) ? $this->request->employee_name : '',
            'email' => !empty($this->request->email) ? $this->request->email : '',
            'project_id' => !empty($this->request->project_id) ? $this->request->project_id : '',
            'absence_type' => !empty($this->request->absence_type) ? $this->request->absence_type : '',
            'from_date' => !empty($this->request->from_date) ? $this->request->from_date : '',
            'to_date' => !empty($this->request->to_date) ? $this->request->to_date : '',
            'confirm_status' => !empty($this->request->confirm_status) ? $this->request->confirm_status : '',
        ];
        foreach ($params as $key => $value) {
            $po_id = $value['po_id'];
            $employee_name = $value['employee_name'];
            $email = $value['email'];
            $project_id = $value['project_id'];
            $absence_type = $value['absence_type'];
            $from_date = $value['from_date'];
            $to_date = $value['to_date'];
            $confirm_status = $value['confirm_status'];
        }

//        $status = !is_null($this->request->status) ? $this->request->status : '';
        if (!empty($role)) {
            $query
                ->whereHas('role', function ($query) use ($role) {
                    $query->where("name",$role);
                });
        }
        $query->join('absences', 'absences.id', '=', 'confirms.absence_id')
            ->join('employees', 'employees.id', '=', 'absences.employee_id')
            ->join('processes', 'processes.employee_id', '=', 'employees.id')
            ->join('projects', 'projects.id', '=', 'processes.project_id');
        if (!empty($employee_name)) {
            $query->where('employees.name', 'like', '%'.$employee_name.'%');
        }
        if (!empty($email)) {
            $query->where('employees.email', 'like', '%'.$email.'%');

        }
        if (!empty($project_id)) {
            $query->where('projects.id', '=', $project_id);

        }
        if (!empty($absence_type)) {
            $query->where('absences.absence_type_id', '=', $absence_type);

        }
        if (!empty($from_date) && !empty($to_date)) {
            $from_date .= ':00';
            $to_date .= ':00';
            $query->where('absences.from_date', '>=', $from_date);
            $query->where('absences.to_date', '<=', $to_date);
        } else if (!empty($from_date) && empty($to_date)) {
            $from_date .= ':00';
            $query->where('absences.from_date', '>=', $from_date);
        } else if (empty($from_date) && !empty($to_date)) {
            $to_date .= ':00';
            $query->where('absences.to_date', '<=', $to_date);
        }
        if (!empty($confirm_status)) {
            $query->where('confirms.absence_status_id', '=', $confirm_status);
        }

       /* if (!is_null($request['status'])) {
            $query->Where('work_status', $request['status']);
        }*/

        $confirmSearch = $query->
            where('confirms.employee_id', '=', $po_id)
                ->where('confirms.is_process', '=', 1)
                ->where('confirms.delete_flag', '=', 0)
                ->orderBy('confirms.id', 'desc')
                ->paginate($this->request['number_record_per_page'], ['confirms.*']);

        return $confirmSearch->map(function(Confirm $item) {
//            $item->
            

            if ($item->team_id == null){
                $item->team_id = "-";
            }
            else{
                $teamFindId = Team::where('id',$item->team_id)->first();
                $item->team_id = $teamFindId->name;
            }
            if ($item->role_id == null){
                $item->role_id = "-";
            }
            else{
                $roleFindId = Role::where('id',$item->role_id)->first();
                $item->role_id = $roleFindId->name;
            }
//
//            $item->work_status = $item->work_status?'Inactive':'Active';
            $dateNow = date('Y-m-d');
            $dateEndWork = Employee::find($item->id);

            if (($item->work_status == 0) && ($dateEndWork->endwork_date < $dateNow)){
                $item->work_status = 'Expired';
            }
            if(($item->work_status == 0) && ($dateEndWork->endwork_date >= $dateNow)){
                $item->work_status = 'Active';
            }
            if ($item->work_status == 1){
                $item->work_status = 'Quited';
            }
            unset($item->password); unset($item->remember_token);
            unset($item->birthday);unset($item->gender);
            unset($item->mobile);
            unset($item->address);
            unset($item->marital_status);
//            unset($item->work_status);
            unset($item->startwork_date);
            unset($item->endwork_date);unset($item->curriculum_vitae);
            unset($item->is_employee);unset($item->company);
            unset($item->avatar);unset($item->employee_type_id);unset($item->salary_id);
            unset($item->updated_at);unset($item->last_updated_by_employee);
            unset($item->created_at);unset($item->created_by_employee);
            unset($item->delete_flag);

            return $item;
        });
    }
    public static function beforeExport(BeforeExport $event)
    {
        //
    }

    public static function beforeWriting(BeforeWriting $event)
    {
        //
    }

    public static function beforeSheet(BeforeSheet $event)
    {
        //
    }

    public static function afterSheet(AfterSheet $event)
    {
        //
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            trans('absence.confirmation.employee_name'),
            trans('absence.confirmation.email'),
            trans('absence.confirmation.project_name'),
            trans('absence.confirmation.from'),
            trans('absence.confirmation.to'),
            trans('absence.confirmation.type'),
            trans('absence.confirmation.cause'),
            trans('absence.confirmation.description'),
            trans('absence.confirmation.status'),
            trans('absence.confirmation.reject_cause')
        ];
    }

}