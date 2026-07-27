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
        $userId=(int)Session::get('user_id');
        $deadlineGroups = $this->deadlineModel->getGroupedByUser($userId);
        if ($clientId) {
            $byEntity=[]; foreach($deadlineGroups as $group) $byEntity[(int)$group['entity_id']]=$group;
            $deadlineGroups=array_map(static fn(array $entity):array=>$byEntity[(int)$entity['id']]??['entity_id'=>(int)$entity['id'],'entity_name'=>$entity['company_name'],'entity_type'=>$entity['entity_type'],'deadlines'=>[]],(new \Application\Models\EntityAccess())->accessibleEntities($userId));
        }

        $this->render('client/deadlines/index', [
            'pageTitle' => 'Important Dates & Compliance',
            'deadlineGroups' => $deadlineGroups,
        ], 'main');
    }
}
