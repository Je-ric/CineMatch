# Authentication Flow Documentation

## Overview
This document explains the complete authentication flow in CineMatch, covering traditional login/register and OAuth social authentication (Google, Facebook).

---

## Table of Contents
1. [Entry Points](#entry-points)
2. [Traditional Authentication](#traditional-authentication)
3. [Social Authentication Flow](#social-authentication-flow)
4. [MVC Communication Flow](#mvc-communication-flow)
5. [Database Operations](#database-operations)
6. [Session Management](#session-management)

---

## Entry Points

### Routes (routes/web.php)

| Route | Method | Controller/Action | Purpose |
|-------|--------|-------------------|---------|
| `/auth` | GET | `view('auth')` | Display login/register form |
| `/login` | GET | Redirect to `/auth` | Login redirect alias |
| `/register` | POST | `AuthController@register` | Handle registration |
| `/login` | POST | `AuthController@login` | Handle login |
| `/logout` | POST | `AuthController@logout` | Handle logout |
| `/auth/google/redirect` | GET | `AuthController@redirectGoogle` | Initiate Google OAuth |
| `/auth/google/callback` | GET | `AuthController@handleGoogleCallback` | Handle Google callback |
| `/auth/facebook/redirect` | GET | `AuthController@redirectFacebook` | Initiate Facebook OAuth |
| `/auth/facebook/callback` | GET | `AuthController@handleFacebookCallback` | Handle Facebook callback |

---

## Traditional Authentication

### 1. Registration Flow

#### Step-by-Step Sequence:

```
User → View (auth.blade.php) → Form Submit → Route → Controller → Database → Redirect
```

#### Detailed Flow:

**A. View Layer (`resources/views/auth.blade.php`)**
- User sees registration form (hidden by default, toggleable with JavaScript)
- Form fields: `name`, `email`, `username`, `password`
- Form action: `{{ route('auth.register') }}`
- Method: `POST`
- CSRF token included: `@csrf`

**B. Route Layer (`routes/web.php:35`)**
```php
Route::post('/register', [AuthController::class, 'register'])
    ->name('auth.register');
```

**C. Controller Layer (`app/Http/Controllers/AuthController.php:14-32`)**

Function: `register(Request $request)`

**Process:**
1. **Validation** (lines 16-21):
   - `name`: required, string, max 255
   - `email`: required, email, unique in users table
   - `username`: required, string, unique in users table
   - `password`: required, string, min 6 characters

2. **User Creation** (lines 23-29):
   ```php
   User::create([
       'name' => $request->name,
       'email' => $request->email,
       'role' => 'user',  // Default role
       'username' => $request->username,
       'password' => Hash::make($request->password),  // Hashed password
   ]);
   ```

3. **Response** (line 31):
   - Redirect to `route('auth')` with success message
   - Flash message: "Account created successfully!"

**D. Model Layer (`app/Models/User.php`)**
- Mass assignable fields defined in `$fillable` (lines 32-42)
- Password automatically hashed via `casts()` method (line 74)
- Model saved to `users` table

**E. Database Schema**
- Table: `users`
- Columns: `id`, `name`, `email`, `username`, `password`, `role`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`

---

### 2. Login Flow

#### Step-by-Step Sequence:

```
User → View (auth.blade.php) → Form Submit → Route → Controller → Auth Facade → Session → Redirect
```

#### Detailed Flow:

**A. View Layer (`resources/views/auth.blade.php:27-37`)**
- Login form visible by default
- Fields: `username`, `password`
- Form action: `{{ route('login') }}`
- Method: `POST`

**B. Route Layer (`routes/web.php:36`)**
```php
Route::post('/login', [AuthController::class, 'login'])
    ->name('login');
```

**C. Controller Layer (`app/Http/Controllers/AuthController.php:34-49`)**

Function: `login(Request $request)`

**Process:**
1. **Validation** (lines 36-39):
   - `username`: required, string
   - `password`: required, string

2. **Authentication Attempt** (lines 41-44):
   ```php
   if (Auth::attempt($credentials)) {
       $request->session()->regenerate();  // Security: regenerate session ID
       return redirect()->intended(route('home'));
   }
   ```
   - `Auth::attempt()` checks credentials against database
   - If successful: regenerate session ID (prevents session fixation attacks)
   - Redirect to intended URL or home

3. **Error Handling** (lines 46-48):
   - If authentication fails, redirect back with error message
   - Error key: `username` → "The provided credentials do not match our records."

---

### 3. Logout Flow

#### Step-by-Step Sequence:

```
User → Form/Button → Route → Controller → Auth Facade → Session Destroy → Redirect
```

#### Detailed Flow:

**Route:** `routes/web.php:37`
```php
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
```

**Controller:** `app/Http/Controllers/AuthController.php:51-58`

Function: `logout(Request $request)`

**Process:**
1. **Logout** (line 53): `Auth::logout()`
2. **Invalidate Session** (line 54): `$request->session()->invalidate()`
3. **Regenerate Token** (line 55): `$request->session()->regenerateToken()` (CSRF token)
4. **Redirect** (line 57): Redirect to `route('login')`

---

## Social Authentication Flow

### 1. Google OAuth Flow

#### Step-by-Step Sequence:

```
User → Click Button → Redirect → Google OAuth → Callback → Controller → Database → Login → Redirect
```

#### Detailed Flow:

**A. Initial Redirect (`routes/web.php:26`)**
```php
Route::get('/auth/google/redirect', [AuthController::class, 'redirectGoogle'])
    ->name('auth.google.redirect');
```

**B. View Layer Button (`resources/views/auth.blade.php:88-92`)**
```blade
<a href="{{ route('auth.google.redirect') }}">
    <img src="{{ asset('img/auth/google-logo.png') }}" alt="Google">
    <span>Continue with Google</span>
</a>
```

**C. Controller Redirect (`app/Http/Controllers/AuthController.php:61-68`)**

Function: `redirectGoogle()`

**Process:**
```php
return Socialite::driver('google')
    ->stateless()  // No session storage, use token-based
    ->with(['prompt' => 'select_account'])  // Force account selection
    ->redirect();  // Redirects to Google OAuth consent screen
```

**What Happens:**
1. User clicks "Continue with Google"
2. Application redirects to Google OAuth consent screen
3. User selects account and grants permissions
4. Google redirects back to callback URL with authorization code

**D. Callback Route (`routes/web.php:27`)**
```php
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');
```

**E. Callback Handler (`app/Http/Controllers/AuthController.php:70-91`)**

Function: `handleGoogleCallback()`

**Process:**

1. **Get Social User** (line 73):
   ```php
   $socialUser = Socialite::driver('google')->stateless()->user();
   ```
   Returns object with: `id`, `name`, `email`, `token`, `refreshToken`

2. **Logging** (lines 75-80):
   - Logs social user data for debugging

3. **Find or Create User** (line 82):
   ```php
   $user = $this->findOrCreateSocialUser($socialUser, 'google');
   ```

4. **Login User** (line 84):
   ```php
   Auth::login($user);
   ```

5. **Redirect** (line 86):
   ```php
   return redirect()->route('home');
   ```

6. **Error Handling** (lines 87-90):
   - Try-catch block catches exceptions
   - Logs error and redirects with error message

---

### 2. Facebook OAuth Flow

#### Similar to Google, with differences:

**A. Redirect (`routes/web.php:28`)**
```php
Route::get('/auth/facebook/redirect', [AuthController::class, 'redirectFacebook'])
    ->name('auth.facebook.redirect');
```

**B. View Button (`resources/views/auth.blade.php:94-98`)**
- Similar structure to Google button

**C. Controller Redirect (`app/Http/Controllers/AuthController.php:94-102`)**

Function: `redirectFacebook()`

**Differences:**
- Scopes requested: `['public_profile', 'email']`
- Auth type: `rerequest` (forces re-asking permissions if email missing)

**D. Callback (`app/Http/Controllers/AuthController.php:104-127`)**

Function: `handleFacebookCallback()`

- Same structure as Google callback
- Uses `'facebook'` as provider name

---

### 3. Social User Creation Logic

#### Helper Function: `findOrCreateSocialUser()`

**Location:** `app/Http/Controllers/AuthController.php:147-184`

**Process:**

1. **Find by Email First** (lines 150-152):
   ```php
   $user = User::where('email', $socialUser->getEmail())->first();
   ```

2. **If User Exists** (lines 154-165):
   - Update provider information:
     ```php
     $user->update([
         'provider_id' => $socialUser->getId(),
         'provider_name' => $provider,
         'provider_token' => $socialUser->token ?? null,
         'provider_refresh_token' => $socialUser->refreshToken ?? null,
     ]);
     ```
   - Return existing user

3. **If User Doesn't Exist** (lines 169-183):
   ```php
   return User::updateOrCreate(
       [
           'provider_id' => $socialUser->getId(),
           'provider_name' => $provider,
       ],
       [
           'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
           'email' => $socialUser->getEmail() ?? ($socialUser->getId() . '@' . $provider . '.local'),
           'username' => $this->generateUsername($socialUser),
           'password' => bcrypt(Str::random(16)),  // Random password since OAuth
           'provider_token' => $socialUser->token ?? null,
           'provider_refresh_token' => $socialUser->refreshToken ?? null,
           'role' => 'user',
       ]
   );
   ```

4. **Username Generation** (lines 188-206):
   - Uses nickname or name from social provider
   - Falls back to email prefix or provider ID
   - Cleans username (removes special characters)
   - Ensures uniqueness by appending numbers if needed

---

## MVC Communication Flow

### Traditional Registration Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌─────────┐      ┌──────────┐
│  View   │ ───> │  Route   │ ───> │  Controller │ ───> │  Model  │ ───> │ Database │
│ (Blade) │      │ (web.php)│      │ (AuthCtrl)  │      │  (User) │      │  (users) │
└─────────┘      └──────────┘      └──────────────┘      └─────────┘      └──────────┘
     │                  │                   │                  │                  │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┴──────────────────┴──────────────────┘
     │                                                          │
     │                                                          ▼
     │                                                   INSERT user record
     │                                                          │
     └──────────────────────────────────────────────────────────┘
                           Redirect with success message
```

### Traditional Login Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌──────────┐      ┌──────────┐
│  View   │ ───> │  Route   │ ───> │  Controller │ ───> │   Auth   │ ───> │ Database │
│ (Blade) │      │ (web.php)│      │ (AuthCtrl)  │      │  Facade  │      │  (users) │
└─────────┘      └──────────┘      └──────────────┘      └──────────┘      └──────────┘
     │                  │                   │                  │                  │
     │                  │                   │                  │                  │
     │                  │                   │            Check credentials      │
     │                  │                   │                  │                  │
     │                  │                   │ <─────────────────┘                  │
     │                  │                   │                                      │
     │                  │                   ▼                                      │
     │                  │            Create session                                  │
     │                  │            Regenerate ID                                  │
     │                  │                   │                                      │
     └──────────────────┴───────────────────┘                                      │
                           Redirect to home                                         │
```

### Social OAuth Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌───────────┐      ┌──────────┐
│  View   │ ───> │  Route   │ ───> │  Controller │ ───> │ Socialite │ ───> │  Google  │
│ (Blade) │      │ (web.php)│      │ (AuthCtrl)  │      │   Driver  │      │   OAuth  │
└─────────┘      └──────────┘      └──────────────┘      └───────────┘      └──────────┘
     │                  │                   │                  │                  │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┘                  │                  │
     │                           Redirect                       │                  │
     │                                                           │                  │
     │                                                           │ <───────────────┘
     │                                                           │  User grants permission
     │                                                           │                  │
     │                                                           ▼                  │
     │                                                      Authorization Code     │
     │                                                           │                  │
     │                                                           │                  │
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌───────────┐              │
│ Callback│ <─── │  Route   │ <─── │  Controller │ <─── │ Socialite │ <────────────┘
│   URL   │      │ (web.php)│      │ (AuthCtrl)  │      │   Driver  │
└─────────┘      └──────────┘      └──────────────┘      └───────────┘
     │                  │                   │                  │
     │                  │                   │                  │
     │                  │                   │            Exchange code for token
     │                  │                   │            Get user info
     │                  │                   │                  │
     │                  │                   ▼                  │
     │                  │            findOrCreateSocialUser    │
     │                  │                   │                  │
     │                  │                   ▼                  │
     │                  │            Create/Update User         │
     │                  │                   │                  │
     │                  │                   ▼                  │
     │                  │            Auth::login($user)         │
     │                  │                   │                  │
     └──────────────────┴───────────────────┘                  │
                           Redirect to home                     │
```

---

## Database Operations

### User Model (`app/Models/User.php`)

**Fillable Fields** (lines 32-42):
- `name`, `email`, `password`, `username`, `role`
- `provider_name`, `provider_id`, `provider_token`, `provider_refresh_token`

**Relationships:**
- `ratings()`: `HasMany` → `RatingReview` (one user has many reviews)
- `favorites()`: `BelongsToMany` → `Movie` via `user_favorites` pivot table

**Password Handling:**
- Auto-hashed via `casts()` method (line 74)
- Uses Laravel's `Hash::make()` or `bcrypt()`

---

## Session Management

### Session Creation (Login)

**Location:** `app/Http/Controllers/AuthController.php:41-44`

```php
if (Auth::attempt($credentials)) {
    $request->session()->regenerate();  // New session ID
    return redirect()->intended(route('home'));
}
```

**Why Regenerate?**
- Prevents session fixation attacks
- Creates new session ID after authentication
- Invalidates old session

### Session Destruction (Logout)

**Location:** `app/Http/Controllers/AuthController.php:51-58`

```php
Auth::logout();                              // Remove user from session
$request->session()->invalidate();          // Destroy session data
$request->session()->regenerateToken();     // New CSRF token
```

**Steps:**
1. Remove authenticated user
2. Invalidate all session data
3. Regenerate CSRF token (prevents token reuse)

---

## Key Functions Reference

### AuthController Functions

| Function | Purpose | Parameters | Returns |
|----------|---------|------------|---------|
| `register()` | Handle user registration | `Request $request` | `RedirectResponse` |
| `login()` | Handle user login | `Request $request` | `RedirectResponse` |
| `logout()` | Handle user logout | `Request $request` | `RedirectResponse` |
| `redirectGoogle()` | Initiate Google OAuth | None | `RedirectResponse` |
| `handleGoogleCallback()` | Handle Google OAuth callback | None | `RedirectResponse` |
| `redirectFacebook()` | Initiate Facebook OAuth | None | `RedirectResponse` |
| `handleFacebookCallback()` | Handle Facebook OAuth callback | None | `RedirectResponse` |
| `findOrCreateSocialUser()` | Find or create user from social provider | `$socialUser, $provider` | `User` |
| `generateUsername()` | Generate unique username | `$socialUser` | `string` |

---

## Common Patterns

### 1. Form Validation Pattern

```php
$validated = $request->validate([
    'field' => 'required|type|constraints',
]);
```

### 2. Authentication Pattern

```php
if (Auth::attempt($credentials)) {
    $request->session()->regenerate();
    return redirect()->intended(route('home'));
}
```

### 3. Social OAuth Pattern

```php
// Redirect
return Socialite::driver('provider')->stateless()->redirect();

// Callback
$socialUser = Socialite::driver('provider')->stateless()->user();
$user = $this->findOrCreateSocialUser($socialUser, 'provider');
Auth::login($user);
return redirect()->route('home');
```

---

## Security Considerations

1. **CSRF Protection**: All forms include `@csrf` token
2. **Password Hashing**: Passwords are never stored in plain text
3. **Session Regeneration**: Session ID regenerated on login
4. **Input Validation**: All inputs validated before processing
5. **Unique Constraints**: Email and username must be unique
6. **SQL Injection**: Protected via Eloquent ORM
7. **OAuth State**: Stateless OAuth (token-based) reduces session risks

---

## Error Handling

### Registration Errors

- Validation errors: Displayed via `@error` directive in Blade
- Database errors: Caught and logged (not shown in production)

### Login Errors

- Invalid credentials: Redirect back with error message
- Error key: `username` (for security, doesn't specify which field failed)

### Social OAuth Errors

- Network errors: Caught in try-catch, logged, redirected with error message
- Missing email: Handled with fallback email generation

---

## Testing Flow

### Test Registration:

1. Navigate to `/auth`
2. Click "Register here"
3. Fill form: name, email (unique), username (unique), password (min 6 chars)
4. Submit
5. Should redirect to `/auth` with success message
6. Try logging in with new credentials

### Test Login:

1. Navigate to `/auth`
2. Enter username and password
3. Submit
4. Should redirect to home (`/`)
5. Session should be active

### Test Google OAuth:

1. Navigate to `/auth`
2. Click "Continue with Google"
3. Should redirect to Google OAuth screen
4. Select account and grant permissions
5. Should redirect back and log in automatically
6. Check database for new user or updated provider info

---

## Troubleshooting

### Common Issues:

1. **"The provided credentials do not match"**
   - Check username/password combination
   - Verify user exists in database
   - Check password hashing

2. **OAuth Redirect Issues**
   - Verify callback URLs in OAuth provider settings
   - Check `config/services.php` for correct credentials
   - Verify routes are accessible

3. **Session Not Persisting**
   - Check `config/session.php` driver
   - Verify session middleware is applied
   - Check browser cookie settings

---

## Next Steps

After authentication, users are redirected to:
- **Home page** (`route('home')`) - Movie browsing and discovery
- **Profile page** (`route('profile')`) - User favorites, ratings, recommendations (requires authentication)

See other documentation files for:
- [Home Flow](./05-Home-Flow.md)
- [Profile Flow](./04-Profile-Flow.md)
- [ViewMovie Flow](./03-ViewMovie-Flow.md)

