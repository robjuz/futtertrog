<?php

namespace App\Http\Controllers;

use App\Deposit;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
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
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  UserStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(UserStoreRequest $request)
    {
        $user = User::make($request->validated());
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
     * @param  Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, User $user)
    {
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
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(User $user)
    {
        return view('user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UserUpdateRequest  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UserUpdateRequest $request, User $user)
    {
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
     * @param  Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\RedirectResponse|Response
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('users.index');
    }

    public function restore(Request $request, $user)
    {
        User::withTrashed()->findOrFail($user)->restore();

        $this->authorize('restore', $user);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.show', $user);
    }
}
