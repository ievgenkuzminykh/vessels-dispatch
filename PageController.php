<?php

namespace App\Http\Controllers;

use App\Models\UserPrivilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {

        if (!Auth::check()) {
            $vsl_list = [];
            $locname = 'europe';
            $lat = '40';
            $lon = '6.2';
            $zoom = 3;
        } else {
            $vsl_list = DB::table('vessels')
                ->orderBy('name', 'asc')
                ->get(['id','name','abbr','imo','type','img']);

            $vsl_list = $vsl_list;
            $locname = 'kamsar';
            $lat = '10.30';
            $lon = '-14.39';
            $zoom = 9;
        }

        return view('main', [
            'vsl_list' => $vsl_list,
            'locname' =>  $locname,
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => $zoom
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CHANGE LOCATION ---------------------------
    //--------------------------------------------------------------------
    public function changeLocation($locname)
    {
        if ($locname == 'kamsar') {
            $lat = '10.30';
            $lon = '-14.39288';
        }

        if ($locname == 'conakry') {
            $lat = '9.510';
            $lon = '-13.775';
        }

        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id','name','abbr','imo','type','img']);

        return view('main', [
            'vsl_list' => $vsl_list,
            'locname' => $locname,
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => 9
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ USERS START -------------------------------
    //--------------------------------------------------------------------
    public function usersStart()
    {
        $users_list = DB::table('users')
            ->orderBy('state')
            ->orderBy('user_type', 'asc')
            ->get();

        $vessels_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $units_list = DB::table('company_units')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return view('users', [
            'users_list' => $users_list,
            'vessels_list' => $vessels_list,
            'units_list' => $units_list,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ UPDATE USER -------------------------------
    //--------------------------------------------------------------------
    public function usersUpdate(Request $request)
    {
        $valid = $request->validate([
            'full_name' => 'required',
        ]);

        $vessels_list = $request->vsl_names ? implode(',', $request->vsl_names) : null;
        $units_list = $request->units_names ? implode(',', $request->units_names) : null;

        DB::table('users')
            ->where('id', $request->user_id)
            ->update([
                'state' => $request->state ? 'active' : 'archived',
                'expiration_date' => $request->expiration_date,
                'name' => $request->full_name,
                'phone_work' => $request->phone_work,
                'email_work' => $request->email_work,
                'phone_backup' => $request->phone_backup,
                'sms' => $request->sms,
                'qms_doc' => $request->qms_doc,
                'qms_rec' => $request->qms_rec,
                'units' => $request->units,
                'vessels' => $request->vessels,
                'voyages' => $request->voyages,
                'cranes' => $request->cranes,
                'ogv' => $request->ogv,
                'locations' => $request->locations,
                'certificates' => $request->certificates,
                'users' => $request->users,
                'actionplan' => $request->actionplan,
                'planning' => $request->planning,
                'budget' => $request->budget,
                'chat' => $request->chat,
                'contacts' => $request->contacts,
                'vessels_list' => $vessels_list,
                'units_list' => $units_list,
            ]);


        return redirect()
            ->route('users_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE USER -------------------------------
    //--------------------------------------------------------------------
    public function usersDelete($id)
    {
        DB::table('users')
            ->where('id', $id)
            ->delete();

        Auth::logout();
        return redirect()
            ->route('start_page')
            ->with('status_msg', 'Account deleted successfully!');
    }
}
