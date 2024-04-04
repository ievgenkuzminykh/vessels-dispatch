<?php

namespace App\Http\Controllers;

use App\Models\Qms;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psy\Command\WhereamiCommand;
use ZipArchive;

class QmsController extends Controller


{
    //--------------------------------------------------------------------
    //------------------------ QMS DOCS ---------------------------------
    //--------------------------------------------------------------------
    public function qmsDocs()
    {
        $qmsList = DB::table('qms')
            ->get();

        $filesList = DB::table('qms')
            ->where('item_type', 'file')
            ->orderBy('doc_type')
            ->orderBy('id')
            ->get();

        $folders_4folders = [];
        $allFolders = [];

        foreach ($qmsList as $qms) {
            if ($qms->item_type != 'folder') continue;

            //---ROOT
            if (strlen($qms->index_number) <= 4) {
                $folders_4folders[] = $qms->index_number;
                $allFolders[] = $qms->index_number;
            } else {
                $allFolders[] = $qms->index_number;
            }
        }

        return view('qms.qms_docs', [
            'qmsList' => $qmsList,
            'folders_4folders' => $folders_4folders,
            'allFolders' => $allFolders,
            'filesList' => $filesList,
        ]);
    }

    //--------------------------------------------------------------------
    //------------------------ EDIT FOLDER -------------------------------
    //--------------------------------------------------------------------
    public function editFolder(Request $request)
    {
        $valid = $request->validate([
            'item_name' => 'required',
        ]);

        //---ADD NEW-------------------------
        if ($request->edit_mode == 'new') {
            $lastNumber = DB::table('qms')
                ->where('item_type', 'folder')
                ->where('sub_for', $request->sub_for)
                ->max('item_number');
            $newNumber = $lastNumber ? $lastNumber + 1 : 1;
            $indexNumber = $request->sub_for ? $request->sub_for . '.' . $newNumber : $newNumber;
            DB::table('qms')
                ->insert([
                    'item_type' => 'folder',
                    'item_name' => $request->item_name,
                    'item_number' => $newNumber,
                    'sub_for' => $request->sub_for,
                    'index_number' => $indexNumber,
                ]);
            $msg = 'New folder created successfully!';
        }

        //---EDIT-----------------------------
        if ($request->edit_mode == 'edit') {
            DB::table('qms')
                ->where('id', $request->rec_id)
                ->update([
                    'item_name' => $request->item_name,
                ]);
            $msg = 'Folder updated successfully!';
        }

        //---DELETE----------------------------
        if ($request->edit_mode == 'delete') {

            $recordsDeleteMass = [$request->rec_id];
            $filesDeleteMass = [];

            $rootRecord = DB::table('qms')
                ->where('id', $request->rec_id)
                ->first();

            //---create sub-1 mass
            $sub1Mass = DB::table('qms')
                ->where('sub_for', $rootRecord->index_number)
                ->get();
            //---collect from sub-1
            $sub1IndexMass = [];
            foreach ($sub1Mass as $sub1) {
                $sub1IndexMass[] = $sub1->index_number;
                $recordsDeleteMass[] = $sub1->id;
                if ($sub1->att_file) {
                    $filesDeleteMass[] = $sub1->att_file;
                }
            }

            //---create sub-2
            $sub2Mass = DB::table('qms')
                ->whereIn('sub_for', $sub1IndexMass)
                ->get();
            //---collect from sub-2
            $sub2IndexMass = [];
            foreach ($sub2Mass as $sub2) {
                $sub2IndexMass[] = $sub2->index_number;
                $recordsDeleteMass[] = $sub2->id;
                if ($sub2->att_file) {
                    $filesDeleteMass[] = $sub2->att_file;
                }
            }

            //---create sub-3
            $sub3Mass = DB::table('qms')
                ->whereIn('sub_for', $sub2IndexMass)
                ->get();
            //---collect from sub-3
            foreach ($sub3Mass as $sub3) {
                $recordsDeleteMass[] = $sub3->id;
                if ($sub3->att_file) {
                    $filesDeleteMass[] = $sub3->att_file;
                }
            }

            //---delete all related DB records
            DB::table('qms')
                ->whereIn('id', $recordsDeleteMass)
                ->delete();

            //---delete files
            foreach ($filesDeleteMass as $item) {
                $filePath = str_replace('storage', 'public', $item);
                Storage::delete($filePath);
            }

            $msg = 'Folder deleted successfully!';
        }


        return redirect()
            ->route('qms_docs')
            ->with('status_msg', $msg);
    }


