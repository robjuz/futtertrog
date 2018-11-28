<?php

namespace App\Http\Controllers;

use App\Meal;
use App\User;
use DiDom\Document;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        //Login
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-login.html')
            ->withData([
                'kdnr' => '101846',
                'passwort' => 'hwessen24',
                'is_send' => 'login'
            ])
            ->setCookieJar(storage_path('holtzke_cookie.txt'))
            ->post();

        //get data

        $date = today();


        do {

            $response = Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
                ->withData([
                    't' => $date->timestamp,
                ])
                ->setCookieFile(storage_path('holtzke_cookie.txt'))
                ->get();


            $document = new Document($response);

            $meals = $document->find('.meal');

            foreach ($meals as $meal) {
                $title = $meal->find('h2')[0]->text();

                preg_match('/^[\w\s]*/mu', $title, $titleMatch);
                preg_match('/\((\S*)/', $title, $priceMatch);


                Meal::updateOrCreate([
                    'title' => $titleMatch[0],
                    'date' => $date
                ], [
                    'title' => $titleMatch[0],
                    'description' => $meal->find('.cBody')[0]->removeChildren()[0]->text(),
                    'price' => floatval($priceMatch[1]),
                    'date' => $date
                ]);
            }

            $date->addWeekday();

        } while (count($meals));

//        return User::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
