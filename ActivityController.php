<?php

namespace App\Http\Controllers;

use App\Models\Voyage;
use App\Models\VoyageActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ ACTIVITY START ----------------------------
    //--------------------------------------------------------------------
    public function activityStart($voyage_id, $vsl_type)
    {
        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        $locList = DB::table('locations')
            ->where('apply_for', 'activity')
            ->orWhere('apply_for', 'voy-act')
            ->get();

        $voyageData = DB::table('voyages')
            ->where('voyage_id', $voyage_id)
            ->get();

        $lastActivityData = DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->whereNull('end_date')
            ->get();

        if ($lastActivityData->count()) {
            return redirect()
                ->route('voyage_show_description', [
                    'voyage_id' => $voyage_id,
                ])
                ->with('status_msg', 'ATTENTION. You should finish ongoing activitiy before start a new one');
        } else {

            //--- GET END DATE OF LAST ACTIVITY
            $lastActivityData = DB::table('voyage_activities')
                ->where('voyage_id', $voyage_id)
                ->whereNull('subactiv_for')
                ->orderBy('id', 'desc')
                ->first('end_date');
            if ($lastActivityData) {
                $last_end_data = $lastActivityData->end_date;
            } else {
                $last_end_data = null;
            }

            return view('activities.add_new_activity', [
                'loc_list' => $locList,
                'voyage_data' => $voyageData[0],
                'last_end_date' => $last_end_data,
                'voyage_id' => $voyage_id,
                'vsl_type' => $vsl_type,
                'vsl_list' => $vslList,
            ]);
        }
    }

    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDesc($id)
    {
        $activityData = VoyageActivity::find($id);

        $voyage_data = DB::table('voyages')
            ->where('voyage_id', $activityData->voyage_id)
            ->first();

        if ($voyage_data->voyage_state == 'finished') {
            return view('activities.about_activity', [
                'voyage_data' => $voyage_data,
                'activity_data' => $activityData,
            ]);
        }

        $locList = DB::table('locations')
            ->where('apply_for', 'activity')
            ->orWhere('apply_for', 'voy-act')
            ->get();

        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        return view('activities.show_description', [
            'loc_list' => $locList,
            'voyage_data' => $voyage_data,
            'activity_data' => $activityData,
            'vsl_list' => $vslList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SAVE ACTIVITY -----------------------------
    //--------------------------------------------------------------------
    public function saveActivity(Request $request)
    {
        $valid = $request->validate([
            'activity_name' => 'required',
            'start_date' => 'required',
            'location' => 'required',
        ]);        

        DB::table('voyage_activities')
            ->insert([
                'voyage_id' => $request->voyage_id,
                'vsl_type' => $request->vsl_type,
                'vsl_name' => $request->vsl_name,
                'ogv' => $request->ogv,
                'voyage_id' => $request->voyage_id,
                'activity_name' => $request->activity_name,
                'activity_number' => time(),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'duration' => $request->duration,
                'location' => $request->location,
                'cargo_loaded' => $request->cargo_loaded,
                'cargo_discharged' => $request->cargo_discharged,
                'cargo_transfered' => $request->cargo_transfered,
                'load_rate' => $request->load_rate,
                'disch_rate' => $request->disch_rate,
                'transf_rate' => $request->transf_rate,
                'feeder_name' => $request->feeder_name,
                'barge_name' => $request->barge_name,
                'remark' => $request->remark,
            ]);

            //---SAVE INTO ACTION PLAN
            if($request->action_plan_add){
                DB::table('action_plans')
                ->insert([
                    'unit_name' => $request->vsl_name,
                    'action_group' => "VOYAGE",
                    'act_date' => $request->start_date,
                    'raised_by' => $request->vsl_name,
                    'description' => $request->remark,
                    'state' => "OPEN",
                ]);
            }

        return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $request->voyage_id,
            ])
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE ACTIVITY ---------------------------
    //--------------------------------------------------------------------
    public function updateActivity(Request $request)
    {
        $valid = $request->validate([
            'activity_name' => 'required',
            'start_date' => 'required',
            'duration' => 'required',
            'location' => 'required',
        ]);

        DB::table('voyage_activities')
            ->where('id', $request->id)
            ->update([
                'activity_name' => $request->activity_name,
                'main_break' => $request->main_break,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'duration' => $request->duration,
                'location' => $request->location,

                'cargo_loaded' => $request->cargo_loaded,
                'cargo_discharged' => $request->cargo_discharged,
                'cargo_transfered' => $request->cargo_transfered,

                'load_rate' => $request->load_rate,
                'disch_rate' => $request->disch_rate,
                'transf_rate' => $request->transf_rate,

                'feeder_name' => $request->feeder_name,
                'barge_name' => $request->barge_name,
                'remark' => $request->remark,
            ]);


        return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $request->voyage_id,
            ])
            ->with('status_msg', 'Activity updated successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ FINISH ACTIVITY START----------------------
    //--------------------------------------------------------------------
    public function finishActivityStart($voyage_id, $activity_number)
    {
        $activity_data = DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->where('activity_number', $activity_number)
            ->first();

        $voyage_data = DB::table('voyages')
            ->where('voyage_id', $voyage_id)
            ->first();

        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        return view('activities.finish_activity', [
            'activity_data' => $activity_data,
            'voyage_data' => $voyage_data,
            'voyage_id' => $voyage_id,
            'vsl_type' => $voyage_data->vsl_type,
            'vsl_list' => $vslList
        ]);
    }



    //--------------------------------------------------------------------
    //------------------------ FINISH ACTIVITY ---------------------------
    //--------------------------------------------------------------------
    public function finishActivity(Request $request)
    {

        $valid = $request->validate([
            'end_date' => 'required',
            'duration' => 'required',
        ]);

        DB::table('voyage_activities')
            ->where('id', $request->id)
            ->update([
                'end_date' => $request->end_date,
                'duration' => $request->duration,
                'cargo_loaded' => $request->cargo_loaded,
                'cargo_discharged' => $request->cargo_discharged,
                'cargo_transfered' => $request->cargo_transfered,
                'load_rate' => $request->load_rate,
                'disch_rate' => $request->disch_rate,
                'transf_rate' => $request->transf_rate,
                'remark' => $request->remark,
            ]);


        return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $request->voyage_id,
            ])
            ->with('status_msg', 'Activity finished successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE ACTIVITY ---------------------------
    //--------------------------------------------------------------------
    public function deleteActivity($voyage_id, $activity_number)
    {
        DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->where('activity_number', $activity_number)
            ->delete();

        DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->where('subactiv_for', $activity_number)
            ->delete();

            return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $voyage_id,
            ])
            ->with('status_msg', 'Activity removed successfully!');
    }







    //#################################################################################################################
    //#################################################################################################################

    //--------------------------------------------------------------------
    //------------------------ SUBACTIVITY START -------------------------
    //--------------------------------------------------------------------
    public function subactivityStart($voyage_id, $sub_for, $vsl_type)
    {
        $voyageData = DB::table('voyages')
            ->where('voyage_id', $voyage_id)
            ->get();

        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        $locList = DB::table('locations')
            ->where('apply_for', 'activity')
            ->orWhere('apply_for', 'voy-act')
            ->get();

        return view('activities.add_sub_activity', [
            'sub_for' => $sub_for,
            'loc_list' => $locList,
            'voyage_data' => $voyageData[0],
            'voyage_id' => $voyage_id,
            'vsl_type' => $vsl_type,
            'vsl_list' => $vslList,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ ADD SUB-ACTIVITY --------------------------
    //--------------------------------------------------------------------
    public function saveSubActivity(Request $request)
    {
        $valid = $request->validate([
            'activity_name' => 'required',
            'start_date' => 'required',
            'location' => 'required',
        ]);

        DB::table('voyage_activities')
            ->insert([
                'voyage_id' => $request->voyage_id,
                'vsl_type' => $request->vsl_type,
                'vsl_name' => $request->vsl_name,
                'ogv' => $request->ogv,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'duration' => $request->duration,
                'location' => $request->location,
                'activity_name' => $request->activity_name,
                'activity_number' => time(),
                'subactiv_for' => $request->subactiv_for,
                'main_break' => $request->main_break,
                'feeder_name' => $request->feeder_name,
                'barge_name' => $request->barge_name,
                'remark' => $request->remark,
            ]);


        return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $request->voyage_id,
            ])
            ->with('status_msg', 'Data saved successfully!');
    }
}
