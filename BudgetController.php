<?php

namespace App\Http\Controllers;

use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BudgetController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {

        $usrVslListRaw = DB::table('users')
            ->where('id', Auth::id())
            ->first('vessels_list');
        $usrVesselsMass = explode(',', $usrVslListRaw->vessels_list);


        $availableVessels = DB::table('vessels')
            ->whereIn('name', $usrVesselsMass)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'abbr', 'type']);

        //---MAKE NEW FULL MASS FOR VESSELS    
        $vesselsIdMass = [];
        $vslMass = [];
        foreach ($availableVessels as $item) {
            $vslMass[$item->id] = [
                'name' => $item->name,
                'abbr' => $item->abbr,
                'type' => $item->type,
                'forapprove' => 0,
                'approve' => 0,
                'decline' => 0,
                'unpaid' => 0,
            ];
        }

        //---COUNT INVOICES FOR AVAILABLE VESSELS
        $forApproveAllCnt = 0;
        foreach ($vslMass as $k => $v) {
            $unpaidCnt = 0;
            $forApproveCnt = DB::table('invoices')->where('vsl_id', '=', $k)->where('state', 'forapprove')->count();
            $approveCnt = DB::table('invoices')->where('vsl_id', '=', $k)->where('state', 'approve')->count();
            $declineCnt = DB::table('invoices')->where('vsl_id', '=', $k)->where('state', 'decline')->count();
            $forApproveAllCnt += $forApproveCnt;
            $vslMass[$k]['forapprove'] = $forApproveCnt;
            $vslMass[$k]['approve'] = $approveCnt;
            $vslMass[$k]['decline'] = $declineCnt;
            $vslMass[$k]['unpaid'] = $forApproveCnt + $approveCnt + $declineCnt;
        }


        return view('budget.start', [
            'vslMass' => $vslMass,
            'forApproveAllCnt' => $forApproveAllCnt,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ FOR APPROVE -------------------------------
    //--------------------------------------------------------------------
    public function forApprove($year)
    {
        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'abbr', 'imo', 'type']);
        $vslMass = [];
        foreach ($vsl_list as $item) {
            $vslMass[$item->id] = $item->name;
        }

        $invoicesForApprove = DB::table('invoices')
            ->where('year', $year)
            ->where('state', 'forapprove')
            ->orderBy('id', 'desc')
            ->get();

        $budgetCategoryMass = [];
        foreach ($invoicesForApprove as $item) {
            if (!in_array($item->category_code, $budgetCategoryMass)) {
                $budgetCategoryMass[] = $item->category_code;
            }
        }

        //---MAKE PRC MASS WITH SUMM FOR CATEGORIES
        $prcMass = [];
        $budget_list = DB::table('budgets')
            ->whereIn('category_code', $budgetCategoryMass)
            ->where('year', $year)
            ->get();
        foreach ($budget_list as $item) {
            if (!key_exists($item->vsl_id, $prcMass)) {
                $prcMass[$item->vsl_id][$item->category_code]['budget'] = $item->summ;
                $prcMass[$item->vsl_id][$item->category_code]['used'] = 0;
                $prcMass[$item->vsl_id][$item->category_code]['prc'] = 0;
            } else {
                $prcMass[$item->vsl_id][$item->category_code]['budget'] += $item->summ;
            }
        }

        //---ADD TO PRC MASS PAID INVOICES
        $paidInvoices = DB::table('invoices')
            ->whereIn('category_code', $budgetCategoryMass)
            ->where('year', $year)
            ->where('state', 'paid')
            ->get();
        foreach ($paidInvoices as $item) {
            $prcMass[$item->vsl_id][$item->category_code]['used'] += $item->summ;
        }
        //---CALC PRC OF USE
        foreach ($paidInvoices as $item) {
            $budget = $prcMass[$item->vsl_id][$item->category_code]['budget'];
            $used = $prcMass[$item->vsl_id][$item->category_code]['used'];
            $prcMass[$item->vsl_id][$item->category_code]['prc'] = round($used / $budget *  100);
        }
        // dd($prcMass);

        return view('budget.forapprove', [
            'year' => $year,
            'vslMass' => $vslMass,
            'invoices_list' => $invoicesForApprove,
            'prcMass' => $prcMass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ SHOW UNPAID ---------------------------------
    //--------------------------------------------------------------------
    public function showUnpaid($vsl_id, $vsl_name, $year)
    {
        $budgetSumm = DB::table('budgets')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->first('summ');

        $invoices_list = DB::table('invoices')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->orderBy('id', 'desc')
            ->get();

        return view('budget.unpaid', [
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'year' => $year,
            'summ' => $budgetSumm->summ ?? 0,
            'invoices_list' => $invoices_list,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ START VSL ---------------------------------
    //--------------------------------------------------------------------
    public function startVslSelected($vsl_id, $vsl_name, $year)
    {
        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'abbr', 'imo', 'type']);

        $budget_list = DB::table('budgets')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->orderBy('category_code', 'asc')
            ->get();

        $invoices_list = DB::table('invoices')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->orderBy('id', 'desc')
            ->get();

        return view('budget.start_vsl', [
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'year' => $year,
            'vsl_list' => $vsl_list,
            'budget_list' => $budget_list,
            'invoices_list' => $invoices_list,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ SHOW DESC ---------------------------------
    //--------------------------------------------------------------------
    public function showDescription($vsl_id, $vsl_name, $year, $code, $desc)
    {
        $budgetSumm = DB::table('budgets')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->where('category_code', $code)
            ->first('summ');

        $invoices_list = DB::table('invoices')
            ->where('vsl_id', $vsl_id)
            ->where('year', $year)
            ->where('category_code', $code)
            ->orderBy('id', 'desc')
            ->get();

        return view('budget.show_description', [
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'year' => $year,
            'code' => $code,
            'desc' => $desc,
            'summ' => $budgetSumm->summ ?? 0,
            'invoices_list' => $invoices_list,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ INVOICE ADD ------------------------------
    //--------------------------------------------------------------------
    public function invoiceAdd(Request $request)
    {
        $valid = $request->validate([
            'invoice_date' => 'required',
            'issued_by' => 'required',
            'summ' => 'required',
        ]);

        $abbrRaw = DB::table('vessels')
            ->where('id', $request->vsl_id)
            ->first(['abbr']);

        $abbr = strtoupper($abbrRaw->abbr);
        $date = $request->invoice_date;
        $code = $request->category_code;
        $desc = $request->category_desc;
        $supplier = $request->issued_by;
        $summ = $request->summ;
        $summ_orig = $request->summ_orig;
        $curr_orig = $request->curr_orig;

        $vslCnt = count($request->vsl_id);
        $summ = $vslCnt > 1 ? $summ / $vslCnt : $summ;
        $summ_orig = $vslCnt > 1 ? $summ_orig / $vslCnt : $summ_orig;

        $filePath = null;
        if ($request->att_file) {
            $fileExt = $request->att_file->extension();
            $fileName = "LTE-INV-$date-$abbr-$code-$desc-$supplier-$summ$($summ_orig-$curr_orig).$fileExt";
            $path = Storage::putFileAs("public/files/invoices", $request->file('att_file'), $fileName);
            $filePath = Storage::url($path);
        }

        foreach ($request->vsl_id as $vsl_id) {
            DB::table('invoices')
                ->insert([
                    'vsl_id' => $vsl_id,
                    'invoice_date' => $date,
                    'year' => substr($date, 0, 4),
                    'category_code' => $code,
                    'category_desc' => $desc,
                    'summ' => round($summ, 2),
                    'summ_orig' => round($summ_orig, 2),
                    'curr_orig' => $curr_orig,
                    'issued_by' => $request->issued_by,
                    'created_by' => Auth::user()->name,
                    'state' => 'forapprove',
                    'state_by_whom' => Auth::user()->name,
                    'att_file' => $filePath,
                ]);
        }

        return redirect()
            ->route('budget_start')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ INVOICE STATE -----------------------------
    //--------------------------------------------------------------------
    public function invoiceState(Request $request)
    {
        //---DELETE
        if ($request->state == 'delete') {
            if ($request->att_file) {
                //---delete old file
                $oldFilePath = str_replace('storage', 'public', $request->att_file);
                Storage::delete($oldFilePath);
            }
            DB::table('invoices')
                ->where('id', $request->rec_id)
                ->delete();
            return redirect()
                ->route('budget_desc', [
                    'vsl_id' => $request->vsl_id,
                    'vsl_name' => $request->vsl_name,
                    'year' => $request->year,
                    'code' => $request->code,
                    'desc' => $request->desc,
                ])
                ->with('status_msg', 'Invoice deleted successfully!');
        }

        //---SET STATE FROM APPROVED PAGE
        DB::table('invoices')
            ->where('id', $request->rec_id)
            ->update([
                'state' => $request->state,
                'state_by_whom' => Auth::user()->name,
                'state_comment' => $request->state_comment,
            ]);

        $msg = 'State of invoice changed successfully!';

        //---SET STATE FROM DESC
        if ($request->state_set_from == 'show_description') {
            return redirect()
                ->route('budget_desc', [
                    'vsl_id' => $request->vsl_id,
                    'vsl_name' => $request->vsl_name,
                    'year' => $request->year,
                    'code' => $request->code,
                    'desc' => $request->desc,
                ])
                ->with('status_msg', $msg);
        }

        //---SET STATE FROM FORAPPROVE
        if ($request->state_set_from == 'forapprove') {
            return redirect()
                ->route('budget_forapprove', ['year' => $request->year])
                ->with('status_msg', $msg);
        }

        //---SET STATE FROM UNPAID
        if ($request->state_set_from == 'unpaid') {
            $budgetSumm = DB::table('budgets')
                ->where('vsl_id', $request->vsl_id)
                ->where('year', $request->year)
                ->first('summ');

            $invoices_list = DB::table('invoices')
                ->where('vsl_id', $request->vsl_id)
                ->where('year', $request->year)
                ->orderBy('id', 'desc')
                ->get();


            return redirect()
                ->route('budget_unpaid', [
                    'vsl_id' => $request->vsl_id,
                    'vsl_name' => $request->vsl_name,
                    'year' => $request->year,
                    'summ' => $budgetSumm->summ ?? 0,
                    'invoices_list' => $invoices_list,
                ])
                ->with('status_msg', $msg);
        }
    }


    //--------------------------------------------------------------------
    //------------------------ INVOICE EDIT ------------------------------
    //--------------------------------------------------------------------
    public function invoiceEdit(Request $request)
    {
        $abbrRaw = DB::table('vessels')
            ->where('id', $request->vsl_id)
            ->first(['abbr']);

        $abbr = strtoupper($abbrRaw->abbr);
        $date = $request->invoice_date;
        $code = $request->category_code;
        $desc = $request->category_desc;
        $supplier = $request->issued_by;
        $summ = $request->summ;
        $summ_orig = $request->summ_orig;
        $curr_orig = $request->curr_orig;

        //---UPLOAD NEW FILE-----------------------------
        if ($request->att_file) {
            //---delete old file
            $oldFilePath = str_replace('storage', 'public', $request->old_att_file);
            Storage::delete($oldFilePath);
            //---upload new file
            $fileExt = $request->att_file->extension();
            $fileName = "LTE-INV-$date-$abbr-$code-$desc-$supplier-$summ$($summ_orig-$curr_orig).$fileExt";
            $path = Storage::putFileAs("public/files/invoices", $request->file('att_file'), $fileName);
            $filePath = Storage::url($path);
        }
        //---NO NEW & RENAME OLD FILE--------------------
        if (!$request->att_file && $request->old_att_file) {
            $filePath = $request->old_att_file;
            $oldPath = str_replace('/storage', '/public', $request->old_att_file);
            $fileExt = str_replace('.', '', substr($oldPath, -4));
            $newName = "LTE-INV-$date-$abbr-$code-$desc-$supplier-$summ$($summ_orig-$curr_orig).$fileExt";
            $newPath = "/public/files/invoices/$newName";
            Storage::move($oldPath, $newPath); //rename file
            $filePath = str_replace('/storage//public', '/storage', Storage::url($newPath));
        }
        //---NO FILES-----------------------------
        if (!$request->att_file && !$request->old_att_file) {
            $filePath = null;
        }

        DB::table('invoices')
            ->where('id', $request->rec_id)
            ->update([
                'invoice_date' => $date,
                'category_code' => $code,
                'category_desc' => $desc,
                'summ' => $summ,
                'summ_orig' => $summ_orig,
                'curr_orig' => $curr_orig,
                'issued_by' => $request->issued_by,
                'state_comment' => $request->state_comment,
                'att_file' => $filePath,
            ]);

        return redirect()
            ->route('budget_desc', [
                'vsl_id' => $request->vsl_id,
                'vsl_name' => $request->vsl_name,
                'year' => $request->year,
                'code' => $request->code,
                'desc' => $request->desc,
            ])
            ->with('status_msg', 'Invoice changed successfully!');
    }



    //--------------------------------------------------------------------
    //------------------------ SET LIMIT ---------------------------------
    //--------------------------------------------------------------------
    public function budgetLimitSet(Request $request)
    {
        $valid = $request->validate([
            'summ' => 'required|numeric',
        ]);

        DB::table('budgets')
            ->where('vsl_id', $request->vsl_id)
            ->where('year', $request->year)
            ->where('category_code', $request->category_code)
            ->delete();

        DB::table('budgets')
            ->insert([
                'vsl_id' => $request->vsl_id,
                'year' => $request->year,
                'category_code' => $request->category_code,
                'category_desc' => $request->category_desc,
                'summ' => $request->summ,
            ]);

        return redirect()
            ->route('budget_vsl', [
                'vsl_id' => $request->vsl_id,
                'vsl_name' => $request->vsl_name,
                'year' => $request->year,
            ])
            ->with('status_msg', 'Data saved successfully!');
    }
}
