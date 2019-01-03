<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = User::all();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', User::class);

        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserStoreRequest $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(UserStoreRequest $request)
    {
        $this->authorize('create', User::class);

        $user = User::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.edit', $user);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  \App\User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        $meals = $user->meals()->orderBy('date')->paginate(5, ['*'], 'meals_page');
        $meals->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()->paginate(5, ['*'], 'deposits_page');
        $deposits->appends('meals_page', $request->meals_page);

        return view('user.show', compact('user', 'meals', 'deposits'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param  \App\User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request, User $user)
    {
        $this->authorize('edit', $user);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return view('user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserUpdateRequest $request
     * @param  \App\User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return back()->with('message', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param  \App\User $user
     * @return \Illuminate\Http\RedirectResponse|Response
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('users.index');
    }
}
