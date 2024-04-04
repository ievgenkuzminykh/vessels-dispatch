<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanningController extends Controller
{

    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start($location, $year)
    {
        $startDate = "$year-01-01 00:00";
        $endDate = "$year-12-31 23:00";

        $planList = DB::table('plannings')
            ->where('location', $location)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('vsl_name', 'asc')
            ->get();

        $vesselsList = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['name', 'type', 'imo']);

        $ogvList = DB::table('ogvs')
            ->where('location', $location)
            ->where('state', 'active')
            ->whereBetween('planned_eta', [$startDate, $endDate])
            ->orderBy('name', 'asc')
            ->get(['name', 'imo', 'planned_eta', 'planned_etd']);

        return view('planning.start', [
            'selectedLocation' => $location,
            'selectedYear' => $year,
            'vesselsList' => $vesselsList,
            'ogvList' => $ogvList,
            'planList' => $planList,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ EDIT PLANNING -----------------------------
    //--------------------------------------------------------------------
    public function editPlanning(Request $request)
    {
        $valid = $request->validate([
            'vsl_name' => 'required',
            'activity_name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        //---ADD NEW
        if ($request->edit_mode == 'new') {
            DB::table('plannings')
                ->insert([
                    'vsl_name' => $request->vsl_name,
                    'location' => $request->location,
                    'activity_name' => $request->activity_name,
                    'start_date' => $request->start_date . ' ' . $request->start_time . ':00',
                    'end_date' => $request->end_date . ' ' . $request->end_time . ':00',
                ]);
            $msg = 'Data saved successfully!';
        }

        //---EDIT
        if ($request->edit_mode == 'edit') {
            DB::table('plannings')
                ->where('id', $request->rec_id)
                ->update([
                    'vsl_name' => $request->vsl_name,
                    'location' => $request->location,
                    'activity_name' => $request->activity_name,
                    'start_date' => $request->start_date . ' ' . $request->start_time . ':00',
                    'end_date' => $request->end_date . ' ' . $request->end_time . ':00',
                ]);
            $msg = 'Record changed successfully!';
        }

        //---DELETE
        if ($request->edit_mode == 'delete') {
            DB::table('plannings')
                ->where('id', $request->rec_id)
                ->delete();
            $msg = 'Record deleted successfully!';
        }


        return redirect("/plan-start/$request->location/$request->year")
            ->with('status_msg', $msg);
    }

    //--------------------------------------------------------------------
    //------------------------ ADD REPEAT -----------------------------
    //--------------------------------------------------------------------
    public function addRepeat(Request $request)
    {
        $valid = $request->validate([
            'vsl_name' => 'required',
            'activity_name' => 'required',
            'start_date' => 'required',
        ]);

        $startMass = explode(' ', $request->start_date);
        $startDateMass = explode('-', $startMass[0]);
        $startY = $startDateMass[0];
        $startM = $startDateMass[1];
        $startD = $startDateMass[2];
        $insertMass = [];

        //---24 hrs------------------------------
        if ($request->activity_duration == 24) {
            $start_time = $request->start_time . ':00';
            $end_time = $start_time;
            for ($m = 1; $m < 13; $m++) {
                if ($m < $startM) continue;
                $maxDayInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $startY);
                if ($startD > $maxDayInMonth) {
                    $m2sign = $m < 10 ? '0' . $m : $m;
                    $startDate = "$startY-$m2sign-$maxDayInMonth $start_time";
                    $endD = '01';
                    $endM = $m + 1;
                    $endM = $endM < 10 ? '0' . $endM : $endM;
                    $endDate = "$startY-$endM-$endD $end_time";
                } else {
                    $m2sign = $m < 10 ? '0' . $m : $m;
                    $startDate = "$startY-$m2sign-$startD $start_time";
                    $endD = $startD + 1;
                    $endD2sign = $endD < 10 ? '0' . $endD : $endD;
                    $endDate = "$startY-$m2sign-$endD2sign $end_time";
                    if ($endD > $maxDayInMonth) {
                        $endD = '01';
                        $endM = $m + 1;
                        if ($endM > 12) break;
                        $endM = $endM < 10 ? '0' . $endM : $endM;
                        $endDate = "$startY-$endM-$endD $end_time";
                    }
                }
                $insertMass[] = ['start_date' => $startDate, 'end_date' => $endDate];
            }
        }

        //---36 hrs------------------------------
        if ($request->activity_duration == 36) {
            $start_time = $request->start_time . ':00';
            $endHour = $request->start_time + 12;
            for ($m = 1; $m < 13; $m++) {
                if ($m < $startM) continue;
                $maxDayInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $startY);
                $m2sign = $m < 10 ? '0' . $m : $m;
                $startDate = "$startY-$m2sign-$startD $start_time";
                //--- START DAY BIGGER THAN MAX DAY IN MONTH
                if ($startD > $maxDayInMonth) {
                    $startMonthNext = $m + 1;
                    if ($startMonthNext > 12) break;
                    $startMonthNext2sign = $startMonthNext < 10 ? '0' . $startMonthNext : $startMonthNext;
                    $startDate = "$startY-$startMonthNext2sign-$maxDayInMonth $start_time";
                    $endDayNext = '01';
                    $endDate = "$startY-$startMonthNext2sign-$endDayNext $endHour:00";
                } else {
                    $startDate = "$startY-$m2sign-$startD $start_time";
                    $endDayNext = $startD + 1;
                    $endDayNext2sign = $endDayNext < 10 ? '0' . $endDayNext : $endDayNext;
                    $endDate = "$startY-$m2sign-$endDayNext2sign $endHour:00";
                    if ($endDayNext > $maxDayInMonth) {
                        $endDayNext = '01';
                        $startMonthNext = $m + 1;
                        if ($startMonthNext > 12) break;
                        $startMonthNext2sign = $startMonthNext < 10 ? '0' . $startMonthNext : $startMonthNext;
                        $endDate = "$startY-$startMonthNext2sign-$endDayNext $endHour:00";
                    }
                }
                $insertMass[] = ['start_date' => $startDate, 'end_date' => $endDate];
            }
        }

        //---48 hrs------------------------------
        if ($request->activity_duration == 48) {
            $start_time = $request->start_time . ':00';
            $end_time = $start_time;
            for ($m = 1; $m < 13; $m++) {
                if ($m < $startM) continue;
                $maxDayInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $startY);
                if ($startD > $maxDayInMonth) {
                    $m2sign = $m < 10 ? '0' . $m : $m;
                    $startDate = "$startY-$m2sign-$maxDayInMonth $start_time";
                    $endD = '01';
                    $endM = $m + 1;
                    $endM = $endM < 10 ? '0' . $endM : $endM;
                    $endDate = "$startY-$endM-$endD $end_time";
                } else {
                    $m2sign = $m < 10 ? '0' . $m : $m;
                    $startDate = "$startY-$m2sign-$startD $start_time";
                    $endD = $startD + 2;
                    $endD2sign = $endD < 10 ? '0' . $endD : $endD;
                    $endDate = "$startY-$m2sign-$endD2sign $end_time";
                    if ($endD > $maxDayInMonth) {
                        $endD = '02';
                        $endM = $m + 1;
                        if ($endM > 12) break;
                        $endM = $endM < 10 ? '0' . $endM : $endM;
                        $endDate = "$startY-$endM-$endD $end_time";
                    }
                }
                $insertMass[] = ['start_date' => $startDate, 'end_date' => $endDate];
            }
        }


        foreach ($insertMass as $item) {
            DB::table('plannings')
                ->insert([
                    'vsl_name' => $request->vsl_name,
                    'location' => $request->location,
                    'activity_name' => $request->activity_name,
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                ]);
        }

        return redirect("/plan-start/$request->location/$request->year")
            ->with('status_msg', 'Data saved successfully!');
    }



    //--------------------------------- SEQUENCES ----------------------------------------------

    //--------------------------------------------------------------------
    //------------------------ ADD SEQ -----------------------------
    //--------------------------------------------------------------------
    public function addSeq(Request $request)
    {
        $valid = $request->validate([
            'vsl_name' => 'required',
            'start_date' => 'required',
        ]);

        $seqList = json_decode($request->activ_seq);
        $startMass = explode(' ', $request->start_date);
        $startDateMass = explode('-', $startMass[0]);
        $year = $startDateMass[0];
        $startM = $startDateMass[1];
        $startD = $startDateMass[2];

        //---FILL START + END DATES VIA DURATION
        foreach ($seqList as $k => $val) {
            if ($k == 0) {
                //---FIRST IN LIST
                $seqList[$k]->start = $request->start_date . " " . $request->start_time . ":00";
                $seqList[$k]->end = $this->calcEndDate([
                    'year' => $year,
                    'start_d' => $startD,
                    'start_m' => $startM,
                    'start_h' => $request->start_time,
                    'dur' => $this->correctDur($val->dur),
                ]);
            } else {
                //---NEXT ACTIVITIES
                $seqList[$k]->start = $seqList[$k - 1]->end;
                $curr_startMass = explode(' ', $seqList[$k]->start);
                $curr_startDateMass = explode('-', $curr_startMass[0]);
                $curr_startM = $curr_startDateMass[1];
                $curr_startD = $curr_startDateMass[2];
                $curr_startH = substr($curr_startMass[1], 0, 2);
                $seqList[$k]->end = $this->calcEndDate([
                    'year' => $year,
                    'start_d' => $curr_startD,
                    'start_m' => $curr_startM,
                    'start_h' => $curr_startH,
                    'dur' => $this->correctDur($val->dur),
                ]);
            }
        }


        $seqListNew = [];
        //--- REPEAT SEQUENCE
        for ($i = 0; $i < $request->repeat_times; $i++) {
            foreach ($seqList as $k => $act) {
                if ($i == 0 && $k == 0) {
                    $lastDate = $request->start_date . " " . $request->start_time . ":00";
                    $endMass = explode(' ', $lastDate);
                    $endDateMass = explode('-', $endMass[0]);
                    $startM = $endDateMass[1];
                    $startD = $endDateMass[2];
                    $startH = substr($endMass[1], 0, 2);
                    $seqListNew[] = (object)[
                        'activ' => $act->activ,
                        'start' => $lastDate,
                        'end' => $this->calcEndDate([
                            'year' => $year,
                            'start_d' => $startD,
                            'start_m' => $startM,
                            'start_h' => $startH,
                            'dur' => $act->dur,
                        ]),
                        'dur' => $act->dur,
                    ];
                } else {
                    $lastDate = $seqListNew[count($seqListNew) - 1]->end;
                    $endMass = explode(' ', $lastDate);
                    $endDateMass = explode('-', $endMass[0]);
                    $startM = $endDateMass[1];
                    $startD = $endDateMass[2];
                    $startH = substr($endMass[1], 0, 2);
                    $seqListNew[] = (object)[
                        'activ' => $act->activ,
                        'start' => $lastDate,
                        'end' => $this->calcEndDate([
                            'year' => $year,
                            'start_d' => $startD,
                            'start_m' => $startM,
                            'start_h' => $startH,
                            'dur' => $act->dur,
                        ]),
                        'dur' => $act->dur,
                    ];
                }
            }
        }



        foreach ($seqListNew as $item) {
            DB::table('plannings')
                ->insert([
                    'vsl_name' => $request->vsl_name,
                    'location' => $request->location,
                    'activity_name' => $item->activ,
                    'start_date' => $item->start,
                    'end_date' => $item->end,
                ]);
        }

        return redirect("/plan-start/$request->location/$request->year")
            ->with('status_msg', 'Data saved successfully!');
    }


    //----------- CALC END DATE ------------------------------------
    private function calcEndDate($mass)
    {
        $y = $mass['year'];
        $start_h = $mass['start_h'];
        $start_d = $mass['start_d'];
        $start_m = $mass['start_m'];
        $dur = $mass['dur'];

        $end_d = $start_d;
        $end_m = $start_m;
        $end_h = $start_h + $dur;

        if ($end_h >= 24) {
            $end_d = $start_d + 1;
            $corr = $this->correctDayMonth($end_d, $end_m, $y);
            $end_d = $corr[0];
            $end_m = $corr[1];
            $end_h = $end_h - 24;

            if ($end_h >= 24) {
                $end_d++;
                $corr = $this->correctDayMonth($end_d, $end_m, $y);
                $end_d = $corr[0];
                $end_m = $corr[1];
                $end_h = $end_h - 24;

                if ($end_h >= 24) {
                    $end_d++;
                    $corr = $this->correctDayMonth($end_d, $end_m, $y);
                    $end_d = $corr[0];
                    $end_m = $corr[1];
                    $end_h = $end_h - 24;

                    if ($end_h >= 24) {
                        $end_d++;
                        $corr = $this->correctDayMonth($end_d, $end_m, $y);
                        $end_d = $corr[0];
                        $end_m = $corr[1];
                        $end_h = $end_h - 24;
                    }
                }
            }
        }

        $end_h2sign = $end_h < 10 ? '0' . $end_h : $end_h;
        return "$y-$end_m-$end_d $end_h2sign:00";
    }

    //---------------- CORRECT DAY IN MONTH ------------------------
    private function correctDayMonth($d, $m, $y)
    {
        $maxDayInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $y);
        if ($d > $maxDayInMonth) {
            $corr_d = '01';
            $m = $m + 1;
            $corr_m = $m < 10 ? '0' . $m : $m;
        } else {
            $corr_d = $d;
            $corr_m = $m;
        }
        return [$corr_d, $corr_m];
    }

    private function correctDur($dur)
    {
        if ((int)$dur > 96) $dur = 96;
        return $dur;
    }
}
