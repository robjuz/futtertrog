<?php

namespace App\Http\Controllers;

use App\Repositories\OrdersRepository;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Illuminate\Http\Request;

class IcalController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param \App\Repositories\OrdersRepository $ordersRepository
     * @return void
     */
    public function __invoke(Request $request, OrdersRepository $ordersRepository)
    {
        $orderItems = $ordersRepository->usersAllOrders(
            $request->user(),
            $request->input('from', null),
            $request->input('to', null)
        );

        $vCalendar = new Calendar(config('app.url'));

        foreach ($orderItems as $orderItem) {
            $vCalendar->addComponent(
                (new Event)->setDtStart($orderItem->order->date)
                    ->setDtEnd($orderItem->order->date)
                    ->setNoTime(true)
                    ->setSummary($orderItem->meal->title.'('.$orderItem->qunatity.')')
            );
        }

        return response($vCalendar->render())->withHeaders(
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="cal.ics"',
            ]
        );
    }
}
