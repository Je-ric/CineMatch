# CineMatch — Miscellaneous Notes

---

## Facebook OAuth App Settings

To configure Facebook login, you need to set up a Facebook Developer App.

**Facebook App Dashboard:**
https://developers.facebook.com/apps/

### Required Settings

1. Go to your app → **Use Cases** → **Facebook Login** → **Settings**
2. Under **Valid OAuth Redirect URIs**, add:
   ```
   http://localhost:8000/auth/facebook/callback
   https://yourdomain.com/auth/facebook/callback
   ```
3. Enable **Email** permission under the login use case.

### Environment Variables

```env
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/auth/facebook/callback
```

### Notes

- Facebook requires HTTPS for production redirect URIs.
- The app must be in **Live** mode (not Development) for users outside the developer team to log in.
- In Development mode, only app admins, developers, and testers can log in.
- The `public_profile` and `email` permissions are requested during login.

---

## Google OAuth App Settings

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project → Enable **Google+ API** or **Google Identity**
3. Create OAuth 2.0 credentials
4. Add authorized redirect URIs:
   ```
   http://localhost:8000/auth/google/callback
   https://yourdomain.com/auth/google/callback
   ```

### Environment Variables

```env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

## Storage Setup

Run this after cloning to expose uploaded files (posters, backgrounds) publicly:

```bash
php artisan storage:link
```

Uploaded files are stored in:
- `public/uploads/posters/`
- `public/uploads/backgrounds/`

---

## Seeding

```bash
# Seed all
php artisan db:seed

# Seed specific
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=GenreSeeder
```
