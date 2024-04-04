<?php

namespace App\Http\Controllers;

use App\Models\SmsAddition;
use App\Models\SmsComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SmsController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ SAVE TO FILE -----------=------------------
    //--------------------------------------------------------------------
    public function saveToFile()
    {
        $sect_list = DB::table('sms_sections')
            ->orderBy('section_number')
            ->get();

        $sub_1_list = DB::table('sms_sub_1')
            ->orderBy('sub_1_number')
            ->get();

        $sub_2_list = DB::table('sms_sub_2')
            ->orderBy('sub_2_number')
            ->get();

        $sub_3_list = DB::table('sms_sub_3')
            ->orderBy('sub_3_number')
            ->get();

        $add_list = DB::table('sms_additions')
            ->orderBy('add_type')
            ->get();

        $comments_list = DB::table('sms_comments')
            ->orderBy('id')
            ->get();


        $add_cnt = [];
        //---sect add cnt
        foreach ($sect_list as $item) {
            $k = 'add_' . $item->section_number;
            $cnt = DB::table('sms_additions')
                ->where('section_number', $item->section_number)
                ->whereNull('sub_1_number')
                ->count();
            $add_cnt[$k] = $cnt;
        }
        //---sub-1 add cnt
        foreach ($sub_1_list as $item) {
            $k = 'add_' . $item->section_number . '_' . $item->sub_1_number;
            $cnt = DB::table('sms_additions')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->count();
            $add_cnt[$k] = $cnt;
        }


        $comm_cnt = [];
        //---sect comm cnt
        foreach ($sect_list as $item) {
            $k = $item->section_number . '.0.0.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', 0)
                ->where('sub_2_number', 0)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-1 comm cnt
        foreach ($sub_1_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.0.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', 0)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-2 comm cnt
        foreach ($sub_2_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.' . $item->sub_2_number . '.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', $item->sub_2_number)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-3 comm cnt
        foreach ($sub_3_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.' . $item->sub_2_number . '.' . $item->sub_3_number;
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', $item->sub_2_number)
                ->where('sub_3_number', $item->sub_3_number)
                ->count();
            $comm_cnt[$k] = $cnt;
        }

        return view('sms.sms_save', [
            'sect_list' => $sect_list,
            'sub_1_list' => $sub_1_list,
            'sub_2_list' => $sub_2_list,
            'sub_3_list' => $sub_3_list,
            'add_list' => $add_list,
            'comm_cnt' => $comm_cnt,
            'comments_list' => $comments_list,
            'add_cnt' => $add_cnt,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SMS MENU ----------------------------------
    //--------------------------------------------------------------------
    public function smsMenu()
    {
        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'abbr','type']);

        return view('sms.menu', ['vsl_list' => $vsl_list]);
    }


    //--------------------------------------------------------------------
    //------------------------ SMS LOG ---------------------------------
    //--------------------------------------------------------------------
    public function smsLog()
    {
        $itemsList = DB::table('sms_log')
            ->orderBy('id', 'desc')
            ->get();

        return view('sms.sms_log', [
            'itemsList' => $itemsList,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SMS START ---------------------------------
    //--------------------------------------------------------------------
    public function smsStart()
    {
        $sect_list = DB::table('sms_sections')
            ->orderBy('section_number')
            ->get();

        $sub_1_list = DB::table('sms_sub_1')
            ->orderBy('sub_1_number')
            ->get();

        $sub_2_list = DB::table('sms_sub_2')
            ->orderBy('sub_2_number')
            ->get();

        $sub_3_list = DB::table('sms_sub_3')
            ->orderBy('sub_3_number')
            ->get();

        $add_list = DB::table('sms_additions')
            ->orderBy('add_type')
            ->get();

        $comments_list = DB::table('sms_comments')
            ->orderBy('id')
            ->get();


        $add_cnt = [];
        //---sect add cnt
        foreach ($sect_list as $item) {
            $k = 'add_' . $item->section_number;
            $cnt = DB::table('sms_additions')
                ->where('section_number', $item->section_number)
                ->whereNull('sub_1_number')
                ->count();
            $add_cnt[$k] = $cnt;
        }
        //---sub-1 add cnt
        foreach ($sub_1_list as $item) {
            $k = 'add_' . $item->section_number . '_' . $item->sub_1_number;
            $cnt = DB::table('sms_additions')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->count();
            $add_cnt[$k] = $cnt;
        }

        //   dd($add_cnt);

        $comm_cnt = [];
        //---sect comm cnt
        foreach ($sect_list as $item) {
            $k = $item->section_number . '.0.0.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', 0)
                ->where('sub_2_number', 0)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-1 comm cnt
        foreach ($sub_1_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.0.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', 0)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-2 comm cnt
        foreach ($sub_2_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.' . $item->sub_2_number . '.0';
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', $item->sub_2_number)
                ->where('sub_3_number', 0)
                ->count();
            $comm_cnt[$k] = $cnt;
        }
        //---sub-3 comm cnt
        foreach ($sub_3_list as $item) {
            $k = $item->section_number . '.' . $item->sub_1_number . '.' . $item->sub_2_number . '.' . $item->sub_3_number;
            $cnt = DB::table('sms_comments')
                ->where('section_number', $item->section_number)
                ->where('sub_1_number', $item->sub_1_number)
                ->where('sub_2_number', $item->sub_2_number)
                ->where('sub_3_number', $item->sub_3_number)
                ->count();
            $comm_cnt[$k] = $cnt;
        }

        return view('sms.start_sms', [
            'sect_list' => $sect_list,
            'sub_1_list' => $sub_1_list,
            'sub_2_list' => $sub_2_list,
            'sub_3_list' => $sub_3_list,
            'add_list' => $add_list,
            'comm_cnt' => $comm_cnt,
            'comments_list' => $comments_list,
            'add_cnt' => $add_cnt,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ ADD NEW SECTION ---------------------------
    //--------------------------------------------------------------------
    public function addNewSection()
    {
        $lastNumber = DB::table('sms_sections')
            ->max('section_number');
        $newNumber = $lastNumber ? $lastNumber + 1 : 1;

        return view('sms.add_section', [
            'new_section_number' => $newNumber
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ EDIT SECTION ------------------------------
    //--------------------------------------------------------------------
    public function editSection($section_number)
    {
        $sect_data = DB::table('sms_sections')
            ->where('section_number', $section_number)
            ->first();

        $sect_vsl_types_mass = explode(',', $sect_data->vsl_types);
        $sect_process_owner_mass = explode(',', $sect_data->process_owner);
        $sect_responsible_mass = explode(',', $sect_data->responsible);

        return view('sms.edit_section', [
            'sect_data' => $sect_data,
            'sect_vsl_types_mass' => $sect_vsl_types_mass,
            'sect_process_owner_mass' => $sect_process_owner_mass,
            'sect_responsible_mass' => $sect_responsible_mass,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SAVE SECTION ------------------------------
    //--------------------------------------------------------------------
    public function saveSection(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'section_name' => 'required',
            'purpose' => 'required',
            'scope' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $procOwner = $request->process_owner ? implode(',', $request->process_owner) : null;
        $responsible = $request->responsible ? implode(',', $request->responsible) : null;

        DB::table('sms_sections')
            ->insert([
                'section_number' => $request->section_number,
                'section_name' => $request->section_name,
                'purpose' => $request->purpose,
                'scope' => $request->scope,
                'vsl_types' => $vslTypes,
                'process_owner' => $procOwner,
                'responsible' => $responsible,
                'ver' => $request->ver,
                'ver_date' => $request->ver_date,
                'rev' => $request->rev,
                'rev_date' => $request->rev_date,
            ]);

        //---save LOG
        DB::table('sms_log')
            ->insert([
                'ver' => $request->ver,
                'user_name' => Auth::user()->name,
                'item_name' => $request->section_name,
                'item_number' => $request->section_number,
                'date_of_change' => date('Y-m-d'),
                'remarks' => 'Create new section',
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE SECTION ----------------------------
    //--------------------------------------------------------------------
    public function updateSection(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'section_name' => 'required',
            'purpose' => 'required',
            'scope' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $procOwner = $request->process_owner ? implode(',', $request->process_owner) : null;
        $responsible = $request->responsible ? implode(',', $request->responsible) : null;

        DB::table('sms_sections')
            ->where('section_number', $request->section_number)
            ->update([
                'section_name' => $request->section_name,
                'purpose' => $request->purpose,
                'scope' => $request->scope,
                'vsl_types' => $vslTypes,
                'process_owner' => $procOwner,
                'responsible' => $responsible,
                'ver' => $request->ver,
                'ver_date' => $request->ver_date,
                'rev' => $request->rev,
                'rev_date' => $request->rev_date,
            ]);


        //---save LOG
        DB::table('sms_log')
            ->insert([
                'ver' => $request->ver,
                'user_name' => Auth::user()->name,
                'item_name' => $request->section_name,
                'item_number' => $request->section_number,
                'date_of_change' => date('Y-m-d'),
                'remarks' => $request->remarks,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE SECTION ----------------------------
    //--------------------------------------------------------------------
    public function deleteSection($section_number)
    {
        DB::table('sms_sections')
            ->where('section_number', $section_number)
            ->delete();

        DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->delete();

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->delete();

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->delete();

        //---delete additions  + files
        $add_data = DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->get(['att_1', 'att_2']);
        foreach ($add_data as $item) {
            $filePath1 = str_replace('storage', 'public', $item->att_1);
            $filePath2 = str_replace('storage', 'public', $item->att_2);
            Storage::delete($filePath1);
            Storage::delete($filePath2);
        }
        $add_data = DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->delete();


        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data deleted successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ RENUMBER SECTION --------------------------
    //--------------------------------------------------------------------
    public function renumSection($section_number, $new_number)
    {
        $chkNewNumber = DB::table('sms_sections')
            ->where('section_number', $new_number)
            ->count();

        if ($chkNewNumber) {
            return redirect()
                ->route('sms_start')
                ->with('status_msg', 'ERROR. Section with new number already exist');
        }

        DB::table('sms_sections')
            ->where('section_number', $section_number)
            ->update(['section_number' => $new_number]);

        DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->update(['section_number' => $new_number]);

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->update(['section_number' => $new_number]);

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->update(['section_number' => $new_number]);

        //---UPDATE additions
        DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->update(['section_number' => $new_number]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Renumbering finish successfully!');
    }





    //======================================= SUB-1 ========================================================================

    //--------------------------------------------------------------------
    //------------------------ ADD SUB-1 ---------------------------------
    //--------------------------------------------------------------------
    public function addNewSub1($section_number)
    {
        $lastNumber = DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->max('sub_1_number');

        $newNumber = $lastNumber ? $lastNumber + 1 : 1;

        return view('sms.sub-1.add_sub_1', [
            'section_number' => $section_number,
            'new_sub_1_number' => $newNumber,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ EDIT SUB-1 --------------------------------
    //--------------------------------------------------------------------
    public function editSub1($section_number, $sub_1_number)
    {
        $sub1_data = DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->first();

        $sect_vsl_types_mass = explode(',', $sub1_data->vsl_types);
        $sect_process_owner_mass = explode(',', $sub1_data->process_owner);
        $sect_responsible_mass = explode(',', $sub1_data->responsible);
        $sect_ism_mass = explode(',', $sub1_data->ism);
        $sect_iso_mass = explode(',', $sub1_data->iso);

        return view('sms.sub-1.edit_sub_1', [
            'sub1_data' => $sub1_data,
            'sub1_vsl_types_mass' => $sect_vsl_types_mass,
            'sub1_process_owner_mass' => $sect_process_owner_mass,
            'sub1_responsible_mass' => $sect_responsible_mass,
            'sub1_ism_mass' => $sect_ism_mass,
            'sub1_iso_mass' => $sect_iso_mass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SAVE SUB 1 --------------------------------
    //--------------------------------------------------------------------
    public function saveSub1(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_1_name' => 'required',
            'sub_1_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $procOwner = $request->process_owner ? implode(',', $request->process_owner) : null;
        $responsible = $request->responsible ? implode(',', $request->responsible) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_1')
            ->insert([
                'section_number' => $request->section_number,
                'sub_1_number' => $request->sub_1_number,
                'sub_1_name' => $request->sub_1_name,
                'sub_1_text' => $request->sub_1_text,
                'vsl_types' => $vslTypes,
                'process_owner' => $procOwner,
                'responsible' => $responsible,
                'ver' => $request->ver,
                'ver_date' => $request->ver_date,
                'rev' => $request->rev,
                'rev_date' => $request->rev_date,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        //---save LOG
        DB::table('sms_log')
            ->insert([
                'ver' => $request->ver,
                'user_name' => Auth::user()->name,
                'item_name' => $request->sub_1_name,
                'item_number' => "$request->section_number.$request->sub_1_number",
                'date_of_change' => date('Y-m-d'),
                'remarks' => 'Create new sub-item',
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ UPDATE SUB-1 ----------------------------
    //--------------------------------------------------------------------
    public function updateSub1(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_1_name' => 'required',
            'sub_1_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $procOwner = $request->process_owner ? implode(',', $request->process_owner) : null;
        $responsible = $request->responsible ? implode(',', $request->responsible) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_1')
            ->where('section_number', $request->section_number)
            ->where('sub_1_number', $request->sub_1_number)
            ->update([
                'sub_1_name' => $request->sub_1_name,
                'sub_1_text' => $request->sub_1_text,
                'vsl_types' => $vslTypes,
                'process_owner' => $procOwner,
                'responsible' => $responsible,
                'ver' => $request->ver,
                'ver_date' => $request->ver_date,
                'rev' => $request->rev,
                'rev_date' => $request->rev_date,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        //---save LOG
        DB::table('sms_log')
            ->insert([
                'ver' => $request->ver,
                'user_name' => Auth::user()->name,
                'item_name' => $request->sub_1_name,
                'item_number' => "$request->section_number.$request->sub_1_number",
                'date_of_change' => date('Y-m-d'),
                'remarks' => $request->remarks,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE SUB-1 ------------------------------
    //--------------------------------------------------------------------
    public function deleteSub1($section_number, $sub_1_number)
    {
        DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->delete();

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->delete();

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->delete();

        //---delete additions  + files
        $add_data = DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->get(['att_1', 'att_2']);
        foreach ($add_data as $item) {
            $filePath1 = str_replace('storage', 'public', $item->att_1);
            $filePath2 = str_replace('storage', 'public', $item->att_2);
            Storage::delete($filePath1);
            Storage::delete($filePath2);
        }
        $add_data = DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->delete();

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data deleted successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ RENUMBER SUB-1 ----------------------------
    //--------------------------------------------------------------------
    public function renumSub1($section_number, $sub_1_number, $new_number)
    {
        $chkNewNumber = DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $new_number)
            ->count();

        if ($chkNewNumber) {
            return redirect()
                ->route('sms_start')
                ->with('status_msg', 'ERROR. Item with new number already exist');
        }

        DB::table('sms_sub_1')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->update(['sub_1_number' => $new_number]);

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->update(['sub_1_number' => $new_number]);

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->update(['sub_1_number' => $new_number]);

        //---UPDATE additions
        DB::table('sms_additions')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->update(['sub_1_number' => $new_number]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Renumbering finish successfully!');
    }






    //======================================= SUB-2 ========================================================================

    //--------------------------------------------------------------------
    //------------------------ ADD SUB-2 ---------------------------------
    //--------------------------------------------------------------------
    public function addNewSub2($section_number, $sub_1_number)
    {
        $lastNumber = DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->max('sub_2_number');

        $newNumber = $lastNumber ? $lastNumber + 1 : 1;

        return view('sms.sub-2.add_sub_2', [
            'section_number' => $section_number,
            'sub_1_number' => $sub_1_number,
            'new_sub_2_number' => $newNumber,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ EDIT SUB-2 --------------------------------
    //--------------------------------------------------------------------
    public function editSub2($section_number, $sub_1_number, $sub_2_number)
    {
        $sub2_data = DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->first();

        $sect_vsl_types_mass = explode(',', $sub2_data->vsl_types);
        $sect_iso_mass = explode(',', $sub2_data->iso);
        $sect_ism_mass = explode(',', $sub2_data->ism);

        return view('sms.sub-2.edit_sub_2', [
            'sub2_data' => $sub2_data,
            'sub2_vsl_types_mass' => $sect_vsl_types_mass,
            'sub2_iso_mass' => $sect_iso_mass,
            'sub2_ism_mass' => $sect_ism_mass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SAVE SUB-2 --------------------------------
    //--------------------------------------------------------------------
    public function saveSub2(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_2_number' => 'required',
            'sub_2_name' => 'required',
            'sub_2_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_2')
            ->insert([
                'section_number' => $request->section_number,
                'sub_1_number' => $request->sub_1_number,
                'sub_2_number' => $request->sub_2_number,
                'sub_2_name' => $request->sub_2_name,
                'sub_2_text' => $request->sub_2_text,
                'vsl_types' => $vslTypes,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ UPDATE SUB-2 ----------------------------
    //--------------------------------------------------------------------
    public function updateSub2(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_2_number' => 'required',
            'sub_2_name' => 'required',
            'sub_2_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_2')
            ->where('section_number', $request->section_number)
            ->where('sub_1_number', $request->sub_1_number)
            ->where('sub_2_number', $request->sub_2_number)
            ->update([
                'sub_2_name' => $request->sub_2_name,
                'sub_2_text' => $request->sub_2_text,
                'vsl_types' => $vslTypes,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE SUB-2 ------------------------------
    //--------------------------------------------------------------------
    public function deleteSub2($section_number, $sub_1_number, $sub_2_number)
    {

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->delete();

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->delete();

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data deleted successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ RENUMBER SUB-2 ----------------------------
    //--------------------------------------------------------------------
    public function renumSub2($section_number, $sub_1_number, $sub_2_number, $new_number)
    {

        $chkNewNumber = DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $new_number)
            ->count();

        if ($chkNewNumber) {
            return redirect()
                ->route('sms_start')
                ->with('status_msg', 'ERROR. Item with new number already exist');
        }

        DB::table('sms_sub_2')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->update(['sub_2_number' => $new_number]);

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->update(['sub_2_number' => $new_number]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Renumbering finish successfully!');
    }





    //======================================= SUB-3 ========================================================================

    //--------------------------------------------------------------------
    //------------------------ ADD SUB-3 ---------------------------------
    //--------------------------------------------------------------------
    public function addNewSub3($section_number, $sub_1_number, $sub_2_number)
    {
        $lastNumber = DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->max('sub_3_number');

        $newNumber = $lastNumber ? $lastNumber + 1 : 1;

        return view('sms.sub-3.add_sub_3', [
            'section_number' => $section_number,
            'sub_1_number' => $sub_1_number,
            'sub_2_number' => $sub_2_number,
            'new_sub_3_number' => $newNumber,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ EDIT SUB-3 --------------------------------
    //--------------------------------------------------------------------
    public function editSub3($section_number, $sub_1_number, $sub_2_number, $sub_3_number)
    {
        $sub3_data = DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->where('sub_3_number', $sub_3_number)
            ->first();

        $sect_vsl_types_mass = explode(',', $sub3_data->vsl_types);
        $sect_iso_mass = explode(',', $sub3_data->iso);
        $sect_ism_mass = explode(',', $sub3_data->ism);

        return view('sms.sub-3.edit_sub_3', [
            'sub3_data' => $sub3_data,
            'sub3_vsl_types_mass' => $sect_vsl_types_mass,
            'sub3_iso_mass' => $sect_iso_mass,
            'sub3_ism_mass' => $sect_ism_mass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SAVE SUB-3 --------------------------------
    //--------------------------------------------------------------------
    public function saveSub3(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_2_number' => 'required',
            'sub_3_number' => 'required',
            'sub_3_name' => 'required',
            'sub_3_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_3')
            ->insert([
                'section_number' => $request->section_number,
                'sub_1_number' => $request->sub_1_number,
                'sub_2_number' => $request->sub_2_number,
                'sub_3_number' => $request->sub_3_number,
                'sub_3_name' => $request->sub_3_name,
                'sub_3_text' => $request->sub_3_text,
                'vsl_types' => $vslTypes,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ UPDATE SUB-3 ----------------------------
    //--------------------------------------------------------------------
    public function updateSub3(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'sub_1_number' => 'required',
            'sub_2_number' => 'required',
            'sub_3_number' => 'required',
            'sub_3_name' => 'required',
            'sub_3_text' => 'required',
        ]);

        $vslTypes = $request->vsl_types ? implode(',', $request->vsl_types) : null;
        $ism = $request->ism ? implode(',', $request->ism) : null;
        $iso = $request->iso ? implode(',', $request->iso) : null;

        DB::table('sms_sub_3')
            ->where('section_number', $request->section_number)
            ->where('sub_1_number', $request->sub_1_number)
            ->where('sub_2_number', $request->sub_2_number)
            ->where('sub_3_number', $request->sub_3_number)
            ->update([
                'sub_3_name' => $request->sub_3_name,
                'sub_3_text' => $request->sub_3_text,
                'vsl_types' => $vslTypes,
                'ism' => $ism,
                'iso' => $iso,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE SUB-3 ------------------------------
    //--------------------------------------------------------------------
    public function deleteSub3($section_number, $sub_1_number, $sub_2_number, $sub_3_number)
    {

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->where('sub_3_number', $sub_3_number)
            ->delete();

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data deleted successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ RENUMBER SUB-3 ----------------------------
    //--------------------------------------------------------------------
    public function renumSub3($section_number, $sub_1_number, $sub_2_number, $sub_3_number, $new_number)
    {

        $chkNewNumber = DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->where('sub_3_number', $new_number)
            ->count();

        if ($chkNewNumber) {
            return redirect()
                ->route('sms_start')
                ->with('status_msg', 'ERROR. Item with new number already exist');
        }

        DB::table('sms_sub_3')
            ->where('section_number', $section_number)
            ->where('sub_1_number', $sub_1_number)
            ->where('sub_2_number', $sub_2_number)
            ->where('sub_3_number', $sub_3_number)
            ->update(['sub_3_number' => $new_number]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Renumbering finish successfully!');
    }




    //======================================= ADDITIONS ========================================================================

    //--------------------------------------------------------------------
    //------------------------ ADD NEW ADDITION --------------------------
    //--------------------------------------------------------------------
    public function addNewAddition($section_number, $sub_1_number)
    {
        $sub_1_number = $sub_1_number > 0 ? $sub_1_number : '';
        return view('sms.additions.add_new', [
            'section_number' => $section_number,
            'sub_1_number' => $sub_1_number,
            'add_text' => '',
            'att_1' => '',
            'att_2' => '',
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ EDIT ADDITION -----------------------------
    //--------------------------------------------------------------------
    public function editAddition($id)
    {
        $add_data = SmsAddition::find($id);
        return view('sms.additions.edit', [
            'id' => $add_data->id,
            'section_number' => $add_data->section_number,
            'sub_1_number' => $add_data->sub_1_number,
            'add_type' => $add_data->add_type,
            'add_name' => $add_data->add_name,
            'add_text' => $add_data->add_text,
            'att_1' => $add_data->att_1,
            'att_2' => $add_data->att_2,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SAVE ADDITION -----------------------------
    //--------------------------------------------------------------------
    public function saveAddition(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'add_name' => 'required',
        ]);

        //---FILES
        // if (config('app.env') == 'production' && !file_exists("/home/mytran/mytransship.com/www/storage/app/public/photos/sms")) {
        //     mkdir("/home/mytran/mytransship.com/www/storage/app/public/photos/sms", 0750);
        // }
        $new_att_1 = null;
        $new_att_2 = null;
        if ($request->att_1) {
            $path = Storage::putFile("public/photos/sms", $request->file('att_1'));
            $new_att_1 = Storage::url($path);
        }
        if ($request->att_2) {
            $path = Storage::putFile("public/photos/sms", $request->file('att_2'));
            $new_att_2 = Storage::url($path);
        }

        //---GET NEW ADDITION NUMBER
        $sub_1_number = $request->sub_1_number;
        $lastNumber = DB::table('sms_additions')
            ->where('section_number', $request->section_number)
            ->where('add_type', $request->add_type)
            ->when($sub_1_number, function ($query, $sub_1_number) {
                $query->where('sub_1_number', $sub_1_number);
            })
            ->max('add_number');

        $newNumber = $lastNumber ? $lastNumber + 1 : 1;

        DB::table('sms_additions')
            ->insert([
                'section_number' => $request->section_number,
                'sub_1_number' => $request->sub_1_number,
                'add_type' => $request->add_type,
                'add_number' => $newNumber,
                'add_name' => $request->add_name,
                'add_text' => $request->add_text,
                'att_1' => $new_att_1,
                'att_2' => $new_att_2,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', "New $request->add_type saved successfully!");
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE ADDITION ---------------------------
    //--------------------------------------------------------------------
    public function updateAddition(Request $request)
    {
        $valid = $request->validate([
            'add_name' => 'required',
        ]);


        //---FILES
        // if (config('app.env') == 'production' && !file_exists("/home/mytran/mytransship.com/www/storage/app/public/photos/sms")) {
        //     mkdir("/home/mytran/mytransship.com/www/storage/app/public/photos/sms", 0750);
        // }

        //---GET OLD FILES --------------------------------------
        $oldAdvData = DB::table('sms_additions')
            ->where('id', $request->id)
            ->first(['att_1', 'att_2']);
        $old_att_1 = $oldAdvData->att_1;
        $old_att_2 = $oldAdvData->att_2;

        //==========FILE-1=============================================
        $att_1_action = $request->att_1_action;
        $new_att_1 = $old_att_1;
        if ($request->att_1) {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $old_att_1);
            Storage::delete($oldFilePath);
            $path = Storage::putFile("public/photos/sms", $request->file('att_1'));
            $new_att_1 = Storage::url($path);
        }
        if ($att_1_action == "delete") {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $old_att_1);
            Storage::delete($oldFilePath);
            $new_att_1 = null;
        }


        //==========FILE-2=============================================
        $att_2_action = $request->att_2_action;
        $new_att_2 = $old_att_2;
        if ($request->att_2) {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $old_att_2);
            Storage::delete($oldFilePath);
            $path = Storage::putFile("public/photos/sms", $request->file('att_2'));
            $new_att_2 = Storage::url($path);
        }
        if ($att_2_action == "delete") {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $old_att_2);
            Storage::delete($oldFilePath);
            $new_att_2 = null;
        }


        DB::table('sms_additions')
            ->where('id', $request->id)
            ->update([
                'add_type' => $request->add_type,
                'add_name' => $request->add_name,
                'add_text' => $request->add_text,
                'att_1' => $new_att_1,
                'att_2' => $new_att_2,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', "New $request->add_type updated successfully!");
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE ADDITION ---------------------------
    //--------------------------------------------------------------------
    public function deleteAddition($id)
    {
        $add_data = SmsAddition::find($id);

        $old_att_1 = $add_data->att_1;
        $old_att_2 = $add_data->att_2;
        $oldFilePath1 = str_replace('storage', 'public', $old_att_1);
        Storage::delete($oldFilePath1);
        $oldFilePath2 = str_replace('storage', 'public', $old_att_2);
        Storage::delete($oldFilePath2);

        $add_data->delete();
        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data deleted successfully!');
    }




    //======================================= COMMENTS ========================================================================

    //--------------------------------------------------------------------
    //------------------------ ADD COMMENT -------------------------------
    //--------------------------------------------------------------------
    public function addComment($section_number, $sub_1_number, $sub_2_number, $sub_3_number)
    {
        return view('sms.comments.add_comment', [
            'section_number' => $section_number,
            'sub_1_number' => $sub_1_number,
            'sub_2_number' => $sub_2_number,
            'sub_3_number' => $sub_3_number,
            'content' => '',
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SAVE COMMENT ------------------------------
    //--------------------------------------------------------------------
    public function saveComment(Request $request)
    {
        $valid = $request->validate([
            'section_number' => 'required|numeric',
            'content' => 'required',
        ]);

        DB::table('sms_comments')
            ->insert([
                'user_name' => Auth::user()->name,
                'section_number' => $request->section_number,
                'sub_1_number' => $request->sub_1_number,
                'sub_2_number' => $request->sub_2_number,
                'sub_3_number' => $request->sub_3_number,
                'content' => $request->content,
                'comment_state' => 'new',
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ EDIT COMMENT ------------------------------
    //--------------------------------------------------------------------
    public function editComment($id)
    {
        return view('sms.comments.edit_comment', [
            'comment_data' => SmsComment::find($id),
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ UPDATE COMMENT ---------------------------
    //--------------------------------------------------------------------
    public function updateComment(Request $request)
    {
        $valid = $request->validate([
            'content' => 'required',
        ]);

        DB::table('sms_comments')
            ->where('id', $request->id)
            ->update([
                'content' => $request->content,
            ]);

        return redirect()
            ->route('sms_start')
            ->with('status_msg', "Comment updated successfully!");
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE COMMENT ----------------------------
    //--------------------------------------------------------------------
    public function deleteComment($id)
    {
        SmsComment::find($id)->delete();
        return redirect()
            ->route('sms_start')
            ->with('status_msg', 'Comment deleted successfully!');
    }
}
