<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------  START ------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $loc_list = DB::table('locations')
            ->orderBy('name', 'asc')
            ->get();

        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'asc')
            ->get('name');

        $vsl_list = DB::table('vessels')
            ->where('type', 'feeder')
            ->orWhere('type', 'barge')
            ->orWhere('type', 'transshiper')
            ->orderBy('name', 'asc')
            ->get(['name', 'type']);

        $dt_1 = date('Y-m-d', strtotime("-30 days"));

        $activ_data = DB::table('voyage_activities')
            ->where('start_date', '>', $dt_1)
            ->orderBy('id', 'asc')
            ->get();

        $labelsMass = [];
        $loadMass = [];
        $dischMass = [];
        $transfMass = [];

        $loadActivNumbers = [];
        $dischActivNumbers = [];
        $transfActivNumbers = [];

        //--- CREATE EMPTY MASS WITH VSL KEYS + OPS NUMBERS
        foreach ($activ_data as $item) {
            $actName = $item->activity_name;
            $vsl = $item->vsl_name;
            if (!in_array($vsl, $labelsMass)) $labelsMass[] = $vsl;
            if (!array_key_exists($vsl, $loadMass)) {
                $loadMass[$vsl]['total_time'] = 0;
                $loadMass[$vsl]['stby_time'] = 0;
                $loadMass[$vsl]['cargo'] = 0;
                $loadMass[$vsl]['gross'] = 0;
                $loadMass[$vsl]['nett'] = 0;
            }
            if (!array_key_exists($vsl, $dischMass)) {
                $dischMass[$vsl]['total_time'] = 0;
                $dischMass[$vsl]['stby_time'] = 0;
                $dischMass[$vsl]['cargo'] = 0;
                $dischMass[$vsl]['gross'] = 0;
                $dischMass[$vsl]['nett'] = 0;
            }
            if (!array_key_exists($vsl, $transfMass)) {
                $transfMass[$vsl]['total_time'] = 0;
                $transfMass[$vsl]['stby_time'] = 0;
                $transfMass[$vsl]['cargo'] = 0;
                $transfMass[$vsl]['gross'] = 0;
                $transfMass[$vsl]['nett'] = 0;
            }
            //---COLLECT LOAD/DISCH/TRASF NUMBERS
            switch ($actName) {
                case 'Loading':
                    $loadActivNumbers[] = $item->activity_number;
                    break;
                case 'Discharging OGV':
                case 'Discharging TSV':
                    $dischActivNumbers[] = $item->activity_number;
                    break;
                case 'Transshipment':
                    $transfActivNumbers[] = $item->activity_number;
                    break;
            }
        }



        //--- FILL MASS BY FOR VESSELS
        foreach ($activ_data as $item) {
            $vsl = $item->vsl_name;
            $duration = $item->duration;
            $subactiv_for = $item->subactiv_for;
            switch ($item->activity_name) {
                case 'Loading':
                    $loadMass[$vsl]['total_time'] += $duration;
                    $loadMass[$vsl]['cargo'] += $item->cargo_loaded;
                    break;
                case 'Discharging OGV':
                case 'Discharging TSV':
                    $dischMass[$vsl]['total_time'] += $duration;
                    $dischMass[$vsl]['cargo'] += $item->cargo_discharged;
                    break;
                case 'Transshipment':
                    $transfMass[$vsl]['total_time'] += $duration;
                    $transfMass[$vsl]['cargo'] += $item->cargo_transfered;
                    break;
            }

            //---loading STBY
            if (in_array($subactiv_for, $loadActivNumbers) && $item->main_break) {
                $loadMass[$vsl]['stby_time'] += $item->duration;
            }
            //---disch STBY
            if (in_array($subactiv_for, $dischActivNumbers) && $item->main_break) {
                $dischMass[$vsl]['stby_time'] += $item->duration;
            }
            //---transf STBY
            if (in_array($subactiv_for, $transfActivNumbers) && $item->main_break) {
                $transfMass[$vsl]['stby_time'] += $item->duration;
            }
        }


        //--- CALC GROSS & NETT & PERCENT VALUES ------------
        foreach ($labelsMass as $vsl) {
            if ($loadMass[$vsl]['cargo']) {
                $loadMass[$vsl]['gross'] =  round($loadMass[$vsl]['cargo'] / $loadMass[$vsl]['total_time'], 2);
                $loadMass[$vsl]['nett'] =  round($loadMass[$vsl]['cargo'] / ($loadMass[$vsl]['total_time'] - $loadMass[$vsl]['stby_time']), 2);
            }
            if ($dischMass[$vsl]['cargo']) {
                $dischMass[$vsl]['gross'] =  round($dischMass[$vsl]['cargo'] / $dischMass[$vsl]['total_time'], 2);
                $dischMass[$vsl]['nett'] =  round($dischMass[$vsl]['cargo'] / ($dischMass[$vsl]['total_time'] - $dischMass[$vsl]['stby_time']), 2);
            }
            if ($transfMass[$vsl]['cargo']) {
                $transfMass[$vsl]['gross'] =  round($transfMass[$vsl]['cargo'] / $transfMass[$vsl]['total_time'], 2);
                $transfMass[$vsl]['nett'] =  round($transfMass[$vsl]['cargo'] / ($transfMass[$vsl]['total_time'] - $transfMass[$vsl]['stby_time']), 2);
            }
        }


        //---------- CHART MASS---------------------
        $loadTotalMass = [];
        $loadGrossMass = [];
        $loadNettMass = [];
        $dischTotalMass = [];
        $dischGrossMass = [];
        $dischNettMass = [];
        $transfTotalMass = [];
        $transfGrossMass = [];
        $transfNettMass = [];
        foreach ($labelsMass as $vsl) {
            $loadTotalMass[] = $loadMass[$vsl]['cargo'];
            $loadGrossMass[] = $loadMass[$vsl]['gross'];
            $loadNettMass[] = $loadMass[$vsl]['nett'];
            $dischTotalMass[] = $dischMass[$vsl]['cargo'];
            $dischGrossMass[] = $dischMass[$vsl]['gross'];
            $dischNettMass[] = $dischMass[$vsl]['nett'];
            $transfTotalMass[] = $transfMass[$vsl]['cargo'];
            $transfGrossMass[] = $transfMass[$vsl]['gross'];
            $transfNettMass[] = $transfMass[$vsl]['nett'];
        }


        return view('statistics.start', [
            'filter_title' => "Last 30 days data",
            'loc_list' => $loc_list,
            'activ_data' => $activ_data,
            'ogv_list' => $ogv_list,
            'vsl_list' => $vsl_list,
            'selectedVslTypes' => ['feeder'],
            'selectedVslNames' => [],
            'labelsMass' => $labelsMass,
            'loadTotalMass' => $loadTotalMass,
            'loadGrossMass' => $loadGrossMass,
            'loadNettMass' => $loadNettMass,
            'dischTotalMass' => $dischTotalMass,
            'dischGrossMass' => $dischGrossMass,
            'dischNettMass' => $dischNettMass,
            'transfTotalMass' => $transfTotalMass,
            'transfGrossMass' => $transfGrossMass,
            'transfNettMass' => $transfNettMass,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------  FILTER APPLY -----------------------------
    //--------------------------------------------------------------------
    public function filterApply(Request $request)
    {
        $loc_list = DB::table('locations')
            ->orderBy('name', 'asc')
            ->get();

        $ogv_list = DB::table('ogvs')
            ->orderBy('name', 'asc')
            ->get('name');

        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['name', 'type']);


        $location = $request->location;
        $vsl_type = $request->vsl_types;
        $vslNamesMass = $request->vsl_names;
        $ogvMass = $request->ogv;

        $dt1 = $request->date_from . ' 00:00';
        $dt2 = $request->date_to . ' 23:59';
        
        $activ_data = DB::table('voyage_activities')
            ->whereBetween('start_date', [$dt1, $dt2])

            ->when($location, function ($query, $location) {
                if($location == 'kamsar'){
                    $query->where('location','like', '%kamsar');
                }else if($location == 'conakry'){
                    $query->where('location','like', '%conakry');
                }else{
                    $query->where('location', $location);
                }
            })
            
            ->when($vsl_type, function ($query, $vsl_type) {
                $query->where('vsl_type', $vsl_type);
            })
            ->when($vslNamesMass, function ($query, $vslNamesMass) {
                $query->whereIn('vsl_name', $vslNamesMass);
            })
            ->when($ogvMass, function ($query, $ogvMass) {
                $query->whereIn('ogv', $ogvMass);
            })
            ->orderBy('id', 'asc')
            ->get();



        $labelsMass = [];
        $loadMass = [];
        $dischMass = [];
        $transfMass = [];

        $loadActivNumbers = [];
        $dischActivNumbers = [];
        $transfActivNumbers = [];


        //--- CREATE EMPTY MASS WITH VSL KEYS + OPS NUMBERS
        foreach ($activ_data as $item) {
            $actName = $item->activity_name;
            $vsl = $item->vsl_name;
            if (!in_array($vsl, $labelsMass)) $labelsMass[] = $vsl;

            if (!array_key_exists($vsl, $loadMass)) {
                $loadMass[$vsl]['total_time'] = 0;
                $loadMass[$vsl]['stby_time'] = 0;
                $loadMass[$vsl]['cargo'] = 0;
                $loadMass[$vsl]['gross'] = 0;
                $loadMass[$vsl]['nett'] = 0;
            }
            if (!array_key_exists($vsl, $dischMass)) {
                $dischMass[$vsl]['total_time'] = 0;
                $dischMass[$vsl]['stby_time'] = 0;
                $dischMass[$vsl]['cargo'] = 0;
                $dischMass[$vsl]['gross'] = 0;
                $dischMass[$vsl]['nett'] = 0;
            }
            if (!array_key_exists($vsl, $transfMass)) {
                $transfMass[$vsl]['total_time'] = 0;
                $transfMass[$vsl]['stby_time'] = 0;
                $transfMass[$vsl]['cargo'] = 0;
                $transfMass[$vsl]['gross'] = 0;
                $transfMass[$vsl]['nett'] = 0;
            }
            //---COLLECT LOAD/DISCH/TRASF NUMBERS
            switch ($actName) {
                case 'Loading':
                    $loadActivNumbers[] = $item->activity_number;
                    break;
                case 'Discharging OGV':
                case 'Discharging TSV':
                    $dischActivNumbers[] = $item->activity_number;
                    break;
                case 'Transshipment':
                    $transfActivNumbers[] = $item->activity_number;
                    break;
            }
        }



        //--- FILL MASS BY FOR VESSELS
        foreach ($activ_data as $item) {
            $vsl = $item->vsl_name;
            $duration = $item->duration;
            $subactiv_for = $item->subactiv_for;
            switch ($item->activity_name) {
                case 'Loading':
                    $loadMass[$vsl]['total_time'] += $duration;
                    $loadMass[$vsl]['cargo'] += $item->cargo_loaded;
                    break;
                case 'Discharging OGV':
                case 'Discharging TSV':
                    $dischMass[$vsl]['total_time'] += $duration;
                    $dischMass[$vsl]['cargo'] += $item->cargo_discharged;
                    break;
                case 'Transshipment':
                    $transfMass[$vsl]['total_time'] += $duration;
                    $transfMass[$vsl]['cargo'] += $item->cargo_transfered;
                    break;
            }

            //---loading STBY
            if (in_array($subactiv_for, $loadActivNumbers) && $item->main_break) {
                $loadMass[$vsl]['stby_time'] += $item->duration;
            }
            //---disch STBY
            if (in_array($subactiv_for, $dischActivNumbers) && $item->main_break) {
                $dischMass[$vsl]['stby_time'] += $item->duration;
            }
            //---transf STBY
            if (in_array($subactiv_for, $transfActivNumbers) && $item->main_break) {
                $transfMass[$vsl]['stby_time'] += $item->duration;
            }
        }


        //--- CALC GROSS & NETT & PERCENT VALUES ------------
        foreach ($labelsMass as $vsl) {
            if ($loadMass[$vsl]['cargo']) {
                $loadMass[$vsl]['gross'] =  round($loadMass[$vsl]['cargo'] / $loadMass[$vsl]['total_time'], 2);
                $loadMass[$vsl]['nett'] =  round($loadMass[$vsl]['cargo'] / ($loadMass[$vsl]['total_time'] - $loadMass[$vsl]['stby_time']), 2);
            }
            if ($dischMass[$vsl]['cargo']) {
                $dischMass[$vsl]['gross'] =  round($dischMass[$vsl]['cargo'] / $dischMass[$vsl]['total_time'], 2);
                $dischMass[$vsl]['nett'] =  round($dischMass[$vsl]['cargo'] / ($dischMass[$vsl]['total_time'] - $dischMass[$vsl]['stby_time']), 2);
            }
            if ($transfMass[$vsl]['cargo']) {
                $transfMass[$vsl]['gross'] =  round($transfMass[$vsl]['cargo'] / $transfMass[$vsl]['total_time'], 2);
                $transfMass[$vsl]['nett'] =  round($transfMass[$vsl]['cargo'] / ($transfMass[$vsl]['total_time'] - $transfMass[$vsl]['stby_time']), 2);
            }
        }


        //---------- CHART MASS---------------------
        $loadTotalMass = [];
        $loadGrossMass = [];
        $loadNettMass = [];
        $dischTotalMass = [];
        $dischGrossMass = [];
        $dischNettMass = [];
        $transfTotalMass = [];
        $transfGrossMass = [];
        $transfNettMass = [];
        foreach ($labelsMass as $vsl) {
            $loadTotalMass[] = $loadMass[$vsl]['cargo'];
            $loadGrossMass[] = $loadMass[$vsl]['gross'];
            $loadNettMass[] = $loadMass[$vsl]['nett'];
            $dischTotalMass[] = $dischMass[$vsl]['cargo'];
            $dischGrossMass[] = $dischMass[$vsl]['gross'];
            $dischNettMass[] = $dischMass[$vsl]['nett'];
            $transfTotalMass[] = $transfMass[$vsl]['cargo'];
            $transfGrossMass[] = $transfMass[$vsl]['gross'];
            $transfNettMass[] = $transfMass[$vsl]['nett'];
        }



        //---TITLE-------------------------------------------------
        $filter_title = "Data for $vsl_type";
        if ($vslNamesMass) {
            $filter_title .= ' (' . implode(',', $vslNamesMass) . ')';
        }
        $filter_title .= ' ' . $request->date_from . ' / ';
        $filter_title .= $request->date_to;

        return view('statistics.filter', [
            'filter_title' => $filter_title,
            'loc_list' => $loc_list,
            'activ_data' => $activ_data,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'selectedLoc' => $location,
            'selectedVslType' => $vsl_type,
            'selectedVslNames' => $vslNamesMass,
            'selectedOgvNames' => $ogvMass,
            'ogv_list' => $ogv_list,
            'vsl_list' => $vsl_list,
            'labelsMass' => $labelsMass,
            'loadTotalMass' => $loadTotalMass,
            'loadGrossMass' => $loadGrossMass,
            'loadNettMass' => $loadNettMass,
            'dischTotalMass' => $dischTotalMass,
            'dischGrossMass' => $dischGrossMass,
            'dischNettMass' => $dischNettMass,
            'transfTotalMass' => $transfTotalMass,
            'transfGrossMass' => $transfGrossMass,
            'transfNettMass' => $transfNettMass,
        ]);
    }
}
