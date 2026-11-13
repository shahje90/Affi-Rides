<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        return view('home');
    }

    public function switchLanguage(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'ur'], true), 404);

        session(['locale' => $locale]);

        $previousUrl = $request->headers->get('referer');
        $previous = $previousUrl ? parse_url($previousUrl) : false;
        $expectedScheme = $request->isSecure() ? 'https' : 'http';

        if (
            ! is_array($previous)
            || strtolower($previous['host'] ?? '') !== strtolower($request->getHost())
            || ($previous['scheme'] ?? '') !== $expectedScheme
        ) {
            return redirect()->route('home');
        }

        return redirect()->to($previousUrl);
    }
}
