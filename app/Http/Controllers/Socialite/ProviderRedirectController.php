<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class ProviderRedirectController extends Controller
{
    public function __invoke(Request $request, string $provider){
    if(!in_array($provider, ['google', 'facebook', 'github'])){
        return redirect()->route('auth')->withErrors(['provider' => 'Invalid Provider']);
    }
    try{
        if($provider === 'facebook'){
            return Socialite::driver($provider)
                ->scopes(['public_profile']) // Remove 'email' for now
                ->redirect();
        }
        return Socialite::driver($provider)->redirect();
    }catch(Exception $e){
        return redirect()->route('auth')->withErrors(['provider' => $e->getMessage()]);
    }
}
}
