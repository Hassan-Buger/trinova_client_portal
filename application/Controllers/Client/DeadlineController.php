<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Deadline;
use Application\Models\ClientEntity;

class DeadlineController extends Controller
{
    private Deadline $deadlineModel;

    public function __construct()
    {
        $this->deadlineModel = new Deadline();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId  = Session::get('client_id');
        $deadlineGroups = $clientId ? $this->deadlineModel->getGroupedByClient($clientId) : [];
        if ($clientId) {
            $byEntity=[]; foreach($deadlineGroups as $group) $byEntity[(int)$group['entity_id']]=$group;
            $deadlineGroups=array_map(static fn(array $entity):array=>$byEntity[(int)$entity['id']]??['entity_id'=>(int)$entity['id'],'entity_name'=>$entity['company_name'],'entity_type'=>$entity['entity_type'],'deadlines'=>[]],(new ClientEntity())->getByClientId($clientId));
        }

        $this->render('client/deadlines/index', [
            'pageTitle' => 'Important Dates & Compliance',
            'deadlineGroups' => $deadlineGroups,
        ], 'main');
    }
}