    //--------------------------------------------------------------------
    //------------------------ EDIT FILE ---------------------------------
    //--------------------------------------------------------------------
    public function editFile(Request $request)
    {
        $valid = $request->validate([
            'item_name' => 'required',
        ]);

        //---ADD NEW-------------------------
        if ($request->edit_mode == 'new') {
            $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
            $filePath = null;
            if ($request->att_file) {
                $fileExt = $request->att_file->extension();
                $fileName = "LTE-$request->doc_type-$indexNumber-Ver.$request->ver-Rev.$request->rev $request->item_name.$fileExt";
                $path = Storage::putFileAs("public/files/qms", $request->file('att_file'), $fileName);
                $filePath = Storage::url($path);
            }
            DB::table('qms')
                ->insert([
                    'item_type' => 'file',
                    'item_name' => $request->item_name,
                    'item_number' => $request->item_number,
                    'sub_for' => $request->sub_for,
                    'index_number' => $indexNumber,
                    'date_created' => $request->date_created,
                    'rev_date' => $request->rev_date,
                    'doc_type' => $request->doc_type,
                    'ver' => $request->ver,
                    'rev' => $request->rev,
                    'period' => $request->period,
                    'send_to_office' => $request->send_to_office,
                    'format' => $request->format,
                    'att_file' => $filePath,
                ]);
            $msg = 'File uploaded successfully!';
        }

        //---EDIT-----------------------------
        if ($request->edit_mode == 'edit') {
            if ($request->att_file) {
                //---delete old file
                $oldFilePath = str_replace('storage', 'public', $request->old_att_file);
                Storage::delete($oldFilePath);
                //---upload new file
                $lastNumber = DB::table('qms')
                    ->where('item_type', 'file')
                    ->where('sub_for', $request->sub_for)
                    ->max('item_number');
                $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
                $fileExt = $request->att_file->extension();
                $fileName = "LTE-$request->doc_type-$indexNumber-Ver.$request->ver-Rev.$request->rev $request->item_name.$fileExt";
                $path = Storage::putFileAs("public/files/qms", $request->file('att_file'), $fileName);
                $filePath = Storage::url($path);
            }
            if (!$request->att_file && $request->old_att_file) {
                $filePath = $request->old_att_file;
                $oldPath = str_replace('/storage', '/public', $request->old_att_file);
                $fileExt = str_replace('.', '', substr($oldPath, -4));
                $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
                $newName = "LTE-$request->doc_type-$indexNumber-Ver.$request->ver-Rev.$request->rev $request->item_name.$fileExt";
                $newPath = "/public/files/qms/$newName";
                Storage::move($oldPath, $newPath);
                $filePath = str_replace('/storage//public', '/storage', Storage::url($newPath));
            }
            if (!$request->att_file && !$request->old_att_file) {
                $filePath = null;
                $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
            }
            DB::table('qms')
                ->where('id', $request->rec_id)
                ->update([
                    'item_name' => $request->item_name,
                    'item_number' => $request->item_number,
                    'sub_for' => $request->sub_for,
                    'index_number' => $indexNumber,
                    'date_created' => $request->date_created,
                    'rev_date' => $request->rev_date,
                    'doc_type' => $request->doc_type,
                    'ver' => $request->ver,
                    'rev' => $request->rev,
                    'period' => $request->period,
                    'send_to_office' => $request->send_to_office,
                    'format' => $request->format,
                    'att_file' => $filePath,
                ]);
            $msg = 'File updated successfully!';
        }

        //---DELETE----------------------------
        if ($request->edit_mode == 'delete') {
            $record = Qms::find($request->rec_id);
            $filePath = str_replace('storage', 'public', $record->att_file);
            Storage::delete($filePath);
            $record->delete();
            $msg = 'File deleted successfully!';
        }

        return redirect()
            ->route('qms_docs')
            ->with('status_msg', $msg);
    }








    //================================= QMS RECORDS ===================================

