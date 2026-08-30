<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Folder;
use Auth;

class FolderController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function create(){
        return view('user.create-folder');
    }

    public function store(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $userSegment = preg_replace('/[^A-Za-z0-9_\- ]/', '', Auth::user()->name .' '. (Auth::user()->surname ?? ''));
        $userSegment = trim(preg_replace('/\s+/', ' ', $userSegment));

        $existing = Folder::where([
            ['user_id', Auth::id()],
            ['name', $request->get('name')],
        ])->first();

        if($existing){
            return redirect()->route('documents')->with('warning','Folder already exists.');
        }

        $folder = new Folder([
            'user_id' => Auth::id(),
            'name' => $request->get('name'),
            'created_by' => $userSegment,
        ]);

        $folder->save();

        return redirect()->route('documents')->with('success','Folder created successfully.');
    }
}
