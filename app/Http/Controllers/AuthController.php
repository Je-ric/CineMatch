<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'user',
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('auth')->with('success', 'Account created successfully!');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ---------- GOOGLE ----------
    public function redirectGoogle()
    {
        // Forces Google to show account chooser every time
        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();

            \Log::info('Google social user:', [
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'token' => $socialUser->token,
            ]);

            $user = $this->findOrCreateSocialUser($socialUser, 'google');

            Auth::login($user);

            return redirect()->route('home');
        } catch (\Exception $e) {
            \Log::error('Google login failed: ' . $e->getMessage());
            return redirect()->route('auth')->withErrors(['google' => 'Failed to login with Google. Please try again.']);
        }
    }


    // ---------- FACEBOOK ----------
    public function redirectFacebook()
    {
        return Socialite::driver('facebook')
            ->stateless()
            ->scopes(['public_profile', 'email'])
            ->with(['auth_type' => 'rerequest']) // Forces re-asking permissions if email missing
            ->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->stateless()->user();

            \Log::info('Facebook social user:', [
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'token' => $socialUser->token,
            ]);

            $user = $this->findOrCreateSocialUser($socialUser, 'facebook');

            Auth::login($user);

            return redirect()->route('home');
        } catch (\Exception $e) {
            \Log::error('Facebook login failed: ' . $e->getMessage());
            return redirect()->route('auth')->withErrors([
                'facebook' => 'Failed to login with Facebook. Please try again.',
            ]);
        }
    }

    // ---------- GITHUB ----------
    public function redirectGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $socialUser = Socialite::driver('github')->stateless()->user();
            $user = $this->findOrCreateSocialUser($socialUser, 'github');
            Auth::login($user);
            return redirect()->route('home');
        } catch (\Exception $e) {
            return redirect()->route('auth')->withErrors(['github' => $e->getMessage()]);
        }
    }

    private function findOrCreateSocialUser($socialUser, $provider)
    {
        // Try to find by email first
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update existing record with provider info
            $user->update([
                'provider_id' => $socialUser->getId(),
                'provider_name' => $provider,
                'provider_token' => $socialUser->token ?? null,
                'provider_refresh_token' => $socialUser->refreshToken ?? null,
            ]);

            return $user;
        }

        // Otherwise, find by provider or create new
        return User::updateOrCreate(
            [
                'provider_id' => $socialUser->getId(),
                'provider_name' => $provider,
            ],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail() ?? ($socialUser->getId() . '@' . $provider . '.local'),
                'username' => $this->generateUsername($socialUser),
                'password' => bcrypt(Str::random(16)),
                'provider_token' => $socialUser->token ?? null,
                'provider_refresh_token' => $socialUser->refreshToken ?? null,
                'role' => 'user',
            ]
        );
    }



    private function generateUsername($socialUser)
    {
        $username = $socialUser->getNickname() ?? null;
        if (!$username) {
            $name = $socialUser->getName() ?? ($socialUser->getEmail() ? strstr($socialUser->getEmail(), '@', true) : $socialUser->getId());
            $username = Str::lower(preg_replace('/\s+/', '', $name)) . '_' . rand(1000, 9999);
        }

        $username = preg_replace('/[^A-Za-z0-9]/', '', Str::lower($username));

        $baseUsername = $username;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $count;
            $count++;
        }

        return $username;
    }
}
