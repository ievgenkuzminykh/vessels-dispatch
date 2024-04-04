<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    //--------------------------------------------------------------------
    //------------------------ START -------------------------------------
    //--------------------------------------------------------------------
    public function start()
    {
        $itemList = DB::table('chat_messages')
            ->orderBy('id', 'desc')
            ->get();

        $locList = DB::table('locations')
            ->get();

        return view('chat.start', [
            'itemList' => $itemList,
            'loc_list' => $locList
        ]);
    }


    //--------------------------------------------------------------------
    //------------------------ MINI -------------------------------------
    //--------------------------------------------------------------------
    public function mini()
    {
        $itemList = DB::table('chat_messages')
            ->orderBy('id', 'desc')
            ->get();

        return view('chat.start_mini', ['itemList' => $itemList]);
    }


    //--------------------------------------------------------------------
    //------------------------ SEND MESSAGE ------------------------------
    //--------------------------------------------------------------------
    public function sendMessage(Request $request)
    {
        $valid = $request->validate([
            'location' => 'required',
            'msg_content' => 'required'
        ]);

        DB::table('chat_messages')
            ->insert([
                'user_name' => Auth::user()->name,
                'location' => $request->location,
                'msg_content' => $request->msg_content,
            ]);


        return redirect()
            ->route('chat_start')
            ->with('status_msg', 'Data saved successfully!');
    }



    //--------------------------------------------------------------------
    //------------------------ SEND MESSAGE MAIN--------------------------
    //--------------------------------------------------------------------
    public function sendMessageMain(Request $request)
    {
        $valid = $request->validate([
            'location' => 'required',
            'msg_content' => 'required'
        ]);

        DB::table('chat_messages')
            ->insert([
                'user_name' => Auth::user()->name,
                'location' => $request->location,
                'msg_content' => $request->msg_content,
            ]);


        return redirect()
            ->route('start_page')
            ->with('status_msg', 'Data saved successfully!');
    }


    //--------------------------------------------------------------------
    //------------------------ DELETE MESSAGE ----------------------------
    //--------------------------------------------------------------------
    public function deleteMessage($id)
    {
        DB::table('chat_messages')
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('chat_start')
            ->with('status_msg', 'Data removed successfully!');
    }
}
