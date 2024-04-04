<?php

namespace App\Http\Controllers;

use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoyageController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $itemList = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id','name','abbr','imo','type']);

        return view('voyages.start', [
            'vsl_list' => $itemList,
        ]);
    }



    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDesc($voyage_id)
    {
        $voyageData = DB::table('voyages')
            ->where('voyage_id', $voyage_id)
            ->get();

        $actList = DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->orderBy('id', 'asc')
            ->get();

        return view('voyages.show_description', [
            'voyage_data' => $voyageData,
            'activities_list' => $actList
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ START VESSEL SELECTED ---------------------
    //--------------------------------------------------------------------
    public function startVslSelected($vsl_id, $vsl_name, $vsl_type)
    {
        $itemList = DB::table('voyages')
            ->where('vsl_id', $vsl_id)
            ->where('vsl_name', $vsl_name)
            ->orderBy('id', 'asc')
            ->get();

        return view('voyages.start_vsl', [
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'vsl_type' => $vsl_type,
            'voyages_list' => $itemList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ ADD NEW VOYAGE START-----------------------
    //--------------------------------------------------------------------
    public function addVoyageStart($vsl_id, $vsl_name, $vsl_type)
    {
        //---CHK ACTIVE VOYAGES
        $activeVoyageCnt = DB::table('voyages')
            ->where('vsl_id', $vsl_id)
            ->where('voyage_state', 'active')
            ->count('voyage_number');

        //---GET LAST ROB
        $lastRob = DB::table('voyages')
            ->where('vsl_id', $vsl_id)
            ->where('voyage_state', 'finished')
            ->orderBy('id', 'desc')
            ->first(
                [
                    'end_fo',
                    'end_do',
                    'end_lo',
                    'end_bw',
                    'end_fw',
                    'end_sludge',
                    'end_bilge',
                    'end_stores',
                    'end_lightship',
                    'end_constant',
                    'end_cargo',
                ]
            );

        $activeVoyages =  $activeVoyageCnt > 0;

        $ogvList = DB::table('ogvs')
            ->orderBy('name', 'desc')
            ->get();

        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        $locList = DB::table('locations')
            ->where('apply_for', 'voyage')
            ->orWhere('apply_for', 'voy-act')
            ->get();

        return view('voyages.add_new', [
            'loc_list' => $locList,
            'active_voyages' => $activeVoyages,
            'last_rob' => $lastRob,
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'vsl_type' => $vsl_type,
            'ogvList' => $ogvList,
            'vsl_list' => $vslList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SAVE NEW VOYAGE----------------------------
    //--------------------------------------------------------------------
    public function saveVoyage(Request $request)
    {
        $valid = $request->validate([
            'location' => 'required',
            'tsv' => 'required',
        ]);

        //--- SET VOYAGE NUMBER
        $lastVoyNumber = DB::table('voyages')
            ->where('vsl_id', $request->vsl_id)
            ->orderBy('id', 'desc')
            ->get('voyage_number');

        if ($lastVoyNumber->count()) {
            $voyageNumber = $lastVoyNumber[0]->voyage_number + 1;
        } else {
            $voyageNumber = 1;
        }

        $voyageRaw = $request->ogv . "_" . $request->vsl_name . "_" . $voyageNumber . "_" . $request->location;
        $voyageId = strtolower(str_replace(' ', '-', $voyageRaw));


        DB::table('voyages')
            ->insert([
                'voyage_id' => $voyageId,
                'voyage_number' => $voyageNumber,
                'ogv' => $request->ogv,
                'tow' => $request->tow,
                'tsv' => $request->tsv,
                'vsl_id' => $request->vsl_id,
                'vsl_name' => $request->vsl_name,
                'vsl_type' => $request->vsl_type,
                'location' => $request->location,
                'start_fo' => $request->start_fo,
                'start_do' => $request->start_do,
                'start_lo' => $request->start_lo,
                'start_bw' => $request->start_bw,
                'start_fw' => $request->start_fw,
                'start_sludge' => $request->start_sludge,
                'start_bilge' => $request->start_bilge,
                'start_stores' => $request->start_stores,
                'start_lightship' => $request->start_lightship,
                'start_constant' => $request->start_constant,
                'start_cargo' => $request->start_cargo,
                'cargo_adj' => $request->cargo_adj,
                'const_adj' => $request->const_adj,
                'voyage_state' => 'active',
            ]);


        return redirect()
            ->route('voyage_show_description', [
                'voyage_id' => $voyageId,
            ])
            ->with('status_msg', 'Voyage saved successfully! Now you can add activity');
    }


    //--------------------------------------------------------------------
    //------------------------ FINISH VOYAGE START------------------------
    //--------------------------------------------------------------------
    public function finishVoyageStart($voyage_id)
    {
        $ongoingActCnt = DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->whereNull('end_date')
            ->count();

        if ($ongoingActCnt) {
            return redirect()
                ->route('voyage_show_description', [
                    'voyage_id' => $voyage_id,
                ])
                ->with('status_msg', 'ATTENTION. You should finish ongoing activitiy before finish voyage');
        }


        $activityData = DB::table('voyage_activities')
            ->where('voyage_id', $voyage_id)
            ->orderBy('id', 'asc')
            ->get();

        $voyageData = DB::table('voyages')
            ->where('voyage_id', $voyage_id)
            ->get();

        $vslList = DB::table('vessels')
            ->get(['name', 'imo', 'type']);

        return view('voyages.finish', [
            'activity_data' => $activityData,
            'voyage_data' => $voyageData,
            'vsl_list' => $vslList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ FINISH VOYAGE -----------------------------
    //--------------------------------------------------------------------
    public function finishVoyage(Request $request)
    {
        $valid = $request->validate([
            'end_fo' => 'required|numeric',
            'end_do' => 'required|numeric',
            'end_lo' => 'required|numeric',
            'end_bw' => 'required|numeric',
            'end_fw' => 'required|numeric',
            'end_sludge' => 'required|numeric',
            'end_bilge' => 'required|numeric',
            'end_stores' => 'required|numeric',
            'end_lightship' => 'required|numeric',
            'end_constant' => 'required|numeric',
            'end_cargo' => 'numeric',
        ]);


        DB::table('voyages')
            ->where('voyage_id', $request->voyage_id)
            ->update([
                'tow' => $request->tow,
                'end_fo' => $request->end_fo,
                'end_do' => $request->end_do,
                'end_lo' => $request->end_lo,
                'end_bw' => $request->end_bw,
                'end_fw' => $request->end_fw,
                'end_sludge' => $request->end_sludge,
                'end_bilge' => $request->end_bilge,
                'end_stores' => $request->end_stores,
                'end_lightship' => $request->end_lightship,
                'end_constant' => $request->end_constant,
                'end_cargo' => $request->end_cargo,

                'cargo_adj' => $request->cargo_adj,
                'const_adj' => $request->const_adj,
                'voyage_state' => 'finished',
            ]);

        return redirect()
            ->route('voyage_start_vsl', [
                'vsl_id' => $request->vsl_id,
                'vsl_name' => $request->vsl_name,
                'vsl_type' => $request->vsl_type
            ])
            ->with('status_msg', 'Voyage finished successfully!');
    }
}
