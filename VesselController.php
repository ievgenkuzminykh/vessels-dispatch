<?php

namespace App\Http\Controllers;

use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VesselController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $itemList = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id','name','abbr','imo','type','owner']);

        $unitsList = DB::table('company_units')
            ->orderBy('name', 'asc')
            ->get();

        return view('vessels.start', [
            'itemList' => $itemList,
            'unitsList' => $unitsList,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDesc($id)
    {
        $vessel = new Vessel();
        $vesselData = $vessel::find($id);

        return view('vessels.show_description', [
            'vessel_data' => $vesselData,
        ]);
    }

    //------------------------UNITS---------------------------------------------------------------------------
    //--------------------------------------------------------------------
    //------------------------ EDIT UNITS --------------------------------
    //--------------------------------------------------------------------
    public function editUnits(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required',
        ]);
        if ($request->unit_edit_mode == 'new') {
            DB::table('company_units')
                ->insert([
                    'name' => $request->name,
                    'reg_no' => $request->reg_no,
                    'reg_date' => $request->reg_date,
                    'address' => $request->address,
                ]);
        }
        if ($request->unit_edit_mode == 'edit') {
            DB::table('company_units')
                ->where('id', $request->rec_id)
                ->update([
                    'name' => $request->name,
                    'reg_no' => $request->reg_no,
                    'reg_date' => $request->reg_date,
                    'address' => $request->address,
                ]);
        }
        if ($request->unit_edit_mode == 'delete') {
            DB::table('company_units')
                ->where('id', $request->rec_id)
                ->delete();
            return redirect()
                ->route('vsl_start')
                ->with('status_msg', 'Unit deleted successfully!');
        }
        return redirect()
            ->route('vsl_start')
            ->with('status_msg', 'Data saved successfully!');
    }



    //--------------------------------------------------------------------
    //------------------------ ADD VESSEL START --------------------------
    //--------------------------------------------------------------------
    public function addVesselStart()
    {
        return view('vessels.add_new');
    }


    //--------------------------------------------------------------------
    //------------------------ ADD VESSEL --------------------------------
    //--------------------------------------------------------------------
    public function addVessel(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required',
            'abbr' => 'required',
            'owner' => 'required',
            'imo' => 'required|numeric|min_digits:7|max_digits:7'
        ]);

        /*if (config('app.env') == 'production' && !file_exists("/home/mytran/mytransship.com/www/storage/app/public/photos")) {
            mkdir("/home/mytran/mytransship.com/www/storage/app/public/photos", 0750);
        }
        
        if (config('app.env') == 'production' && !file_exists("/home/u322712243/domains/mytransshipment.com/public_html/app/public/photos")) {
            mkdir("/home/u322712243/domains/mytransshipment.com/public_html/app/public/photos", 0750);
        }*/

        $newImg = null;
        if ($request->img) {
            $path = Storage::putFile("public/photos", $request->file('img'));
            $newImg = Storage::url($path);
        }


        //---INSERT 1
        $recId = DB::table('vessels')
            ->insertGetId([
                'img' => $newImg,
                'name' => strtolower($request->name),
                'abbr' => strtolower($request->abbr),
                'imo' => $request->imo,
                'type' => $request->type,
                'owner' => $request->owner,
                'role' => $request->role,
                'port' => $request->port,
                'off_number' => $request->off_number,
                'flag' => $request->flag,
                'call_sign' => $request->call_sign,
                'mmsi' => $request->mmsi,
                'class_society' => $request->class_society,
                'class_notation' => $request->class_notation,
                'trade_area' => $request->trade_area,
                'reg_owner' => $request->reg_owner,
                'tech_manager' => $request->tech_manager,
                'shipbuilder' => $request->shipbuilder,
                'delivery_date' => $request->delivery_date,
                'yard_number' => $request->yard_number,
                'gross_ton' => $request->gross_ton,
                'net_ton' => $request->net_ton,
                'length' => $request->length,
                'lpp' => $request->lpp,
                'breath' => $request->breath,
                'depth_moulded' => $request->depth_moulded,
                'scantling_draft' => $request->scantling_draft,
                'summer_draft' => $request->summer_draft,
                'fw_allowance' => $request->fw_allowance,
                'air_draft' => $request->air_draft,
                'sum_deadweight' => $request->sum_deadweight,
                'sum_disp' => $request->sum_disp,
                'lightweight' => $request->lightweight,
                'max_deck_load' => $request->max_deck_load,
                'wood_deck_area' => $request->wood_deck_area,
                'work_deck_area' => $request->work_deck_area,
            ]);

        //---INSERT 2
        DB::table('vessels')
            ->where('id', $recId)
            ->update([
                'tank_hsfo' => $request->tank_hsfo,
                'tank_vslfo' => $request->tank_vslfo,
                'tank_mgo' => $request->tank_mgo,
                'tank_lo' => $request->tank_lo,
                'tank_fw' => $request->tank_fw,
                'tank_bal_cap' => $request->tank_bal_cap,
                'tank_bal_perm' => $request->tank_bal_perm,
                'tank_water_cap' => $request->tank_water_cap,
                'tank_seawage' => $request->tank_seawage,
                'tank_sludge' => $request->tank_sludge,
                'speed_bal_full' => $request->speed_bal_full,
                'speed_bal_eco' => $request->speed_bal_eco,
                'speed_laden_full' => $request->speed_laden_full,
                'speed_laden_eco' => $request->speed_laden_eco,
                'consump_me_bal_full' => $request->consump_me_bal_full,
                'consump_me_bal_eco' => $request->consump_me_bal_eco,
                'consump_me_laden_full' => $request->consump_me_laden_full,
                'consump_me_laden_eco' => $request->consump_me_laden_eco,
                'consump_ae_1' => $request->consump_ae_1,
                'consump_ae_2' => $request->consump_ae_2,
                'consump_ae_3' => $request->consump_ae_3,
                'consump_lome_crank' => $request->consump_lome_crank,
                'consump_lome_cylind' => $request->consump_lome_cylind,
                'consump_ae_peng' => $request->consump_ae_peng,
                'consump_bal_intake' => $request->consump_bal_intake,
                'consump_bal_disch' => $request->consump_bal_disch,
                'consump_fire_pump' => $request->consump_fire_pump,
            ]);

        //---INSERT 3
        DB::table('vessels')
            ->where('id', $recId)
            ->update([
                'me_numbers' => $request->me_numbers,
                'me_spec' => $request->me_spec,
                'me_name' => $request->me_name,
                'me_model' => $request->me_model,
                'me_cylinders' => $request->me_cylinders,
                'me_kw' => $request->me_kw,
                'me_rpm' => $request->me_rpm,
                'me_stroke' => $request->me_stroke,
                'me_bore' => $request->me_bore,
                'me_sfoc' => $request->me_sfoc,
                'me_nox' => $request->me_nox,
                'me_overh_interval' => $request->me_overh_interval,
                'ae_numbers' => $request->ae_numbers,
                'ae_design' => $request->ae_design,
                'ae_model' => $request->ae_model,
                'ae_cylinders' => $request->ae_cylinders,
                'ae_kw' => $request->ae_kw,
                'ae_rpm' => $request->ae_rpm,
                'ae_stroke' => $request->ae_stroke,
                'ae_bore' => $request->ae_bore,
                'ae_sfoc' => $request->ae_sfoc,
                'ae_nox' => $request->ae_nox,
                'ae_overh_interval' => $request->ae_overh_interval,
                'prop_bow_kw' => $request->prop_bow_kw,
                'prop_bow_numbers' => $request->prop_bow_numbers,
                'prop_propellers' => $request->prop_propellers,
                'prop_propeller_type' => $request->prop_propeller_type,
                'prop_rud_num' => $request->prop_rud_num,
                'prop_rud_type' => $request->prop_rud_type,
                'prop_index_req' => $request->prop_index_req,
                'prop_index_fact' => $request->prop_index_fact,
                'prop_cii' => $request->prop_cii,
            ]);

        //---INSERT 4
        DB::table('vessels')
            ->where('id', $recId)
            ->update([
                'anch_numbers' => $request->anch_numbers,
                'anch_w' => $request->anch_w,
                'anch_ch_size' => $request->anch_ch_size,
                'anch_ch_ss' => $request->anch_ch_ss,
                'anch_ch_ps' => $request->anch_ch_ps,
                'anch_ch_stern' => $request->anch_ch_stern,
                'crane_type' => $request->crane_type,
                'crane_maker' => $request->crane_maker,
                'crane_outreach' => $request->crane_outreach,
                'comm_c1' => $request->comm_c1,
                'comm_c2' => $request->comm_c2,
                'comm_vsat' => $request->comm_vsat,
                'comm_broad' => $request->comm_broad,
                'comm_gsm' => $request->comm_gsm,
                'comm_irid' => $request->comm_irid,
                'comm_email1' => $request->comm_email1,
                'comm_email2' => $request->comm_email2,
                'insur_pi' => $request->insur_pi,
                'insur_hm' => $request->insur_hm,
                'insur_val' => $request->insur_val,
                'tow_main_len' => $request->tow_main_len,
                'tow_main_dia' => $request->tow_main_dia,
                'tow_spare_len' => $request->tow_spare_len,
                'tow_spare_dia' => $request->tow_spare_dia,
                'tow_winch_type' => $request->tow_winch_type,
                'tow_winch_pull' => $request->tow_winch_pull,
                'tow_jaw' => $request->tow_jaw,
                'tow_jaw_sw' => $request->tow_jaw_sw,
                'tow_pins' => $request->tow_pins,
                'tow_pins_sw' => $request->tow_pins_sw,
            ]);

        //---INSERT 5
        DB::table('vessels')
            ->where('id', $recId)
            ->update([
                'cargo_crane_numbers' => $request->cargo_crane_numbers,
                'cargo_crane_type' => $request->cargo_crane_type,
                'cargo_crane_manuf' => $request->cargo_crane_manuf,
                'cargo_crane_max1' => $request->cargo_crane_max1,
                'cargo_crane_max2' => $request->cargo_crane_max2,
                'cargo_crane_swl' => $request->cargo_crane_swl,
                'cargo_crane_time_full' => $request->cargo_crane_time_full,
                'cargo_crane_time_hois' => $request->cargo_crane_time_hois,
                'cargo_crane_time_luf' => $request->cargo_crane_time_luf,
                'cargo_crane_time_slew' => $request->cargo_crane_time_slew,
                'cargo_crane_is_gear' => $request->cargo_crane_is_gear,
                'cargo_crane_are_winch' => $request->cargo_crane_are_winch,
                'cargo_crane_grab_type' => $request->cargo_crane_grab_type,
                'cargo_crane_grab_cap' => $request->cargo_crane_grab_cap,
                'cargo_crane_grab_pwr' => $request->cargo_crane_grab_pwr,
                'cargo_crane_grab_loc' => $request->cargo_crane_grab_loc,
                'cargo_holds_1' => $request->cargo_holds_1,
                'cargo_holds_2' => $request->cargo_holds_2,
                'cargo_holds_3' => $request->cargo_holds_3,
                'cargo_holds_4' => $request->cargo_holds_4,
                'cargo_holds_5' => $request->cargo_holds_5,
                'cargo_holds_6' => $request->cargo_holds_6,
                'cargo_holds_7' => $request->cargo_holds_7,
                'cargo_holds_8' => $request->cargo_holds_8,
                'cargo_holds_9' => $request->cargo_holds_9,
                'cargo_holds_10' => $request->cargo_holds_10,
                'cargo_holds_11' => $request->cargo_holds_11,
                'cargo_holds_12' => $request->cargo_holds_12,
                'cargo_holds_13' => $request->cargo_holds_13,
                'cargo_holds_14' => $request->cargo_holds_14,
                'cargo_holds_15' => $request->cargo_holds_15,
                'cargo_holds_16' => $request->cargo_holds_16,
                'cargo_holds_17' => $request->cargo_holds_17,
                'cargo_holds_18' => $request->cargo_holds_18,
                'cargo_holds_19' => $request->cargo_holds_19,
                'cargo_holds_20' => $request->cargo_holds_20,
                'cargo_holds_21' => $request->cargo_holds_21,
                'cargo_holds_22' => $request->cargo_holds_22,
                'cargo_holds_23' => $request->cargo_holds_23,
                'cargo_holds_24' => $request->cargo_holds_24,
                'hatches_1' => $request->hatches_1,
                'hatches_2' => $request->hatches_2,
                'hatches_3' => $request->hatches_3,
                'hatches_4' => $request->hatches_4,
                'hatches_5' => $request->hatches_5,
                'hatches_6' => $request->hatches_6,
                'hatches_7' => $request->hatches_7,
                'hatches_8' => $request->hatches_8,
                'hatches_9' => $request->hatches_9,
                'hatches_10' => $request->hatches_10,
            ]);

        //---CREATE CERTIFICATES
        $vslType = $request->type;
        $certMass = DB::table('cert_types')
            ->where('vsl_types', 'like', "%$vslType%")
            ->get();
        foreach ($certMass as $cert) {
            DB::table('vessel_certificates')
                ->insert([
                    'cert_id' => $cert->id,
                    'cert_number' => $cert->cert_number,
                    'cert_name' => $cert->cert_name,
                    'group_number' => $cert->group_number,
                    'group_name' => $cert->group_name,
                    'ref' => $cert->ref,
                    'vsl_id' => $recId,
                    'vsl_type' => $vslType,
                ]);
        }

        return redirect()
            ->route('vsl_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE VESSEL -----------------------------
    //--------------------------------------------------------------------
    public function updateVessel(Request $request, $id)
    {
        $valid = $request->validate([
            'name' => 'required',
            'abbr' => 'required',
            'owner' => 'required',
            'imo' => 'required|numeric|min_digits:7|max_digits:7'
        ]);

        $att_action = $request->att_action;

        //---GET OLD FILE --------------------------------------
        $oldVslData = DB::table('vessels')
            ->where('id', $id)
            ->first(['name', 'img']);
        $oldImg = $oldVslData->img;

        if ($request->att_file) {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $oldImg);
            Storage::delete($oldFilePath);
            $path = Storage::putFile("public/photos", $request->file('att_file'));
            $newImg = Storage::url($path);
        }
        if ($att_action == "delete") {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $oldImg);
            Storage::delete($oldFilePath);
            $newImg = null;
        }
        if ($att_action == "notSet") {
            $newImg = $oldImg;
        }

        //---UPDATE NAME FOR ALL TABLES 
        $oldVslName = $oldVslData->name;
        DB::table('action_plans')
            ->where('unit_name', $oldVslName)
            ->update(['unit_name' => strtolower($request->name)]);
        DB::table('crane_logs')
            ->where('vsl_name', $oldVslName)
            ->update(['vsl_name' => strtolower($request->name)]);
        DB::table('crane_operators')
            ->where('vsl_name', $oldVslName)
            ->update(['vsl_name' => strtolower($request->name)]);
        DB::table('min_of_meetings')
            ->where('unit_name', $oldVslName)
            ->update(['unit_name' => strtolower($request->name)]);
        DB::table('plannings')
            ->where('vsl_name', $oldVslName)
            ->update(['vsl_name' => strtolower($request->name)]);
        DB::table('voyages')
            ->where('vsl_name', $oldVslName)
            ->update(['vsl_name' => strtolower($request->name)]);
        DB::table('voyage_activities')
            ->where('vsl_name', $oldVslName)
            ->update(['vsl_name' => strtolower($request->name)]);


        //---UPDATE 1
        DB::table('vessels')
            ->where('id', $id)
            ->update([
                'img' => $newImg,
                'name' => strtolower($request->name),
                'abbr' => strtolower($request->abbr),
                'imo' => $request->imo,
                'type' => $request->type,
                'owner' => $request->owner,
                'role' => $request->role,
                'port' => $request->port,
                'off_number' => $request->off_number,
                'flag' => $request->flag,
                'call_sign' => $request->call_sign,
                'mmsi' => $request->mmsi,
                'class_society' => $request->class_society,
                'class_notation' => $request->class_notation,
                'trade_area' => $request->trade_area,
                'reg_owner' => $request->reg_owner,
                'tech_manager' => $request->tech_manager,
                'shipbuilder' => $request->shipbuilder,
                'delivery_date' => $request->delivery_date,
                'yard_number' => $request->yard_number,
                'gross_ton' => $request->gross_ton,
                'net_ton' => $request->net_ton,
                'length' => $request->length,
                'lpp' => $request->lpp,
                'breath' => $request->breath,
                'depth_moulded' => $request->depth_moulded,
                'scantling_draft' => $request->scantling_draft,
                'summer_draft' => $request->summer_draft,
                'fw_allowance' => $request->fw_allowance,
                'air_draft' => $request->air_draft,
                'sum_deadweight' => $request->sum_deadweight,
                'sum_disp' => $request->sum_disp,
                'lightweight' => $request->lightweight,
                'max_deck_load' => $request->max_deck_load,
                'wood_deck_area' => $request->wood_deck_area,
                'work_deck_area' => $request->work_deck_area,
            ]);

        //---UPDATE 2
        DB::table('vessels')
            ->where('id', $id)
            ->update([
                'tank_hsfo' => $request->tank_hsfo,
                'tank_vslfo' => $request->tank_vslfo,
                'tank_mgo' => $request->tank_mgo,
                'tank_lo' => $request->tank_lo,
                'tank_fw' => $request->tank_fw,
                'tank_bal_cap' => $request->tank_bal_cap,
                'tank_bal_perm' => $request->tank_bal_perm,
                'tank_water_cap' => $request->tank_water_cap,
                'tank_seawage' => $request->tank_seawage,
                'tank_sludge' => $request->tank_sludge,
                'speed_bal_full' => $request->speed_bal_full,
                'speed_bal_eco' => $request->speed_bal_eco,
                'speed_laden_full' => $request->speed_laden_full,
                'speed_laden_eco' => $request->speed_laden_eco,
                'consump_me_bal_full' => $request->consump_me_bal_full,
                'consump_me_bal_eco' => $request->consump_me_bal_eco,
                'consump_me_laden_full' => $request->consump_me_laden_full,
                'consump_me_laden_eco' => $request->consump_me_laden_eco,
                'consump_ae_1' => $request->consump_ae_1,
                'consump_ae_2' => $request->consump_ae_2,
                'consump_ae_3' => $request->consump_ae_3,
                'consump_lome_crank' => $request->consump_lome_crank,
                'consump_lome_cylind' => $request->consump_lome_cylind,
                'consump_ae_peng' => $request->consump_ae_peng,
                'consump_bal_intake' => $request->consump_bal_intake,
                'consump_bal_disch' => $request->consump_bal_disch,
                'consump_fire_pump' => $request->consump_fire_pump,
            ]);

        //---UPDATE 3
        DB::table('vessels')
            ->where('id', $id)
            ->update([
                'me_numbers' => $request->me_numbers,
                'me_spec' => $request->me_spec,
                'me_name' => $request->me_name,
                'me_model' => $request->me_model,
                'me_cylinders' => $request->me_cylinders,
                'me_kw' => $request->me_kw,
                'me_rpm' => $request->me_rpm,
                'me_stroke' => $request->me_stroke,
                'me_bore' => $request->me_bore,
                'me_sfoc' => $request->me_sfoc,
                'me_nox' => $request->me_nox,
                'me_overh_interval' => $request->me_overh_interval,
                'ae_numbers' => $request->ae_numbers,
                'ae_design' => $request->ae_design,
                'ae_model' => $request->ae_model,
                'ae_cylinders' => $request->ae_cylinders,
                'ae_kw' => $request->ae_kw,
                'ae_rpm' => $request->ae_rpm,
                'ae_stroke' => $request->ae_stroke,
                'ae_bore' => $request->ae_bore,
                'ae_sfoc' => $request->ae_sfoc,
                'ae_nox' => $request->ae_nox,
                'ae_overh_interval' => $request->ae_overh_interval,
                'prop_bow_kw' => $request->prop_bow_kw,
                'prop_bow_numbers' => $request->prop_bow_numbers,
                'prop_propellers' => $request->prop_propellers,
                'prop_propeller_type' => $request->prop_propeller_type,
                'prop_rud_num' => $request->prop_rud_num,
                'prop_rud_type' => $request->prop_rud_type,
                'prop_index_req' => $request->prop_index_req,
                'prop_index_fact' => $request->prop_index_fact,
                'prop_cii' => $request->prop_cii,
            ]);

        //---UPDATE 4
        DB::table('vessels')
            ->where('id', $id)
            ->update([
                'anch_numbers' => $request->anch_numbers,
                'anch_w' => $request->anch_w,
                'anch_ch_size' => $request->anch_ch_size,
                'anch_ch_ss' => $request->anch_ch_ss,
                'anch_ch_ps' => $request->anch_ch_ps,
                'anch_ch_stern' => $request->anch_ch_stern,
                'crane_type' => $request->crane_type,
                'crane_maker' => $request->crane_maker,
                'crane_outreach' => $request->crane_outreach,
                'comm_c1' => $request->comm_c1,
                'comm_c2' => $request->comm_c2,
                'comm_vsat' => $request->comm_vsat,
                'comm_broad' => $request->comm_broad,
                'comm_gsm' => $request->comm_gsm,
                'comm_irid' => $request->comm_irid,
                'comm_email1' => $request->comm_email1,
                'comm_email2' => $request->comm_email2,
                'insur_pi' => $request->insur_pi,
                'insur_hm' => $request->insur_hm,
                'insur_val' => $request->insur_val,
                'tow_main_len' => $request->tow_main_len,
                'tow_main_dia' => $request->tow_main_dia,
                'tow_spare_len' => $request->tow_spare_len,
                'tow_spare_dia' => $request->tow_spare_dia,
                'tow_winch_type' => $request->tow_winch_type,
                'tow_winch_pull' => $request->tow_winch_pull,
                'tow_jaw' => $request->tow_jaw,
                'tow_jaw_sw' => $request->tow_jaw_sw,
                'tow_pins' => $request->tow_pins,
                'tow_pins_sw' => $request->tow_pins_sw,
            ]);

        //---UPDATE 5
        DB::table('vessels')
            ->where('id', $id)
            ->update([
                'cargo_crane_numbers' => $request->cargo_crane_numbers,
                'cargo_crane_type' => $request->cargo_crane_type,
                'cargo_crane_manuf' => $request->cargo_crane_manuf,
                'cargo_crane_max1' => $request->cargo_crane_max1,
                'cargo_crane_max2' => $request->cargo_crane_max2,
                'cargo_crane_swl' => $request->cargo_crane_swl,
                'cargo_crane_time_full' => $request->cargo_crane_time_full,
                'cargo_crane_time_hois' => $request->cargo_crane_time_hois,
                'cargo_crane_time_luf' => $request->cargo_crane_time_luf,
                'cargo_crane_time_slew' => $request->cargo_crane_time_slew,
                'cargo_crane_is_gear' => $request->cargo_crane_is_gear,
                'cargo_crane_are_winch' => $request->cargo_crane_are_winch,
                'cargo_crane_grab_type' => $request->cargo_crane_grab_type,
                'cargo_crane_grab_cap' => $request->cargo_crane_grab_cap,
                'cargo_crane_grab_pwr' => $request->cargo_crane_grab_pwr,
                'cargo_crane_grab_loc' => $request->cargo_crane_grab_loc,
                'cargo_holds_1' => $request->cargo_holds_1,
                'cargo_holds_2' => $request->cargo_holds_2,
                'cargo_holds_3' => $request->cargo_holds_3,
                'cargo_holds_4' => $request->cargo_holds_4,
                'cargo_holds_5' => $request->cargo_holds_5,
                'cargo_holds_6' => $request->cargo_holds_6,
                'cargo_holds_7' => $request->cargo_holds_7,
                'cargo_holds_8' => $request->cargo_holds_8,
                'cargo_holds_9' => $request->cargo_holds_9,
                'cargo_holds_10' => $request->cargo_holds_10,
                'cargo_holds_11' => $request->cargo_holds_11,
                'cargo_holds_12' => $request->cargo_holds_12,
                'cargo_holds_13' => $request->cargo_holds_13,
                'cargo_holds_14' => $request->cargo_holds_14,
                'cargo_holds_15' => $request->cargo_holds_15,
                'cargo_holds_16' => $request->cargo_holds_16,
                'cargo_holds_17' => $request->cargo_holds_17,
                'cargo_holds_18' => $request->cargo_holds_18,
                'cargo_holds_19' => $request->cargo_holds_19,
                'cargo_holds_20' => $request->cargo_holds_20,
                'cargo_holds_21' => $request->cargo_holds_21,
                'cargo_holds_22' => $request->cargo_holds_22,
                'cargo_holds_23' => $request->cargo_holds_23,
                'cargo_holds_24' => $request->cargo_holds_24,
                'hatches_1' => $request->hatches_1,
                'hatches_2' => $request->hatches_2,
                'hatches_3' => $request->hatches_3,
                'hatches_4' => $request->hatches_4,
                'hatches_5' => $request->hatches_5,
                'hatches_6' => $request->hatches_6,
                'hatches_7' => $request->hatches_7,
                'hatches_8' => $request->hatches_8,
                'hatches_9' => $request->hatches_9,
                'hatches_10' => $request->hatches_10,
            ]);

        return redirect()
            ->route('vsl_start')
            ->with('status_msg', 'Data changed successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE VESSEL -----------------------------
    //--------------------------------------------------------------------
    public function deleteVessel(Request $request, $id)
    {
        //---DELETE OLD FILE --------------------------------------
        $oldVslData = DB::table('vessels')
            ->where('id', $id)
            ->first(['id', 'name', 'img']);
        $oldImg = $oldVslData->img;
        if ($oldImg) {
            $filePath = str_replace('storage', 'public', $oldImg);
            Storage::delete($filePath);
        }

        //---DELETE FOR ALL TABLES 
        $oldVslName = $oldVslData->name;
        //---DELETE MOM + FILE
        $momFiles = DB::table('min_of_meetings')
            ->where('unit_name', $oldVslName)
            ->get('file_path');
        foreach ($momFiles as $filePath) {
            $oldFilePath = str_replace('storage', 'public', $filePath->file_path);
            Storage::delete($oldFilePath);
        }
        DB::table('min_of_meetings')
            ->where('unit_name', $oldVslName)
            ->delete();
        DB::table('action_plans')
            ->where('unit_name', $oldVslName)
            ->delete();
        DB::table('crane_logs')
            ->where('vsl_name', $oldVslName)
            ->delete();
        DB::table('crane_operators')
            ->where('vsl_name', $oldVslName)
            ->delete();
        DB::table('plannings')
            ->where('vsl_name', $oldVslName)
            ->delete();
        DB::table('voyages')
            ->where('vsl_name', $oldVslName)
            ->delete();
        DB::table('voyage_activities')
            ->where('vsl_name', $oldVslName)
            ->delete();
        DB::table('vessel_certificates')
            ->where('vsl_id', $oldVslData->id)
            ->delete();

        DB::table('vessels')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('vsl_start')
            ->with('status_msg', 'Data deleted successfully!');
    }
}
