<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Events\NewMessage;

class ContactsController extends Controller
{
    public function get()
    {
    	//except the auth user
    	$contacts = User::where('id', '!=' , auth()->id())->get();

    	$unreadids = Message::select(\DB::raw('`from` as sender_id, count(`from`) as messages_count'))
    	                  ->where('to', auth()->id())
    	                  ->where('read', false)
    	                  ->groupBy('from')
    	                  ->get();

    	$contacts = $contacts->map(function($contact) use ($unreadids) {
    		$contactUnread = $unreadids->where('sender_id', $contact->id)->first();

    		$contact->unread = $contactUnread ? $contactUnread->messages_count: 0;

    		return $contact;
    	});                 
    	return response()->json($contacts);
    }

    public function getMessagesFor($id)
    {
       $messages = Message::where('from', $id)->orWhere('to', $id)->get();

       $messages = Message::where(function($q) use ($id){
       	$q->where('from', auth()->id());
       	$q->where('to', $id);
       })->orWhere(function($q) use ($id) {
       	$q->where('from', 'id');
       	$q->where('to', auth()->id());
       }) -> get();
       return response()->json($messages);
    }

    public function send(Request $request)
    {
    	$message = Message::create([
    		'from' => auth()->id(),
    		'to' => $request->contact_id,
    		'text'=> $request->text
    	]);

    	 broadcast(new NewMessage($message));

    	return response()->json($message);
    }
}
