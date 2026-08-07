# Quick Free Preview (Render.com via GitHub)

This is **only for previewing the site with real internet access** (so real food photos load). Your actual production deployment should follow `INSTALL.md` for cPanel instead — this Docker/Render path is not used there.

## Steps

1. Go to **https://render.com** and sign up (free — GitHub login works, no credit card needed for the free web service tier).
2. Click **New +** → **Web Service**.
3. Connect your GitHub account and select the repository (`restaurant-script`), branch `claude/restaurant-website-premium-q0azpk`.
4. Render should auto-detect the `Dockerfile` at the repo root and set **Environment/Runtime: Docker** automatically. If it asks, choose Docker explicitly.
5. Give it a name (e.g. `al-waha-preview`), pick **Instance Type: Free**, leave everything else default.
6. Click **Create Web Service**. Render will build the Docker image and deploy — this takes a few minutes the first time.
7. Once it's live, open the `https://your-service-name.onrender.com` link Render gives you (works on your phone browser too).
8. You'll land on the setup wizard automatically (`/install`) — choose **SQLite** as the database type (simplest, no extra setup needed) and fill in your restaurant name/admin account, then click Install.
9. Your site is now live with real food photography, working cart, reviews, everything.

## Notes

- The **free tier's disk is temporary** — if the service goes to sleep after inactivity and wakes back up, or you redeploy, the database resets and you may need to run the install wizard again. This is expected and fine for a quick preview; it doesn't affect your real cPanel deployment at all.
- When you're ready to go live for real, follow `INSTALL.md` and deploy to your actual hosting — that's the permanent, production setup.