    //--------------------------------------------------------------------
    //------------------------ QMS RECORDS VSL ---------------------------
    //--------------------------------------------------------------------
    public function qmsRecordsVsl($vsl_id, $vsl_name, $date_from, $date_to)
    {
        $docTypesToSelect = ['CHLST', 'CHRT', 'FRM', 'PTW'];
        session([
            'vsl_id' => $vsl_id,
            'vsl_name' => $vsl_name,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);

        $qms_folders = DB::table('qms')
            ->where('item_type', 'folder')
            ->get();

        //---MAKE KEY MASS FOR FILES
        $qmsFilesMass = [];
        $qms_docs_files_list = DB::table('qms')
            ->where('item_type', 'file')
            ->get();
        foreach ($qms_docs_files_list as $item) {
            if (in_array($item->doc_type, $docTypesToSelect)) {
                $indexFile = $item->sub_for . '-' . $item->item_number;
                $fileName = "LTE-$item->doc_type-$indexFile-Ver.$item->ver-Rev.$item->rev $item->item_name";
                if (key_exists($item->sub_for, $qmsFilesMass)) {
                    $qmsFilesMass[$item->sub_for][] = $fileName;
                } else {
                    $qmsFilesMass[$item->sub_for] = [$fileName];
                }
            }
        }

        $datesMass = [$date_from, $date_to];
        $qms_records = DB::table('qms_records')
            ->where('vsl_id', $vsl_id)
            ->whereBetween('rev_date', $datesMass)
            ->orderBy('item_name')
            ->orderBy('id')
            ->get();

        return view('qms.qms_records', [
            'qms_records' => $qms_records,
            'qms_folders' => $qms_folders,
            'qmsFilesMass' => $qmsFilesMass,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ ARCHIVE QMS RECORDS -----------------------
    //--------------------------------------------------------------------
    public function qmsRecordsArchive($sectionNumber)
    {
        $vsl_id = session('vsl_id');
        $datesMass = [session('date_from'), session('date_to')];

        $qms_records = DB::table('qms_records')
            ->where('vsl_id', $vsl_id)
            ->whereNull('state')
            ->whereBetween('rev_date', $datesMass)
            ->where('sub_for', 'like', "$sectionNumber%")
            ->get();

        //---IF NO RECORDS ALERT    
        if (!count($qms_records)) {
            return redirect()
                ->route('qms_records_vsl', [
                    'vsl_id' => session('vsl_id'),
                    'vsl_name' => session('vsl_name'),
                    'date_from' => session('date_from'),
                    'date_to' => session('date_to'),
                ])
                ->with('status_msg', "Section $sectionNumber has no records for archiving");
        }

        //---CREATE MASS FILES-IN-FOLDERS
        $contentMass = [];
        foreach ($qms_records as $item) {
            $contentMass[$item->sub_for][] = [
                'rec_id' => $item->id,
                'file_name' => str_replace('/storage/files/qmsrec/', '', $item->att_file),
                'file_path' => public_path() . $item->att_file
            ];
        }

        //---CREATE ARCHIVE
        try {
            $recForDeleteMass = [];
            $zip = new ZipArchive();
            $zipName = 'QMS_RECORDS_ARCHIVE___' . session('vsl_name') . '___' . $sectionNumber . '___(' . session('date_from') . '__' . session('date_to') . ").zip";
            $zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($contentMass as $k => $innerMass) {
                if (strlen($k) > 1) {
                    $zip->addEmptyDir($k);
                    foreach ($innerMass as $item) {
                        $recForDeleteMass[] = ['rec_id' => $item['rec_id'], 'file_path' => $item['file_path']];
                        $zip->addFile($item['file_path'], "$k/" . $item['file_name']);
                    }
                } else {
                    foreach ($innerMass as $item) {
                        $recForDeleteMass[] = ['rec_id' => $item['rec_id'], 'file_path' => $item['file_path']];
                        $zip->addFile($item['file_path'], $item['file_name']);
                    }
                }
            }
            $zip->close();
        } catch (Exception $e) {
            return redirect()
                ->route('qms_records_vsl', [
                    'vsl_id' => session('vsl_id'),
                    'vsl_name' => session('vsl_name'),
                    'date_from' => session('date_from'),
                    'date_to' => session('date_to'),
                ])
                ->with('status_msg', 'ERROR - ' . $e->getMessage());
        }

        //---DELETE ARCHIVED FILES + SET STATE=>ARCHIVED
        foreach ($recForDeleteMass as $item) {
            DB::table('qms_records')
                ->where('id', $item['rec_id'])
                ->update([
                    'att_file' => null,
                    'state' => 'archived'
                ]);
            unlink($item['file_path']);
        }

        return view('qms.qms_archive')
            ->with([
                'filesCnt' => count($qms_records),
                'zipName' => $zipName,
            ]);
    }

    //--------------------------------------------------------------------
    //----------------- DELETE ARCHIVE QMS RECORDS ---------------------
    //--------------------------------------------------------------------
    public function qmsRecordsArchiveDelete(Request $request)
    {
        unlink($request->zip_name);
        return redirect()
            ->route('qms_records_vsl', [
                'vsl_id' => session('vsl_id'),
                'vsl_name' => session('vsl_name'),
                'date_from' => session('date_from'),
                'date_to' => session('date_to'),
            ])
            ->with('status_msg', 'Archive deleted from the server');
    }


    //--------------------------------------------------------------------
    //------------------------ EDIT FILE QMS RECORDS ---------------------
    //--------------------------------------------------------------------
    public function editFileRecords(Request $request)
    {
        //---ADD NEW-------------------------
        if ($request->edit_mode == 'new') {
            $valid = $request->validate([
                'file_name' => 'required',
                'rev_date' => 'required',
                'att_file' => 'required',
            ]);

            $appx = str_replace(' ', '_', $request->appendix);
            $itemName = strtoupper($request->file_name . "_" . session('vsl_name') . "_" . $request->rev_date . "_" . $appx);
            $fileName0 = str_replace(' ', '_', $itemName);
            $fileName1 = str_replace('.', '_', $fileName0);
            $fileName2 = str_replace(',', '_', $fileName1);
            $fileName3 = str_replace('/', '_', $fileName2);

            $filePath = null;
            if ($request->att_file) {
                $fileExt = $request->att_file->extension();
                $fileName = "$fileName3.$fileExt";
                $path = Storage::putFileAs("public/files/qmsrec", $request->file('att_file'), $fileName);
                $filePath = Storage::url($path);
            }
            $subFor =  strlen($request->sub_for) == 1 ? $request->sub_for . '.0' : $request->sub_for;
            DB::table('qms_records')
                ->insert([
                    'vsl_id' => session('vsl_id'),
                    'sub_for' => $subFor,
                    'item_name' =>  $itemName,
                    'date_created' => date('Y-m-d'),
                    'rev_date' => $request->rev_date,
                    'att_file' => $filePath,
                ]);
            $msg = 'File uploaded successfully!';
        }

        //---DELETE----------------------------
        if ($request->edit_mode == 'delete') {
            $valid = $request->validate([
                'rec_id' => 'required',
            ]);
            $record = DB::table('qms_records')
                ->where('id', $request->rec_id)
                ->first(['att_file']);
            $filePath = str_replace('storage', 'public', $record->att_file);
            Storage::delete($filePath);
            DB::table('qms_records')
                ->where('id', $request->rec_id)
                ->delete();
            $msg = 'File deleted successfully!';
        }

        //---EDIT-----------------------------
        // if ($request->edit_mode == 'edit') {
        //     if ($request->att_file) {
        //         //---delete old file
        //         $oldFilePath = str_replace('storage', 'public', $request->old_att_file);
        //         Storage::delete($oldFilePath);
        //         //---upload new file
        //         $lastNumber = DB::table('qms')
        //             ->where('item_type', 'file')
        //             ->where('sub_for', $request->sub_for)
        //             ->max('item_number');
        //         $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
        //         $fileExt = $request->att_file->extension();
        //         $fileName = "LTE-$request->doc_type-$indexNumber-Ver.$request->ver-Rev.$request->rev $request->item_name.$fileExt";
        //         $path = Storage::putFileAs("public/files/qms", $request->file('att_file'), $fileName);
        //         $filePath = Storage::url($path);
        //     }
        //     if (!$request->att_file && $request->old_att_file) {
        //         $filePath = $request->old_att_file;
        //         $oldPath = str_replace('/storage', '/public', $request->old_att_file);
        //         $fileExt = str_replace('.', '', substr($oldPath, -4));
        //         $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
        //         $newName = "LTE-$request->doc_type-$indexNumber-Ver.$request->ver-Rev.$request->rev $request->item_name.$fileExt";
        //         $newPath = "/public/files/qms/$newName";
        //         Storage::move($oldPath, $newPath);
        //         $filePath = str_replace('/storage//public', '/storage', Storage::url($newPath));
        //     }
        //     if (!$request->att_file && !$request->old_att_file) {
        //         $filePath = null;
        //         $indexNumber = $request->sub_for ? $request->sub_for . '.' . $request->item_number : $request->item_number;
        //     }
        //     DB::table('qms')
        //         ->where('id', $request->rec_id)
        //         ->update([
        //             'item_name' => $request->item_name,
        //             'item_number' => $request->item_number,
        //             'sub_for' => $request->sub_for,
        //             'index_number' => $indexNumber,
        //             'date_created' => $request->date_created,
        //             'rev_date' => $request->rev_date,
        //             'doc_type' => $request->doc_type,
        //             'ver' => $request->ver,
        //             'rev' => $request->rev,
        //             'period' => $request->period,
        //             'send_to_office' => $request->send_to_office,
        //             'format' => $request->format,
        //             'att_file' => $filePath,
        //         ]);
        //     $msg = 'File updated successfully!';
        // }



        return redirect()
            ->route('qms_records_vsl', [
                'vsl_id' => session('vsl_id'),
                'vsl_name' => session('vsl_name'),
                'date_from' => session('date_from'),
                'date_to' => session('date_to'),
            ])
            ->with('status_msg', $msg);
    }
}
