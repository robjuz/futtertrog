<?php

return [
    'description' => 'An easy system to manage meal orders',
    'status.open' => 'Open',
    'status.ordered' => 'Ordered',
    'date_format' => 'd.m.Y',
    'datetime_format' => 'd.m.Y H:i',
    'meal_was_ordered' => 'This Menu was already ordered! Please delete all orders first.',
    'portions_ordered' => '{1} 1 portion ordered|[2,*] :count portions ordered',
    'available_meals' => '{0} There are <strong>none</strong> available meals for <span class="text-primary">:date</span>.|{1} There is <strong>:count</strong>available meal for <span class="text-primary">:date</span>.|[2, *] There are <strong>:count</strong> available meals for <span class="text-primary">:date</span>.',
    'locale' => [
        'de' => 'German',
        'en' => 'English',
    ],
    'status' => [
        'open' => 'Not yet ordered from the supplier',
        'ordered' => 'Ordered from supplier',
    ],
    'calories' => 'Calories: :calories kcal'
];
