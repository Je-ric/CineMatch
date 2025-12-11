# CineMatch Authentication & Social Login Flow

This document explains how authentication works in CineMatch, including **normal login/register** and **social logins** (Google & Facebook) using Laravel Socialite.

---

## 1. Normal Registration & Login

### 1.1 Register (`register` method)
- Validates the user input: `name`, `email`, `username`, `password`.
- Creates a new `User` in the database.
- Password is hashed using `Hash::make()`.
- Default role is `user`.
- Redirects to login page with a success message.

**Flow:**
1. User fills the registration form.
2. Form submits via POST to `register`.
3. Controller validates input.
4. Creates new user record in `users` table.
5. Redirects to login page.

---

### 1.2 Login (`login` method)
- Validates `username` and `password`.
- Uses `Auth::attempt()` to check credentials.
- If valid:
  - Regenerates session (`$request->session()->regenerate()`) to prevent session fixation.
  - Redirects to `home` route.
- If invalid:
  - Returns back with error message.

**Flow:**
1. User fills login form.
2. Form submits via POST to `login`.
3. Controller validates credentials.
4. If correct → user logged in, session regenerated.
5. If incorrect → error displayed.

---

### 1.3 Logout (`logout` method)
- Logs out the user.
- Invalidates the session and regenerates CSRF token.
- Redirects to login page.

**Flow:**
1. User clicks logout.
2. Auth logout executed.
3. Session invalidated.
4. Redirect to login.

---

## 2. Social Login (Google / Facebook)

Laravel **Socialite** is a package that handles OAuth authentication with external providers like Google, Facebook, and GitHub.  
It simplifies the process of logging in via external accounts.

---

### 2.1 Google Login

**Methods:**

#### 2.1.1 `redirectGoogle()`
- Initiates OAuth login with Google.
- Uses `Socialite::driver('google')`.
- `stateless()` disables session state (no need to store OAuth state in session, useful for APIs or SPA).
- `with(['prompt' => 'select_account'])` forces Google to always show account chooser.
- Redirects user to Google login page.

**Flow:**
1. User clicks "Continue with Google".
2. `redirectGoogle()` sends them to Google OAuth page.
3. User logs in on Google and grants permissions.
4. Google redirects back to your app via the **callback URL**.

---

#### 2.1.2 `handleGoogleCallback()`
- Receives Google OAuth response.
- `Socialite::driver('google')->stateless()->user()` fetches user info.
- Checks if a user with the same email exists:
  - If yes → updates `provider_id`, `provider_token`, etc.
  - If no → creates new user with generated username and random password.
- Logs in the user using `Auth::login()`.
- Redirects to `home` page.

**Flow:**
1. Google redirects back to `/auth/google/callback`.
2. Controller receives user info.
3. `findOrCreateSocialUser()` ensures user exists in `users` table.
4. User is logged in and redirected.

---

### 2.2 Facebook Login

**Methods:**

#### 2.2.1 `redirectFacebook()`
- Initiates OAuth login with Facebook.
- Uses `Socialite::driver('facebook')`.
- `stateless()` disables session state.
- `scopes(['public_profile', 'email'])` requests user info.
- `with(['auth_type' => 'rerequest'])` forces Facebook to ask again if email permission was denied.
- Redirects user to Facebook login page.

#### 2.2.2 `handleFacebookCallback()`
- Receives Facebook OAuth response.
- `Socialite::driver('facebook')->stateless()->user()` fetches user info.
- Checks if a user with the same email exists, otherwise creates a new user.
- Logs in the user.
- Redirects to `home`.

**Flow:**
1. User clicks "Continue with Facebook".
2. `redirectFacebook()` sends them to Facebook login page.
3. User logs in and grants permissions.
4. Facebook redirects back to `/auth/facebook/callback`.
5. Controller fetches user info and logs them in.

---

### 2.3 Common Helper Methods

#### `findOrCreateSocialUser($socialUser, $provider)`
- Ensures a user exists in database for given social account.
- Updates provider tokens if user exists.
- Creates a new user if none exists:
  - Generates unique username if necessary.
  - Random password (not used for login, since OAuth manages auth).
  - Sets role as `user`.

#### `generateUsername($socialUser)`
- Generates a unique username based on social user data.
- Removes invalid characters and appends numbers if duplicate exists.

---

### 2.4 What is `stateless()`?
- By default, Socialite stores OAuth state in the session to prevent CSRF attacks.
- `stateless()` disables session state:
  - Useful in SPA or API scenarios where sessions may not be persistent.
  - You take responsibility for security checks yourself (Socialite still validates tokens).

---

### 2.5 Environment Variables for Social Login

In `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],
```

## Purpose of Environment Variables

- **client_id** → OAuth application ID provided by the provider.  
- **client_secret** → OAuth secret key.  
- **redirect** → URL where the provider will send the user back after login.

---

## Flow Example (Google)

1. User clicks login → `redirectGoogle()`.  
2. Google OAuth page opens.  
3. User approves → Google redirects to `GOOGLE_REDIRECT_URI`.  
4. `handleGoogleCallback()` receives OAuth data → user is logged in.

---

## Flow Example (Facebook)

1. User clicks login → `redirectFacebook()`.  
2. Facebook OAuth page opens.  
3. User approves → Facebook redirects to `FACEBOOK_REDIRECT_URI`.  
4. `handleFacebookCallback()` receives OAuth data → user is logged in.

---

## 3. Login/Register Blade Template

- Two forms: **Login** and **Register**, toggled by JS function `toggleForms()`.  
- Each form uses CSRF protection (`@csrf`).  
- Social login buttons redirect to the corresponding `redirectGoogle()` or `redirectFacebook()` routes.  
- The UI is responsive, with background image and gradient overlay.  
- Shows **“OR”** separator between standard login and social login.

---

## 4. Summary of Social Login Flow

| Step | Action |
|------|--------|
| 1    | User clicks social login button (Google/Facebook) |
| 2    | `redirect<Provider>()` → redirects to provider login page |
| 3    | User grants permissions |
| 4    | Provider redirects to callback URL |
| 5    | `handle<Provider>Callback()` fetches user info |
| 6    | `findOrCreateSocialUser()` ensures user exists in database |
| 7    | `Auth::login()` logs in user |
| 8    | Redirect to home page |

---

### Key Points

- Socialite handles OAuth for multiple providers.  
- `stateless()` disables session storage for OAuth state.  
- `findOrCreateSocialUser()` ensures unique and consistent user records.  
- Environment variables store credentials safely.
