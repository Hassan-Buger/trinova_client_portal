<?php
namespace Application\Controllers\Auth;

use Application\Core\Controller; use Application\Core\Request; use Application\Core\Response; use Application\Core\Session;
use Application\Models\User; use Application\Models\OtpChallenge; use Application\Services\AuditService; use Application\Services\NotificationService;

class ActivationController extends Controller
{
    private User $users; private OtpChallenge $otps;
    public function __construct(){ $this->users=new User(); $this->otps=new OtpChallenge(); }

    public function showActivationForm(Request $request, Response $response): void
    {
        $token=trim((string)$request->input('token','')); $user=$token!==''?$this->users->findByActivationToken($token):null;
        if(!$user){ Session::setFlash('error','Invalid or expired activation link.'); $response->redirect('/login'); return; }
        $verified=$this->verified((int)$user['id'],OtpChallenge::ACTIVATION);
        $this->render('auth/activate',['pageTitle'=>'Activate Your Account','token'=>$token,'userName'=>$user['name'],'email'=>$this->mask($user['email']),'verified'=>$verified,'error'=>Session::getFlash('error'),'success'=>Session::getFlash('success')],'main');
    }

    public function processActivation(Request $request, Response $response): void
    {
        $token=trim((string)$request->input('token','')); $user=$token!==''?$this->users->findByActivationToken($token):null;
        if(!$user){ Session::setFlash('error','Invalid or expired activation session.'); $response->redirect('/login'); return; }
        $action=(string)$request->input('action','verify'); $uid=(int)$user['id'];
        if($action==='resend'){ $issued=$this->otps->issue($uid,$user['email'],OtpChallenge::ACTIVATION); if(!$issued['ok']) Session::setFlash('error','Please wait before requesting another code.'); elseif(!NotificationService::sendVerificationCodeEmail($user['email'],$user['name'],$issued['code'],'account activation')){ $this->otps->invalidate($uid,OtpChallenge::ACTIVATION); Session::setFlash('error','We could not send the verification code. Please try again.'); } else Session::setFlash('success','A new verification code has been sent to your email.'); $response->redirect('/activate?token='.urlencode($token)); return; }
        if($action==='verify'){ $result=$this->otps->verify($uid,$user['email'],OtpChallenge::ACTIVATION,trim((string)$request->input('code',''))); if($result==='verified'){ Session::set('otp_verified',['user_id'=>$uid,'purpose'=>OtpChallenge::ACTIVATION,'expires'=>time()+600]); Session::setFlash('success','Your email has been verified successfully. Create your password.'); } else Session::setFlash('error',$this->otpError($result)); $response->redirect('/activate?token='.urlencode($token)); return; }
        if(!$this->verified($uid,OtpChallenge::ACTIVATION)){ Session::setFlash('error','Verify your email before creating a password.'); $response->redirect('/activate?token='.urlencode($token)); return; }
        $password=(string)$request->input('password',''); $confirm=(string)$request->input('password_confirm','');
        if(strlen($password)<8||$password!==$confirm){ Session::setFlash('error',strlen($password)<8?'Password must be at least 8 characters long.':'Passwords do not match.'); $response->redirect('/activate?token='.urlencode($token)); return; }
        $this->users->activateAccount($uid,password_hash($password,PASSWORD_ARGON2ID)); Session::remove('otp_verified'); AuditService::log('account_activated','users',$uid,$uid); NotificationService::sendPasswordChangedAlert($user['email'],$user['name']); Session::setFlash('success','Your email has been verified and your password has been created successfully. You can now log in.'); $response->redirect('/login');
    }
    private function verified(int $uid,string $purpose):bool { $v=Session::get('otp_verified',[]); return ($v['user_id']??0)===$uid&&($v['purpose']??'')===$purpose&&($v['expires']??0)>=time(); }
    private function otpError(string $r):string { return $r==='expired'?'The verification code has expired. Please request a new code.':($r==='locked'?'Too many incorrect attempts. Please request a new verification code.':'The verification code is incorrect.'); }
    private function mask(string $e):string { [$a,$d]=array_pad(explode('@',$e,2),2,''); return substr($a,0,1).str_repeat('*',max(2,strlen($a)-1)).'@'.$d; }
}
