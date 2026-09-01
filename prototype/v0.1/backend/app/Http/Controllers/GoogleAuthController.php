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
}
