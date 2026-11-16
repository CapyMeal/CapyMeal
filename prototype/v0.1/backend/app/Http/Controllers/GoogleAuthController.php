<?php

namespace App\Http\Controllers;

class GoogleAuthController extends SocialAuthController
{
    protected function driver(): string
    {
        return 'google';
    }

    protected function providerIdColumn(): string
    {
        return 'google_id';
    }

    // https://capy-meal.vercel.app/api/auth/google/callback ya está
    // registrada en Google Cloud Console (2026-11-16).
    protected function supportsCallbackViaFrontend(): bool
    {
        return true;
    }
}
