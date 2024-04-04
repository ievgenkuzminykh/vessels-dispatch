<?php

namespace App\Http\Controllers;

use App\Models\CraneLog;
use App\Models\CraneOperator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CraneController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ CRANE OPER START --------------------------
    //--------------------------------------------------------------------
    public function craneOperStart()
    {
        $itemList = DB::table('crane_operators')
            ->orderBy('full_name', 'asc')
            ->orderBy('vsl_name', 'desc')
            ->get();

        return view('crane_operators.start', ['itemList' => $itemList]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE OPER DESC ---------------------------
    //--------------------------------------------------------------------
    public function craneOperDesc($id)
    {
        $oper = new CraneOperator();
        $oper_data = $oper::find($id);

        $vslList = DB::table('vessels')
            ->where('type', 'transshiper')
            ->get(['name', 'imo']);

        return view('crane_operators.show_description', [
            'oper_data' => $oper_data,
            'vsl_list' => $vslList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE OPER ADD ----------------------------
    //--------------------------------------------------------------------
    public function craneOperAdd()
    {
        $vslList = DB::table('vessels')
            ->where('type', 'transshiper')
            ->get(['name', 'imo']);

        return view('crane_operators.add_new', [
            'vsl_list' => $vslList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE OPER SAVE ---------------------------
    //--------------------------------------------------------------------
    public function craneOperSave(Request $request)
    {
        $valid = $request->validate([
            'full_name' => 'required',
            'vsl_name' => 'required',
            'user_state' => 'required',
        ]);

        if ($request->user_state == 'active') {
            $sign_on = date('Y-m-d');
            $sign_off = null;
        } else {
            $sign_on = null;
            $sign_off = date('Y-m-d');
        }

        DB::table('crane_operators')
            ->insert([
                'full_name' => strtolower($request->full_name),
                'vsl_name' => strtolower($request->vsl_name),
                'user_state' => $request->user_state,
                'sign_on' => $sign_on,
                'sign_off' => $sign_off,
            ]);

        return redirect()
            ->route('crane_oper_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE OPER UPDATE -------------------------
    //--------------------------------------------------------------------
    public function craneOperUpdate(Request $request)
    {
        $valid = $request->validate([
            'full_name' => 'required',
            'vsl_name' => 'required',
        ]);

        $user_state = $request->user_state ? $request->user_state : 'archived';

        if ($request->user_state == 'active') {
            DB::table('crane_operators')
                ->where('id', $request->id)
                ->update([
                    'full_name' => strtolower($request->full_name),
                    'vsl_name' => strtolower($request->vsl_name),
                    'user_state' => $user_state,
                    'sign_on' => date('Y-m-d'),
                ]);
        } else {
            DB::table('crane_operators')
                ->where('id', $request->id)
                ->update([
                    'full_name' => strtolower($request->full_name),
                    'vsl_name' => strtolower($request->vsl_name),
                    'user_state' => $user_state,
                    'sign_off' => date('Y-m-d'),
                ]);
        }


        return redirect()
            ->route('crane_oper_start')
            ->with('status_msg', 'Data updated successfully!');
    }







    //================================ CRANES ========================================================
    //--------------------------------------------------------------------
    //------------------------ CRANE  STATISTIC --------------------------
    //--------------------------------------------------------------------
    public function craneStatisticStart()
    {

        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'asc')
            ->get('name');

        $oper_list = DB::table('crane_operators')
            ->where('user_state', 'active')
            ->orderBy('full_name', 'asc')
            ->get('full_name');

        $cranes_data = DB::table('crane_logs')
            ->where('oper_name', '!=', 'void-watch')
            ->orderBy('id', 'desc')
            ->take(180)
            ->get();


        $vslLabels = [];
        $craneLabels = [];
        $operLabels = [];
        $ogvLabels = [];

        $vslMass = [];
        $craneMass = [];
        $operMass = [];
        $ogvMass = [];

        //--- CREATE MASS + FILL VSL & CRANES
        foreach ($cranes_data as $item) {
            $vsl = $item->vsl_name;
            $oper = $item->oper_name;
            $ogv = $item->ogv_name;
            $crane = $item->crane_number;
            $crKey = substr($vsl, 0, 2) . '-' . $crane;

            if (!in_array($vsl, $vslLabels)) $vslLabels[] = $vsl;
            if (!in_array($oper, $operLabels)) $operLabels[] = $oper;
            if (!in_array($crKey, $craneLabels)) $craneLabels[] = $crKey;
            if (!in_array($ogv, $ogvLabels)) $ogvLabels[] = $ogv;

            if (!array_key_exists($vsl, $vslMass)) {
                $vslMass[$vsl]['wrk_time'] = 0;
                $vslMass[$vsl]['loaded'] = 0;
                $vslMass[$vsl]['gross'] = 0;
                $vslMass[$vsl]['nett'] = 0;
                $vslMass[$vsl]['stop'] = 0;
                $vslMass[$vsl]['weather_time'] = 0;
                $vslMass[$vsl]['tsv_time'] = 0;
                $vslMass[$vsl]['upmp_time'] = 0;
                $vslMass[$vsl]['pmp_time'] = 0;
                $vslMass[$vsl]['stop_perc'] = 0;
                $vslMass[$vsl]['weather_perc'] = 0;
                $vslMass[$vsl]['tsv_perc'] = 0;
                $vslMass[$vsl]['upmp_perc'] = 0;
                $vslMass[$vsl]['pmp_perc'] = 0;
            }

            if (!array_key_exists($oper, $operMass)) {
                $operMass[$oper]['wrk_time'] = 0;
                $operMass[$oper]['loaded'] = 0;
                $operMass[$oper]['gross'] = 0;
                $operMass[$oper]['nett'] = 0;
                $operMass[$oper]['stop'] = 0;
                $operMass[$oper]['weather_time'] = 0;
                $operMass[$oper]['tsv_time'] = 0;
                $operMass[$oper]['upmp_time'] = 0;
                $operMass[$oper]['pmp_time'] = 0;
                $operMass[$oper]['stop_perc'] = 0;
                $operMass[$oper]['weather_perc'] = 0;
                $operMass[$oper]['tsv_perc'] = 0;
                $operMass[$oper]['upmp_perc'] = 0;
                $operMass[$oper]['pmp_perc'] = 0;
            }

            if (!array_key_exists($crKey, $craneMass)) {
                $craneMass[$crKey]['wrk_time'] = 0;
                $craneMass[$crKey]['loaded'] = 0;
                $craneMass[$crKey]['gross'] = 0;
                $craneMass[$crKey]['nett'] = 0;
                $craneMass[$crKey]['stop'] = 0;
                $craneMass[$crKey]['weather_time'] = 0;
                $craneMass[$crKey]['tsv_time'] = 0;
                $craneMass[$crKey]['upmp_time'] = 0;
                $craneMass[$crKey]['pmp_time'] = 0;
                $craneMass[$crKey]['stop_perc'] = 0;
                $craneMass[$crKey]['weather_perc'] = 0;
                $craneMass[$crKey]['tsv_perc'] = 0;
                $craneMass[$crKey]['upmp_perc'] = 0;
                $craneMass[$crKey]['pmp_perc'] = 0;
            }

            if (!array_key_exists($ogv, $ogvMass)) {
                $ogvMass[$ogv]['wrk_time'] = 0;
                $ogvMass[$ogv]['loaded'] = 0;
                $ogvMass[$ogv]['gross'] = 0;
                $ogvMass[$ogv]['nett'] = 0;
                $ogvMass[$ogv]['stop'] = 0;
                $ogvMass[$ogv]['weather_time'] = 0;
                $ogvMass[$ogv]['tsv_time'] = 0;
                $ogvMass[$ogv]['upmp_time'] = 0;
                $ogvMass[$ogv]['pmp_time'] = 0;
                $ogvMass[$ogv]['stop_perc'] = 0;
                $ogvMass[$ogv]['weather_perc'] = 0;
                $ogvMass[$ogv]['tsv_perc'] = 0;
                $ogvMass[$ogv]['upmp_perc'] = 0;
                $ogvMass[$ogv]['pmp_perc'] = 0;
            }
        }



        //--- FILL MASS ALL
        foreach ($cranes_data as $item) {
            $oper = $item->oper_name;
            $vsl = $item->vsl_name;
            $ogv = $item->ogv_name;
            $crane = $item->crane_number;
            $crKey = substr($vsl, 0, 2) . '-' . $crane;

            $operMass[$oper]['wrk_time'] += 4;
            $operMass[$oper]['loaded'] += $item->total_loaded;
            $operMass[$oper]['stop'] += $item->stop_duration;
            $operMass[$oper]['weather_time'] += $item->weather_time;
            $operMass[$oper]['tsv_time'] += $item->tsv_time;
            $operMass[$oper]['upmp_time'] += $item->upmp_time;
            $operMass[$oper]['pmp_time'] += $item->pmp_time;

            $vslMass[$vsl]['wrk_time'] += 4;
            $vslMass[$vsl]['loaded'] += $item->total_loaded;
            $vslMass[$vsl]['stop'] += $item->stop_duration;
            $vslMass[$vsl]['weather_time'] += $item->weather_time;
            $vslMass[$vsl]['tsv_time'] += $item->tsv_time;
            $vslMass[$vsl]['upmp_time'] += $item->upmp_time;
            $vslMass[$vsl]['pmp_time'] += $item->pmp_time;

            $craneMass[$crKey]['wrk_time'] += 4;
            $craneMass[$crKey]['loaded'] += $item->total_loaded;
            $craneMass[$crKey]['stop'] += $item->stop_duration;
            $craneMass[$crKey]['weather_time'] += $item->weather_time;
            $craneMass[$crKey]['tsv_time'] += $item->tsv_time;
            $craneMass[$crKey]['upmp_time'] += $item->upmp_time;
            $craneMass[$crKey]['pmp_time'] += $item->pmp_time;

            $ogvMass[$ogv]['wrk_time'] += 4;
            $ogvMass[$ogv]['loaded'] += $item->total_loaded;
            $ogvMass[$ogv]['stop'] += $item->stop_duration;
            $ogvMass[$ogv]['weather_time'] += $item->weather_time;
            $ogvMass[$ogv]['tsv_time'] += $item->tsv_time;
            $ogvMass[$ogv]['upmp_time'] += $item->upmp_time;
            $ogvMass[$ogv]['pmp_time'] += $item->pmp_time;
        }


        //--- CALC GROSS & NETT & PERCENT VALUES ------------
        foreach ($operLabels as $i) {
            $operMass[$i]['gross'] =  round($operMass[$i]['loaded'] / $operMass[$i]['wrk_time'], 2);
            $operMass[$i]['nett'] =  round($operMass[$i]['loaded'] / ($operMass[$i]['wrk_time'] - $operMass[$i]['stop'] / 60), 2);
            $operMass[$i]['stop_perc'] = round($operMass[$i]['stop'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['weather_perc'] = round($operMass[$i]['weather_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['tsv_perc'] = round($operMass[$i]['tsv_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['upmp_perc'] = round($operMass[$i]['upmp_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['pmp_perc'] = round($operMass[$i]['pmp_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($vslLabels as $i) {
            $vslMass[$i]['gross'] =  round($vslMass[$i]['loaded'] / $vslMass[$i]['wrk_time'], 2);
            $vslMass[$i]['nett'] =  round($vslMass[$i]['loaded'] / ($vslMass[$i]['wrk_time'] - $vslMass[$i]['stop'] / 60), 2);
            $vslMass[$i]['stop_perc'] = round($vslMass[$i]['stop'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['weather_perc'] = round($vslMass[$i]['weather_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['tsv_perc'] = round($vslMass[$i]['tsv_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['upmp_perc'] = round($vslMass[$i]['upmp_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['pmp_perc'] = round($vslMass[$i]['pmp_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($craneLabels as $i) {
            $craneMass[$i]['gross'] =  round($craneMass[$i]['loaded'] / $craneMass[$i]['wrk_time'], 2);
            $craneMass[$i]['nett'] =  round($craneMass[$i]['loaded'] / ($craneMass[$i]['wrk_time'] - $craneMass[$i]['stop'] / 60), 2);
            $craneMass[$i]['stop_perc'] = round($craneMass[$i]['stop'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['weather_perc'] = round($craneMass[$i]['weather_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['tsv_perc'] = round($craneMass[$i]['tsv_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['upmp_perc'] = round($craneMass[$i]['upmp_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['pmp_perc'] = round($craneMass[$i]['pmp_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($ogvLabels as $i) {
            $ogvMass[$i]['gross'] =  round($ogvMass[$i]['loaded'] / $ogvMass[$i]['wrk_time'], 2);
            $ogvMass[$i]['nett'] =  round($ogvMass[$i]['loaded'] / ($ogvMass[$i]['wrk_time'] - $ogvMass[$i]['stop'] / 60), 2);
            $ogvMass[$i]['stop_perc'] = round($ogvMass[$i]['stop'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['weather_perc'] = round($ogvMass[$i]['weather_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['tsv_perc'] = round($ogvMass[$i]['tsv_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['upmp_perc'] = round($ogvMass[$i]['upmp_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['pmp_perc'] = round($ogvMass[$i]['pmp_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
        }


        //---------- OPER DATA---------------------
        $operLoadMass = [];
        $operGrossMass = [];
        $operNettMass = [];
        $operStopMass = [];
        $operWeatherMass = [];
        $operTsvMass = [];
        $operUpmpMass = [];
        $operPmpMass = [];

        foreach ($operLabels as $op) {
            $operLoadMass[] = $operMass[$op]['loaded'];
            $operGrossMass[] = $operMass[$op]['gross'];
            $operNettMass[] = $operMass[$op]['nett'];
            $operStopMass[] = $operMass[$op]['stop'];
            $operWeatherMass[] = $operMass[$op]['weather_time'];
            $operTsvMass[] = $operMass[$op]['tsv_time'];
            $operUpmpMass[] = $operMass[$op]['upmp_time'];
            $operPmpMass[] = $operMass[$op]['pmp_time'];
        }



        //---------- VSL DATA---------------------
        $vslLoadMass = [];
        $vslGrossMass = [];
        $vslNettMass = [];
        $vslStopMass = [];
        $vslWeatherMass = [];
        $vslTsvMass = [];
        $vslUpmpMass = [];
        $vslPmpMass = [];

        foreach ($vslLabels as $vsl) {
            $vslLoadMass[] = $vslMass[$vsl]['loaded'];
            $vslGrossMass[] = $vslMass[$vsl]['gross'];
            $vslNettMass[] = $vslMass[$vsl]['nett'];
            $vslStopMass[] = $vslMass[$vsl]['stop'];
            $vslWeatherMass[] = $vslMass[$vsl]['weather_time'];
            $vslTsvMass[] = $vslMass[$vsl]['tsv_time'];
            $vslUpmpMass[] = $vslMass[$vsl]['upmp_time'];
            $vslPmpMass[] = $vslMass[$vsl]['pmp_time'];
        }


        //---------- CRANES DATA---------------------
        $craneLoadMass = [];
        $craneGrossMass = [];
        $craneNettMass = [];
        $craneStopMass = [];
        $craneWeatherMass = [];
        $craneTsvMass = [];
        $craneUpmpMass = [];
        $cranePmpMass = [];

        foreach ($craneLabels as $crane) {
            $craneLoadMass[] = $craneMass[$crane]['loaded'];
            $craneGrossMass[] = $craneMass[$crane]['gross'];
            $craneNettMass[] = $craneMass[$crane]['nett'];
            $craneStopMass[] = $craneMass[$crane]['stop'];
            $craneWeatherMass[] = $craneMass[$crane]['weather_time'];
            $craneTsvMass[] = $craneMass[$crane]['tsv_time'];
            $craneUpmpMass[] = $craneMass[$crane]['upmp_time'];
            $cranePmpMass[] = $craneMass[$crane]['pmp_time'];
        }


        //---------- OGV DATA---------------------
        $ogvLoadMass = [];
        $ogvGrossMass = [];
        $ogvNettMass = [];
        $ogvStopMass = [];
        $ogvWeatherMass = [];
        $ogvTsvMass = [];
        $ogvUpmpMass = [];
        $ogvPmpMass = [];

        foreach ($ogvLabels as $ogv) {
            $ogvLoadMass[] = $ogvMass[$ogv]['loaded'];
            $ogvGrossMass[] = $ogvMass[$ogv]['gross'];
            $ogvNettMass[] = $ogvMass[$ogv]['nett'];
            $ogvStopMass[] = $ogvMass[$ogv]['stop'];
            $ogvWeatherMass[] = $ogvMass[$ogv]['weather_time'];
            $ogvTsvMass[] = $ogvMass[$ogv]['tsv_time'];
            $ogvUpmpMass[] = $ogvMass[$ogv]['upmp_time'];
            $ogvPmpMass[] = $ogvMass[$ogv]['pmp_time'];
        }


        return view('cranes.statistic', [
            'filter_title' => 'Last 30 days data',
            'oper_list' => $oper_list,
            'ogv_list' => $ogv_list,

            'vslMass' => $vslMass,
            'craneMass' => $craneMass,
            'operMass' => $operMass,
            'ogvMass' => $ogvMass,

            'operLabels' => $operLabels,
            'operLoadMass' => $operLoadMass,
            'operGrossMass' => $operGrossMass,
            'operNettMass' => $operNettMass,
            'operStopMass' => $operStopMass,
            'operWeatherMass' => $operWeatherMass,
            'operTsvMass' => $operTsvMass,
            'operUpmpMass' => $operUpmpMass,
            'operPmpMass' => $operPmpMass,

            'vslLabels' => $vslLabels,
            'vslLoadMass' => $vslLoadMass,
            'vslGrossMass' => $vslGrossMass,
            'vslNettMass' => $vslNettMass,
            'vslStopMass' => $vslStopMass,
            'vslWeatherMass' => $vslWeatherMass,
            'vslTsvMass' => $vslTsvMass,
            'vslUpmpMass' => $vslUpmpMass,
            'vslPmpMass' => $vslPmpMass,

            'craneLabels' => $craneLabels,
            'craneLoadMass' => $craneLoadMass,
            'craneGrossMass' => $craneGrossMass,
            'craneNettMass' => $craneNettMass,
            'craneStopMass' => $craneStopMass,
            'craneWeatherMass' => $craneWeatherMass,
            'craneTsvMass' => $craneTsvMass,
            'craneUpmpMass' => $craneUpmpMass,
            'cranePmpMass' => $cranePmpMass,

            'ogvLabels' => $ogvLabels,
            'ogvLoadMass' => $ogvLoadMass,
            'ogvGrossMass' => $ogvGrossMass,
            'ogvNettMass' => $ogvNettMass,
            'ogvStopMass' => $ogvStopMass,
            'ogvWeatherMass' => $ogvWeatherMass,
            'ogvTsvMass' => $ogvTsvMass,
            'ogvUpmpMass' => $ogvUpmpMass,
            'ogvPmpMass' => $ogvPmpMass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ CRANE  STATISTIC FILTER -------------------
    //--------------------------------------------------------------------
    public function craneStatisticFilter(Request $request)
    {

        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'asc')
            ->get('name');

        $oper_list = DB::table('crane_operators')
            ->where('user_state', 'active')
            ->orderBy('full_name', 'asc')
            ->get('full_name');

        $dt_1 = $request->date_from;
        $dt_2 = $request->date_to;
        $operNames = $request->operators;
        $ogvNames = $request->ogv;

        $cranes_data = DB::table('crane_logs')
            ->when($operNames, function ($query, $operNames) {
                $query->whereIn('oper_name', $operNames);
            })

            ->when($ogvNames, function ($query, $ogvNames) {
                $query->whereIn('ogv_name', $ogvNames);
            })

            ->whereBetween('start_date', [$dt_1, $dt_2])
            ->where('oper_name', '!=', 'void-watch')
            ->orderBy('id', 'desc')
            ->get();


        $vslLabels = [];
        $craneLabels = [];
        $operLabels = [];
        $ogvLabels = [];

        $vslMass = [];
        $craneMass = [];
        $operMass = [];
        $ogvMass = [];

        //--- CREATE MASS + FILL VSL & CRANES
        foreach ($cranes_data as $item) {
            $vsl = $item->vsl_name;
            $oper = $item->oper_name;
            $ogv = $item->ogv_name;
            $crane = $item->crane_number;
            $crKey = substr($vsl, 0, 2) . '-' . $crane;

            if (!in_array($vsl, $vslLabels)) $vslLabels[] = $vsl;
            if (!in_array($oper, $operLabels)) $operLabels[] = $oper;
            if (!in_array($crKey, $craneLabels)) $craneLabels[] = $crKey;
            if (!in_array($ogv, $ogvLabels)) $ogvLabels[] = $ogv;

            if (!array_key_exists($vsl, $vslMass)) {
                $vslMass[$vsl]['wrk_time'] = 0;
                $vslMass[$vsl]['loaded'] = 0;
                $vslMass[$vsl]['gross'] = 0;
                $vslMass[$vsl]['nett'] = 0;
                $vslMass[$vsl]['stop'] = 0;
                $vslMass[$vsl]['weather_time'] = 0;
                $vslMass[$vsl]['tsv_time'] = 0;
                $vslMass[$vsl]['upmp_time'] = 0;
                $vslMass[$vsl]['pmp_time'] = 0;
                $vslMass[$vsl]['stop_perc'] = 0;
                $vslMass[$vsl]['weather_perc'] = 0;
                $vslMass[$vsl]['tsv_perc'] = 0;
                $vslMass[$vsl]['upmp_perc'] = 0;
                $vslMass[$vsl]['pmp_perc'] = 0;
            }

            if (!array_key_exists($oper, $operMass)) {
                $operMass[$oper]['wrk_time'] = 0;
                $operMass[$oper]['loaded'] = 0;
                $operMass[$oper]['gross'] = 0;
                $operMass[$oper]['nett'] = 0;
                $operMass[$oper]['stop'] = 0;
                $operMass[$oper]['weather_time'] = 0;
                $operMass[$oper]['tsv_time'] = 0;
                $operMass[$oper]['upmp_time'] = 0;
                $operMass[$oper]['pmp_time'] = 0;
                $operMass[$oper]['stop_perc'] = 0;
                $operMass[$oper]['weather_perc'] = 0;
                $operMass[$oper]['tsv_perc'] = 0;
                $operMass[$oper]['upmp_perc'] = 0;
                $operMass[$oper]['pmp_perc'] = 0;
            }

            if (!array_key_exists($crKey, $craneMass)) {
                $craneMass[$crKey]['wrk_time'] = 0;
                $craneMass[$crKey]['loaded'] = 0;
                $craneMass[$crKey]['gross'] = 0;
                $craneMass[$crKey]['nett'] = 0;
                $craneMass[$crKey]['stop'] = 0;
                $craneMass[$crKey]['weather_time'] = 0;
                $craneMass[$crKey]['tsv_time'] = 0;
                $craneMass[$crKey]['upmp_time'] = 0;
                $craneMass[$crKey]['pmp_time'] = 0;
                $craneMass[$crKey]['stop_perc'] = 0;
                $craneMass[$crKey]['weather_perc'] = 0;
                $craneMass[$crKey]['tsv_perc'] = 0;
                $craneMass[$crKey]['upmp_perc'] = 0;
                $craneMass[$crKey]['pmp_perc'] = 0;
            }

            if (!array_key_exists($ogv, $ogvMass)) {
                $ogvMass[$ogv]['wrk_time'] = 0;
                $ogvMass[$ogv]['loaded'] = 0;
                $ogvMass[$ogv]['gross'] = 0;
                $ogvMass[$ogv]['nett'] = 0;
                $ogvMass[$ogv]['stop'] = 0;
                $ogvMass[$ogv]['weather_time'] = 0;
                $ogvMass[$ogv]['tsv_time'] = 0;
                $ogvMass[$ogv]['upmp_time'] = 0;
                $ogvMass[$ogv]['pmp_time'] = 0;
                $ogvMass[$ogv]['stop_perc'] = 0;
                $ogvMass[$ogv]['weather_perc'] = 0;
                $ogvMass[$ogv]['tsv_perc'] = 0;
                $ogvMass[$ogv]['upmp_perc'] = 0;
                $ogvMass[$ogv]['pmp_perc'] = 0;
            }
        }



        //--- FILL MASS ALL
        foreach ($cranes_data as $item) {
            $oper = $item->oper_name;
            $vsl = $item->vsl_name;
            $ogv = $item->ogv_name;
            $crane = $item->crane_number;
            $crKey = substr($vsl, 0, 2) . '-' . $crane;

            $operMass[$oper]['wrk_time'] += 4;
            $operMass[$oper]['loaded'] += $item->total_loaded;
            $operMass[$oper]['stop'] += $item->stop_duration;
            $operMass[$oper]['weather_time'] += $item->weather_time;
            $operMass[$oper]['tsv_time'] += $item->tsv_time;
            $operMass[$oper]['upmp_time'] += $item->upmp_time;
            $operMass[$oper]['pmp_time'] += $item->pmp_time;

            $vslMass[$vsl]['wrk_time'] += 4;
            $vslMass[$vsl]['loaded'] += $item->total_loaded;
            $vslMass[$vsl]['stop'] += $item->stop_duration;
            $vslMass[$vsl]['weather_time'] += $item->weather_time;
            $vslMass[$vsl]['tsv_time'] += $item->tsv_time;
            $vslMass[$vsl]['upmp_time'] += $item->upmp_time;
            $vslMass[$vsl]['pmp_time'] += $item->pmp_time;

            $craneMass[$crKey]['wrk_time'] += 4;
            $craneMass[$crKey]['loaded'] += $item->total_loaded;
            $craneMass[$crKey]['stop'] += $item->stop_duration;
            $craneMass[$crKey]['weather_time'] += $item->weather_time;
            $craneMass[$crKey]['tsv_time'] += $item->tsv_time;
            $craneMass[$crKey]['upmp_time'] += $item->upmp_time;
            $craneMass[$crKey]['pmp_time'] += $item->pmp_time;

            $ogvMass[$ogv]['wrk_time'] += 4;
            $ogvMass[$ogv]['loaded'] += $item->total_loaded;
            $ogvMass[$ogv]['stop'] += $item->stop_duration;
            $ogvMass[$ogv]['weather_time'] += $item->weather_time;
            $ogvMass[$ogv]['tsv_time'] += $item->tsv_time;
            $ogvMass[$ogv]['upmp_time'] += $item->upmp_time;
            $ogvMass[$ogv]['pmp_time'] += $item->pmp_time;
        }


        //--- CALC GROSS & NETT & PERCENT VALUES ------------
        foreach ($operLabels as $i) {
            $operMass[$i]['gross'] =  round($operMass[$i]['loaded'] / $operMass[$i]['wrk_time'], 2);
            $operMass[$i]['nett'] =  round($operMass[$i]['loaded'] / ($operMass[$i]['wrk_time'] - $operMass[$i]['stop'] / 60), 2);
            $operMass[$i]['stop_perc'] = round($operMass[$i]['stop'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['weather_perc'] = round($operMass[$i]['weather_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['tsv_perc'] = round($operMass[$i]['tsv_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['upmp_perc'] = round($operMass[$i]['upmp_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
            $operMass[$i]['pmp_perc'] = round($operMass[$i]['pmp_time'] / ($operMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($vslLabels as $i) {
            $vslMass[$i]['gross'] =  round($vslMass[$i]['loaded'] / $vslMass[$i]['wrk_time'], 2);
            $vslMass[$i]['nett'] =  round($vslMass[$i]['loaded'] / ($vslMass[$i]['wrk_time'] - $vslMass[$i]['stop'] / 60), 2);
            $vslMass[$i]['stop_perc'] = round($vslMass[$i]['stop'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['weather_perc'] = round($vslMass[$i]['weather_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['tsv_perc'] = round($vslMass[$i]['tsv_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['upmp_perc'] = round($vslMass[$i]['upmp_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
            $vslMass[$i]['pmp_perc'] = round($vslMass[$i]['pmp_time'] / ($vslMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($craneLabels as $i) {
            $craneMass[$i]['gross'] =  round($craneMass[$i]['loaded'] / $craneMass[$i]['wrk_time'], 2);
            $craneMass[$i]['nett'] =  round($craneMass[$i]['loaded'] / ($craneMass[$i]['wrk_time'] - $craneMass[$i]['stop'] / 60), 2);
            $craneMass[$i]['stop_perc'] = round($craneMass[$i]['stop'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['weather_perc'] = round($craneMass[$i]['weather_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['tsv_perc'] = round($craneMass[$i]['tsv_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['upmp_perc'] = round($craneMass[$i]['upmp_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
            $craneMass[$i]['pmp_perc'] = round($craneMass[$i]['pmp_time'] / ($craneMass[$i]['wrk_time'] * 60) * 100, 2);
        }

        foreach ($ogvLabels as $i) {
            $ogvMass[$i]['gross'] =  round($ogvMass[$i]['loaded'] / $ogvMass[$i]['wrk_time'], 2);
            $ogvMass[$i]['nett'] =  round($ogvMass[$i]['loaded'] / ($ogvMass[$i]['wrk_time'] - $ogvMass[$i]['stop'] / 60), 2);
            $ogvMass[$i]['stop_perc'] = round($ogvMass[$i]['stop'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['weather_perc'] = round($ogvMass[$i]['weather_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['tsv_perc'] = round($ogvMass[$i]['tsv_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['upmp_perc'] = round($ogvMass[$i]['upmp_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
            $ogvMass[$i]['pmp_perc'] = round($ogvMass[$i]['pmp_time'] / ($ogvMass[$i]['wrk_time'] * 60) * 100, 2);
        }


        //---------- OPER DATA---------------------
        $operLoadMass = [];
        $operGrossMass = [];
        $operNettMass = [];
        $operStopMass = [];
        $operWeatherMass = [];
        $operTsvMass = [];
        $operUpmpMass = [];
        $operPmpMass = [];

        foreach ($operLabels as $op) {
            $operLoadMass[] = $operMass[$op]['loaded'];
            $operGrossMass[] = $operMass[$op]['gross'];
            $operNettMass[] = $operMass[$op]['nett'];
            $operStopMass[] = $operMass[$op]['stop'];
            $operWeatherMass[] = $operMass[$op]['weather_time'];
            $operTsvMass[] = $operMass[$op]['tsv_time'];
            $operUpmpMass[] = $operMass[$op]['upmp_time'];
            $operPmpMass[] = $operMass[$op]['pmp_time'];
        }



        //---------- VSL DATA---------------------
        $vslLoadMass = [];
        $vslGrossMass = [];
        $vslNettMass = [];
        $vslStopMass = [];
        $vslWeatherMass = [];
        $vslTsvMass = [];
        $vslUpmpMass = [];
        $vslPmpMass = [];

        foreach ($vslLabels as $vsl) {
            $vslLoadMass[] = $vslMass[$vsl]['loaded'];
            $vslGrossMass[] = $vslMass[$vsl]['gross'];
            $vslNettMass[] = $vslMass[$vsl]['nett'];
            $vslStopMass[] = $vslMass[$vsl]['stop'];
            $vslWeatherMass[] = $vslMass[$vsl]['weather_time'];
            $vslTsvMass[] = $vslMass[$vsl]['tsv_time'];
            $vslUpmpMass[] = $vslMass[$vsl]['upmp_time'];
            $vslPmpMass[] = $vslMass[$vsl]['pmp_time'];
        }


        //---------- CRANES DATA---------------------
        $craneLoadMass = [];
        $craneGrossMass = [];
        $craneNettMass = [];
        $craneStopMass = [];
        $craneWeatherMass = [];
        $craneTsvMass = [];
        $craneUpmpMass = [];
        $cranePmpMass = [];

        foreach ($craneLabels as $crane) {
            $craneLoadMass[] = $craneMass[$crane]['loaded'];
            $craneGrossMass[] = $craneMass[$crane]['gross'];
            $craneNettMass[] = $craneMass[$crane]['nett'];
            $craneStopMass[] = $craneMass[$crane]['stop'];
            $craneWeatherMass[] = $craneMass[$crane]['weather_time'];
            $craneTsvMass[] = $craneMass[$crane]['tsv_time'];
            $craneUpmpMass[] = $craneMass[$crane]['upmp_time'];
            $cranePmpMass[] = $craneMass[$crane]['pmp_time'];
        }


        //---------- OGV DATA---------------------
        $ogvLoadMass = [];
        $ogvGrossMass = [];
        $ogvNettMass = [];
        $ogvStopMass = [];
        $ogvWeatherMass = [];
        $ogvTsvMass = [];
        $ogvUpmpMass = [];
        $ogvPmpMass = [];

        foreach ($ogvLabels as $ogv) {
            $ogvLoadMass[] = $ogvMass[$ogv]['loaded'];
            $ogvGrossMass[] = $ogvMass[$ogv]['gross'];
            $ogvNettMass[] = $ogvMass[$ogv]['nett'];
            $ogvStopMass[] = $ogvMass[$ogv]['stop'];
            $ogvWeatherMass[] = $ogvMass[$ogv]['weather_time'];
            $ogvTsvMass[] = $ogvMass[$ogv]['tsv_time'];
            $ogvUpmpMass[] = $ogvMass[$ogv]['upmp_time'];
            $ogvPmpMass[] = $ogvMass[$ogv]['pmp_time'];
        }



        return view('cranes.statistic', [
            'filter_title' => "Data for ($dt_1 / $dt_2)",
            'oper_list' => $oper_list,
            'ogv_list' => $ogv_list,

            'vslMass' => $vslMass,
            'craneMass' => $craneMass,
            'operMass' => $operMass,
            'ogvMass' => $ogvMass,

            'operLabels' => $operLabels,
            'operLoadMass' => $operLoadMass,
            'operGrossMass' => $operGrossMass,
            'operNettMass' => $operNettMass,
            'operStopMass' => $operStopMass,
            'operWeatherMass' => $operWeatherMass,
            'operTsvMass' => $operTsvMass,
            'operUpmpMass' => $operUpmpMass,
            'operPmpMass' => $operPmpMass,

            'vslLabels' => $vslLabels,
            'vslLoadMass' => $vslLoadMass,
            'vslGrossMass' => $vslGrossMass,
            'vslNettMass' => $vslNettMass,
            'vslStopMass' => $vslStopMass,
            'vslWeatherMass' => $vslWeatherMass,
            'vslTsvMass' => $vslTsvMass,
            'vslUpmpMass' => $vslUpmpMass,
            'vslPmpMass' => $vslPmpMass,

            'craneLabels' => $craneLabels,
            'craneLoadMass' => $craneLoadMass,
            'craneGrossMass' => $craneGrossMass,
            'craneNettMass' => $craneNettMass,
            'craneStopMass' => $craneStopMass,
            'craneWeatherMass' => $craneWeatherMass,
            'craneTsvMass' => $craneTsvMass,
            'craneUpmpMass' => $craneUpmpMass,
            'cranePmpMass' => $cranePmpMass,

            'ogvLabels' => $ogvLabels,
            'ogvLoadMass' => $ogvLoadMass,
            'ogvGrossMass' => $ogvGrossMass,
            'ogvNettMass' => $ogvNettMass,
            'ogvStopMass' => $ogvStopMass,
            'ogvWeatherMass' => $ogvWeatherMass,
            'ogvTsvMass' => $ogvTsvMass,
            'ogvUpmpMass' => $ogvUpmpMass,
            'ogvPmpMass' => $ogvPmpMass,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE  START ------------------------------
    //--------------------------------------------------------------------
    public function craneStart($vsl_name, $crane_number)
    {
        $itemList = DB::table('crane_logs')
            ->orderBy('id', 'asc')
            ->orderBy('start_date', 'desc')
            ->where('vsl_name', $vsl_name)
            ->where('crane_number', $crane_number)
            ->get();

        return view('cranes.crane_selected', [
            'vsl_name' => $vsl_name,
            'crane_number' => $crane_number,
            'itemList' => $itemList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE  ADD --------------------------------
    //--------------------------------------------------------------------
    public function craneAdd($vsl_name, $crane_number)
    {

        $oper_list = DB::table('crane_operators')
            ->where('vsl_name', $vsl_name)
            ->where('user_state', 'active')
            ->get();

        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'desc')
            ->get();


        $max_cnt = DB::table('crane_logs')
            ->where('start_date', date('Y-m-d', strtotime("-1 days")))
            ->where('vsl_name', $vsl_name)
            ->where('crane_number', $crane_number)
            ->max('end_cnt');

        $prev_data = DB::table('crane_logs')
            ->where('end_cnt', $max_cnt)
            ->where('start_date', date('Y-m-d', strtotime("-1 days")))
            ->where('vsl_name', $vsl_name)
            ->where('crane_number', $crane_number)
            ->first();

        return view('cranes.add_new', [
            'vsl_name' => $vsl_name,
            'crane_number' => $crane_number,
            'oper_list' => $oper_list,
            'ogv_list' => $ogv_list,
            'prev_data' => $prev_data,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ CRANE  SAVE -------------------------------
    //--------------------------------------------------------------------
    public function craneSave(Request $request)
    {

        $valid = $request->validate([
            'vsl_name' => 'required',
            'crane_number' => 'required',
            'start_date' => 'required',
            'ogv_name' => 'required',
        ]);

        $today_record = DB::table('crane_logs')
            ->where('start_date', $request->start_date)
            ->where('vsl_name', $request->vsl_name)
            ->where('crane_number', $request->crane_number)
            ->count();

        if ($today_record) {
            return redirect()
                ->route('crane_log_start', ['vsl_name' => $request->vsl_name, 'crane_number' => $request->crane_number])
                ->with('status_msg', "ERROR. Record for $request->start_date already exist");
        }


        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_0,
                'start_date' => $request->start_date,
                'work_hrs' => '00:00 - 04:00',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_0,
                'end_cnt' => $request->end_0,
                'total_loaded' => $request->total_0,
                'stop_duration' => $request->stop_0,
                'gross_load' => $request->gross_0,
                'nett_load' => $request->nett_0,
                'weather_time' => $request->weather_0_time,
                'weather_comment' => $request->weather_0_comment,
                'tsv_time' => $request->tsv_0_time,
                'tsv_comment' => $request->tsv_0_comment,
                'upmp_time' => $request->upmp_0_time,
                'upmp_comment' => $request->upmp_0_comment,
                'pmp_time' => $request->pmp_0_time,
                'pmp_comment' => $request->pmp_0_comment,
            ]);


        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_4,
                'start_date' => $request->start_date,
                'work_hrs' => '04:00 - 08:00',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_4,
                'end_cnt' => $request->end_4,
                'total_loaded' => $request->total_4,
                'stop_duration' => $request->stop_4,
                'gross_load' => $request->gross_4,
                'nett_load' => $request->nett_4,
                'weather_time' => $request->weather_4_time,
                'weather_comment' => $request->weather_4_comment,
                'tsv_time' => $request->tsv_4_time,
                'tsv_comment' => $request->tsv_4_comment,
                'upmp_time' => $request->upmp_4_time,
                'upmp_comment' => $request->upmp_4_comment,
                'pmp_time' => $request->pmp_4_time,
                'pmp_comment' => $request->pmp_4_comment,
            ]);

        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_8,
                'start_date' => $request->start_date,
                'work_hrs' => '08:00 - 12:00',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_8,
                'end_cnt' => $request->end_8,
                'total_loaded' => $request->total_8,
                'stop_duration' => $request->stop_8,
                'gross_load' => $request->gross_8,
                'nett_load' => $request->nett_8,
                'weather_time' => $request->weather_8_time,
                'weather_comment' => $request->weather_8_comment,
                'tsv_time' => $request->tsv_8_time,
                'tsv_comment' => $request->tsv_8_comment,
                'upmp_time' => $request->upmp_8_time,
                'upmp_comment' => $request->upmp_8_comment,
                'pmp_time' => $request->pmp_8_time,
                'pmp_comment' => $request->pmp_8_comment,
            ]);

        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_12,
                'start_date' => $request->start_date,
                'work_hrs' => '12:00 - 16:00',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_12,
                'end_cnt' => $request->end_12,
                'total_loaded' => $request->total_12,
                'stop_duration' => $request->stop_12,
                'gross_load' => $request->gross_12,
                'nett_load' => $request->nett_12,
                'weather_time' => $request->weather_12_time,
                'weather_comment' => $request->weather_12_comment,
                'tsv_time' => $request->tsv_12_time,
                'tsv_comment' => $request->tsv_12_comment,
                'upmp_time' => $request->upmp_12_time,
                'upmp_comment' => $request->upmp_12_comment,
                'pmp_time' => $request->pmp_12_time,
                'pmp_comment' => $request->pmp_12_comment,
            ]);

        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_16,
                'start_date' => $request->start_date,
                'work_hrs' => '16:00 - 20:00',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_16,
                'end_cnt' => $request->end_16,
                'total_loaded' => $request->total_16,
                'stop_duration' => $request->stop_16,
                'gross_load' => $request->gross_16,
                'nett_load' => $request->nett_16,
                'weather_time' => $request->weather_16_time,
                'weather_comment' => $request->weather_16_comment,
                'tsv_time' => $request->tsv_16_time,
                'tsv_comment' => $request->tsv_16_comment,
                'upmp_time' => $request->upmp_16_time,
                'upmp_comment' => $request->upmp_16_comment,
                'pmp_time' => $request->pmp_16_time,
                'pmp_comment' => $request->pmp_16_comment,
            ]);

        DB::table('crane_logs')
            ->insert([
                'oper_name' => $request->oper_20,
                'start_date' => $request->start_date,
                'work_hrs' => '20:00 - 23:59',
                'vsl_name' => strtolower($request->vsl_name),
                'crane_number' => $request->crane_number,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start_20,
                'end_cnt' => $request->end_20,
                'total_loaded' => $request->total_20,
                'stop_duration' => $request->stop_20,
                'gross_load' => $request->gross_20,
                'nett_load' => $request->nett_20,
                'weather_time' => $request->weather_20_time,
                'weather_comment' => $request->weather_20_comment,
                'tsv_time' => $request->tsv_20_time,
                'tsv_comment' => $request->tsv_20_comment,
                'upmp_time' => $request->upmp_20_time,
                'upmp_comment' => $request->upmp_20_comment,
                'pmp_time' => $request->pmp_20_time,
                'pmp_comment' => $request->pmp_20_comment,
            ]);



        return redirect()
            ->route('crane_log_start', ['vsl_name' => $request->vsl_name, 'crane_number' => $request->crane_number])
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ CRANE  DESC -------------------------------
    //--------------------------------------------------------------------
    public function craneDesc($id)
    {
        $crane = new CraneLog();
        $crane_data = $crane::find($id);

        $max_cnt = DB::table('crane_logs')
            ->where('id', '<', $id)
            ->where('work_hrs', '!=', $crane_data->work_hrs)
            ->where('start_date', $crane_data->start_date)
            ->where('vsl_name', $crane_data->vsl_name)
            ->where('crane_number', $crane_data->crane_number)
            ->max('end_cnt');


        $prev_data = DB::table('crane_logs')
            ->where('work_hrs', '!=', $crane_data->work_hrs)
            ->where('end_cnt', $max_cnt)
            ->where('start_date', $crane_data->start_date)
            ->where('vsl_name', $crane_data->vsl_name)
            ->where('crane_number', $crane_data->crane_number)
            ->first();


        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'desc')
            ->get();

        $oper_list = DB::table('crane_operators')
            ->where('vsl_name', $crane_data->vsl_name)
            ->where('user_state', 'active')
            ->get();

        return view('cranes.show_description', [
            'prev_data' => $prev_data,
            'crane_data' => $crane_data,
            'ogv_list' => $ogv_list,
            'oper_list' => $oper_list,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ CRANE  UPDATE -----------------------------
    //--------------------------------------------------------------------
    public function craneUpdate(Request $request)
    {
        $valid = $request->validate([
            'vsl_name' => 'required',
            'crane_number' => 'required',
            'start_date' => 'required',
            'ogv_name' => 'required',
        ]);

        DB::table('crane_logs')
            ->where('id', $request->id)
            ->update([
                'oper_name' => $request->oper_name,
                'ogv_name' => strtolower($request->ogv_name),
                'start_cnt' => $request->start,
                'end_cnt' => $request->end,
                'total_loaded' => abs($request->total),
                'stop_duration' => $request->stop,
                'gross_load' => abs($request->gross),
                'nett_load' => abs($request->nett),
                'weather_time' => $request->weather_time,
                'weather_comment' => $request->weather_comment,
                'tsv_time' => $request->tsv_time,
                'tsv_comment' => $request->tsv_comment,
                'upmp_time' => $request->upmp_time,
                'upmp_comment' => $request->upmp_comment,
                'pmp_time' => $request->pmp_time,
                'pmp_comment' => $request->pmp_comment,
            ]);

        return redirect()
            ->route('crane_log_start', ['vsl_name' => $request->vsl_name, 'crane_number' => $request->crane_number])
            ->with('status_msg', 'Data saved successfully!');
    }
}
