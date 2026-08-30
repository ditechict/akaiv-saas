<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\User;
use Auth;

class UserController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function create(){
        abort_unless(Auth::check() && optional(Auth::user())->role === 'Admin', 403);
        return view('auth.create');
    }

    public function store(Request $request){
        abort_unless(Auth::check() && optional(Auth::user())->role === 'Admin', 403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'max:255'],
            'role' => ['required', 'string', Rule::in(['Admin', 'User'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = new User([
            'name' => $request->get('name'),
            'surname' => $request->get('surname'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'role' => $request->get('role'),
            'password' => Hash::make($request->get('password')),
        ]);

        $user->save();

        return redirect()->back()->with('success','User created successfully.');
    }
}
