<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class MeetingController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'Book a Meeting',
            'featureName' => 'Microsoft Bookings Integration',
            'description' => 'Meeting options (Existing Client Meeting / Telephone Call) linking to Microsoft Bookings will be built in the Sunday Phase.'
        ], 'main');
    }
}
