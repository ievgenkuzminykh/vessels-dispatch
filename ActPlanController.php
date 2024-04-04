<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ActPlanController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ ACTION MENU -------------------------------
    //--------------------------------------------------------------------
    public function actMenu()
    {
        return view('act_plan.menu');
    }

    //--------------------------------------------------------------------
    //------------------------ STATISTICS --------------------------------
    //--------------------------------------------------------------------
    public function actStatistic()
    {
        $actList = DB::table('action_plans')
            ->orderBy('id', 'desc')
            ->get();

        return view('act_plan.statistic', [
            'actList' => $actList,
        ]);
    }



    //--------------------------------------------------------------------
    //------------------------ MOM START ---------------------------------
    //--------------------------------------------------------------------
    public function actMomStart()
    {
        $momList = DB::table('min_of_meetings')
            ->orderBy('id', 'desc')
            ->get();

        $topicList = [];
        $unitsList = [];
        foreach ($momList as $mom) {
            if (!in_array($mom->mom_topic, $topicList)) {
                $topicList[] = $mom->mom_topic;
            }
            if (!in_array($mom->unit_name, $unitsList)) {
                $unitsList[] = $mom->unit_name;
            }
        }

        return view('act_plan.mom_start', [
            'momList' => $momList,
            'topicList' => $topicList,
            'unitsList' => $unitsList,
            'selected_unit_name' => '',
            'selected_topic' => '',
            'selected_date_from' => '',
            'selected_date_to' => '',
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ MOM FILTER --------------------------------
    //--------------------------------------------------------------------
    public function actMomFilter(Request $request)
    {
        $momDB = DB::table('min_of_meetings')
            ->orderBy('id', 'desc')
            ->get();

        $topicList = [];
        $unitsList = [];
        foreach ($momDB as $mom) {
            if (!in_array($mom->mom_topic, $topicList)) {
                $topicList[] = $mom->mom_topic;
            }
            if (!in_array($mom->unit_name, $unitsList)) {
                $unitsList[] = $mom->unit_name;
            }
        }

        $selected_unit_name = $request->unit_name;
        $selected_topic = $request->mom_topic;
        $dt1 = $request->date_from . ' 00:00';
        $dt2 = $request->date_to . ' 23:59';

        $momList = DB::table('min_of_meetings')
            ->whereBetween('mom_date', [$dt1, $dt2])

            ->when($selected_unit_name, function ($query, $selected_unit_name) {
                $query->where('unit_name', $selected_unit_name);
            })

            ->when($selected_topic, function ($query, $selected_topic) {
                $query->where('mom_topic', $selected_topic);
            })

            ->orderBy('id', 'desc')
            ->get();

        return view('act_plan.mom_start', [
            'momList' => $momList,
            'topicList' => $topicList,
            'unitsList' => $unitsList,
            'selected_unit_name' => $selected_unit_name,
            'selected_topic' => $selected_topic,
            'selected_date_from' => $request->date_from,
            'selected_date_to' => $request->date_to,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ MOM NEW -----------------------------------
    //--------------------------------------------------------------------
    public function actMomNew()
    {
        $usersList = DB::table('users')
            ->orderBy('name', 'asc')
            ->get(['name', 'email']);

        return view('act_plan.mom_add_new', [
            'usersList' => $usersList,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ MOM SAVE ---------------------------------
    //--------------------------------------------------------------------
    public function actMomSave(Request $request)
    {
        $request->validate([
            'unit_name' => 'required',
            'mom_date' => 'required',
            'mom_time' => 'required',
            'mom_topic' => 'required',
            'attendees' => 'required',
        ]);
        $raisedBy = Auth::user()->name;

        $blocksMass = json_decode($request->block_mass);
        $momDate = $request->mom_date . ' ' . $request->mom_time;
        $attendees = implode(',', $request->attendees);

        if (count($blocksMass) < 1) {
            return redirect()
                ->route('act_mom_new')
                ->with('status_msg', 'ERROR. Your MOM has no blocks and can not be saved');
        }

        $cssPath = '/css/bootstrap.min.css';
        $cssPath1 = '/css/kev.css';
        $logoPath = '/img/leiteng.png';
        $htmlContent = '<!DOCTYPE html><html lang="en"><head><title>MINUTES OF MEETING</title>';
        $htmlContent .= "<link rel='stylesheet' type='text/css' href='$cssPath'>";
        $htmlContent .= "<link rel='stylesheet' type='text/css' href='$cssPath1'>";
        $htmlContent .= '</head><body>';
        $htmlContent .= "<div class='container py-4'>";
        $htmlContent .= "<div class='mt-3'><img src='$logoPath' height=40></div>";
        $htmlContent .= "<div class='text-center mt-5'><h2>MINUTES OF MEETING</h2></div>";
        $htmlContent .= <<<HTML

        <div class="row border-bottom p-1 mt-5">
            <div class="col-4 border-end pt-2">
                <small class="text-muted fw-bolder">UNIT NAME</small>
            </div>
            <div class="col text-uppercase">$request->unit_name</div>
        </div>

        <div class="row border-bottom p-1 mt-3">
            <div class="col-4 border-end pt-2">
                <small class="text-muted fw-bolder">RAISED BY</small>
            </div>
            <div class="col text-uppercase">$raisedBy</div>
        </div>

        <div class="row border-bottom p-1 mt-3">
            <div class="col-4 border-end pt-2">
                <small class="text-muted fw-bolder">DATE & TIME</small>
            </div>
            <div class="col">$momDate</div>
        </div>

        <div class="row border-bottom p-1 mt-3">
            <div class="col-4 border-end pt-2">
                <small class="text-muted fw-bolder">TOPIC</small>
            </div>
            <div class="col">$request->mom_topic</div>
        </div>

        <div class="row border-bottom p-1 mt-3 mb-4">
            <div class="col-4 border-end pt-2">
                <small class="text-muted fw-bolder">ATTENDEES</small>
            </div>
            <div class="col">$attendees</div>
        </div>
HTML;

        foreach ($blocksMass as $block) {
            $htmlContent .= "<div class='border rounded mt-4 px-3'>";
            $htmlContent .= <<<HTML
                <div class='row bg-light'>
                    <div class='col-4'><small class='text-muted'>Group</small></div>
                    <div class='col fw-bold border-start'>{$block->action_group}</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Content</small></div>
                    <div class='col border-start'>{$block->txt_content}</div>
                </div>
HTML;

            if ($block->action_plan) {
                DB::table('action_plans')
                    ->insert([
                        'unit_name' =>  $block->raised_to,
                        'action_group' => $block->action_group,
                        'act_date' => $block->act_date,
                        'raised_by' => $raisedBy,
                        'description' => $block->description,
                        'pic' => $block->pic,
                        'comment' => $block->comment,
                        'target_date' => $block->target_date,
                        'state' => $block->state,
                    ]);

                $htmlContent .= <<<HTML
                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Raised by</small></div>
                    <div class='col border-start'>$raisedBy</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Raised to</small></div>
                    <div class='col border-start text-uppercase'>$block->raised_to</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Action date</small></div>
                    <div class='col border-start'>$block->act_date</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Description</small></div>
                    <div class='col border-start'>$block->description</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>PIC</small></div>
                    <div class='col border-start'>$block->pic</div>
                </div>
  
                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Comment</small></div>
                    <div class='col border-start'>$block->comment</div>
                </div>

                <div class='row kev-border-line'>
                    <div class='col-4'><small class='text-muted'>Target date</small></div>
                    <div class='col border-start'>$block->target_date</div>
                </div>

                <div class='row'>
                    <div class='col-4'><small class='text-muted'>Status</small></div>
                    <div class='col border-start'>$block->state</div>
                </div>
HTML;
            }
            $htmlContent .= '</div>';
        }
        $htmlContent .= '</div></body></html>';

        $fileName = 'MOM-' . str_replace(' ', '', $request->unit_name) . '_' . time() . '.html';
        Storage::put("public/files/$fileName", $htmlContent);
        $filePath = "/storage/files/" . $fileName;

        DB::table('min_of_meetings')
            ->insert([
                'raised_by' => $raisedBy,
                'unit_name' => $request->unit_name,
                'mom_date' => $momDate,
                'mom_topic' => $request->mom_topic,
                'attendees' => $attendees,
                'file_path' => $filePath,
            ]);

        return redirect()
            ->route('act_mom_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ MOM DELETE --------------------------------
    //--------------------------------------------------------------------
    public function actMomDelete($id)
    {
        $attFileRaw = DB::table('min_of_meetings')
            ->where('id', $id)
            ->first('file_path');

        //---DELETE OLD FILE
        $oldFilePath = str_replace('storage', 'public', $attFileRaw->file_path);
        Storage::delete($oldFilePath);

        DB::table('min_of_meetings')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('act_mom_start')
            ->with('status_msg', 'Record deleted successfully!');
    }






    //--------------------------------------------------------------------
    //------------------------ ACTION PLAN START -------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $actList = DB::table('action_plans')
            ->orderBy('id', 'desc')
            ->get();

        $usersList = DB::table('users')
            ->orderBy('name', 'asc')
            ->get(['name', 'email']);

        $vessels = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();

        $units = DB::table('company_units')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();

        $unitsList = [];
        foreach ($vessels as $item) {
            $unitsList[] = $item->name;
        }
        foreach ($units as $item) {
            $unitsList[] = $item->name;
        }

        return view('act_plan.start', [
            'selected_unit_name' => '',
            'selected_action_group' => [],
            'selected_pic' => [],
            'selected_state' => [],
            'actList' => $actList,
            'usersList' => $usersList,
            'unitsList' => $unitsList,
        ]);
    }

    //--------------------------------------------------------------------
    //--------------------- ACT PLAN FILTER APPLY ------------------------
    //--------------------------------------------------------------------
    public function filterApply(Request $request)
    {
        $unit_name = $request->unit_name;
        $action_group = $request->action_group;
        $pic = $request->pic;
        $state = $request->state;

        $actList = DB::table('action_plans')
            ->when($unit_name, function ($query, $unit_name) {
                $query->where('unit_name', 'LIKE', '%' . $unit_name . '%');
            })

            ->when($action_group, function ($query, $action_group) {
                $query->whereIn('action_group', $action_group);
            })

            ->when($pic, function ($query, $pic) {
                $query->whereIn('pic', $pic);
            })

            ->when($state, function ($query, $state) {
                $query->whereIn('state', $state);
            })

            ->orderBy('id', 'desc')
            ->get();

        $usersList = DB::table('users')
            ->orderBy('name', 'asc')
            ->get(['name', 'email']);

        $vessels = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();

        $units = DB::table('company_units')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();

        $unitsList = [];
        foreach ($vessels as $item) {
            $unitsList[] = $item->name;
        }
        foreach ($units as $item) {
            $unitsList[] = $item->name;
        }

        return view('act_plan.start', [
            'selected_unit_name' => $request->unit_name,
            'selected_action_group' => $request->action_group,
            'selected_pic' => $request->pic,
            'selected_state' => $request->state,
            'actList' => $actList,
            'usersList' => $usersList,
            'unitsList' => $unitsList,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ ACT PLAN EDIT -----------------------------
    //--------------------------------------------------------------------
    public function actEdit(Request $request)
    {
        $valid = $request->validate([
            'unit_name' => 'required',
            'action_group' => 'required',
            'act_date' => 'required',
            'pic' => 'required',
            'state' => 'required',
        ]);
        if ($request->edit_mode == 'new') {
            DB::table('action_plans')
                ->insert([
                    'unit_name' => implode(',', $request->unit_name),
                    'action_group' => $request->action_group,
                    'act_date' => $request->act_date,
                    'raised_by' => Auth::user()->name,
                    'description' => $request->description,
                    'pic' => $request->pic,
                    'comment' => $request->comment,
                    'target_date' => $request->target_date,
                    'state' => $request->state,
                ]);
        }
        if ($request->edit_mode == 'edit') {
            DB::table('action_plans')
                ->where('id', $request->rec_id)
                ->update([
                    'unit_name' => implode(',', $request->unit_name),
                    'action_group' => $request->action_group,
                    'act_date' => $request->act_date,
                    'description' => $request->description,
                    'pic' => $request->pic,
                    'comment' => $request->comment,
                    'target_date' => $request->target_date,
                    'state' => $request->state,
                ]);
        }
        if ($request->edit_mode == 'delete') {
            DB::table('action_plans')
                ->where('id', $request->rec_id)
                ->delete();
            return redirect()
                ->route('act_start')
                ->with('status_msg', 'Item deleted successfully!');
        }
        return redirect()
            ->route('act_start')
            ->with('status_msg', 'Data saved successfully!');
    }
}
