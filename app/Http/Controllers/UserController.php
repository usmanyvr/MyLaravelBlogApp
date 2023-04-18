<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use Illuminate\Validation\Rule;
use App\Models\User;
use Intervention\Image\Facades\Image;
use Storage;

class UserController extends Controller
{
    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max: 20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);
        $incomingFields['password'] = bcrypt($incomingFields['password']);
        $user = User::create($incomingFields);
        auth()->login($user);
        return redirect('/')->with('success', 'Thank you for creating an account');
    }

    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'You have successfully logged in');
        } else {
            return redirect('/')->with('failure', 'Invalid login.');
        }
    }

    public function logout(Request $request) {
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out');
    }

    public function showCorrectHomepage(Request $request) {
        if(auth()->check()) {
            return view('homepage-feed');
        } else {
            return view('homepage');
        }
    }

    public function profile(User $user) {
        $thePosts = $user->posts()->get();

        return view('profile-posts', [
            'username' => $user->username,
            'posts' => $user->posts()->latest()->get(),
            'postCount' => $user->posts()->count()
        ]);
    }

    public function showAvatarForm() {
        return view('avatar-form');
    }

    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar' => 'required|image|max:2000'
        ]);

        $user = auth()->user();
        $filename = $user->id . '-' . uniqid() . '.jpg';
        
        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $filename, $imgData);
        
        $user->avatar = $filename;
        $user->save();
    }
}
