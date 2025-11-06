<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }  
      

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        Session::flush();
        Auth::logout();
        // Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
        // return response()->json(['success' => true]); // Devuelve una respuesta JSON
    }
    public function customLogin(Request $request)
    {
       $validator =  $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
   
    
        // if (Auth::attempt($request->only('email', 'password'))) {
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            if (Hash::check($request->password, $existingUser->password)) {
                auth()->loginUsingId($existingUser->id);
                return redirect()->intended('dashboard');
            }
        }
        // auth()->loginUsingId(auth()->user()->id);
            // $request->session()->regenerate();
            // return redirect()->intended('dashboard');
        // }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }



    public function registration()
    {
        return view('auth.registration');
    }
      

    public function customRegistration(Request $request)
    {  
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);
           
        $data = $request->only(['name', 'email', 'password']);
        $check = $this->create($request);
         
        return redirect("dashboard")->withSuccess('You have signed-in');
    }


    public function create(Request $request)
    {
        return User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'person_id' => $request->person_id,
        ]);
    }    
    

    public function dashboard()
    {
        if(Auth::check()){
            return view('dashboard');
        }
  
        return redirect("login")->withSuccess('You are not allowed to access');
    }
    

    public function signOut() {
        Session::flush();
        Auth::logout();
  
        return Redirect('login');
    }
}
