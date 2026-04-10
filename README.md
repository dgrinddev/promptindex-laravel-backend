# PromptIndex — Laravel Backend

> Backend API for [PromptIndex](https://promptindex.io/), a community-driven platform to discover and share AI prompts for ChatGPT, Midjourney, Claude, and more.

This repository contains the Laravel API. The frontend (Vue SPA) lives in a [separate repository](https://github.com/dgrinddev/promptindex-vue-frontend).

---

## Tech stack

- **Laravel**
- **Laravel Sanctum** (authentication)
- **Laravel Fortify** (authentication flows)

Hosted on **Laravel Cloud**.

---

## Architecture

The backend is a pure API — no Blade views, no Inertia. It is consumed exclusively by the Vue SPA frontend. Authentication uses **Laravel Sanctum** with cookie-based sessions and CSRF protection.

### API routes

Routes are defined in `api.php` and split into three groups:

- **Authenticated routes** — require a valid session
- **Guest-context routes** (`/api/guest/...`) — no authentication required; used by the frontend in guest context
- **Shared routes** — no authentication required; used by both the app and guest frontend contexts

### Validation

`SavePromptRequest.php` handles validation for both prompt creation and updates, including:
- Unique title enforcement
- `coverimage_id` validation — must refer to an image belonging to the prompt (or the current upload token on prompt-creation)
- Category ID validation against the global predefined category set

### Authorization

Authorization is handled via **policies**. All controllers use `can()` / `Gate::authorize()` to ensure users can only access and modify resources they own.

### Categories

Categories in PromptIndex are predefined and shared across all users. Since all prompts are public, shared categories give the prompt-library a coherent structure.

---

## Image uploads

Image uploading is handled via `ImageController.php`. Images can be uploaded before a prompt is created by generating an `upload_image_token` (UUID) in the frontend and attaching it to each upload request. When the prompt is eventually saved, the backend links the uploaded images to the new prompt via this token.

---

## Seeding

`PromptUsersSeeder.php` provides a thorough seed setup for the prompt library — creating users, categories, and prompts complete with images, cover images, and realistic timestamps, sourced from structured JSON files.

---

## Related

- [Frontend repository (Vue SPA)](https://github.com/dgrinddev/promptindex-vue-frontend)
- [Live site](https://promptindex.io/)
