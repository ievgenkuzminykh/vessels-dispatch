<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $itemList = DB::table('locations')
            ->orderBy('name', 'asc')
            ->get();

        return view('locations.start', ['itemList' => $itemList]);
    }


    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDesc($id)
    {
        $loc = new Location();
        $locData = $loc::find($id);

        return view('locations.show_description', [
            'loc_data' => $locData,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ ADD LOCATION START ------------------------
    //--------------------------------------------------------------------
    public function addLocationStart()
    {
        return view('locations.add_new');
    }


    //--------------------------------------------------------------------
    //------------------------ ADD LOCATION ------------------------------
    //--------------------------------------------------------------------
    public function addLocation(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required',
            'abr' => 'required',
            'apply_for' => 'required',
        ]);

        DB::table('locations')
            ->insert([
                'name' => strtolower($request->name),
                'abr' => strtolower($request->abr),
                'apply_for' => $request->apply_for,
            ]);

        return redirect()
            ->route('locations_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE LOCATION ---------------------------
    //--------------------------------------------------------------------
    public function updateLocation(Request $request, $id)
    {
        $valid = $request->validate([
            'name' => 'required',
            'abr' => 'required',
            'apply_for' => 'required',
        ]);

        DB::table('locations')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'abr' => $request->abr,
                'apply_for' => $request->apply_for,
            ]);


        return redirect()
            ->route('locations_start')
            ->with('status_msg', 'Data changed successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE LOCATION ---------------------------
    //--------------------------------------------------------------------
    public function deleteLocation(Request $request, $id)
    {
        DB::table('locations')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('locations_start')
            ->with('status_msg', 'Data deleted successfully!');
    }
}
