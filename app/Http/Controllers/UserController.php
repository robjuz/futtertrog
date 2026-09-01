<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|View
     *
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = User::withTrashed()
            ->with('orderItems.meal')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function create()
    {
        Gate::authorize('create', User::class);

        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(UserStoreRequest $request)
    {
        Gate::authorize('create', User::class);

        $user = new User($request->validated());
        $user->password = Hash::make($request->input('password'));

        $user->save();

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.edit', $user)->with('success', __('Success'));
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse|View
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, User $user)
    {
        Gate::authorize('view', $user);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        $orders = $user->orderItems()
            ->with(['meal'])
            ->latest()
            ->paginate(5, ['*'], 'orders_page')
            ->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()
            ->whereStatus(Deposit::STATUS_OK)
            ->latest()
            ->paginate(5, ['*'], 'deposits_page')
            ->appends('orders_page', $request->meals_page);

        return view('user.show', compact('user', 'orders', 'deposits'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return JsonResponse|View
     *
     * @throws AuthorizationException
     */
    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        $user->fill($request->validated());

        if ($request->has('password') && ! is_null($request->input('password'))) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return back()->with('success', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     * @throws AuthorizationException
     */
    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('users.index');
    }

    public function restore(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $user->restore();

        Gate::authorize('restore', $user);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.show', $user);
    }
}
