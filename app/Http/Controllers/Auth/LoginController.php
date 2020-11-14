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
use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *      path="/api/login",
 *      summary="Sign in",
 *      description="Login by email, password",
 *      operationId="login",
 *      tags={"auth"},
 *      @OA\RequestBody(
 *          required=true,
 *          description="Pass user credentials",
 *          @OA\JsonContent(
 *              required={"email","password"},
 *              @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
 *              @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Success",
 *          @OA\JsonContent(
 *              @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validation error",
 *          @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                property="errors",
 *                type="object",
 *                @OA\Property(
 *                   property="email",
 *                   type="array",
 *                   collectionFormat="multi",
 *                   @OA\Items(
 *                      type="string",
 *                      example={"The email field is required.","The email must be a valid email address."},
 *                   )
 *                )
 *             )
 *          )
 *       ),
 *      @OA\Response( response="default", ref="#/components/responses/Default" ),
 * ),
 *
 * @OA\Post(
 * path="/api/logout",
 * summary="Logout",
 * description="Logout user",
 * operationId="logout",
 * tags={"auth"},
 * security={ {"bearer": {} }},
 * @OA\Response(
 *    response=200,
 *    description="Success"
 *     ),
 * @OA\Response(
 *    response=401,
 *    description="Returns when user is not authenticated",
 *    @OA\JsonContent(
 *       @OA\Property(property="message", type="string", example="Not authorized"),
 *    )
 * )
 * )
 */
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
            [
                'email' => $gitlabUser->getEmail(),
            ],
            [
                'name' => $gitlabUser->getName(),
                'password' => Hash::make($gitlabUser->getId()),
            ]
        );

        abort_if($user->deleted_at !== null, Response::HTTP_UNAUTHORIZED);

        $user->save();

        Auth::login($user, true);

        return $this->sendLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate(
            [
                $this->username() => 'required|string',
                'password' => 'required',
            ]
        );
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        if ($request->wantsJson()) {
            return $this->guard()->user();
        }

        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return redirect()->intended($this->redirectPath());
    }
}
