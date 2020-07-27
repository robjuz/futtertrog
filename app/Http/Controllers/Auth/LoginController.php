<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required',
        ]);
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGitlab()
    {
        return Socialite::driver('gitlab')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGitlabCallback(Request $request)
    {
        $gitlabUser = Socialite::driver('gitlab')->user();

        /** @var User $user */
        $user = User::withTrashed()->firstOrNew(
            ['email' => $gitlabUser->getEmail()],
            [
                'name' => $gitlabUser->getName(),
                'password' => Hash::make($gitlabUser->getId()),
            ]
        );

        if ($user->deleted_at) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $user->save();

        Auth::login($user);

        return $this->sendLoginResponse($request);
    }
}
