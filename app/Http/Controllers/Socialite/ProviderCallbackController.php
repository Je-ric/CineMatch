<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ProviderCallbackController extends Controller
{
    public function __invoke(String $provider){
        if(!in_array($provider, ['google', 'facebook', 'github'])){
            return redirect()->route('auth')->withErrors(['provider' => 'Invalid Provider']);
        }

        $socialUser = Socialite::driver($provider)->user();


        // Generate Username
        $username = $this->generateUsername($socialUser, $provider);

        $user = User::updateOrCreate([
            'provider_id' => $socialUser->id,
            'provider_name' => $provider,
        ], [
            'name' => $socialUser->name,
            'email' => $socialUser->email ?? $socialUser->id . '@' . $provider . '.local', // Fallback email
            'username' => $username,
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
            'role'=>'user', //by default
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }

    private function generateUsername($socialUser, $provider){
        $username = $socialUser->getNickname() ?? null;

        if(!$username){
            if(!empty($socialUser->name)){
                $username = Str::lower(str_replace(' ', '', $socialUser->name)) . '_' . rand(1000, 9999);
            }else{
                $username = Str::lower(str_replace(' ', '', $socialUser->name)) . '_' . rand(1000, 9999);
            }
        }

        // clean username

        $username = preg_replace('/[^A-Za-z0-9]/', '', Str::lower($username));

        $baseUsername = $username;
        $count = 1;
        while(User::where('username', $username)->exists()){
            $username = $baseUsername . '_' . $count;
            $count++;
        }

        return $username;
    }
}
