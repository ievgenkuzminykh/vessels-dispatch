<?php

namespace App\Http\Controllers;

use App\Models\CertificateName;
use App\Models\Vessel;
use App\Models\VesselCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psy\Command\WhereamiCommand;

class CertificateController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $vsl_list = DB::table('vessels')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'abbr', 'imo', 'type']);

        $expiredCert = DB::table('vessel_certificates')
            ->whereNotNull('exp_date')
            ->where('exp_date', '<', date('Y-m-d'))
            ->get('vsl_id');

        $expCertMass = [];
        foreach ($expiredCert as $cert) {
            if (!in_array($cert->vsl_id, $expCertMass)) {
                $expCertMass[] = $cert->vsl_id;
            }
        }

        return view('certificates.start', [
            'vsl_list' => $vsl_list,
            'expCertMass' => $expCertMass,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ CERT TYPES START --------------------------
    //--------------------------------------------------------------------
    public function certTypesStart()
    {
        $cert_types = DB::table('cert_types')
            ->orderBy('group_number', 'asc')
            ->get();

        $cert_groups = DB::table('cert_groups')
            ->orderBy('group_number', 'asc')
            ->get();

        return view('certificates.types_start', [
            'cert_groups' => $cert_groups,
            'cert_types' => $cert_types,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ ADD GROUP ---------------------------------
    //--------------------------------------------------------------------
    public function certGroupAdd(Request $request)
    {
        $valid = $request->validate([
            'group_number' => 'required|numeric',
            'group_name' => 'required',
        ]);
        if ($request->group_edit_mode == 'new') {
            DB::table('cert_groups')
                ->insert([
                    'group_number' => $request->group_number,
                    'group_name' => strtolower($request->group_name),
                ]);
        }
        if ($request->group_edit_mode == 'edit') {
            DB::table('cert_groups')
                ->where('id', $request->rec_id)
                ->update([
                    'group_number' => $request->group_number,
                    'group_name' => strtolower($request->group_name),
                ]);
        }
        return redirect()
            ->route('cert_types_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE GROUP ---------------------------
    //--------------------------------------------------------------------
    public function deleteCertGroup(Request $request, $id)
    {
        DB::table('cert_groups')
            ->where('id', $id)
            ->delete();
        return redirect()
            ->route('cert_types_start')
            ->with('status_msg', 'Data deleted successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ ADD TYPE ---------------------------------
    //--------------------------------------------------------------------
    public function certTypeAdd(Request $request)
    {
        // dd($request);
        $vslDbMass = DB::table('vessels')->get(['id', 'type']);
        $valid = $request->validate([
            'cert_group_number' => 'required|numeric',
            'cert_group_name' => 'required',
            'cert_number' => 'required',
            'cert_name' => 'required',
            'vsl_types' => 'required',
        ]);

        $selectedVslTypes = implode(',', $request->vsl_types);

        //---SAVE NEW CERTIFICATE------------------------------------------------
        if ($request->cert_edit_mode == 'new') {
            //delete old cert if exist with same group/name & numbers
            DB::table('cert_types')
                ->where('cert_number', $request->cert_number)
                ->where('cert_name', strtolower($request->cert_name))
                ->where('group_number', $request->cert_group_number)
                ->where('group_name', $request->cert_group_name)
                ->delete();
            //save new cert
            $newCertId = DB::table('cert_types')
                ->insertGetId([
                    'group_number' => $request->cert_group_number,
                    'group_name' => $request->cert_group_name,
                    'cert_number' => $request->cert_number,
                    'cert_name' => strtolower($request->cert_name),
                    'ref' => $request->ref,
                    'vsl_types' => $selectedVslTypes,
                ]);
            //insert for each vessel
            foreach ($vslDbMass as $vslDb) {
                if (in_array($vslDb->type, $request->vsl_types)) {
                    //get old certificates if exist
                    $oldCert = DB::table('vessel_certificates')
                        ->where('vsl_id', $vslDb->id)
                        ->where('cert_number', $request->cert_number)
                        ->where('cert_name', strtolower($request->cert_name))
                        ->where('group_number', $request->cert_group_number)
                        ->where('group_name', $request->cert_group_name)
                        ->get(['id', 'att_file']);
                    //delete att + db record for vessel
                    foreach ($oldCert  as $cert) {
                        VesselCertificate::find($cert->id)->delete();
                        if ($cert->att_file) {
                            $filePath = str_replace('storage', 'public', $cert->att_file);
                            Storage::delete($filePath);
                        }
                    }
                    //add to vsl DB
                    DB::table('vessel_certificates')
                        ->insert([
                            'cert_id' => $newCertId,
                            'cert_number' => $request->cert_number,
                            'cert_name' => strtolower($request->cert_name),
                            'group_number' => $request->cert_group_number,
                            'group_name' => $request->cert_group_name,
                            'ref' => $request->ref,
                            'vsl_id' => $vslDb->id,
                            'vsl_type' => $vslDb->type,
                        ]);
                }
            }
        }

        //---UPDATE CERTIFICATE------------------------------------------
        if ($request->cert_edit_mode == 'edit') {
            //update type of certificate
            DB::table('cert_types')
                ->where('id', $request->cert_id)
                ->update([
                    'group_number' => $request->cert_group_number,
                    'group_name' => $request->cert_group_name,
                    'cert_number' => $request->cert_number,
                    'cert_name' => strtolower($request->cert_name),
                    'ref' => $request->ref,
                    'vsl_types' => $selectedVslTypes,
                ]);
            //update for each vessel
            DB::table('vessel_certificates')
                ->where('cert_id', $request->cert_id)
                ->update([
                    'group_number' => $request->cert_group_number,
                    'group_name' => $request->cert_group_name,
                    'cert_number' => $request->cert_number,
                    'cert_name' => strtolower($request->cert_name),
                    'ref' => $request->ref,
                ]);
            //delete attachment for non-in-vessel list
            $certAttMass = DB::table('vessel_certificates')
                ->whereNotIn('vsl_type', $request->vsl_types)
                ->where('cert_id', $request->cert_id)
                ->get('att_file');
            foreach ($certAttMass as $att) {
                if ($att->att_file) {
                    //---DELETE OLD FILE
                    $oldFilePath = str_replace('storage', 'public', $att->att_file);
                    Storage::delete($oldFilePath);
                }
            }
            //delete cert for non-in-vessel list
            DB::table('vessel_certificates')
                ->whereNotIn('vsl_type', $request->vsl_types)
                ->where('cert_id', $request->cert_id)
                ->delete();
        }
        return redirect()
            ->route('cert_types_start')
            ->with('status_msg', 'Data saved successfully!');
    }

    //--------------------------------------------------------------------
    //------------------------ DELETE TYPE -------------------------------
    //--------------------------------------------------------------------
    public function deleteCertType(Request $request, $id)
    {
        $certAttMass = DB::table('vessel_certificates')
            ->where('cert_id', $id)
            ->get('att_file');

        foreach ($certAttMass as $att) {
            if ($att->att_file) {
                //---DELETE OLD FILE
                $oldFilePath = str_replace('storage', 'public', $att->att_file);
                Storage::delete($oldFilePath);
            }
        }

        DB::table('vessel_certificates')
            ->where('cert_id', $id)
            ->delete();

        DB::table('cert_types')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('cert_types_start')
            ->with('status_msg', 'Data deleted successfully!');
    }







    //********************************************************************************************************** */
    //********************************************************************************************************** */
    //********************************************************************************************************** */
    //--------------------------------------------------------------------
    //------------------------ CERT VSL START ----------------------------
    //--------------------------------------------------------------------
    public function certVslStart($vsl_id)
    {
        $vsl_data = DB::table('vessels')
            ->where('id', $vsl_id)
            ->first();

        $vessel_certificates = DB::table('vessel_certificates')
            ->where('vsl_id', $vsl_id)
            ->orderBy('group_number', 'asc')
            ->orderBy('cert_number', 'asc')
            ->get();

        return view('certificates.vsl_start', [
            'vsl_data' => $vsl_data,
            'vessel_certificates' => $vessel_certificates,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ VESSEL CERT ADD ---------------------------
    //--------------------------------------------------------------------
    public function certVslAdd(Request $request)
    {
        $valid = $request->validate([
            'vsl_id' => 'required',
            'issue_date' => 'required',
        ]);

        //---GET OLD FILE --------------------------------------
        $oldAdvData = DB::table('vessel_certificates')
            ->where('id', $request->cert_id)
            ->first('att_file');
        $oldImg = $oldAdvData->att_file;

        if ($request->att_file) {
            //---DELETE OLD FILE
            $oldFilePath = str_replace('storage', 'public', $oldImg);
            Storage::delete($oldFilePath);
            $fileExt = $request->att_file->extension();
            $vsl = strtoupper($request->vsl_name);
            $cert = strtoupper($request->cert_name);
            $fileName = "LTE-$vsl-$cert.$fileExt";
            $fileNameCorrected = str_replace(' ', '_', $fileName);
            $path = Storage::putFileAs("public/files/cert", $request->file('att_file'), $fileNameCorrected);
            $newImg = Storage::url($path);  
        }else{
            if ($request->att_action == "delete") {
                //---DELETE OLD FILE
                $oldFilePath = str_replace('storage', 'public', $oldImg);
                Storage::delete($oldFilePath);
                $newImg = null;
            }
            if ($request->att_action == "notSet") {
                $newImg = $oldImg;
            }
        }

        DB::table('vessel_certificates')
            ->where('id', $request->cert_id)
            ->update([
                'issue_date' => $request->issue_date,
                'exp_date' => $request->exp_date,
                'next_date' => $request->next_date,
                'remarks' => $request->remarks,
                'att_file' => $newImg,
            ]);

        return redirect()
            ->route('cert_vsl_start', ['vsl_id' => $request->vsl_id])
            ->with('status_msg', 'Data saved successfully!');
    }
}
