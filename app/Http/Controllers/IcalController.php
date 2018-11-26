<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Repositories\OrdersRepository;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Illuminate\Http\Request;

class IcalController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param OrdersRepository $ordersRepository
     * @return void
     */
    public function __invoke(Request $request, OrdersRepository $ordersRepository)
    {
        $orderItems = $ordersRepository->usersAllOrders(
            $request->user(),
            $request->input('from', null),
            $request->input('to', null)
        );

        $events = $orderItems->map(fn(OrderItem $orderItem) => (new Event())
            ->setOccurrence(new SingleDay(new Date($orderItem->meal->date)))
            ->setSummary($orderItem->meal->title . ' (' . $orderItem->quantity . ')')
            ->setDescription($orderItem->meal->description ?? '')
        )->toArray();


        $calendar = new Calendar($events);

        $calendarComponent = (new CalendarFactory())->createCalendar($calendar);


        return response($calendarComponent)->withHeaders(
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="cal.ics"',
            ]
        );
    }
}
