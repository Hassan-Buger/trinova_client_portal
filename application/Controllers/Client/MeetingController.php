<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Meeting;
use Application\Services\AuditService;

class MeetingController extends Controller
{
    private Meeting $meetingModel;

    public function __construct()
    {
        $this->meetingModel = new Meeting();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $meetings = $clientId ? $this->meetingModel->getByClient($clientId) : [];

        $this->render('client/meetings/book', [
            'pageTitle' => 'Book a Meeting',
            'meetings'  => $meetings,
        ], 'main');
    }

    public function book(Request $request, Response $response): void
    {
        $clientId    = Session::get('client_id');
        $meetingType = trim($request->getBody()['meeting_type'] ?? '');

        if ($clientId && in_array($meetingType, ['existing_client_meeting', 'telephone_call'], true)) {
            $bookingRef = 'MSB-' . strtoupper(bin2hex(random_bytes(4)));

            $id = $this->meetingModel->create([
                'client_id'                  => $clientId,
                'type'                       => $meetingType,
                'external_booking_reference' => $bookingRef,
            ]);

            AuditService::log('meeting_booked', 'meetings', $id);
            Session::setFlash('success', "Meeting slot requested. Booking Reference: {$bookingRef}");
        }

        $response->redirect('/client/meetings/book');
    }

    public function delete(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $id = (int)($request->getBody()['meeting_id'] ?? 0);

        if ($clientId && $id > 0) {
            $meeting = $this->meetingModel->find($id);
            if ($meeting && (int)$meeting['client_id'] === (int)$clientId) {
                if ($this->meetingModel->softDelete($id)) {
                    AuditService::log('meeting_deleted', 'meetings', $id, Session::get('user_id'), ['client_id' => $clientId]);
                    Session::setFlash('success', 'Meeting request deleted successfully.');
                } else {
                    Session::setFlash('error', 'Failed to delete meeting request.');
                }
            } else {
                Session::setFlash('error', 'Unauthorized or meeting not found.');
            }
        }

        $response->redirect('/client/meetings/book');
    }
}

