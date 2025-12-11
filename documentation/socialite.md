## 🔐 Socialite Login Flow (Google / Facebook)

1. **[Login Button Clicked]**  
   - Pag kapindot ng user sa login button (route name) para sa Google o Facebook login.

2. **[Route]**  
   - `/auth/google` o `/auth/facebook`  
   - Makikita yung route at magbubukas yung controller na naka-link dito.

3. **[Controller Redirect Method]**  
   - `redirectGoogle()` / `redirectFacebook()`  
   - Nakalagay dito yung Socialite driver (google/facebook/github) at magre-redirect sa provider login.  
   - Connected ito sa `config/services.php` para malaman yung OAuth details.

4. **[Provider Login Page]**  
   - Dito mag-login yung user sa Google o Facebook.  
   - Ang redirect URL ay nakaset sa `config/services.php` na nakalink sa `.env` (`REDIRECT_URI`).  
   - Pag natapos yung login at permission, ire-redirect pabalik sa controller callback.

5. **[Provider Callback URL]**  
   - `/auth/google/callback` o `/auth/facebook/callback`  
   - Ito yung link na binigay sa provider at nag-oopen ng route/controller na naka-set dito.

6. **[Controller Callback Method]**  
   - `handleGoogleCallback()` / `handleFacebookCallback()`  
   - Controller na nag-process ng data galing sa provider (user info, token).

7. **[findOrCreateSocialUser()]**  
   - Chine-check kung may existing user sa DB gamit email.  
   - Kung wala → gagawa ng bagong user.  
   - Kung meron → u-update ang provider ID at token.

8. **[Database]**  
   - Users table na sinesave o ina-update ang user record.

9. **[Auth::login()]**  
   - Nila-login yung user sa system at nire-regenerate ang session.

10. **[Redirect Home]**  
    - Pag-login successful → ire-redirect sa home/dashboard page.

11. **[User Sees Home / Dashboard]**  
    - Nakikita na ng user ang home page na naka-login.

---

**Additional Components:**  
- **Services Config (`config/services.php`)** → binabasa yung env variables para sa OAuth credentials.  
- **Environment Variables (`.env`)** → `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.  
