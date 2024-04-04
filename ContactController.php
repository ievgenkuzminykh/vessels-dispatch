<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $contactList = DB::table('contacts')
            ->get();

        return view('contacts.start', [
            'contactList' => $contactList,
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ UPDATE CONTACT ----------------------------
    //--------------------------------------------------------------------
    public function contactsUpdate(Request $request)
    {
        $valid = $request->validate([
            'block_id' => 'required',
        ]);

        DB::table('contacts')
            ->where('block_id', $request->block_id)
            ->delete();

        DB::table('contacts')
            ->insert([
                'block_id' => $request->block_id,
                'block_content' => $request->block_content,
                'last_updated' => date('Y-m-d')
            ]);

        return redirect()
            ->route('contacts_start')
            ->with('status_msg', 'Data saved successfully!');
    }
}
