<?php

namespace App\Http\Controllers;

use App\Models\Ogv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OgvController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $itemList = DB::table('ogvs')
            ->orderBy('id', 'desc')
            ->orderBy('location', 'asc')
            ->get();

        return view('ogv.start', ['itemList' => $itemList]);
    }


    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDesc($id)
    {
        $vessel = new Ogv();
        $ogv = $vessel::find($id);

        $trans_list = DB::table('vessels')
            ->where('type', 'transshiper')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();
  
        return view('ogv.show_description', [
            'ogv' => $ogv,
            'trans_list' => $trans_list,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ ADD OGV START -----------------------------
    //--------------------------------------------------------------------
    public function addVesselStart()
    {
        $loc_list = DB::table('locations')
            ->orderBy('name', 'asc')
            ->get();

        $trans_list = DB::table('vessels')
            ->where('type', 'transshiper')
            ->orderBy('name', 'asc')
            ->get('name')->toArray();

        return view('ogv.add_new', [
            'loc_list' => $loc_list,
            'trans_list' => $trans_list,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ ADD OGV -----------------------------------
    //--------------------------------------------------------------------
    public function addVessel(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required',
            'imo' => 'required|numeric|min_digits:7|max_digits:7',
            'location' => 'required',
            'planned_eta' => 'required',
            'planned_etd' => 'required',
            'planned_transship' => 'required',
        ]);
  
        DB::table('ogvs')
            ->insert([
                'state' => $request->state ? 'active' : 'archived',
                'name' => strtolower($request->name),
                'imo' => $request->imo,
                'deadweight' => $request->deadweight,
                'location' => $request->location,
                'planned_transship' => $request->planned_transship,
                'actual_transship' => $request->actual_transship,
                'planned_eta' => $request->planned_eta,
                'actual_eta' => $request->actual_eta,
                'planned_etd' => $request->planned_etd,
                'actual_etd' => $request->actual_etd,
                'planned_nor' => $request->planned_nor,
                'actual_nor' => $request->actual_nor,
                'planned_frprt' => $request->planned_frprt,
                'actual_frprt' => $request->actual_frprt,
                'planned_load_start' => $request->planned_load_start,
                'actual_load_start' => $request->actual_load_start,
                'planned_load_end' => $request->planned_load_end,
                'actual_load_end' => $request->actual_load_end,
                'planned_clrnc' => $request->planned_clrnc,
                'actual_clrnc' => $request->actual_clrnc,
                'planned_qtty' => $request->planned_qtty,
                'actual_qtty' => $request->actual_qtty,
                'planned_rate_tons' => $request->planned_rate_tons,
                'actual_rate_tons' => $request->actual_rate_tons,
                'planned_rate_usd' => $request->planned_rate_usd,
                'actual_rate_usd' => $request->actual_rate_usd,
            ]);

        return redirect()
            ->route('ogv_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE OGV --------------------------------
    //--------------------------------------------------------------------
    public function updateVessel(Request $request, $id)
    {
        $valid = $request->validate([
            'name' => 'required',
            'imo' => 'required|numeric|min_digits:7|max_digits:7',
            'location' => 'required',
            'planned_eta' => 'required',
            'planned_etd' => 'required',
            'planned_transship' => 'required',
        ]);

        DB::table('ogvs')
            ->where('id', $id)
            ->update([
                'state' => $request->state ? 'active' : 'archived',
                'name' => strtolower($request->name),
                'imo' => $request->imo,
                'deadweight' => $request->deadweight,
                'location' => $request->location,
                'planned_transship' => $request->planned_transship,
                'actual_transship' => $request->actual_transship,
                'planned_eta' => $request->planned_eta,
                'actual_eta' => $request->actual_eta,
                'planned_etd' => $request->planned_etd,
                'actual_etd' => $request->actual_etd,
                'planned_nor' => $request->planned_nor,
                'actual_nor' => $request->actual_nor,
                'planned_frprt' => $request->planned_frprt,
                'actual_frprt' => $request->actual_frprt,
                'planned_load_start' => $request->planned_load_start,
                'actual_load_start' => $request->actual_load_start,
                'planned_load_end' => $request->planned_load_end,
                'actual_load_end' => $request->actual_load_end,
                'planned_clrnc' => $request->planned_clrnc,
                'actual_clrnc' => $request->actual_clrnc,
                'planned_qtty' => $request->planned_qtty,
                'actual_qtty' => $request->actual_qtty,
                'planned_rate_tons' => $request->planned_rate_tons,
                'actual_rate_tons' => $request->actual_rate_tons,
                'planned_rate_usd' => $request->planned_rate_usd,
                'actual_rate_usd' => $request->actual_rate_usd,
            ]);

        return redirect()
            ->route('ogv_start')
            ->with('status_msg', 'Data update successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE OGV --------------------------------
    //--------------------------------------------------------------------
    public function deleteVessel(Request $request, $id)
    {
        DB::table('ogvs')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('ogv_start')
            ->with('status_msg', 'Data deleted successfully!');
    }
}
